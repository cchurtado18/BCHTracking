<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePreregistrationRequest;
use App\Models\Agency;
use App\Models\Preregistration;
use App\Services\PreregistrationPhotoService;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PreregistrationController extends Controller
{
    protected WarehouseService $warehouseService;
    protected PreregistrationPhotoService $photoService;

    public function __construct(WarehouseService $warehouseService, PreregistrationPhotoService $photoService)
    {
        $this->warehouseService = $warehouseService;
        $this->photoService = $photoService;
    }

    public function index(Request $request)
    {
        if ($request->has('clear_filters')) {
            session()->forget('preregistrations_index_filters');
            return redirect()->route('preregistrations.index');
        }

        $filterKeys = ['search', 'service_type', 'intake_type', 'status', 'date_from', 'date_to'];
        $stateKeys = array_merge($filterKeys, ['page']);
        if (! $request->hasAny($stateKeys) && session()->has('preregistrations_index_filters')) {
            return redirect()->route('preregistrations.index', session('preregistrations_index_filters'));
        }

        if ($request->hasAny($stateKeys)) {
            $state = $request->only($filterKeys);

            // Persist current pagination page so "Volver" can return to the same list page.
            if ($request->filled('page')) {
                $state['page'] = (int) $request->query('page');
            }

            session(['preregistrations_index_filters' => $state]);
        }

        $query = Preregistration::with(['photos', 'agency']);

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }
        if ($request->filled('intake_type')) {
            $query->where('intake_type', $request->intake_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_external', 'like', "%{$search}%")
                    ->orWhere('warehouse_code', 'like', "%{$search}%")
                    ->orWhere('label_name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $preregistrations = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Estadísticas con los mismos filtros
        $statsQuery = Preregistration::query();
        if ($request->filled('service_type')) {
            $statsQuery->where('service_type', $request->service_type);
        }
        if ($request->filled('intake_type')) {
            $statsQuery->where('intake_type', $request->intake_type);
        }
        if ($request->filled('status')) {
            $statsQuery->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $statsQuery->where(function ($q) use ($search) {
                $q->where('tracking_external', 'like', "%{$search}%")
                    ->orWhere('warehouse_code', 'like', "%{$search}%")
                    ->orWhere('label_name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }
        $statsTotal = $statsQuery->count();
        $statsAir = (clone $statsQuery)->where('service_type', 'AIR')->count();
        $statsSea = (clone $statsQuery)->where('service_type', 'SEA')->count();
        $statsReceived = (clone $statsQuery)->where('status', 'RECEIVED_MIAMI')->count();
        $statsReady = (clone $statsQuery)->where('status', 'READY')->count();

        return view('preregistrations.index', compact('preregistrations', 'statsTotal', 'statsAir', 'statsSea', 'statsReceived', 'statsReady'));
    }

    public function create(Request $request)
    {
        if ($request->has('cancel_dropoff')) {
            session()->forget(['dropoff_warehouse_code', 'dropoff_bultos_total', 'dropoff_agency_id', 'dropoff_service_type', 'dropoff_tracking_external', 'dropoff_created_ids']);
            return redirect()->route('preregistrations.create');
        }

        $agencies = Agency::where('is_active', true)->orderBy('name')->get();
        $dropoffContinuation = false;
        $dropoffStep = 1;
        $dropoffTotal = 1;
        $dropoffAgencyName = null;
        $dropoffServiceType = 'AIR';

        if (session('dropoff_warehouse_code') && session('dropoff_bultos_total') && session('dropoff_created_ids')) {
            $created = count(session('dropoff_created_ids', []));
            $total = (int) session('dropoff_bultos_total');
            if ($created < $total) {
                $dropoffContinuation = true;
                $dropoffStep = $created + 1;
                $dropoffTotal = $total;
                $agencyId = session('dropoff_agency_id');
                if ($agencyId) {
                    $agency = Agency::find($agencyId);
                    $dropoffAgencyName = $agency ? $agency->name : null;
                }
                $dropoffServiceType = session('dropoff_service_type', 'AIR');
            }
        }

        return response()
            ->view('preregistrations.create', compact(
                'agencies',
                'dropoffContinuation',
                'dropoffStep',
                'dropoffTotal',
                'dropoffAgencyName',
                'dropoffServiceType'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function store(StorePreregistrationRequest $request)
    {
        $data = $request->validated();
        $data['intake_type'] = $data['intake_type'] ?? 'COURIER';
        $data['status'] = 'RECEIVED_MIAMI';

        // Drop off multi-bulto paso a paso: un bulto por vez, imprimir etiqueta y continuar
        if ($request->isDropoffStepSubmission()) {
            return $this->storeDropoffBultoStep($request, $data);
        }

        if ($request->isMultiBultoDropoff()) {
            return $this->storeMultiBultoDropoff($request, $data);
        }

        try {
            $data['warehouse_code'] = $this->warehouseService->generateWarehouseCode();
            $preregistration = Preregistration::create($data);
        } catch (\Throwable $e) {
            \Log::error('Preregistration store failed', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'data' => $data]);
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'No se pudo guardar el preregistro. ' . $e->getMessage()]);
        }

        if ($request->hasFile('photo')) {
            try {
                $photo = $request->file('photo');
                if ($photo->getSize() > 10 * 1024 * 1024) {
                    return redirect()->route('preregistrations.show', $preregistration->id)
                        ->with('error', 'La foto excede el tamaño máximo de 10MB.');
                }
                if (!in_array($photo->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])) {
                    return redirect()->route('preregistrations.show', $preregistration->id)
                        ->with('error', 'El formato de la foto no es válido.');
                }
                $this->photoService->uploadPhoto($preregistration, $photo);
                return redirect()->route('preregistrations.label', $preregistration->id)
                    ->with('success', 'Preregistro creado con foto. Imprime la etiqueta para pegarla al paquete.');
            } catch (\Exception $e) {
                return redirect()->route('preregistrations.show', $preregistration->id)
                    ->with('error', 'Error al subir la foto: ' . $e->getMessage());
            }
        }

        return redirect()->route('preregistrations.label', $preregistration->id)
            ->with('success', 'Preregistro creado. Imprime la etiqueta.')
            ->with('warning', 'No se subió foto.');
    }

    /**
     * Drop off paso a paso: guardar un bulto, redirigir a imprimir su etiqueta, luego el usuario continúa con el siguiente.
     */
    protected function storeDropoffBultoStep(StorePreregistrationRequest $request, array $data): \Illuminate\Http\RedirectResponse
    {
        $step = (int) $request->input('dropoff_step', 1);
        $total = (int) $request->input('bultos_count', 1);
        $total = min(max($total, 1), 20);

        if ($step === 1) {
            // Primer bulto: generar warehouse_code y guardar en sesión
            try {
                $warehouseCode = $this->warehouseService->generateWarehouseCode();
                $dropoffTracking = trim((string) ($data['tracking_external'] ?? ''));
                if ($dropoffTracking === '') {
                    $dropoffTracking = null;
                }
                $row = [
                    'label_name' => $data['label_name'] ?? '',
                    'intake_weight_lbs' => $data['intake_weight_lbs'] ?? 0,
                    'dimension' => $data['dimension'] ?? '',
                    'description' => $data['description'] ?? null,
                ];
                $preregistration = Preregistration::create([
                    'intake_type' => 'DROP_OFF',
                    'warehouse_code' => $warehouseCode,
                    'agency_id' => $data['agency_id'],
                    'service_type' => $data['service_type'],
                    'status' => 'RECEIVED_MIAMI',
                    'tracking_external' => $dropoffTracking,
                    'label_name' => $row['label_name'],
                    'intake_weight_lbs' => $row['intake_weight_lbs'],
                    'dimension' => $row['dimension'],
                    'description' => $row['description'],
                    'bulto_index' => 1,
                    'bultos_total' => $total,
                ]);
                if ($request->hasFile('photo')) {
                    $this->photoService->uploadPhoto($preregistration, $request->file('photo'));
                }
                session([
                    'dropoff_warehouse_code' => $warehouseCode,
                    'dropoff_bultos_total' => $total,
                    'dropoff_agency_id' => $data['agency_id'],
                    'dropoff_service_type' => $data['service_type'],
                    'dropoff_tracking_external' => $dropoffTracking,
                    'dropoff_created_ids' => [$preregistration->id],
                ]);
                return redirect()->route('preregistrations.label', $preregistration->id)
                    ->with('success', "Bulto 1 de {$total} guardado. Imprime esta etiqueta y luego continúa con el siguiente.");
            } catch (\Throwable $e) {
                \Log::error('Preregistration storeDropoffBultoStep step 1 failed', ['exception' => $e->getMessage()]);
                return redirect()->back()->withInput()->withErrors(['general' => 'No se pudo guardar. ' . $e->getMessage()]);
            }
        }

        // Paso 2 en adelante: usar datos de sesión
        $warehouseCode = session('dropoff_warehouse_code');
        $agencyId = session('dropoff_agency_id');
        $serviceType = session('dropoff_service_type');
        $trackingExternal = session('dropoff_tracking_external');
        $createdIds = session('dropoff_created_ids', []);
        $sessionTotal = (int) session('dropoff_bultos_total', 0);

        if (! $warehouseCode || $sessionTotal < 2 || $step > $sessionTotal || $step !== count($createdIds) + 1) {
            session()->forget(['dropoff_warehouse_code', 'dropoff_bultos_total', 'dropoff_agency_id', 'dropoff_service_type', 'dropoff_tracking_external', 'dropoff_created_ids']);
            return redirect()->route('preregistrations.create')
                ->with('error', 'Sesión de drop off expirada o inválida. Comienza de nuevo con el bulto 1.');
        }

        try {
            $row = [
                'label_name' => $data['label_name'] ?? '',
                'intake_weight_lbs' => $data['intake_weight_lbs'] ?? 0,
                'dimension' => $data['dimension'] ?? '',
                'description' => $data['description'] ?? null,
            ];
            $preregistration = Preregistration::create([
                'intake_type' => 'DROP_OFF',
                'warehouse_code' => $warehouseCode,
                'agency_id' => $agencyId,
                'service_type' => $serviceType,
                'status' => 'RECEIVED_MIAMI',
                'tracking_external' => $trackingExternal,
                'label_name' => $row['label_name'],
                'intake_weight_lbs' => $row['intake_weight_lbs'],
                'dimension' => $row['dimension'],
                'description' => $row['description'],
                'bulto_index' => $step,
                'bultos_total' => $sessionTotal,
            ]);
            if ($request->hasFile('photo')) {
                $this->photoService->uploadPhoto($preregistration, $request->file('photo'));
            }
            $newIds = array_merge($createdIds, [$preregistration->id]);
            session(['dropoff_created_ids' => $newIds]);

            if ($step >= $sessionTotal) {
                session()->forget(['dropoff_warehouse_code', 'dropoff_bultos_total', 'dropoff_agency_id', 'dropoff_service_type', 'dropoff_tracking_external', 'dropoff_created_ids']);
                return redirect()->route('preregistrations.label', $preregistration->id)
                    ->with('success', "Bulto {$step} de {$sessionTotal} guardado. Ya completaste todos los bultos. Imprime esta etiqueta.");
            }

            return redirect()->route('preregistrations.label', $preregistration->id)
                ->with('success', "Bulto {$step} de {$sessionTotal} guardado. Imprime esta etiqueta y luego continúa con el siguiente.");
        } catch (\Throwable $e) {
            \Log::error('Preregistration storeDropoffBultoStep step > 1 failed', ['exception' => $e->getMessage()]);
            return redirect()->back()->withInput()->withErrors(['general' => 'No se pudo guardar. ' . $e->getMessage()]);
        }
    }

    /** Drop Off con varios bultos: un warehouse code, N preregistros (envío único de todos). */
    protected function storeMultiBultoDropoff(StorePreregistrationRequest $request, array $data): \Illuminate\Http\RedirectResponse
    {
        $bultos = $request->input('bultos', []);
        $n = min((int) $request->input('bultos_count', 1), count($bultos));
        if ($n < 1) {
            return redirect()->back()->withInput()->withErrors(['bultos' => 'Indique al menos un bulto.']);
        }

        try {
            $warehouseCode = $this->warehouseService->generateWarehouseCode();
            $base = [
                'intake_type' => 'DROP_OFF',
                'warehouse_code' => $warehouseCode,
                'agency_id' => $data['agency_id'],
                'service_type' => $data['service_type'],
                'status' => 'RECEIVED_MIAMI',
                'tracking_external' => !empty($data['tracking_external']) ? trim((string) $data['tracking_external']) : null,
            ];
            $ids = [];
            for ($i = 0; $i < $n; $i++) {
                $row = $bultos[$i] ?? [];
                $preregistration = Preregistration::create(array_merge($base, [
                    'label_name' => $row['label_name'] ?? '',
                    'intake_weight_lbs' => $row['intake_weight_lbs'] ?? 0,
                    'dimension' => $row['dimension'] ?? '',
                    'description' => $row['description'] ?? null,
                    'bulto_index' => $i + 1,
                    'bultos_total' => $n,
                ]));
                $ids[] = $preregistration->id;
                $photoKey = 'photo_bulto_' . $i;
                if ($request->hasFile($photoKey)) {
                    try {
                        $photo = $request->file($photoKey);
                        if ($photo->getSize() <= 10 * 1024 * 1024 && in_array($photo->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])) {
                            $this->photoService->uploadPhoto($preregistration, $photo);
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Dropoff multi: photo upload failed for bulto ' . ($i + 1), ['e' => $e->getMessage()]);
                    }
                }
            }
            $idsParam = implode(',', $ids);
            return redirect()->route('preregistrations.dropoff-labels', ['ids' => $idsParam])
                ->with('success', "Se crearon {$n} bultos con el mismo código de almacén ({$warehouseCode}). Imprime una etiqueta por cada bulto.");
        } catch (\Throwable $e) {
            \Log::error('Preregistration storeMultiBultoDropoff failed', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'No se pudo guardar los bultos. ' . $e->getMessage()]);
        }
    }

    public function show(string $id)
    {
        $preregistration = Preregistration::with(['photos', 'agency', 'consolidationItem.consolidation'])->findOrFail($id);
        $preregistration->photos->each(function ($photo) {
            $photo->url = asset('storage/' . $photo->path);
        });
        $dropoffLabelIds = [];
        if ($preregistration->warehouse_code && $preregistration->bultos_total && $preregistration->bultos_total > 1) {
            $dropoffLabelIds = Preregistration::where('warehouse_code', $preregistration->warehouse_code)
                ->whereNotNull('bultos_total')
                ->orderBy('bulto_index')
                ->pluck('id')
                ->toArray();
        }
        return view('preregistrations.show', compact('preregistration', 'dropoffLabelIds'));
    }

    public function edit(string $id)
    {
        $preregistration = Preregistration::with('photos', 'agency')->findOrFail($id);
        $preregistration->photos->each(function ($photo) {
            $photo->url = asset('storage/' . $photo->path);
        });
        $agencies = Agency::where('is_active', true)->orderBy('name')->get();
        return view('preregistrations.edit', compact('preregistration', 'agencies'));
    }

    public function update(Request $request, string $id)
    {
        $preregistration = Preregistration::findOrFail($id);
        $wasPhotoPending = $preregistration->status === 'PHOTO_PENDING';
        $trackingRules = ['nullable', 'string', 'max:255'];
        if ($request->filled('tracking_external')) {
            $trackingRules[] = \Illuminate\Validation\Rule::unique('preregistrations', 'tracking_external')->ignore($preregistration->id);
        }
        $data = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'label_name' => 'sometimes|string|max:255',
            'service_type' => 'sometimes|in:AIR,SEA',
            'intake_weight_lbs' => 'sometimes|numeric|min:0|max:999999.99',
            'tracking_external' => $trackingRules,
            'dimension' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ], [
            'tracking_external.unique' => 'Este tracking ya está registrado en otro paquete. Use otro número o elimine el paquete que lo tiene.',
        ]);

        // Cuando estaba en PHOTO_PENDING y ya se completan los datos,
        // pasarlo a RECEIVED_MIAMI y generar código de almacén si aún no tiene.
        if ($wasPhotoPending) {
            $data['status'] = 'RECEIVED_MIAMI';
            if (! $preregistration->warehouse_code) {
                $data['warehouse_code'] = $this->warehouseService->generateWarehouseCode();
            }
        }

        $preregistration->update($data);
        $preregistration->refresh();

        // Si venía de captura rápida, al completar mandamos directo a la etiqueta,
        // igual que cuando se guarda un preregistro normal.
        if ($wasPhotoPending && $preregistration->warehouse_code) {
            return redirect()->route('preregistrations.label', $preregistration->id)
                ->with('success', 'Preregistro completado. Imprime la etiqueta para pegarla al paquete.');
        }

        return redirect()->route('preregistrations.show', $preregistration->id)
            ->with('success', 'Preregistro actualizado.');
    }

    /**
     * Formulario de captura rápida para Courier: solo tracking (opcional) + foto.
     */
    public function quickCourier()
    {
        return view('preregistrations.quick-courier');
    }

    /**
     * Guarda un preregistro rápido de Courier con estado PHOTO_PENDING.
     */
    public function storeQuickCourier(Request $request)
    {
        $data = $request->validate([
            'tracking_external' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('preregistrations', 'tracking_external')->whereNull('deleted_at'),
            ],
            'photo' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $photoFiles = $request->file('photos', []);
        if (empty($photoFiles) && $request->hasFile('photo')) {
            $photoFiles = [$request->file('photo')];
        }
        if (empty($photoFiles)) {
            $message = 'Debes tomar al menos una foto del paquete.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->back()->withInput()->withErrors(['photo' => $message]);
        }

        // Algunos campos de la tabla (como label_name) no permiten NULL,
        // así que usamos valores de marcador que luego se podrán editar.
        $preregistration = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => $data['tracking_external'] ?? null,
            'label_name' => '[PENDIENTE]',
            'status' => 'PHOTO_PENDING',
        ]);

        foreach ($photoFiles as $photoFile) {
            $this->photoService->uploadPhoto($preregistration, $photoFile);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Preregistro rápido creado.',
                'redirect_url' => route('preregistrations.show', $preregistration->id),
            ]);
        }

        return redirect()->route('preregistrations.show', $preregistration->id)
            ->with('success', 'Preregistro rápido creado. Falta completar los datos de etiqueta y agencia.');
    }

    public function uploadPhoto(Request $request, string $id)
    {
        $preregistration = Preregistration::findOrFail($id);
        $request->validate(['photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240']);
        try {
            if ($preregistration->photos()->count() >= 3) {
                $message = 'Este preregistro ya tiene 3 fotos. Máximo permitido: 3.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 422);
                }
                return redirect()->route('preregistrations.show', $preregistration->id)->with('error', $message);
            }

            $photo = $this->photoService->uploadPhoto($preregistration, $request->file('photo'));

            if ($request->expectsJson()) {
                return response()->json([
                    'id' => $photo->id,
                    'url' => asset('storage/' . $photo->path),
                    'message' => 'Foto subida.',
                    'photos_count' => $preregistration->photos()->count(),
                ]);
            }

            return redirect()->route('preregistrations.show', $preregistration->id)->with('success', 'Foto subida.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return redirect()->route('preregistrations.show', $preregistration->id)->with('error', $e->getMessage());
        }
    }

    public function label(Request $request, string $id)
    {
        $preregistration = Preregistration::with(['agency', 'agency.parent'])->findOrFail($id);
        if (empty($preregistration->warehouse_code)) {
            return redirect()->route('preregistrations.show', $preregistration->id)
                ->with('error', 'Este preregistro no tiene código de almacén.');
        }
        $dropoffNextStep = null;
        $dropoffTotal = null;
        if (session('dropoff_warehouse_code') && session('dropoff_bultos_total') && session('dropoff_created_ids')) {
            $created = count(session('dropoff_created_ids'));
            $total = (int) session('dropoff_bultos_total');
            if ($created < $total) {
                $dropoffNextStep = $created + 1;
                $dropoffTotal = $total;
            }
        }

        $labelFormat = $this->resolveLabelPaperFormat($request);

        // Usar el mismo diseño nuevo de etiqueta para cualquier agencia/servicio.
        return view('preregistrations.label-skylink-one', compact('preregistration', 'dropoffNextStep', 'dropoffTotal', 'labelFormat'));
    }

    /** Imprimir todas las etiquetas de un dropoff (varios bultos mismo warehouse). Query: ids=1,2,3 */
    public function dropoffLabels(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', $request->query('ids', ''))));
        if (empty($ids)) {
            return redirect()->route('preregistrations.index')->with('error', 'No se indicaron preregistros.');
        }
        $preregistrations = Preregistration::with(['agency', 'agency.parent'])
            ->whereIn('id', $ids)
            ->orderBy('bulto_index')
            ->get();
        if ($preregistrations->isEmpty()) {
            return redirect()->route('preregistrations.index')->with('error', 'No se encontraron preregistros.');
        }
        $labelFormat = $this->resolveLabelPaperFormat($request);

        return view('preregistrations.dropoff-labels', compact('preregistrations', 'labelFormat'));
    }

    /**
     * Papel de etiqueta: 4×6 (por defecto) o 2.25×4 para drivers que solo exponen ese tamaño (ej. térmicas estrechas).
     * Query: ?format=narrow | ?format=225x4 | ?paper=225x4
     */
    private function resolveLabelPaperFormat(Request $request): string
    {
        $f = strtolower((string) $request->query('format', ''));
        $p = strtolower((string) $request->query('paper', ''));

        if (in_array($f, ['narrow', '225x4', '2.25x4'], true) || in_array($p, ['225x4', '2.25x4', 'narrow'], true)) {
            return 'narrow';
        }

        return '4x6';
    }

    public function destroy(string $id)
    {
        $preregistration = Preregistration::with('photos')->findOrFail($id);

        if (! in_array($preregistration->status, ['RECEIVED_MIAMI', 'CANCELLED'])) {
            return redirect()->route('preregistrations.index', session('preregistrations_index_filters', []))
                ->with('error', 'No se puede eliminar un preregistro en proceso (solo se permite en estado Recibido Miami o Cancelado).');
        }

        if ($preregistration->consolidationItem()->exists()) {
            return redirect()->route('preregistrations.index', session('preregistrations_index_filters', []))
                ->with('error', 'No se puede eliminar: el preregistro está en un saco.');
        }

        if ($preregistration->delivery()->exists()) {
            return redirect()->route('preregistrations.index', session('preregistrations_index_filters', []))
                ->with('error', 'No se puede eliminar: el preregistro ya tiene entrega.');
        }

        foreach ($preregistration->photos as $photo) {
            if (Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
            $photo->delete();
        }

        $preregistration->delete();

        return redirect()->route('preregistrations.index', session('preregistrations_index_filters', []))
            ->with('success', 'Preregistro eliminado.');
    }
}
