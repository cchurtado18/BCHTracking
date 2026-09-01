<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingCreditNote;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Services\Accounting\ClientCreditService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingCreditNoteController extends Controller
{
    public function index()
    {
        $notes = AccountingCreditNote::query()
            ->with(['agency:id,code,name', 'movements'])
            ->orderByDesc('id')
            ->paginate(25);

        $creditTotal = round((float) Agency::query()->sum('credit_balance_usd'), 2);

        return view('accounting.credit-notes.index', compact('notes', 'creditTotal'));
    }

    public function create(Request $request)
    {
        $agencies = Agency::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $selectedAgencyId = $request->integer('agency_id') ?: null;

        return view('accounting.credit-notes.create', compact('agencies', 'selectedAgencyId'));
    }

    public function store(Request $request, ClientCreditService $credits)
    {
        $data = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'amount_usd' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:5|max:500',
        ], [
            'agency_id.required' => 'Seleccione el cliente.',
            'amount_usd.required' => 'Indique el monto.',
            'reason.required' => 'Indique el motivo de la nota de crédito.',
            'reason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $note = null;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $note = DB::transaction(function () use ($data, $request, $credits) {
                    $agency = Agency::findOrFail((int) $data['agency_id']);
                    $note = AccountingCreditNote::create([
                        'folio' => AccountingCreditNote::generateFolio(),
                        'agency_id' => $agency->id,
                        'amount_usd' => round((float) $data['amount_usd'], 2),
                        'reason' => trim($data['reason']),
                        'status' => 'active',
                        'created_by' => $request->user()->id,
                    ]);

                    $credits->add($agency, (float) $note->amount_usd, \App\Models\AccountingCreditMovement::TYPE_CREDIT_NOTE, [
                        'credit_note_id' => $note->id,
                        'notes' => $note->reason,
                        'created_by' => $request->user()->id,
                    ]);

                    return $note;
                });
                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === 4) {
                    return back()
                        ->withInput()
                        ->with('error', 'No se pudo generar el folio de la nota de crédito. Intente de nuevo.');
                }
            }
        }

        if (! $note) {
            return back()
                ->withInput()
                ->with('error', 'No se pudo generar el folio de la nota de crédito. Intente de nuevo.');
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_credit_note',
            'auditable_id' => $note->id,
            'action' => 'credit_note_registered',
            'summary' => 'Emitió nota de crédito '.$note->folio.' por $'.number_format((float) $note->amount_usd, 2),
            'old_values' => null,
            'new_values' => ['folio' => $note->folio, 'amount_usd' => $note->amount_usd],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('accounting.credit-notes.show', $note)
            ->with('success', 'Nota de crédito '.$note->folio.' emitida. El cliente tiene saldo a favor.');
    }

    public function show(AccountingCreditNote $creditNote)
    {
        $creditNote->load([
            'agency:id,code,name',
            'createdBy:id,name',
            'voidedBy:id,name',
            'movements.invoice:id,folio,status,total_usd,amount_paid',
        ]);

        return view('accounting.credit-notes.show', compact('creditNote'));
    }

    public function void(Request $request, AccountingCreditNote $creditNote, ClientCreditService $credits)
    {
        $data = $request->validate([
            'void_reason' => 'required|string|min:5|max:500',
        ], [
            'void_reason.required' => 'Indique el motivo de anulación.',
            'void_reason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        if ($creditNote->isVoid()) {
            return back()->with('error', 'Esta nota de crédito ya está anulada.');
        }

        try {
            DB::transaction(function () use ($creditNote, $data, $request, $credits) {
                $credits->reverseCreditNote($creditNote);
                $creditNote->update([
                    'status' => 'void',
                    'void_reason' => trim($data['void_reason']),
                    'voided_at' => now(),
                    'voided_by' => $request->user()->id,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_credit_note',
            'auditable_id' => $creditNote->id,
            'action' => 'credit_note_voided',
            'summary' => 'Anuló nota de crédito '.$creditNote->folio,
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'void'],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('accounting.credit-notes.show', $creditNote)
            ->with('success', 'Nota de crédito '.$creditNote->folio.' anulada. El saldo a favor se revirtió.');
    }
}
