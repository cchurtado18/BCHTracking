<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAgencyAccess;
use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    use AuthorizesAgencyAccess;

    private const SESSION_BATCH_RETIRER = 'delivery_batch_retirer';

    private const SESSION_SCAN_RETIRER = 'delivery_scan_retirer';

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administradores pueden realizar esta acción.');
    }

    private function denyAgencyDeliveryWrite(): ?\Illuminate\Http\RedirectResponse
    {
        if (auth()->user()?->isAgencyUser()) {
            return redirect()->route('salidas.index');
        }

        return null;
    }

    /**
     * Crea una DeliveryNote con código único, reintentando si hay colisión
     * de unique en el código (race condition con dos requests simultáneos).
     */
    private function createDeliveryNoteForAgency(?Agency $agency): DeliveryNote
    {
        $maxAttempts = 3;
        $lastException = null;
        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                return DB::transaction(function () use ($agency) {
                    return DeliveryNote::create([
                        'code' => DeliveryNote::generateCode(),
                        'agency_id' => $agency?->id,
                    ]);
                });
            } catch (QueryException $e) {
                $lastException = $e;
                $msg = strtolower($e->getMessage());
                if (! str_contains($msg, 'unique') && ! str_contains($msg, 'duplicate')) {
                    throw $e;
                }
            }
        }
        throw $lastException;
    }

    private function batchRetirerSignature(int $agencyId, ?string $serviceType, int $deliveryNoteId): string
    {
        return hash('sha256', json_encode([
            'agency_id' => $agencyId,
            'service_type' => $serviceType ?? '',
            'delivery_note_id' => $deliveryNoteId,
        ]));
    }

    private function batchRetirerSessionMatches(?array $session, DeliveryNote $deliveryNote, int $agencyId, ?string $serviceType): bool
    {
        if (! is_array($session)) {
            return false;
        }

        return (int) ($session['delivery_note_id'] ?? 0) === (int) $deliveryNote->id
            && ($session['signature'] ?? '') === $this->batchRetirerSignature($agencyId, $serviceType, (int) $deliveryNote->id);
    }

    private function mergeBatchRetirerFromSession(Request $request): void
    {
        if (! $request->boolean('return_to_batch') || ! $request->filled('delivery_note_id')) {
            return;
        }

        $note = DeliveryNote::find((int) $request->delivery_note_id);
        if (! $note) {
            return;
        }

        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : ($request->filled('main_agency_id') ? (int) $request->main_agency_id : 0);
        if ($agencyId <= 0) {
            return;
        }

        $serviceType = $request->filled('service_type') && \App\Support\ServiceType::isValid($request->service_type)
            ? $request->service_type
            : null;

        $session = session(self::SESSION_BATCH_RETIRER);
        if (! $this->batchRetirerSessionMatches($session, $note, $agencyId, $serviceType)) {
            return;
        }

        $merge = [];
        if (! $request->filled('delivered_to')) {
            $merge['delivered_to'] = $session['delivered_to'] ?? '';
        }
        if (! $request->filled('retirer_id_number')) {
            $merge['retirer_id_number'] = $session['retirer_id_number'] ?? '';
        }
        if (! $request->filled('retirer_phone')) {
            $merge['retirer_phone'] = $session['retirer_phone'] ?? '';
        }
        if (! $request->filled('invoice_number')) {
            $merge['invoice_number'] = $session['invoice_number'] ?? '';
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    private function mergeScanRetirerFromSession(Request $request): void
    {
        if ($request->boolean('return_to_batch')) {
            return;
        }

        $session = session(self::SESSION_SCAN_RETIRER);
        if (! is_array($session)) {
            return;
        }

        $merge = [];
        if (! $request->filled('delivered_to')) {
            $merge['delivered_to'] = $session['delivered_to'] ?? '';
        }
        if (! $request->filled('retirer_id_number')) {
            $merge['retirer_id_number'] = $session['retirer_id_number'] ?? '';
        }
        if (! $request->filled('retirer_phone')) {
            $merge['retirer_phone'] = $session['retirer_phone'] ?? '';
        }
        if (! $request->filled('invoice_number')) {
            $merge['invoice_number'] = $session['invoice_number'] ?? '';
        }
        if (! $request->filled('delivery_note_id') && ! empty($session['delivery_note_id'])) {
            $merge['delivery_note_id'] = $session['delivery_note_id'];
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    private function persistBatchRetirerSession(Request $request): void
    {
        if (! $request->boolean('return_to_batch') || ! $request->filled('delivery_note_id')) {
            return;
        }

        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : ($request->filled('main_agency_id') ? (int) $request->main_agency_id : 0);
        if ($agencyId <= 0) {
            return;
        }

        $serviceType = $request->filled('service_type') && \App\Support\ServiceType::isValid($request->service_type)
            ? $request->service_type
            : null;

        session([self::SESSION_BATCH_RETIRER => [
            'delivery_note_id' => (int) $request->delivery_note_id,
            'signature' => $this->batchRetirerSignature($agencyId, $serviceType, (int) $request->delivery_note_id),
            'delivered_to' => $request->delivered_to,
            'retirer_id_number' => $request->retirer_id_number,
            'retirer_phone' => $request->retirer_phone,
            'invoice_number' => $request->invoice_number,
        ]]);
    }

    private function persistScanRetirerSession(Request $request): void
    {
        if ($request->boolean('return_to_batch')) {
            return;
        }

        $existing = session(self::SESSION_SCAN_RETIRER);
        $deliveryNoteId = $request->filled('delivery_note_id')
            ? (int) $request->delivery_note_id
            : (is_array($existing) ? ($existing['delivery_note_id'] ?? null) : null);

        session([self::SESSION_SCAN_RETIRER => [
            'delivered_to' => $request->delivered_to,
            'retirer_id_number' => $request->retirer_id_number,
            'retirer_phone' => $request->retirer_phone,
            'invoice_number' => $request->invoice_number,
            'delivery_note_id' => $deliveryNoteId ?: null,
        ]]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $scopeAgency = ($user && $user->isAgencyUser()) ? Agency::find($user->agency_id) : null;

        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : null;
        if ($scopeAgency) {
            $agencyId = (int) $scopeAgency->id;
        }

        $selectedAgency = $agencyId > 0 ? Agency::find($agencyId) : null;
        if ($selectedAgency) {
            $this->ensureUserCanAccessAgency($selectedAgency);
        }

        $deliveryFilter = $this->deliveryScopeFilter($selectedAgency);

        $notesQuery = DeliveryNote::query()
            ->withCount(['deliveries' => $deliveryFilter])
            ->with(['agency', 'firstDelivery.preregistration.agency'])
            ->whereHas('deliveries', $deliveryFilter)
            ->orderByDesc(DB::raw('(SELECT MAX(delivered_at) FROM deliveries WHERE deliveries.delivery_note_id = delivery_notes.id)'));

        $this->applyDeliveryNoteSearch($notesQuery, $request->input('q'));

        $deliveryNotes = $notesQuery->paginate(15)->withQueryString();
        $searchQuery = $request->input('q');
        $agenciesForSelect = $this->agenciesForSelect($user);

        $kpiBase = DeliveryNote::query()->whereHas('deliveries', $deliveryFilter);
        $monthStart = now()->startOfMonth();
        $statsTotal = (clone $kpiBase)->count();
        $statsMonth = (clone $kpiBase)
            ->whereHas('deliveries', fn ($q) => $q->where('delivered_at', '>=', $monthStart))
            ->count();
        $statsPackagesMonth = Delivery::query()
            ->when($selectedAgency, function ($q) use ($selectedAgency) {
                $q->whereHas('preregistration', fn ($q2) => $q2->whereIn('agency_id', $selectedAgency->deliveryNetworkIds()));
            })
            ->where('delivered_at', '>=', $monthStart)
            ->count();
        $statsReady = Preregistration::query()
            ->where('status', 'READY')
            ->whereDoesntHave('delivery')
            ->when($selectedAgency, fn ($q) => $q->whereIn('agency_id', $selectedAgency->deliveryNetworkIds()))
            ->count();

        return view('deliveries.index', compact(
            'deliveryNotes',
            'agenciesForSelect',
            'selectedAgency',
            'agencyId',
            'searchQuery',
            'statsTotal',
            'statsMonth',
            'statsPackagesMonth',
            'statsReady'
        ));
    }

    /**
     * Paso 1 de una hoja nueva: elegir agencia, ver paquetes listos e iniciar el lote.
     */
    public function create(Request $request)
    {
        if ($redirect = $this->denyAgencyDeliveryWrite()) {
            return $redirect;
        }

        $user = auth()->user();
        if ($user && $user->isAgencyUser()) {
            $request->merge(['agency_id' => (int) $user->agency_id]);
        }

        if ($request->has('clear_agency')) {
            session()->forget(['deliveries_index_agency_id', 'deliveries_index_service_type']);

            return redirect()->route('salidas.create');
        }

        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : null;
        $serviceType = $request->filled('service_type') && \App\Support\ServiceType::isValid($request->service_type)
            ? $request->service_type
            : null;

        if ($request->filled('agency_id') && ! $request->filled('service_type')) {
            session()->forget('deliveries_index_service_type');
        }

        if ($agencyId > 0) {
            session(['deliveries_index_agency_id' => $agencyId]);
            if ($serviceType !== null) {
                session(['deliveries_index_service_type' => $serviceType]);
            }
        } elseif (! $request->has('agency_id') && session()->has('deliveries_index_agency_id')) {
            $params = ['agency_id' => session('deliveries_index_agency_id')];
            if (session()->has('deliveries_index_service_type')) {
                $params['service_type'] = session('deliveries_index_service_type');
            }

            return redirect()->route('salidas.create', $params);
        }

        $agenciesForSelect = $this->agenciesForSelect($user);
        $selectedAgency = $agencyId > 0 ? Agency::find($agencyId) : null;
        if ($selectedAgency) {
            $this->ensureUserCanAccessAgency($selectedAgency);
        }

        $availablePackages = collect();
        $availableTotal = 0;
        $availableAir = 0;
        $availableSea = 0;
        $availableCft = 0;

        if ($selectedAgency) {
            $availableQuery = Preregistration::with('agency')
                ->where('status', 'READY')
                ->whereDoesntHave('delivery')
                ->whereIn('agency_id', $selectedAgency->deliveryNetworkIds());

            $allPackages = $availableQuery->orderBy('agency_id')->orderBy('warehouse_code')->get();
            $availableAir = $allPackages->where('service_type', 'AIR')->count();
            $availableSea = $allPackages->whereIn('service_type', \App\Support\ServiceType::servicesForRoute('SEA'))->count();
            $availableCft = $allPackages->where('service_type', 'CFT')->count();
            $availablePackages = $serviceType
                ? $allPackages->whereIn('service_type', \App\Support\ServiceType::operationalFilter($serviceType))->values()
                : $allPackages;
            $availableTotal = $availablePackages->count();
        }

        return view('deliveries.create', compact(
            'agenciesForSelect',
            'selectedAgency',
            'agencyId',
            'serviceType',
            'availablePackages',
            'availableTotal',
            'availableAir',
            'availableSea',
            'availableCft'
        ));
    }

    private function agenciesForSelect(?\App\Models\User $user)
    {
        if ($user && $user->isAgencyUser()) {
            $ownAgency = Agency::find($user->agency_id);
            $agenciesForSelect = collect();
            if ($ownAgency) {
                $agenciesForSelect->push((object) [
                    'id' => $ownAgency->id,
                    'name' => $ownAgency->name,
                    'is_main' => $ownAgency->is_main,
                ]);
            }

            return $agenciesForSelect;
        }

        $mainAgencies = Agency::mainAgencies()->where('is_active', true)->orderBy('name')->get();
        $subAgencies = Agency::where('is_main', false)->where('is_active', true)->with('parent')->orderBy('name')->get();

        return collect()
            ->merge($mainAgencies->map(fn ($a) => (object) [
                'id' => $a->id,
                'name' => $a->name.' (Agencia principal)',
                'is_main' => true,
            ]))
            ->merge($subAgencies->map(fn ($a) => (object) [
                'id' => $a->id,
                'name' => $a->name.($a->parent ? ' — '.$a->parent->name : ''),
                'is_main' => false,
            ]))
            ->sortBy('name')
            ->values();
    }

    private function deliveryScopeFilter(?Agency $selectedAgency): \Closure
    {
        return function ($q) use ($selectedAgency) {
            if ($selectedAgency) {
                $ids = $selectedAgency->deliveryNetworkIds();
                $q->whereHas('preregistration', fn ($q2) => $q2->whereIn('agency_id', $ids));
            }
        };
    }

    private function applyDeliveryNoteSearch($notesQuery, mixed $rawQuery): void
    {
        $q = trim((string) $rawQuery);
        if ($q === '') {
            return;
        }

        $like = '%'.$q.'%';
        $codeNeedles = $this->deliveryNoteCodeNeedles($q);

        $notesQuery->where(function ($query) use ($like, $codeNeedles) {
            $query->where(function ($codeQuery) use ($like, $codeNeedles) {
                $codeQuery->where('code', 'like', $like);
                foreach ($codeNeedles as $needle) {
                    $codeQuery->orWhere('code', 'like', '%'.$needle.'%');
                }
            })
                ->orWhereHas('agency', function ($aq) use ($like) {
                    $aq->where('name', 'like', $like)->orWhere('code', 'like', $like);
                })
                ->orWhereHas('deliveries.preregistration.agency', function ($aq) use ($like) {
                    $aq->where('name', 'like', $like)->orWhere('code', 'like', $like);
                })
                ->orWhereHas('deliveries', function ($dq) use ($like) {
                    $dq->where('delivered_to', 'like', $like)
                        ->orWhere('retirer_id_number', 'like', $like)
                        ->orWhere('retirer_phone', 'like', $like)
                        ->orWhere('invoice_number', 'like', $like)
                        ->orWhereHas('preregistration', function ($pq) use ($like) {
                            $pq->where('warehouse_code', 'like', $like)
                                ->orWhere('tracking_external', 'like', $like)
                                ->orWhere('label_name', 'like', $like);
                        });
                });
        });
    }

    /**
     * Variantes del número de hoja: "42", "0042", "SLO-42", "slo-0042".
     *
     * @return list<string>
     */
    private function deliveryNoteCodeNeedles(string $q): array
    {
        $stripped = strtoupper((string) preg_replace('/\s+/', '', $q));
        $stripped = (string) preg_replace('/^(SLO|BCH)-?/', '', $stripped);
        if ($stripped === '' || ! ctype_digit($stripped)) {
            return [];
        }

        $padded = str_pad($stripped, 4, '0', STR_PAD_LEFT);

        return array_values(array_unique([
            $stripped,
            $padded,
            'SLO-'.$padded,
            'BCH-'.$padded,
        ]));
    }

    /**
     * Reporte de entrega: lista de paquetes a entregar a la agencia y escaneo.
     * Acepta agency_id o main_agency_id por compatibilidad.
     */
    public function batch(Request $request)
    {
        if ($redirect = $this->denyAgencyDeliveryWrite()) {
            return $redirect;
        }

        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : null;
        $mainAgencyId = $request->filled('main_agency_id') ? (int) $request->main_agency_id : null;

        if ($agencyId > 0) {
            $agency = Agency::where('is_active', true)->find($agencyId);
            if (! $agency) {
                return redirect()->route('salidas.create')->with('error', 'Agencia no encontrada o desactivada.');
            }
            $this->ensureUserCanAccessAgency($agency);
            $mainAgencyId = $agency->is_main ? $agency->id : null;
            $subAgencyId = $agency->is_main ? null : $agency->id;
        } elseif ($mainAgencyId > 0) {
            $agency = Agency::where('is_active', true)->find($mainAgencyId);
            if (! $agency) {
                return redirect()->route('salidas.create')->with('error', 'Agencia no encontrada o desactivada.');
            }
            $this->ensureUserCanAccessAgency($agency);
            $subAgencyId = null;
        } else {
            return redirect()->route('salidas.create')->with('error', 'Seleccione una agencia para generar el salida de producto.');
        }

        $availableQuery = Preregistration::with('agency')
            ->where('status', 'READY')
            ->whereDoesntHave('delivery');

        $availableQuery->whereIn('agency_id', $agency->deliveryNetworkIds());

        $serviceType = $request->filled('service_type') && \App\Support\ServiceType::isValid($request->service_type) ? $request->service_type : null;
        if ($serviceType) {
            $availableQuery->whereIn('service_type', \App\Support\ServiceType::operationalFilter($serviceType));
        }

        $availablePackages = $availableQuery->orderBy('warehouse_code')
            ->orderByRaw('COALESCE(bulto_index, 999) ASC')
            ->get();
        $agencyName = $agency->name;
        $filterParams = array_filter(['agency_id' => $agency->id, 'service_type' => $serviceType]);

        // LAZY CREATE: solo cargar la nota si llega delivery_note_id en la URL.
        // Si no llega, mostramos la vista en "paso 1" (sin nota); la nota se crea
        // cuando el operador guarde los datos del retirante en storeBatchRetirerSession.
        $deliveryNote = null;
        if ($request->filled('delivery_note_id')) {
            $deliveryNote = DeliveryNote::find((int) $request->delivery_note_id);
            if (! $deliveryNote) {
                return redirect()->route('salidas.batch', $filterParams)->with('error', 'Hoja de salida no encontrada.');
            }
            if ((int) $deliveryNote->agency_id !== (int) $agency->id) {
                return redirect()->route('salidas.batch', $filterParams)->with('error', 'La hoja de salida no corresponde a esta agencia.');
            }
        }

        $retirerSessionActive = false;
        $batchRetirerSession = null;
        $deliveredCount = 0;
        $scannedDeliveries = collect();
        if ($deliveryNote) {
            $batchRetirerSession = session(self::SESSION_BATCH_RETIRER);
            $retirerSessionActive = $this->batchRetirerSessionMatches($batchRetirerSession, $deliveryNote, (int) $agency->id, $serviceType);
            $scannedDeliveries = $deliveryNote->deliveries()
                ->with('preregistration')
                ->orderByDesc('delivered_at')
                ->orderByDesc('id')
                ->get();
            $deliveredCount = $scannedDeliveries->count();
        }

        return view('deliveries.batch', compact(
            'availablePackages',
            'agencyName',
            'agency',
            'filterParams',
            'deliveryNote',
            'retirerSessionActive',
            'batchRetirerSession',
            'deliveredCount',
            'scannedDeliveries'
        ));
    }

    public function storeBatchRetirerSession(Request $request)
    {
        if ($redirect = $this->denyAgencyDeliveryWrite()) {
            return $redirect;
        }

        $validated = $request->validate([
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'agency_id' => 'required|exists:agencies,id',
            'service_type' => 'nullable|'.\App\Support\ServiceType::rule(),
            'delivered_to' => 'required|string|max:255',
            'retirer_id_number' => 'nullable|string|max:50',
            'retirer_phone' => 'nullable|string|max:50',
            'invoice_number' => 'nullable|string|max:50',
        ], [
            'delivered_to.required' => 'El nombre de quien retira es obligatorio.',
        ]);

        $agency = Agency::find((int) $validated['agency_id']);
        $this->ensureUserCanAccessAgency($agency);
        $serviceType = $validated['service_type'] ?? null;

        // Lazy-create: si no llega delivery_note_id, generamos la nota ahora.
        if (! empty($validated['delivery_note_id'])) {
            $deliveryNote = DeliveryNote::find((int) $validated['delivery_note_id']);
            if (! $deliveryNote) {
                return back()->withInput()->with('error', 'Hoja de salida no encontrada.');
            }
            if ((int) $deliveryNote->agency_id !== (int) $validated['agency_id']) {
                return back()->withInput()->with('error', 'La hoja de salida no corresponde a esta agencia.');
            }
        } else {
            $deliveryNote = $this->createDeliveryNoteForAgency($agency);
        }

        session([self::SESSION_BATCH_RETIRER => [
            'delivery_note_id' => (int) $deliveryNote->id,
            'signature' => $this->batchRetirerSignature((int) $validated['agency_id'], $serviceType, (int) $deliveryNote->id),
            'delivered_to' => $validated['delivered_to'],
            'retirer_id_number' => $validated['retirer_id_number'] ?? '',
            'retirer_phone' => $validated['retirer_phone'] ?? '',
            'invoice_number' => $validated['invoice_number'] ?? '',
        ]]);

        $redirectParams = array_filter([
            'agency_id' => (int) $validated['agency_id'],
            'service_type' => $serviceType,
            'delivery_note_id' => (int) $deliveryNote->id,
        ]);

        return redirect()->route('salidas.batch', $redirectParams)
            ->with('success', 'Datos de quien retira guardados. Ya puede escanear los paquetes.');
    }

    public function clearBatchRetirerSession(Request $request)
    {
        if ($redirect = $this->denyAgencyDeliveryWrite()) {
            return $redirect;
        }

        $validated = $request->validate([
            'delivery_note_id' => 'required|exists:delivery_notes,id',
            'agency_id' => 'required|exists:agencies,id',
            'service_type' => 'nullable|'.\App\Support\ServiceType::rule(),
        ]);

        $deliveryNote = DeliveryNote::find((int) $validated['delivery_note_id']);
        if ($deliveryNote && (int) $deliveryNote->agency_id !== (int) $validated['agency_id']) {
            return back()->with('error', 'La hoja de salida no corresponde a esta agencia.');
        }
        $agency = Agency::find((int) $validated['agency_id']);
        $this->ensureUserCanAccessAgency($agency);

        session()->forget(self::SESSION_BATCH_RETIRER);

        $redirectParams = array_filter([
            'agency_id' => (int) $validated['agency_id'],
            'service_type' => $request->filled('service_type') ? $request->service_type : null,
            'delivery_note_id' => (int) $validated['delivery_note_id'],
        ]);

        return redirect()->route('salidas.batch', $redirectParams)
            ->with('success', 'Indique de nuevo los datos de quien retira para continuar escaneando.');
    }

    /**
     * Reporte de salida imprimible: por hoja de salida (delivery_note_id) o por agencia + fecha.
     */
    public function printReport(Request $request)
    {
        $request->validate([
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'agency_id' => 'required_without_all:main_agency_id,delivery_note_id|nullable|exists:agencies,id',
            'main_agency_id' => 'required_without_all:agency_id,delivery_note_id|nullable|exists:agencies,id',
            'date' => 'nullable|date',
        ]);

        $deliveryNote = null;
        $date = $request->filled('date') ? $request->date : now()->toDateString();

        $deliveryNotesInReport = collect();

        if ($request->filled('delivery_note_id')) {
            $deliveryNote = DeliveryNote::with(['agency.parent', 'accountingInvoice', 'linkedInvoices'])->findOrFail((int) $request->delivery_note_id);
            $this->ensureUserCanAccessAgency($deliveryNote->agency);
            $deliveries = Delivery::with('preregistration.agency', 'preregistration.agencyClient', 'deliveryNote')
                ->where('delivery_note_id', $deliveryNote->id)
                ->orderBy('delivered_at')
                ->get();
            $agency = $deliveryNote->agency;
            $agencyName = $agency ? $agency->name : 'Agencia';
            $date = $deliveries->first()?->delivered_at?->toDateString()
                ?? $deliveryNote->created_at?->toDateString()
                ?? $date;
            $deliveryNotesInReport = collect([$deliveryNote]);
        } else {
            if ($request->filled('agency_id')) {
                $agency = Agency::with('parent')->find((int) $request->agency_id);
            } else {
                $mid = (int) $request->main_agency_id;
                $agency = Agency::with('parent')->find($mid);
            }
            $this->ensureUserCanAccessAgency($agency);

            $query = Delivery::with('preregistration.agency', 'preregistration.agencyClient', 'deliveryNote')
                ->whereDate('delivered_at', $date);

            $query->whereHas('preregistration', fn ($q) => $q->whereIn('agency_id', $agency->deliveryNetworkIds()));

            $deliveries = $query->orderBy('delivered_at')->get();
            $agencyName = $agency ? $agency->name : 'Agencia';
            $noteIds = $deliveries->pluck('delivery_note_id')->filter()->unique()->values();
            if ($noteIds->isNotEmpty()) {
                $deliveryNotesInReport = DeliveryNote::whereIn('id', $noteIds)->orderBy('code')->get();
            }
        }

        $first = $deliveries->first();
        $retiradoPor = $first ? $first->delivered_to : null;
        $retiradoCedula = $first && $deliveries->pluck('delivered_to')->unique()->count() === 1 && $deliveries->pluck('retirer_id_number')->unique()->count() === 1
            ? $first->retirer_id_number : null;
        $retiradoTelefono = $first && $deliveries->pluck('delivered_to')->unique()->count() === 1 && $deliveries->pluck('retirer_phone')->unique()->count() === 1
            ? $first->retirer_phone : null;

        // CH Logistics: usar diseño "Nota de cobro"
        if ($agency && $agency->isChLogistics()) {
            $firstPrereg = $first?->preregistration;
            $clientName = $firstPrereg?->agencyClient?->full_name ?? $firstPrereg?->label_name ?? $retiradoPor ?? '—';
            $clientPhone = $firstPrereg?->agencyClient?->phone ?? $retiradoTelefono ?? '—';
            $clientAddress = '—';
            $deliveryAddress = $agency->address ?? null;
            $deliveryPhone = $agency->phone ?? null;

            return view('deliveries.print-report-nota-cobro', compact(
                'deliveries', 'agencyName', 'agency', 'date', 'deliveryNote',
                'clientName', 'clientPhone', 'clientAddress', 'deliveryAddress', 'deliveryPhone',
                'retiradoPor', 'retiradoCedula', 'retiradoTelefono'
            ));
        }

        return view('deliveries.print-report', compact('deliveries', 'agencyName', 'agency', 'date', 'retiradoPor', 'retiradoCedula', 'retiradoTelefono', 'deliveryNote', 'deliveryNotesInReport'));
    }

    public function scan()
    {
        if ($redirect = $this->denyAgencyDeliveryWrite()) {
            return $redirect;
        }

        $scanRetirerSession = session(self::SESSION_SCAN_RETIRER);
        $scanRetirerSessionActive = is_array($scanRetirerSession)
            && filled($scanRetirerSession['delivered_to'] ?? null);

        $scanDeliveryNote = null;
        $scannedDeliveries = collect();
        if ($scanRetirerSessionActive) {
            $noteId = (int) ($scanRetirerSession['delivery_note_id'] ?? 0);
            if ($noteId > 0) {
                $scanDeliveryNote = DeliveryNote::find($noteId);
                $scannedDeliveries = Delivery::with('preregistration')
                    ->where('delivery_note_id', $noteId)
                    ->orderByDesc('delivered_at')
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get();
            } else {
                $scannedDeliveries = Delivery::with('preregistration')
                    ->where('delivered_to', $scanRetirerSession['delivered_to'])
                    ->whereDate('delivered_at', now()->toDateString())
                    ->orderByDesc('delivered_at')
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get();
            }
        }

        return view('deliveries.scan', compact(
            'scanRetirerSession',
            'scanRetirerSessionActive',
            'scannedDeliveries',
            'scanDeliveryNote'
        ));
    }

    public function storeScanRetirerSession(Request $request)
    {
        if ($redirect = $this->denyAgencyDeliveryWrite()) {
            return $redirect;
        }

        $validated = $request->validate([
            'delivered_to' => 'required|string|max:255',
            'retirer_id_number' => 'nullable|string|max:50',
            'retirer_phone' => 'nullable|string|max:50',
            'invoice_number' => 'nullable|string|max:50',
        ], [
            'delivered_to.required' => 'El nombre de quien retira es obligatorio.',
        ]);

        session([self::SESSION_SCAN_RETIRER => [
            'delivered_to' => $validated['delivered_to'],
            'retirer_id_number' => $validated['retirer_id_number'] ?? '',
            'retirer_phone' => $validated['retirer_phone'] ?? '',
            'invoice_number' => $validated['invoice_number'] ?? '',
        ]]);

        return redirect()->route('salidas.scan')
            ->with('success', 'Datos de quien retira guardados. Ya puede escanear warehouse o tracking.');
    }

    public function clearScanRetirerSession()
    {
        if ($redirect = $this->denyAgencyDeliveryWrite()) {
            return $redirect;
        }

        session()->forget(self::SESSION_SCAN_RETIRER);

        return redirect()->route('salidas.scan')
            ->with('success', 'Indique de nuevo los datos de quien retira.');
    }

    public function processScan(Request $request)
    {
        if ($redirect = $this->denyAgencyDeliveryWrite()) {
            return $redirect;
        }

        $this->mergeBatchRetirerFromSession($request);
        $this->mergeScanRetirerFromSession($request);

        // Acepta "code" (nuevo) o "warehouse_code" (compatibilidad con formularios anteriores).
        if (! $request->filled('code') && $request->filled('warehouse_code')) {
            $request->merge(['code' => $request->input('warehouse_code')]);
        }

        $request->validate([
            'code' => 'required|string|max:100',
            'bulto_index' => 'nullable|integer|min:1|max:255',
            'delivered_to' => 'required|string|max:255',
            'retirer_id_number' => 'nullable|string|max:50',
            'retirer_phone' => 'nullable|string|max:50',
            'invoice_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ], [
            'code.required' => 'Escanee el código warehouse o el tracking.',
            'delivered_to.required' => 'El nombre de quien retira es obligatorio.',
        ]);

        $code = strtoupper(trim((string) $request->input('code')));
        $isWarehouseCode = (bool) preg_match('/^\d{6}$/', $code);

        // Calcular agencias permitidas: combinación de la del batch + la del usuario (intersección)
        $userAllowed = $this->userAllowedAgencyIds();
        $batchAllowed = null;
        if ($request->boolean('return_to_batch')) {
            $batchAgencyId = $request->filled('agency_id')
                ? (int) $request->agency_id
                : (int) $request->main_agency_id;
            if ($batchAgencyId > 0) {
                $batchAgency = Agency::find($batchAgencyId);
                $batchAllowed = $batchAgency ? $batchAgency->deliveryNetworkIds() : [$batchAgencyId];
            }
        }
        $allowedAgencyIds = null;
        if ($userAllowed !== null && $batchAllowed !== null) {
            $allowedAgencyIds = array_values(array_intersect($userAllowed, $batchAllowed));
        } elseif ($userAllowed !== null) {
            $allowedAgencyIds = $userAllowed;
        } elseif ($batchAllowed !== null) {
            $allowedAgencyIds = $batchAllowed;
        }

        try {
            $result = DB::transaction(function () use ($request, $allowedAgencyIds, $code, $isWarehouseCode) {
                $candidates = Preregistration::query()
                    ->when(
                        $isWarehouseCode,
                        fn ($query) => $query->where('warehouse_code', $code),
                        fn ($query) => $query->whereRaw('UPPER(tracking_external) = ?', [$code])
                    )
                    ->where('status', 'READY')
                    ->whereDoesntHave('delivery')
                    ->orderByRaw('COALESCE(bulto_index, 999) ASC')
                    ->lockForUpdate()
                    ->get();

                if ($candidates->isEmpty()) {
                    $any = Preregistration::query()
                        ->when(
                            $isWarehouseCode,
                            fn ($query) => $query->where('warehouse_code', $code),
                            fn ($query) => $query->whereRaw('UPPER(tracking_external) = ?', [$code])
                        )
                        ->first();
                    if (! $any) {
                        return ['error' => $isWarehouseCode
                            ? 'Código de almacén no encontrado.'
                            : 'Tracking no encontrado.'];
                    }
                    if ($any->status !== 'READY') {
                        return ['error' => 'El paquete no está listo para salida (debe estar READY).'];
                    }
                    if ($any->delivery) {
                        return ['error' => 'El paquete ya salió en una hoja.'];
                    }

                    return ['error' => 'No hay paquetes pendientes con ese código.'];
                }

                if ($candidates->count() > 1) {
                    $bultoIndex = $request->filled('bulto_index') ? (int) $request->bulto_index : null;
                    if ($bultoIndex === null) {
                        return ['error' => 'Varios bultos con este código. Indique cuál entregó (ej. 1/11, 2/11…).'];
                    }
                    $preregistration = $candidates->firstWhere('bulto_index', $bultoIndex);
                    if (! $preregistration) {
                        return ['error' => 'Bulto '.$bultoIndex.'/'.($candidates->first()->bultos_total ?? '?').' no encontrado o ya registrado en hoja.'];
                    }
                } else {
                    $preregistration = $candidates->first();
                }

                // Restricción de agencia (incondicional): el usuario solo puede entregar paquetes
                // de su agencia (si es de subagencia) y/o de la agencia del batch.
                if ($allowedAgencyIds !== null && ! in_array((int) $preregistration->agency_id, $allowedAgencyIds, true)) {
                    return ['error' => 'Este paquete no corresponde a esta salida. Solo se aceptan paquetes de la agencia indicada.'];
                }

                $deliveryData = [
                    'preregistration_id' => $preregistration->id,
                    'delivered_at' => now(),
                    'delivered_to' => $request->delivered_to,
                    'retirer_id_number' => $request->filled('retirer_id_number') ? $request->retirer_id_number : null,
                    'retirer_phone' => $request->filled('retirer_phone') ? $request->retirer_phone : null,
                    'invoice_number' => $request->filled('invoice_number') ? $request->invoice_number : null,
                    'delivery_type' => 'PICKUP',
                    'notes' => $request->notes,
                ];

                // Toda entrega debe quedar vinculada a una nota de salida.
                $preregistration->loadMissing('agency');
                $pkgAgencyId = (int) $preregistration->agency_id;
                $note = null;

                if ($request->filled('delivery_note_id')) {
                    $note = DeliveryNote::lockForUpdate()->find((int) $request->delivery_note_id);
                    if (! $note) {
                        return ['error' => 'Hoja de salida no encontrada.'];
                    }
                    $noteAgency = $note->relationLoaded('agency') ? $note->agency : Agency::find($note->agency_id);
                    $allowedOnNote = $noteAgency ? $noteAgency->deliveryNetworkIds() : [(int) $note->agency_id];
                    if (! in_array($pkgAgencyId, $allowedOnNote, true)) {
                        return ['error' => 'Este paquete no corresponde a la red de la hoja de salida.'];
                    }
                } else {
                    $note = $this->createDeliveryNoteForAgency($preregistration->agency);
                }

                $deliveryData['delivery_note_id'] = $note->id;

                $delivery = Delivery::create($deliveryData);
                $preregistration->update(['status' => 'DELIVERED']);

                return [
                    'delivery' => $delivery,
                    'preregistration' => $preregistration,
                    'delivery_note_id' => $note->id,
                ];
            });
        } catch (QueryException $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'unique') || str_contains($msg, 'duplicate')) {
                return back()->with('error', 'Este paquete ya salió en una hoja.')->withInput();
            }
            throw $e;
        }

        if (isset($result['error'])) {
            return back()->with('error', $result['error'])->withInput();
        }

        /** @var Delivery $delivery */
        $delivery = $result['delivery'];
        /** @var Preregistration $preregistration */
        $preregistration = $result['preregistration'];
        $linkedNoteId = (int) ($result['delivery_note_id'] ?? $delivery->delivery_note_id);

        if (! $request->boolean('return_to_batch') && $linkedNoteId > 0) {
            $request->merge(['delivery_note_id' => $linkedNoteId]);
        }

        $this->persistBatchRetirerSession($request);
        $this->persistScanRetirerSession($request);

        if ($request->boolean('return_to_batch')) {
            $params = array_filter([
                'main_agency_id' => $request->main_agency_id,
                'agency_id' => $request->agency_id,
                'delivery_note_id' => $request->delivery_note_id ?: $linkedNoteId,
                'service_type' => $request->filled('service_type') && \App\Support\ServiceType::isValid($request->service_type)
                    ? $request->service_type
                    : null,
            ]);

            return redirect()->route('salidas.batch', $params)
                ->with('success', 'Salida registrada: '.$preregistration->label_name);
        }

        return redirect()->route('salidas.scan')
            ->with('success', 'Salida registrada: '.$preregistration->label_name.' ('.($preregistration->warehouse_code ?: $preregistration->tracking_external).')');
    }

    public function show(string $id)
    {
        $delivery = Delivery::with(['preregistration.agency', 'preregistration.agencyClient', 'deliveryNote'])->findOrFail($id);
        $this->ensureUserCanAccessAgency($delivery->preregistration?->agency);

        return view('deliveries.show', compact('delivery'));
    }

    /**
     * Admin: editar hoja de salida y quitar paquetes escaneados por error.
     */
    public function editNote(DeliveryNote $deliveryNote)
    {
        $this->ensureAdmin();

        $deliveryNote->load([
            'agency',
            'accountingInvoice',
            'linkedInvoices',
            'deliveries' => fn ($q) => $q->with('preregistration.agency')->orderBy('delivered_at'),
        ]);

        $firstDelivery = $deliveryNote->deliveries->first();

        return view('deliveries.edit-note', compact('deliveryNote', 'firstDelivery'));
    }

    public function updateNote(Request $request, DeliveryNote $deliveryNote)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'delivered_to' => 'required|string|max:255',
            'retirer_id_number' => 'nullable|string|max:50',
            'retirer_phone' => 'nullable|string|max:50',
        ], [
            'delivered_to.required' => 'El nombre de quien retira es obligatorio.',
        ]);

        $count = $deliveryNote->deliveries()->update([
            'delivered_to' => $validated['delivered_to'],
            'retirer_id_number' => $validated['retirer_id_number'] ?: null,
            'retirer_phone' => $validated['retirer_phone'] ?: null,
        ]);

        return redirect()->route('salidas.hojas.edit', $deliveryNote)
            ->with('success', "Hoja actualizada ({$count} " . ($count === 1 ? 'salida' : 'salidas') . ').');
    }

    public function removeFromNote(DeliveryNote $deliveryNote, Delivery $delivery)
    {
        $this->ensureAdmin();

        $activeInvoice = $deliveryNote->currentInvoice();
        if ($activeInvoice) {
            return back()->with('error', 'No se puede quitar paquetes: esta hoja ya tiene la factura '.$activeInvoice->folio.'. Anúlela primero.');
        }

        if ((int) $delivery->delivery_note_id !== (int) $deliveryNote->id) {
            return back()->with('error', 'Este paquete no pertenece a la nota indicada.');
        }

        $label = $delivery->preregistration?->warehouse_code
            ?? $delivery->preregistration?->label_name
            ?? '#'.$delivery->id;

        DB::transaction(function () use ($delivery, $deliveryNote) {
            $pre = Preregistration::lockForUpdate()->find($delivery->preregistration_id);
            $delivery->delete();

            if ($pre && $pre->status === 'DELIVERED') {
                $pre->update(['status' => 'READY']);
            }

            if ($deliveryNote->deliveries()->count() === 0) {
                $deliveryNote->delete();
            }
        });

        if (! DeliveryNote::whereKey($deliveryNote->id)->exists()) {
            return redirect()->route('salidas.index', session('deliveries_index_filters', []))
                ->with('success', "Paquete {$label} quitado. La nota quedó vacía y fue eliminada.");
        }

        return redirect()->route('salidas.hojas.edit', $deliveryNote)
            ->with('success', "Paquete {$label} quitado de la nota. El paquete volvió a estado «Listo para retiro».");
    }
}
