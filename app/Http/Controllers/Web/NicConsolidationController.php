<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use Illuminate\Http\Request;

class NicConsolidationController extends Controller
{
    public function index(Request $request)
    {
        // Escaneo por código del saco: pistola envía código + Enter y redirigimos al saco
        if ($request->filled('saco_code')) {
            $code = trim((string) $request->input('saco_code'));
            $consolidation = Consolidation::where('code', $code)->where('status', 'SENT')->first();
            if ($consolidation) {
                return redirect()->route('nic-consolidations.show', $consolidation->id);
            }
            return redirect()->route('nic-consolidations.index')
                ->with('error', 'Código de saco no encontrado o no está enviado.')
                ->withInput($request->only('service_type'));
        }

        $query = Consolidation::withCount('items')
            ->where('status', 'SENT');

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        $consolidations = $query->orderBy('sent_at', 'desc')->paginate(15)->withQueryString();

        // Estadísticas con los mismos filtros (sacos enviados)
        $statsQuery = Consolidation::where('status', 'SENT');
        if ($request->filled('service_type')) {
            $statsQuery->where('service_type', $request->service_type);
        }
        $statsTotal = $statsQuery->count();
        $statsAir = (clone $statsQuery)->where('service_type', 'AIR')->count();
        $statsSea = (clone $statsQuery)->where('service_type', 'SEA')->count();
        $statsTotalItems = (clone $statsQuery)->withCount('items')->get()->sum('items_count');

        return view('nic-consolidations.index', compact('consolidations', 'statsTotal', 'statsAir', 'statsSea', 'statsTotalItems'));
    }

    public function show(string $id)
    {
        $consolidation = Consolidation::with(['items.preregistration'])->findOrFail($id);

        if ($consolidation->status !== 'SENT') {
            return redirect()->route('nic-consolidations.index')
                ->with('error', 'Solo se pueden escanear sacos enviados.');
        }

        $items = $consolidation->items;
        $receivableItems = $items->whereNotNull('preregistration_id')->values();
        $unmatchedItems = $items->whereNull('preregistration_id')->values();

        $totalItems = $receivableItems->count();
        $scannedItems = $receivableItems->whereNotNull('scanned_at')->values();
        $missingItems = $receivableItems->whereNull('scanned_at')->values();
        $scannedCount = $scannedItems->count();
        $missingCount = $missingItems->count();
        $scannedLbsTotal = $scannedItems->sum(fn (ConsolidationItem $i) => $this->preregistrationWeightLbs($i->preregistration));

        return view('nic-consolidations.show', compact(
            'consolidation',
            'totalItems',
            'scannedCount',
            'missingCount',
            'scannedItems',
            'missingItems',
            'unmatchedItems',
            'scannedLbsTotal',
        ));
    }

    public function scan(Request $request, string $id)
    {
        $consolidation = Consolidation::with(['items.preregistration'])->findOrFail($id);
        $wantsJson = $request->expectsJson() || $request->ajax();

        if ($consolidation->status !== 'SENT') {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Solo se pueden escanear sacos enviados.'], 422);
            }
            return back()->with('error', 'Solo se pueden escanear sacos enviados.');
        }

        $request->validate(['code' => 'required|string|max:100']);
        $code = trim($request->input('code'));

        $isSixDigits = preg_match('/^\d{6}$/', $code);
        // Mismo warehouse puede tener varios bultos (dropoff): buscar el siguiente ítem no escaneado en orden (bulto 1, 2, 3…)
        $matchingPreregIds = $isSixDigits
            ? Preregistration::where('warehouse_code', $code)->pluck('id')->toArray()
            : [Preregistration::where('tracking_external', $code)->value('id')];

        $matchingPreregIds = array_filter($matchingPreregIds);
        if (empty($matchingPreregIds)) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Código no encontrado.'], 422);
            }
            return back()->with('error', 'Código no encontrado.');
        }

        // Siguiente ítem del saco con este código que aún no se ha escaneado, ordenado por bulto_index para no perder orden
        $item = ConsolidationItem::where('consolidation_id', $consolidation->id)
            ->whereIn('preregistration_id', $matchingPreregIds)
            ->whereNull('scanned_at')
            ->join('preregistrations', 'preregistrations.id', '=', 'consolidation_items.preregistration_id')
            ->orderByRaw('COALESCE(preregistrations.bulto_index, 999) ASC')
            ->select('consolidation_items.*')
            ->first();

        if (! $item) {
            $anyInSack = ConsolidationItem::where('consolidation_id', $consolidation->id)
                ->whereIn('preregistration_id', $matchingPreregIds)
                ->exists();
            $message = $anyInSack
                ? 'Todos los paquetes con este código ya fueron escaneados.'
                : 'El paquete no pertenece a este saco.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with($anyInSack ? 'warning' : 'error', $message);
        }

        $preregistration = $item->preregistration ?? Preregistration::find($item->preregistration_id);
        if ($preregistration->status !== 'IN_TRANSIT') {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'El paquete no está en tránsito.'], 422);
            }
            return back()->with('error', 'El paquete no está en tránsito.');
        }

        $item->update(['scanned_at' => now()]);
        $preregistration->update([
            'status' => 'IN_WAREHOUSE_NIC',
            'received_nic_at' => now(),
        ]);

        $bultoInfo = ($preregistration->bultos_total && $preregistration->bultos_total > 1)
            ? ' (bulto ' . $preregistration->bulto_index . ' de ' . $preregistration->bultos_total . ')'
            : '';
        $message = 'Paquete escaneado: ' . $preregistration->label_name . $bultoInfo;

        if ($request->expectsJson() || $request->ajax()) {
            $consolidation->load(['items' => fn ($q) => $q->with('preregistration')]);
            $receivable = $consolidation->items->whereNotNull('preregistration_id');
            $scannedCount = $receivable->whereNotNull('scanned_at')->count();
            $missingCount = $receivable->count() - $scannedCount;
            $scannedCode = $preregistration->warehouse_code ?? $preregistration->tracking_external ?? $code;
            $scannedRowHtml = view('nic-consolidations.partials.scanned-row', [
                'item' => $item->fresh(['preregistration']),
            ])->render();

            $scannedLbsTotal = $receivable
                ->filter(fn (ConsolidationItem $i) => $i->scanned_at !== null)
                ->sum(fn (ConsolidationItem $i) => $this->preregistrationWeightLbs($i->preregistration));

            return response()->json([
                'success' => true,
                'message' => $message,
                'scanned_code' => $scannedCode,
                'scanned_count' => $scannedCount,
                'missing_count' => $missingCount,
                'total_items' => $receivable->count(),
                'scanned_lbs_total' => round($scannedLbsTotal, 1),
                'scanned_row_html' => $scannedRowHtml,
            ]);
        }

        return redirect()->route('nic-consolidations.show', $consolidation->id)
            ->with('success', $message);
    }

    private function preregistrationWeightLbs(?Preregistration $p): float
    {
        if ($p === null) {
            return 0.0;
        }

        return (float) ($p->verified_weight_lbs ?? $p->intake_weight_lbs ?? 0);
    }
}
