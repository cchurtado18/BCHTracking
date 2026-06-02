<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsolidationRequest;
use App\Http\Requests\StoreConsolidationScanRequest;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Services\ConsolidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsolidationController extends Controller
{
    public function __construct(protected ConsolidationService $consolidationService)
    {
    }

    public function index(Request $request)
    {
        if ($request->has('clear_filters')) {
            session()->forget('consolidations_index_filters');
            return redirect()->route('consolidations.index');
        }

        $filterKeys = ['status', 'service_type'];
        if (! $request->hasAny($filterKeys) && session()->has('consolidations_index_filters')) {
            return redirect()->route('consolidations.index', session('consolidations_index_filters'));
        }
        if ($request->hasAny($filterKeys)) {
            session(['consolidations_index_filters' => $request->only($filterKeys)]);
        }

        $query = Consolidation::withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        $consolidations = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Estadísticas con los mismos filtros
        $statsQuery = Consolidation::query();
        if ($request->filled('status')) {
            $statsQuery->where('status', $request->status);
        }
        if ($request->filled('service_type')) {
            $statsQuery->where('service_type', $request->service_type);
        }
        $statsTotal = $statsQuery->count();
        $statsOpen = (clone $statsQuery)->where('status', 'OPEN')->count();
        $statsSent = (clone $statsQuery)->where('status', 'SENT')->count();
        $statsReceived = (clone $statsQuery)->where('status', 'RECEIVED')->count();
        $statsAir = (clone $statsQuery)->where('service_type', 'AIR')->count();
        $statsSea = (clone $statsQuery)->where('service_type', 'SEA')->count();

        return view('consolidations.index', compact('consolidations', 'statsTotal', 'statsOpen', 'statsSent', 'statsReceived', 'statsAir', 'statsSea'));
    }

    public function create()
    {
        return view('consolidations.create');
    }

    public function createSelect()
    {
        $availablePreregistrations = Preregistration::where('status', 'RECEIVED_MIAMI')
            ->whereDoesntHave('consolidationItem')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('service_type');

        $availableByServiceType = [
            'AIR' => $availablePreregistrations->get('AIR', collect()),
            'SEA' => $availablePreregistrations->get('SEA', collect()),
        ];

        return view('consolidations.create-select', compact('availableByServiceType'));
    }

    public function createScan()
    {
        $scanLookup = Preregistration::where('status', 'RECEIVED_MIAMI')
            ->whereDoesntHave('consolidationItem')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'tracking_external', 'warehouse_code', 'label_name', 'service_type', 'intake_weight_lbs', 'verified_weight_lbs']);

        return view('consolidations.create-scan', compact('scanLookup'));
    }

    public function store(StoreConsolidationRequest $request)
    {
        $data = $request->validated();
        $data['code'] = $this->consolidationService->generateCode();
        $data['status'] = 'OPEN';

        $consolidation = Consolidation::create($data);

        $ids = $request->input('preregistration_ids', []);

        if (is_array($ids)) {
            foreach ($ids as $preregId) {
                $pre = Preregistration::find($preregId);
                if ($pre && $pre->status === 'RECEIVED_MIAMI' && $pre->service_type === $consolidation->service_type && ! $pre->consolidationItem) {
                    ConsolidationItem::create([
                        'consolidation_id' => $consolidation->id,
                        'preregistration_id' => $pre->id,
                    ]);
                }
            }
        }

        return redirect()->route('consolidations.label', $consolidation->id)
            ->with('success', 'Saco creado. Imprime la etiqueta del saco para pegarla al mismo.');
    }

    public function storeScan(StoreConsolidationScanRequest $request)
    {
        $codes = collect($request->input('entry_codes', []))
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return redirect()->route('consolidations.create-scan')
                ->withInput($request->except('entry_codes'))
                ->with('error', 'Agregue al menos un código escaneado antes de crear el saco.');
        }

        $sackService = $request->validated()['service_type'];
        foreach ($codes as $code) {
            $anyMatch = $this->findPreregistrationByCodeAnyService($code);
            if ($anyMatch && $anyMatch->service_type !== $sackService) {
                $sackLabel = $sackService === 'AIR' ? 'aéreo' : 'marítimo';
                $pkgLabel = $anyMatch->service_type === 'AIR' ? 'aéreo' : 'marítimo';

                return redirect()->route('consolidations.create-scan')
                    ->withInput($request->except('entry_codes'))
                    ->withErrors([
                        'entry_codes' => "El código {$code} corresponde a un paquete {$pkgLabel} en preregistro, no {$sackLabel}. Cambie el tipo de servicio del saco o elimine ese código de la lista.",
                    ]);
            }
        }

        $consolidation = DB::transaction(function () use ($request, $codes) {
            $consolidation = Consolidation::create([
                'code' => $this->consolidationService->generateCode(),
                'service_type' => $request->validated()['service_type'],
                'status' => 'OPEN',
                'notes' => $request->validated()['notes'] ?? null,
            ]);

            foreach ($codes as $code) {
                $pre = $this->findPreregistrationForSackScan($code, $consolidation->service_type);
                if ($pre) {
                    if (! filled($pre->tracking_external)) {
                        $pre->tracking_external = $code;
                        $pre->save();
                    }
                    ConsolidationItem::create([
                        'consolidation_id' => $consolidation->id,
                        'preregistration_id' => $pre->id,
                        'unmatched_code' => null,
                    ]);
                } else {
                    ConsolidationItem::create([
                        'consolidation_id' => $consolidation->id,
                        'preregistration_id' => null,
                        'unmatched_code' => $code,
                    ]);
                }
            }

            return $consolidation;
        });

        return redirect()->route('consolidations.label', $consolidation->id)
            ->with('success', 'Saco creado por escaneo. Imprime la etiqueta del saco para pegarla al mismo.');
    }

    /**
     * Busca preregistro en Miami por tracking/warehouse sin filtrar por tipo de servicio.
     */
    protected function findPreregistrationByCodeAnyService(string $normalizedCode): ?Preregistration
    {
        if ($normalizedCode === '') {
            return null;
        }

        return Preregistration::query()
            ->where('status', 'RECEIVED_MIAMI')
            ->whereDoesntHave('consolidationItem')
            ->orderBy('created_at', 'desc')
            ->get()
            ->first(function (Preregistration $p) use ($normalizedCode) {
                $t = strtoupper(trim((string) ($p->tracking_external ?? '')));
                $w = strtoupper(trim((string) ($p->warehouse_code ?? '')));

                return $normalizedCode === $t || $normalizedCode === $w;
            });
    }

    /**
     * Preregistro en Miami disponible para saco, coincidiendo por tracking o warehouse (mismo tipo de servicio).
     */
    protected function findPreregistrationForSackScan(string $normalizedCode, string $serviceType): ?Preregistration
    {
        if ($normalizedCode === '') {
            return null;
        }

        return Preregistration::query()
            ->where('status', 'RECEIVED_MIAMI')
            ->where('service_type', $serviceType)
            ->whereDoesntHave('consolidationItem')
            ->orderBy('created_at', 'desc')
            ->get()
            ->first(function (Preregistration $p) use ($normalizedCode) {
                $t = strtoupper(trim((string) ($p->tracking_external ?? '')));
                $w = strtoupper(trim((string) ($p->warehouse_code ?? '')));

                return $normalizedCode === $t || $normalizedCode === $w;
            });
    }

    public function label(string $id)
    {
        $consolidation = Consolidation::with(['items.preregistration'])->findOrFail($id);
        $report = $this->consolidationService->getReport($consolidation);
        return view('consolidations.label', compact('consolidation', 'report'));
    }

    public function show(Request $request, string $id)
    {
        $consolidation = Consolidation::with(['items.preregistration'])->findOrFail($id);
        $report = $this->consolidationService->getReport($consolidation);

        $availablePreregistrations = collect();
        $scanLookup = collect();
        if ($consolidation->status === 'OPEN') {
            $availablePreregistrations = Preregistration::where('status', 'RECEIVED_MIAMI')
                ->where('service_type', $consolidation->service_type)
                ->whereDoesntHave('consolidationItem')
                ->orderBy('created_at', 'desc')
                ->get();

            // Datos para que el JS pueda validar al instante (mismo formato que create-scan)
            $scanLookup = Preregistration::where('status', 'RECEIVED_MIAMI')
                ->whereDoesntHave('consolidationItem')
                ->orderBy('created_at', 'desc')
                ->get(['id', 'tracking_external', 'warehouse_code', 'label_name', 'service_type', 'intake_weight_lbs', 'verified_weight_lbs']);
        }

        $mode = in_array($request->query('mode'), ['scan', 'select'], true) ? $request->query('mode') : null;

        return view('consolidations.show', compact('consolidation', 'report', 'availablePreregistrations', 'scanLookup', 'mode'));
    }

    public function edit(string $id)
    {
        $consolidation = Consolidation::findOrFail($id);
        return view('consolidations.edit', compact('consolidation'));
    }

    public function update(Request $request, string $id)
    {
        $consolidation = Consolidation::findOrFail($id);
        if ($consolidation->status !== 'OPEN') {
            return redirect()->route('consolidations.show', $consolidation->id)
                ->with('error', 'Solo se pueden editar sacos abiertos.');
        }
        $request->validate(['notes' => 'nullable|string|max:1000']);
        $consolidation->update($request->only('notes'));
        return redirect()->route('consolidations.show', $consolidation->id)->with('success', 'Actualizado.');
    }

    public function destroy(string $id)
    {
        $consolidation = Consolidation::findOrFail($id);
        if ($consolidation->status !== 'OPEN') {
            return redirect()->route('consolidations.index')->with('error', 'Solo se pueden eliminar sacos abiertos.');
        }
        $consolidation->items()->delete();
        $consolidation->delete();
        return redirect()->route('consolidations.index')->with('success', 'Saco eliminado.');
    }

    public function addItem(Request $request, string $id)
    {
        $consolidation = Consolidation::findOrFail($id);
        if ($consolidation->status !== 'OPEN') {
            return back()->with('error', 'Solo se pueden agregar items a sacos abiertos.');
        }
        $preregId = $request->input('preregistration_id');
        $pre = Preregistration::find($preregId);
        if (!$pre) {
            return back()->with('error', 'Preregistro no encontrado.');
        }
        if ($pre->status !== 'RECEIVED_MIAMI') {
            return back()->with('error', 'El preregistro debe estar en Miami.');
        }
        if ($pre->consolidationItem) {
            return back()->with('error', 'El preregistro ya está en otro saco.');
        }
        if ($pre->service_type !== $consolidation->service_type) {
            return back()->with('error', 'El tipo de servicio no coincide.');
        }
        ConsolidationItem::create([
            'consolidation_id' => $consolidation->id,
            'preregistration_id' => $pre->id,
        ]);
        return back()->with('success', 'Item agregado.');
    }

    /**
     * Agrega un ítem al saco abierto mediante un código escaneado (tracking o warehouse).
     * Si coincide con un preregistro disponible del mismo servicio se enlaza,
     * de lo contrario se guarda como código no enlazado (unmatched_code).
     */
    public function addItemByScan(Request $request, string $id)
    {
        $consolidation = Consolidation::findOrFail($id);
        if ($consolidation->status !== 'OPEN') {
            return redirect()->route('consolidations.show', $consolidation->id)
                ->with('error', 'Solo se pueden agregar ítems a sacos abiertos.');
        }

        $request->validate([
            'entry_code' => 'required|string|max:120',
        ]);

        $code = strtoupper(trim((string) $request->input('entry_code')));
        if ($code === '') {
            return redirect()->route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan'])
                ->with('error', 'Debe ingresar un código.');
        }

        $duplicateInSack = $consolidation->items()->where(function ($q) use ($code) {
            $q->where('unmatched_code', $code)
              ->orWhereHas('preregistration', function ($qq) use ($code) {
                  $qq->where('tracking_external', $code)
                     ->orWhere('warehouse_code', $code);
              });
        })->exists();
        if ($duplicateInSack) {
            return redirect()->route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan'])
                ->with('error', "El código {$code} ya está en este saco.");
        }

        $anyMatch = $this->findPreregistrationByCodeAnyService($code);
        if ($anyMatch && $anyMatch->service_type !== $consolidation->service_type) {
            $sackLabel = $consolidation->service_type === 'AIR' ? 'aéreo' : 'marítimo';
            $pkgLabel = $anyMatch->service_type === 'AIR' ? 'aéreo' : 'marítimo';

            return redirect()->route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan'])
                ->with('error', "El código {$code} corresponde a un paquete {$pkgLabel} en preregistro, no {$sackLabel}.");
        }

        $pre = $this->findPreregistrationForSackScan($code, $consolidation->service_type);

        if ($pre) {
            if (! filled($pre->tracking_external)) {
                $pre->tracking_external = $code;
                $pre->save();
            }
            ConsolidationItem::create([
                'consolidation_id' => $consolidation->id,
                'preregistration_id' => $pre->id,
                'unmatched_code' => null,
            ]);

            return redirect()->route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan'])
                ->with('success', "Paquete {$code} agregado al saco.");
        }

        ConsolidationItem::create([
            'consolidation_id' => $consolidation->id,
            'preregistration_id' => null,
            'unmatched_code' => $code,
        ]);

        return redirect()->route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan'])
            ->with('warning', "Código {$code} agregado al saco sin preregistro asociado.");
    }

    /**
     * Eliminar un ítem específico de un saco abierto. Permite corregir errores
     * (ej. un paquete que no debía ir en el saco) antes de enviarlo.
     */
    public function removeItem(string $id, string $itemId)
    {
        $consolidation = Consolidation::findOrFail($id);
        if ($consolidation->status !== 'OPEN') {
            return redirect()->route('consolidations.show', $consolidation->id)
                ->with('error', 'Solo se pueden eliminar ítems de sacos abiertos.');
        }

        $item = ConsolidationItem::where('consolidation_id', $consolidation->id)
            ->where('id', $itemId)
            ->first();

        if (! $item) {
            return redirect()->route('consolidations.show', $consolidation->id)
                ->with('error', 'El ítem no existe en este saco.');
        }

        $label = $item->preregistration
            ? ($item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? $item->preregistration->label_name)
            : $item->unmatched_code;

        $item->delete();

        return redirect()->route('consolidations.show', $consolidation->id)
            ->with('success', "Ítem eliminado del saco" . ($label ? ": {$label}" : '.'));
    }

    public function send(string $id)
    {
        $consolidation = Consolidation::withCount('items')->findOrFail($id);
        if ($consolidation->status !== 'OPEN') {
            return back()->with('error', 'Solo se pueden enviar sacos abiertos.');
        }
        if ($consolidation->items_count < 1) {
            return back()->with('error', 'El saco no tiene items.');
        }
        try {
            $this->consolidationService->sendConsolidation($consolidation);
            return back()->with('success', 'Saco enviado. Los paquetes vinculados a un preregistro pasaron a IN_TRANSIT.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Crear un saco con un solo preregistro (envío de una sola caja).
     * Redirige a la etiqueta del saco para imprimir.
     */
    public function createSingleFromPreregistration(Preregistration $preregistration)
    {
        if ($preregistration->status !== 'RECEIVED_MIAMI') {
            return redirect()->route('preregistrations.show', $preregistration->id)
                ->with('error', 'Solo se puede crear un saco unitario para preregistros en Miami (RECEIVED_MIAMI).');
        }
        if ($preregistration->consolidationItem) {
            return redirect()->route('preregistrations.show', $preregistration->id)
                ->with('error', 'Este preregistro ya está en un saco.');
        }

        $serviceType = $preregistration->service_type ?? 'AIR';
        $consolidation = Consolidation::create([
            'code' => $this->consolidationService->generateCode(),
            'service_type' => $serviceType,
            'status' => 'OPEN',
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $consolidation->id,
            'preregistration_id' => $preregistration->id,
        ]);

        return redirect()->route('consolidations.label', $consolidation->id)
            ->with('success', 'Saco unitario creado (1 caja). Imprime la etiqueta del saco y pégala a la caja.');
    }
}
