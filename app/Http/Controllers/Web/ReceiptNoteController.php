<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\ReceiptNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = ReceiptNote::with(['agency.parent', 'receivedBy'])
            ->withCount('preregistrations')
            ->orderByDesc('id');

        if ($request->filled('agency_id')) {
            $query->where('agency_id', (int) $request->agency_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($qq) use ($q) {
                $qq->where('code', 'like', '%'.$q.'%')
                   ->orWhere('delivered_by', 'like', '%'.$q.'%')
                   ->orWhere('delivered_by_id_number', 'like', '%'.$q.'%');
            });
        }

        $notes = $query->paginate(20)->withQueryString();
        $agencies = Agency::orderBy('name')->get(['id', 'code', 'name']);

        return view('receipt-notes.index', compact('notes', 'agencies'));
    }

    /**
     * Pantalla en 2 pasos: paso 1 si no hay receipt_note_id, paso 2 si lo hay.
     */
    public function batch(Request $request)
    {
        $receiptNote = null;
        if ($request->filled('receipt_note_id')) {
            $receiptNote = ReceiptNote::with(['preregistrations.agency', 'agency.parent', 'receivedBy'])
                ->find((int) $request->receipt_note_id);
        }

        $agencies = Agency::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'parent_agency_id']);

        $availablePreregistrations = collect();
        if ($receiptNote) {
            $availablePreregistrations = Preregistration::with('agency')
                ->where('intake_type', 'DROP_OFF')
                ->whereNull('receipt_note_id')
                ->where(function ($q) use ($receiptNote) {
                    if ($receiptNote->agency_id) {
                        $q->where('agency_id', $receiptNote->agency_id);
                    }
                })
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->orderByDesc('id')
                ->limit(200)
                ->get();
        }

        return view('receipt-notes.batch', compact('receiptNote', 'agencies', 'availablePreregistrations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivered_by' => 'required|string|max:200',
            'delivered_by_id_number' => 'nullable|string|max:50',
            'delivered_by_phone' => 'nullable|string|max:50',
            'agency_id' => 'required|exists:agencies,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $receiptNote = ReceiptNote::create([
            'code' => ReceiptNote::generateCode(),
            'delivered_by' => $validated['delivered_by'],
            'delivered_by_id_number' => $validated['delivered_by_id_number'] ?? null,
            'delivered_by_phone' => $validated['delivered_by_phone'] ?? null,
            'agency_id' => (int) $validated['agency_id'],
            'received_by_user_id' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('receipt-notes.batch', ['receipt_note_id' => $receiptNote->id])
            ->with('success', "Nota de recepción {$receiptNote->code} creada. Ahora agregue los paquetes.");
    }

    /**
     * Agrega uno o más preregistros a la nota.
     * Acepta `warehouse_code` (escaneo, agrega todos los bultos de ese código sin nota)
     * o `preregistration_id` (selección directa desde la tabla).
     */
    public function addItem(Request $request, $id)
    {
        $receiptNote = ReceiptNote::findOrFail($id);

        $preregId = $request->input('preregistration_id');
        $warehouseCode = trim((string) $request->input('warehouse_code', ''));

        if (! $preregId && $warehouseCode === '') {
            return back()->with('error', 'Indique un código warehouse o seleccione un preregistro.');
        }

        $added = 0;
        $skipped = 0;
        $errors = [];

        if ($preregId) {
            $pre = Preregistration::with('agency')->find((int) $preregId);
            if (! $pre) {
                return back()->with('error', 'Preregistro no encontrado.');
            }
            [$ok, $msg] = $this->attachPreregistration($pre, $receiptNote);
            if ($ok) {
                $added++;
            } else {
                $errors[] = $msg;
            }
        } else {
            $matches = Preregistration::with('agency')
                ->where('warehouse_code', $warehouseCode)
                ->where('intake_type', 'DROP_OFF')
                ->orderByRaw('COALESCE(bulto_index, 1) ASC')
                ->get();

            if ($matches->isEmpty()) {
                return back()
                    ->withInput()
                    ->with('error', "No se encontró ningún preregistro DROP_OFF con warehouse {$warehouseCode}.");
            }

            foreach ($matches as $pre) {
                [$ok, $msg] = $this->attachPreregistration($pre, $receiptNote);
                if ($ok) {
                    $added++;
                } else {
                    $skipped++;
                    if ($msg) {
                        $errors[] = "Bulto {$pre->bulto_index}/{$pre->bultos_total}: {$msg}";
                    }
                }
            }
        }

        $params = ['receipt_note_id' => $receiptNote->id];

        if ($added > 0) {
            $msg = "Se agregó/agregaron {$added} bulto(s) a la nota.";
            if ($skipped > 0) {
                $msg .= " Se omitieron {$skipped} (ver detalles).";
            }
            return redirect()->route('receipt-notes.batch', $params)
                ->with('success', $msg)
                ->with('warning', $errors ? implode(' • ', $errors) : null);
        }

        return redirect()->route('receipt-notes.batch', $params)
            ->with('error', $errors ? implode(' • ', $errors) : 'No se pudo agregar ningún bulto.');
    }

    /**
     * Valida y enlaza un preregistro a la nota. Devuelve [ok, mensajeError].
     */
    protected function attachPreregistration(Preregistration $pre, ReceiptNote $note): array
    {
        if ($pre->intake_type !== 'DROP_OFF') {
            return [false, 'No es un preregistro DROP_OFF.'];
        }
        if ($pre->receipt_note_id) {
            if ((int) $pre->receipt_note_id === (int) $note->id) {
                return [false, 'Ya estaba en esta nota.'];
            }
            return [false, 'Ya pertenece a otra nota de recepción.'];
        }
        if ($note->agency_id && $pre->agency_id && (int) $pre->agency_id !== (int) $note->agency_id) {
            return [false, "El paquete va a otra agencia ({$pre->agency?->name}). No se puede mezclar con esta nota."];
        }

        $pre->receipt_note_id = $note->id;
        $pre->save();

        return [true, null];
    }

    public function removeItem($id, $preregistrationId)
    {
        $receiptNote = ReceiptNote::findOrFail($id);
        $pre = Preregistration::where('id', (int) $preregistrationId)
            ->where('receipt_note_id', $receiptNote->id)
            ->first();

        if (! $pre) {
            return back()->with('error', 'Bulto no encontrado en esta nota.');
        }

        $pre->receipt_note_id = null;
        $pre->save();

        return back()->with('success', "Bulto {$pre->warehouse_code} retirado de la nota.");
    }

    public function destroy($id)
    {
        $receiptNote = ReceiptNote::with('preregistrations')->findOrFail($id);

        if ($receiptNote->preregistrations->count() > 0) {
            return back()->with('error', 'No se puede eliminar: la nota tiene bultos. Quítelos primero.');
        }

        $receiptNote->delete();

        return redirect()->route('receipt-notes.index')
            ->with('success', "Nota {$receiptNote->code} eliminada.");
    }

    public function printReport($id)
    {
        $receiptNote = ReceiptNote::with([
            'preregistrations' => function ($q) {
                $q->orderBy('warehouse_code')->orderByRaw('COALESCE(bulto_index, 1) ASC');
            },
            'preregistrations.agency',
            'agency.parent',
            'receivedBy',
        ])->findOrFail($id);

        $totalLbs = (float) $receiptNote->preregistrations->sum(function ($p) {
            return (float) ($p->intake_weight_lbs ?? 0);
        });
        $totalFt3 = (float) $receiptNote->preregistrations->sum(function ($p) {
            return (float) ($p->cubic_feet ?? 0);
        });

        return view('receipt-notes.print-report', compact('receiptNote', 'totalLbs', 'totalFt3'));
    }

    /**
     * Atajo para crear nota con un único preregistro DROP_OFF (desde su pantalla show).
     */
    public function quickFromPreregistration(Request $request, Preregistration $preregistration)
    {
        if ($preregistration->intake_type !== 'DROP_OFF') {
            return back()->with('error', 'Solo se puede generar comprobante para preregistros DROP_OFF.');
        }
        if ($preregistration->receipt_note_id) {
            return redirect()->route('receipt-notes.print', $preregistration->receipt_note_id);
        }

        $validated = $request->validate([
            'delivered_by' => 'required|string|max:200',
            'delivered_by_id_number' => 'nullable|string|max:50',
            'delivered_by_phone' => 'nullable|string|max:50',
        ]);

        $receiptNote = DB::transaction(function () use ($validated, $preregistration) {
            $note = ReceiptNote::create([
                'code' => ReceiptNote::generateCode(),
                'delivered_by' => $validated['delivered_by'],
                'delivered_by_id_number' => $validated['delivered_by_id_number'] ?? null,
                'delivered_by_phone' => $validated['delivered_by_phone'] ?? null,
                'agency_id' => $preregistration->agency_id,
                'received_by_user_id' => auth()->id(),
            ]);

            $preregistration->receipt_note_id = $note->id;
            $preregistration->save();

            return $note;
        });

        return redirect()->route('receipt-notes.print', $receiptNote->id)
            ->with('success', "Comprobante {$receiptNote->code} generado.");
    }
}
