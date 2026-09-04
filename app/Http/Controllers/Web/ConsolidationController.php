<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsolidationRequest;
use App\Http\Requests\StoreConsolidationScanRequest;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Services\ConsolidationService;
use App\Support\ServiceType;
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
            'SEA' => $availablePreregistrations->get('SEA', collect())
                ->concat($availablePreregistrations->get('CFT', collect()))
                ->values(),
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
        $ids = $data['preregistration_ids'] ?? [];
        unset($data['preregistration_ids']);
        $data['code'] = $this->consolidationService->generateCode($data['service_type']);
        $data['status'] = 'OPEN';

        $consolidation = Consolidation::create($data);

        if (is_array($ids)) {
            foreach ($ids as $preregId) {
                $pre = Preregistration::find($preregId);
                if ($pre && $pre->status === 'RECEIVED_MIAMI' && ServiceType::matchesRoute($pre->service_type, $consolidation->service_type) && ! $pre->consolidationItem) {
                    ConsolidationItem::create([
                        'consolidation_id' => $consolidation->id,
                        'preregistration_id' => $pre->id,
                    ]);
                }
            }
        }

        return redirect()->route('consolidations.label', $consolidation->id)
            ->with('success', $consolidation->unitNounTitle().' creado. Imprime la etiqueta para pegarla.');
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
                ->with('error', 'Agregue al menos un código escaneado antes de crear el '.$this->unitNoun($request->input('service_type')).'.');
        }

        $sackService = $request->validated()['service_type'];
        foreach ($codes as $code) {
            $anyMatch = $this->consolidationService->findAvailableForScan($code, $sackService, true);
            if ($anyMatch && ! ServiceType::matchesRoute($anyMatch->service_type, $sackService)) {
                $sackLabel = ServiceType::routeLabelLower($sackService);
                $pkgLabel = ServiceType::routeLabelLower($anyMatch->service_type);

                return redirect()->route('consolidations.create-scan')
                    ->withInput($request->except('entry_codes'))
                    ->withErrors([
                        'entry_codes' => "El código {$code} corresponde a un paquete {$pkgLabel} en preregistro, no {$sackLabel}. Cambie el tipo de servicio o elimine ese código de la lista.",
                    ]);
            }
        }

        $consolidation = DB::transaction(function () use ($request, $codes) {
            $consolidation = Consolidation::create([
                'code' => $this->consolidationService->generateCode($request->validated()['service_type']),
                'service_type' => $request->validated()['service_type'],
                'transport_number' => $request->validated()['transport_number'],
                'status' => 'OPEN',
                'notes' => $request->validated()['notes'] ?? null,
            ]);

            foreach ($codes as $code) {
                $pre = $this->consolidationService->findAvailableForScan($code, $consolidation->service_type);
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
            ->with('success', $consolidation->unitNounTitle().' creado por escaneo. Imprime la etiqueta para pegarla.');
    }

    public function label(string $id)
    {
        $consolidation = $this->loadResolvedConsolidation($id);
        $report = $this->consolidationService->getReport($consolidation);
        return view('consolidations.label', compact('consolidation', 'report'));
    }

    /**
     * Reporte imprimible y actualizado del contenido actual del saco.
     */
    public function report(string $id)
    {
        $consolidation = Consolidation::with([
            'items' => fn ($query) => $query
                ->with('preregistration.agency')
                ->orderBy('id'),
        ])->findOrFail($id);
        if ($this->consolidationService->resolveUnmatchedItems($consolidation) > 0) {
            $consolidation->load([
                'items' => fn ($query) => $query
                    ->with('preregistration.agency')
                    ->orderBy('id'),
            ]);
        }
        $report = $this->consolidationService->getReport($consolidation);

        return view('consolidations.report', compact('consolidation', 'report'));
    }

    public function show(Request $request, string $id)
    {
        $consolidation = $this->loadResolvedConsolidation($id);
        $report = $this->consolidationService->getReport($consolidation);

        $availablePreregistrations = collect();
        $scanLookup = collect();
        if ($consolidation->status === 'OPEN') {
            $availablePreregistrations = Preregistration::where('status', 'RECEIVED_MIAMI')
                ->whereIn('service_type', ServiceType::servicesForRoute($consolidation->service_type))
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
                ->with('error', 'Solo se pueden editar '.$consolidation->unitNoun(true).' abiertos.');
        }
        $data = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'transport_number' => 'required|string|max:80',
        ], [
            'transport_number.required' => 'Indique el '.$consolidation->transportNumberLabel().'.',
        ]);
        $data['transport_number'] = strtoupper(trim((string) $data['transport_number']));
        $consolidation->update($data);

        return redirect()->route('consolidations.show', $consolidation->id)->with('success', 'Actualizado.');
    }

    public function destroy(string $id)
    {
        $consolidation = Consolidation::findOrFail($id);
        if ($consolidation->status !== 'OPEN') {
            return redirect()->route('consolidations.index')->with('error', 'Solo se pueden eliminar '.$consolidation->unitNoun(true).' abiertos.');
        }
        $unit = $consolidation->unitNounTitle();
        $consolidation->items()->delete();
        $consolidation->delete();

        return redirect()->route('consolidations.index')->with('success', $unit.' eliminado.');
    }

    public function addItem(Request $request, string $id)
    {
        $consolidation = Consolidation::findOrFail($id);
        if ($consolidation->status !== 'OPEN') {
            return back()->with('error', 'Solo se pueden agregar items a '.$consolidation->unitNoun(true).' abiertos.');
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
            return back()->with('error', 'El preregistro ya está en otro '.$pre->consolidationItem->consolidation?->unitNoun().'.');
        }
        if (! ServiceType::matchesRoute($pre->service_type, $consolidation->service_type)) {
            return back()->with('error', 'El tipo de servicio no coincide.');
        }
        ConsolidationItem::create([
            'consolidation_id' => $consolidation->id,
            'preregistration_id' => $pre->id,
        ]);
        return back()->with('success', 'Item agregado.');
    }

    /**
     * Agrega un ítem mediante un código escaneado (tracking o warehouse).
     */
    public function addItemByScan(Request $request, string $id)
    {
        $consolidation = Consolidation::findOrFail($id);
        $unit = $consolidation->unitNoun();
        if ($consolidation->status !== 'OPEN') {
            return redirect()->route('consolidations.show', $consolidation->id)
                ->with('error', 'Solo se pueden agregar ítems a '.$consolidation->unitNoun(true).' abiertos.');
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
                ->with('error', "El código {$code} ya está en este {$unit}.");
        }

        $anyMatch = $this->consolidationService->findAvailableForScan($code, $consolidation->service_type, true);
        if ($anyMatch && ! ServiceType::matchesRoute($anyMatch->service_type, $consolidation->service_type)) {
            $sackLabel = ServiceType::routeLabelLower($consolidation->service_type);
            $pkgLabel = ServiceType::routeLabelLower($anyMatch->service_type);

            return redirect()->route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan'])
                ->with('error', "El código {$code} corresponde a un paquete {$pkgLabel} en preregistro, no {$sackLabel}.");
        }

        $pre = $this->consolidationService->findAvailableForScan($code, $consolidation->service_type);

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
                ->with('success', "Paquete {$code} agregado al {$unit}.");
        }

        ConsolidationItem::create([
            'consolidation_id' => $consolidation->id,
            'preregistration_id' => null,
            'unmatched_code' => $code,
        ]);

        return redirect()->route('consolidations.show', ['consolidation' => $consolidation->id, 'mode' => 'scan'])
            ->with('warning', "Código {$code} agregado al {$unit} sin preregistro asociado.");
    }

    /**
     * Eliminar un ítem específico de un consolidado abierto.
     */
    public function removeItem(string $id, string $itemId)
    {
        $consolidation = Consolidation::findOrFail($id);
        $unit = $consolidation->unitNoun();
        if ($consolidation->status !== 'OPEN') {
            return redirect()->route('consolidations.show', $consolidation->id)
                ->with('error', 'Solo se pueden eliminar ítems de '.$consolidation->unitNoun(true).' abiertos.');
        }

        $item = ConsolidationItem::where('consolidation_id', $consolidation->id)
            ->where('id', $itemId)
            ->first();

        if (! $item) {
            return redirect()->route('consolidations.show', $consolidation->id)
                ->with('error', 'El ítem no existe en este '.$unit.'.');
        }

        $label = $item->preregistration
            ? ($item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? $item->preregistration->label_name)
            : $item->unmatched_code;

        $item->delete();

        return redirect()->route('consolidations.show', $consolidation->id)
            ->with('success', "Ítem eliminado del {$unit}".($label ? ": {$label}" : '.'));
    }

    public function send(string $id)
    {
        $consolidation = Consolidation::withCount('items')->findOrFail($id);
        $this->consolidationService->resolveUnmatchedItems($consolidation);
        $consolidation->loadCount('items');
        $unit = $consolidation->unitNoun();
        $Unit = $consolidation->unitNounTitle();
        if ($consolidation->status !== 'OPEN') {
            return back()->with('error', 'Solo se pueden enviar '.$consolidation->unitNoun(true).' abiertos.');
        }
        if ($consolidation->items_count < 1) {
            return back()->with('error', 'El '.$unit.' no tiene items.');
        }
        try {
            $this->consolidationService->sendConsolidation($consolidation);
            return back()->with('success', $Unit.' enviado. Los paquetes vinculados a un preregistro pasaron a IN_TRANSIT.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Crear un consolidado con un solo preregistro (envío de una sola caja).
     */
    public function createSingleFromPreregistration(Request $request, Preregistration $preregistration)
    {
        $serviceType = ServiceType::route($preregistration->service_type);
        $unit = ServiceType::consolidationNoun($serviceType);
        $data = $request->validate([
            'transport_number' => 'required|string|max:80',
        ], [
            'transport_number.required' => 'Indique el '.ServiceType::transportNumberLabel($serviceType).'.',
        ]);

        if ($preregistration->status !== 'RECEIVED_MIAMI') {
            return redirect()->route('preregistrations.show', $preregistration->id)
                ->with('error', 'Solo se puede crear un '.$unit.' unitario para preregistros en Miami (RECEIVED_MIAMI).');
        }
        if ($preregistration->consolidationItem) {
            return redirect()->route('preregistrations.show', $preregistration->id)
                ->with('error', 'Este preregistro ya está en un '.$preregistration->consolidationItem->consolidation?->unitNoun().'.');
        }

        $consolidation = Consolidation::create([
            'code' => $this->consolidationService->generateCode($serviceType),
            'service_type' => $serviceType === ServiceType::SEA ? ServiceType::SEA : ServiceType::AIR,
            'transport_number' => strtoupper(trim((string) $data['transport_number'])),
            'status' => 'OPEN',
        ]);
        ConsolidationItem::create([
            'consolidation_id' => $consolidation->id,
            'preregistration_id' => $preregistration->id,
        ]);

        return redirect()->route('consolidations.label', $consolidation->id)
            ->with('success', $consolidation->unitNounTitle().' unitario creado (1 caja). Imprime la etiqueta y pégala.');
    }

    private function loadResolvedConsolidation(string $id): Consolidation
    {
        $consolidation = Consolidation::with(['items.preregistration'])->findOrFail($id);
        if ($this->consolidationService->resolveUnmatchedItems($consolidation) > 0) {
            $consolidation->load(['items.preregistration']);
        }

        return $consolidation;
    }

    private function unitNoun(?string $service, bool $plural = false): string
    {
        return ServiceType::consolidationNoun($service, $plural);
    }
}
