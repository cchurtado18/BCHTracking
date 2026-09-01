<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingInvoice;
use App\Models\AccountingPayment;
use App\Models\AccountingSetting;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountingReceivableController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountingInvoice::query()
            ->with(['agency:id,code,name,account_type,is_main,credit_days'])
            ->where('status', '!=', 'void')
            ->orderByDesc('issued_at')
            ->orderByDesc('id');

        if ($request->filled('client')) {
            $name = trim((string) $request->client);
            $query->whereHas('agency', function ($q) use ($name) {
                $q->where(function ($inner) use ($name) {
                    $inner->where('name', 'like', "%{$name}%")
                        ->orWhere('code', 'like', "%{$name}%");
                });
            });
        }

        $status = (string) $request->input('status', 'all');
        if ($status === '') {
            $status = 'all';
        }

        if ($status === 'paid') {
            $query->where('status', 'paid');
        } elseif ($status === 'partial') {
            $query->where('status', 'partially_paid');
        } elseif (in_array($status, ['pending', 'current', 'overdue'], true)) {
            $query->whereIn('status', ['issued', 'partially_paid']);
        }

        $all = $query->get();

        if ($status === 'current') {
            $all = $all->filter(fn ($i) => $i->arStatus() === 'current')->values();
        } elseif ($status === 'overdue') {
            $all = $all->filter(fn ($i) => $i->arStatus() === 'overdue')->values();
        } elseif ($status === 'pending') {
            $all = $all->filter(fn ($i) => $i->status === 'issued')->values();
        }

        $openForKpis = AccountingInvoice::query()
            ->with(['agency:id,credit_days'])
            ->whereIn('status', ['issued', 'partially_paid'])
            ->get();

        $kpis = (object) [
            'open_count' => $openForKpis->count(),
            'total' => round($openForKpis->sum(fn ($i) => $i->balanceUsd()), 2),
            'current' => round($openForKpis->filter(fn ($i) => $i->arStatus() === 'current')->sum(fn ($i) => $i->balanceUsd()), 2),
            'overdue' => round($openForKpis->filter(fn ($i) => $i->arStatus() === 'overdue')->sum(fn ($i) => $i->balanceUsd()), 2),
            'credit' => round((float) Agency::query()->sum('credit_balance_usd'), 2),
        ];

        $page = max(1, $request->integer('page', 1));
        $perPage = 25;
        $invoices = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('accounting.receivables.index', compact('invoices', 'kpis', 'status'));
    }

    public function show(Agency $agency)
    {
        $agency->loadMissing(['parent:id,name,code', 'users:id,agency_id,email']);

        $invoices = AccountingInvoice::query()
            ->where('agency_id', $agency->id)
            ->where('status', '!=', 'void')
            ->with(['agency:id,code,name,account_type,is_main,credit_days', 'deliveryNote:id,code'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $payments = AccountingPayment::query()
            ->where('agency_id', $agency->id)
            ->with('allocations.invoice:id,folio')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $creditNotes = \App\Models\AccountingCreditNote::query()
            ->where('agency_id', $agency->id)
            ->with('movements')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $creditMovements = \App\Models\AccountingCreditMovement::query()
            ->where('agency_id', $agency->id)
            ->with('invoice:id,folio')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $openInvoices = $invoices->whereIn('status', ['issued', 'partially_paid']);
        $balance = round($openInvoices->sum(fn ($i) => $i->balanceUsd()), 2);
        $creditBalance = round((float) $agency->credit_balance_usd, 2);
        $activePayments = $payments->filter(fn ($p) => ! $p->isVoid());
        $activeNotes = $creditNotes->filter(fn ($n) => ! $n->isVoid());

        $aging = [
            'current' => 0.0,
            'd1_30' => 0.0,
            'd31_60' => 0.0,
            'd61_90' => 0.0,
            'd90' => 0.0,
        ];
        foreach ($openInvoices as $invoice) {
            $row = $invoice->balanceUsd();
            $days = $invoice->daysOverdue();
            if ($days <= 0) {
                $aging['current'] += $row;
            } elseif ($days <= 30) {
                $aging['d1_30'] += $row;
            } elseif ($days <= 60) {
                $aging['d31_60'] += $row;
            } elseif ($days <= 90) {
                $aging['d61_90'] += $row;
            } else {
                $aging['d90'] += $row;
            }
        }
        $aging = array_map(fn ($v) => round($v, 2), $aging);

        $statement = (object) [
            'company' => AccountingSetting::current()->toCompanyArray(),
            'generated_at' => now()->timezone((string) config('app.display_timezone')),
            'period_from' => $invoices->min('issued_at'),
            'period_to' => $invoices->max('issued_at'),
            'billed' => round($invoices->sum(fn ($i) => (float) $i->total_usd), 2),
            'collected' => round($activePayments->sum(fn ($p) => (float) $p->amount_usd), 2),
            'credits_issued' => round($activeNotes->sum(fn ($n) => (float) $n->amount_usd), 2),
            'credits_remaining' => round($activeNotes->sum(fn ($n) => $n->remainingUsd()), 2),
            'open_count' => $openInvoices->count(),
            'overdue_count' => $invoices->filter(fn ($i) => $i->arStatus() === 'overdue')->count(),
            'overdue_usd' => round($openInvoices->filter(fn ($i) => $i->arStatus() === 'overdue')->sum(fn ($i) => $i->balanceUsd()), 2),
            'net_position' => round($balance - $creditBalance, 2),
            'aging' => (object) $aging,
        ];

        return view('accounting.receivables.show', compact(
            'agency',
            'invoices',
            'payments',
            'creditNotes',
            'creditMovements',
            'balance',
            'creditBalance',
            'statement'
        ));
    }
}
