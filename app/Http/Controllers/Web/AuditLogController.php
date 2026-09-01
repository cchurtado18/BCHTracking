<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingCreditNote;
use App\Models\AccountingExpense;
use App\Models\AccountingExpenseCategory;
use App\Models\AccountingInvoice;
use App\Models\AccountingPayment;
use App\Models\Agency;
use App\Models\AgencyClient;
use App\Models\AuditLog;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        if (auth()->user() && auth()->user()->isAgencyUser()) {
            abort(403, 'No tiene acceso al módulo de auditoría.');
        }

        $query = AuditLog::with(['user.agency'])->orderByDesc('created_at');
        $this->applyFilters($query, $request);

        $logs = $query->paginate(25)->withQueryString();
        $this->attachLiveContext($logs->getCollection());

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        [$agencyNames, $recipientNames, $categoryNames] = $this->resolveNames($logs->getCollection());

        $statsQuery = AuditLog::query();
        $this->applyFilters($statsQuery, $request);
        $statsTotal = $statsQuery->count();
        $statsCreated = (clone $statsQuery)->where('action', 'created')->count();
        $statsUpdated = (clone $statsQuery)->where('action', 'updated')->count();
        $statsDeleted = (clone $statsQuery)->whereIn('action', ['deleted', 'invoice_deleted', 'expense_deleted'])->count();

        return view('audit.index', [
            'logs' => $logs,
            'users' => $users,
            'agencyNames' => $agencyNames,
            'recipientNames' => $recipientNames,
            'categoryNames' => $categoryNames,
            'actionOptions' => AuditLog::actionOptions(),
            'typeOptions' => AuditLog::typeOptions(),
            'statsTotal' => $statsTotal,
            'statsCreated' => $statsCreated,
            'statsUpdated' => $statsUpdated,
            'statsDeleted' => $statsDeleted,
        ]);
    }

    public function show(string $id): View
    {
        if (auth()->user() && auth()->user()->isAgencyUser()) {
            abort(403, 'No tiene acceso al módulo de auditoría.');
        }
        $log = AuditLog::with(['user.agency'])->findOrFail($id);
        $this->attachLiveContext(collect([$log]));

        [$agencyNames, $recipientNames, $categoryNames] = $this->resolveNames(collect([$log]));

        $recordUrl = match ($log->auditable_type) {
            'preregistration' => Preregistration::query()->whereKey($log->auditable_id)->exists()
                ? route('packages.show', $log->auditable_id)
                : null,
            'accounting_invoice' => AccountingInvoice::query()->whereKey($log->auditable_id)->exists()
                ? route('accounting.invoices.show', $log->auditable_id)
                : null,
            'accounting_payment' => AccountingPayment::query()->whereKey($log->auditable_id)->exists()
                ? route('accounting.payments.show', $log->auditable_id)
                : null,
            'accounting_credit_note' => AccountingCreditNote::query()->whereKey($log->auditable_id)->exists()
                ? route('accounting.credit-notes.show', $log->auditable_id)
                : null,
            'accounting_expense' => route('accounting.expenses.index'),
            default => null,
        };

        return view('audit.show', compact(
            'log',
            'agencyNames',
            'recipientNames',
            'categoryNames',
            'recordUrl'
        ));
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }
        if ($request->filled('user_id') && (int) $request->user_id > 0) {
            $query->where('user_id', (int) $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if (! $request->filled('search')) {
            return;
        }

        $term = trim((string) $request->search);
        $like = '%'.$term.'%';

        $packageIds = Preregistration::query()
            ->where(function (Builder $q) use ($like) {
                $q->where('warehouse_code', 'like', $like)
                    ->orWhere('tracking_external', 'like', $like)
                    ->orWhere('label_name', 'like', $like);
            })
            ->limit(200)
            ->pluck('id');

        $invoiceIds = AccountingInvoice::query()
            ->where('folio', 'like', $like)
            ->limit(200)
            ->pluck('id');

        $creditIds = AccountingCreditNote::query()
            ->where('folio', 'like', $like)
            ->limit(200)
            ->pluck('id');

        $query->where(function (Builder $q) use ($like, $packageIds, $invoiceIds, $creditIds) {
            $q->where('summary', 'like', $like)
                ->orWhere('old_values', 'like', $like)
                ->orWhere('new_values', 'like', $like)
                ->orWhere('ip_address', 'like', $like)
                ->orWhereHas('user', function (Builder $user) use ($like) {
                    $user->where('name', 'like', $like)->orWhere('email', 'like', $like);
                });

            if ($packageIds->isNotEmpty()) {
                $q->orWhere(function (Builder $related) use ($packageIds) {
                    $related->where('auditable_type', 'preregistration')
                        ->whereIn('auditable_id', $packageIds);
                });
            }
            if ($invoiceIds->isNotEmpty()) {
                $q->orWhere(function (Builder $related) use ($invoiceIds) {
                    $related->where('auditable_type', 'accounting_invoice')
                        ->whereIn('auditable_id', $invoiceIds);
                });
            }
            if ($creditIds->isNotEmpty()) {
                $q->orWhere(function (Builder $related) use ($creditIds) {
                    $related->where('auditable_type', 'accounting_credit_note')
                        ->whereIn('auditable_id', $creditIds);
                });
            }
        });
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     */
    private function attachLiveContext(Collection $logs): void
    {
        $idsByType = $logs->groupBy('auditable_type')->map(
            fn (Collection $group) => $group->pluck('auditable_id')->unique()->values()
        );

        $packages = ($idsByType->get('preregistration')?->isNotEmpty() ?? false)
            ? Preregistration::query()
                ->whereIn('id', $idsByType->get('preregistration'))
                ->get(['id', 'warehouse_code', 'tracking_external', 'label_name', 'agency_id', 'agency_client_id', 'status', 'service_type'])
                ->keyBy('id')
            : collect();

        $invoices = ($idsByType->get('accounting_invoice')?->isNotEmpty() ?? false)
            ? AccountingInvoice::query()
                ->whereIn('id', $idsByType->get('accounting_invoice'))
                ->get(['id', 'folio', 'agency_id', 'status', 'total_usd'])
                ->keyBy('id')
            : collect();

        $payments = ($idsByType->get('accounting_payment')?->isNotEmpty() ?? false)
            ? AccountingPayment::query()
                ->whereIn('id', $idsByType->get('accounting_payment'))
                ->get(['id', 'agency_id', 'amount_usd', 'status', 'method', 'reference'])
                ->keyBy('id')
            : collect();

        $credits = ($idsByType->get('accounting_credit_note')?->isNotEmpty() ?? false)
            ? AccountingCreditNote::query()
                ->whereIn('id', $idsByType->get('accounting_credit_note'))
                ->get(['id', 'folio', 'agency_id', 'amount_usd', 'status'])
                ->keyBy('id')
            : collect();

        $expenses = ($idsByType->get('accounting_expense')?->isNotEmpty() ?? false)
            ? AccountingExpense::query()
                ->whereIn('id', $idsByType->get('accounting_expense'))
                ->get(['id', 'agency_id', 'category_id', 'amount_usd'])
                ->keyBy('id')
            : collect();

        foreach ($logs as $log) {
            $log->liveContext = match ($log->auditable_type) {
                'preregistration' => $this->packageContext($packages->get($log->auditable_id)),
                'accounting_invoice' => $this->invoiceContext($invoices->get($log->auditable_id)),
                'accounting_payment' => $this->paymentContext($payments->get($log->auditable_id)),
                'accounting_credit_note' => $this->creditContext($credits->get($log->auditable_id)),
                'accounting_expense' => $this->expenseContext($expenses->get($log->auditable_id)),
                default => null,
            };
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function packageContext(?Preregistration $row): ?array
    {
        if (! $row) {
            return null;
        }

        return [
            'code' => $row->warehouse_code ?: $row->tracking_external,
            'agency_id' => $row->agency_id,
            'agency_client_id' => $row->agency_client_id,
            'label_name' => $row->label_name,
            'status' => $row->status,
            'service_type' => $row->service_type,
            'tracking_external' => $row->tracking_external,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function invoiceContext(?AccountingInvoice $row): ?array
    {
        if (! $row) {
            return null;
        }

        return [
            'code' => $row->folio,
            'agency_id' => $row->agency_id,
            'status' => $row->status,
            'total_usd' => $row->total_usd,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paymentContext(?AccountingPayment $row): ?array
    {
        if (! $row) {
            return null;
        }

        return [
            'code' => $row->reference ?: ('#'.$row->id),
            'agency_id' => $row->agency_id,
            'status' => $row->status,
            'amount_usd' => $row->amount_usd,
            'method' => $row->method,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function creditContext(?AccountingCreditNote $row): ?array
    {
        if (! $row) {
            return null;
        }

        return [
            'code' => $row->folio,
            'agency_id' => $row->agency_id,
            'status' => $row->status,
            'amount_usd' => $row->amount_usd,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function expenseContext(?AccountingExpense $row): ?array
    {
        if (! $row) {
            return null;
        }

        return [
            'agency_id' => $row->agency_id,
            'category_id' => $row->category_id,
            'amount_usd' => $row->amount_usd,
        ];
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>}
     */
    private function resolveNames(Collection $logs): array
    {
        $agencyIds = $logs
            ->map(fn (AuditLog $log) => $log->snapshotAgencyId())
            ->filter()
            ->unique()
            ->values();

        $recipientIds = $logs
            ->map(fn (AuditLog $log) => $log->snapshotAgencyClientId())
            ->filter()
            ->unique()
            ->values();

        $categoryIds = $logs
            ->map(fn (AuditLog $log) => (int) ($log->snapshotGet('category_id') ?? $log->liveContext['category_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();

        $agencyNames = $agencyIds->isEmpty()
            ? []
            : Agency::query()
                ->whereIn('id', $agencyIds)
                ->get(['id', 'code', 'name'])
                ->mapWithKeys(fn (Agency $agency) => [(int) $agency->id => trim(($agency->code ? $agency->code.' · ' : '').$agency->name)])
                ->all();

        $recipientNames = $recipientIds->isEmpty()
            ? []
            : AgencyClient::query()
                ->whereIn('id', $recipientIds)
                ->get(['id', 'full_name'])
                ->mapWithKeys(fn (AgencyClient $client) => [(int) $client->id => $client->full_name])
                ->all();

        $categoryNames = $categoryIds->isEmpty()
            ? []
            : AccountingExpenseCategory::query()
                ->whereIn('id', $categoryIds)
                ->get(['id', 'name'])
                ->mapWithKeys(fn (AccountingExpenseCategory $category) => [(int) $category->id => $category->name])
                ->all();

        return [$agencyNames, $recipientNames, $categoryNames];
    }
}
