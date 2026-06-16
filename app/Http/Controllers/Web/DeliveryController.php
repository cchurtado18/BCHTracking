<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    private const SESSION_BATCH_RETIRER = 'delivery_batch_retirer';

    private const SESSION_SCAN_RETIRER = 'delivery_scan_retirer';

    /**
     * Aborta con 403 si el usuario actual es de subagencia y no tiene acceso
     * a la agencia indicada (ni como agencia propia ni como subagencia hija).
     */
    private function ensureUserCanAccessAgency(?Agency $agency): void
    {
        $user = auth()->user();
        if (! $user || ! $agency) {
            return;
        }
        if (! $user->isAgencyUser()) {
            return;
        }
        $userAgencyId = (int) $user->agency_id;
        $allowed = (int) $agency->id === $userAgencyId
            || (int) ($agency->parent_agency_id ?? 0) === $userAgencyId;
        abort_unless($allowed, 403, 'No tiene permiso para esta agencia.');
    }

    /**
     * IDs de agencias que el usuario actual puede manipular.
     * Devuelve null si es central (acceso total).
     */
    private function userAllowedAgencyIds(): ?array
    {
        $user = auth()->user();
        if (! $user || ! $user->isAgencyUser()) {
            return null;
        }
        $ids = [(int) $user->agency_id];
        $ids = array_merge($ids, Agency::where('parent_agency_id', $user->agency_id)->pluck('id')->all());

        return array_values(array_unique($ids));
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

        $serviceType = $request->filled('service_type') && in_array($request->service_type, ['AIR', 'SEA'], true)
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

        $serviceType = $request->filled('service_type') && in_array($request->service_type, ['AIR', 'SEA'], true)
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

        session([self::SESSION_SCAN_RETIRER => [
            'delivered_to' => $request->delivered_to,
            'retirer_id_number' => $request->retirer_id_number,
            'retirer_phone' => $request->retirer_phone,
            'invoice_number' => $request->invoice_number,
        ]]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        // Usuario de subagencia: forzar su propia agencia (ignorar overrides).
        if ($user && $user->isAgencyUser()) {
            $request->merge(['agency_id' => (int) $user->agency_id]);
        }

        if ($request->has('clear_agency')) {
            session()->forget(['deliveries_index_agency_id', 'deliveries_index_service_type']);

            return redirect()->route('deliveries.index');
        }

        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : null;
        $serviceType = $request->filled('service_type') && in_array($request->service_type, ['AIR', 'SEA'], true) ? $request->service_type : null;

        // Si se eligió agencia sin filtro explícito de servicio, limpiar el filtro persistido
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

            return redirect()->route('deliveries.index', $params);
        }

        // Selector de agencias: si el usuario es de subagencia, solo la suya
        if ($user && $user->isAgencyUser()) {
            $ownAgency = Agency::find($user->agency_id);
            $agenciesForSelect = collect();
            if ($ownAgency) {
                $agenciesForSelect->push((object) ['id' => $ownAgency->id, 'name' => $ownAgency->name, 'is_main' => $ownAgency->is_main]);
            }
        } else {
            $mainAgencies = Agency::mainAgencies()->where('is_active', true)->orderBy('name')->get();
            $subAgencies = Agency::where('is_main', false)->where('is_active', true)->with('parent')->orderBy('name')->get();
            $agenciesForSelect = collect()
                ->merge($mainAgencies->map(fn ($a) => (object) ['id' => $a->id, 'name' => $a->name.' (Agencia principal)', 'is_main' => true]))
                ->merge($subAgencies->map(fn ($a) => (object) ['id' => $a->id, 'name' => $a->name.($a->parent ? ' — '.$a->parent->name : ''), 'is_main' => false]))
                ->sortBy('name')
                ->values();
        }

        $selectedAgency = $agencyId > 0 ? Agency::find($agencyId) : null;
        if ($selectedAgency) {
            $this->ensureUserCanAccessAgency($selectedAgency);
        }

        // Paquetes listos para retiro: solo si hay agencia seleccionada
        $availablePackages = collect();
        $availableTotal = 0;
        $availableAir = 0;
        $availableSea = 0;

        if ($selectedAgency) {
            $availableQuery = Preregistration::with('agency')
                ->where('status', 'READY')
                ->whereDoesntHave('delivery');

            if ($selectedAgency->is_main) {
                $availableQuery->whereHas('agency', fn ($q) => $q->where('id', $selectedAgency->id)->orWhere('parent_agency_id', $selectedAgency->id));
            } else {
                $availableQuery->where('agency_id', $selectedAgency->id);
            }

            $allPackages = $availableQuery->orderBy('agency_id')->orderBy('warehouse_code')->get();
            $availableAir = $allPackages->where('service_type', 'AIR')->count();
            $availableSea = $allPackages->where('service_type', 'SEA')->count();

            if ($serviceType) {
                $availablePackages = $allPackages->where('service_type', $serviceType)->values();
            } else {
                $availablePackages = $allPackages;
            }
            $availableTotal = $availablePackages->count();
        }

        // Notas de entrega: solo notas con al menos 1 entrega registrada (excluye huérfanas).
        $deliveryFilter = function ($q) use ($selectedAgency) {
            if ($selectedAgency) {
                if ($selectedAgency->is_main) {
                    $q->whereHas('preregistration', function ($q2) use ($selectedAgency) {
                        $q2->whereHas('agency', fn ($q3) => $q3->where('id', $selectedAgency->id)->orWhere('parent_agency_id', $selectedAgency->id));
                    });
                } else {
                    $q->whereHas('preregistration', fn ($q2) => $q2->where('agency_id', $selectedAgency->id));
                }
            }
        };

        $notesQuery = DeliveryNote::query()
            ->withCount(['deliveries' => $deliveryFilter])
            ->with(['agency', 'firstDelivery.preregistration.agency'])
            ->whereHas('deliveries', $deliveryFilter)
            ->orderByDesc(DB::raw('(SELECT MAX(delivered_at) FROM deliveries WHERE deliveries.delivery_note_id = delivery_notes.id)'));

        $deliveryNotes = $notesQuery->paginate(15)->withQueryString();

        return view('deliveries.index', compact(
            'deliveryNotes',
            'agenciesForSelect',
            'selectedAgency',
            'agencyId',
            'serviceType',
            'availablePackages',
            'availableTotal',
            'availableAir',
            'availableSea'
        ));
    }

    /**
     * Reporte de entrega: lista de paquetes a entregar a la agencia y escaneo.
     * Acepta agency_id o main_agency_id por compatibilidad.
     */
    public function batch(Request $request)
    {
        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : null;
        $mainAgencyId = $request->filled('main_agency_id') ? (int) $request->main_agency_id : null;

        if ($agencyId > 0) {
            $agency = Agency::where('is_active', true)->find($agencyId);
            if (! $agency) {
                return redirect()->route('deliveries.index')->with('error', 'Agencia no encontrada o desactivada.');
            }
            $this->ensureUserCanAccessAgency($agency);
            $mainAgencyId = $agency->is_main ? $agency->id : null;
            $subAgencyId = $agency->is_main ? null : $agency->id;
        } elseif ($mainAgencyId > 0) {
            $agency = Agency::where('is_active', true)->find($mainAgencyId);
            if (! $agency) {
                return redirect()->route('deliveries.index')->with('error', 'Agencia no encontrada o desactivada.');
            }
            $this->ensureUserCanAccessAgency($agency);
            $subAgencyId = null;
        } else {
            return redirect()->route('deliveries.index')->with('error', 'Seleccione una agencia para generar el reporte de entrega.');
        }

        $availableQuery = Preregistration::with('agency')
            ->where('status', 'READY')
            ->whereDoesntHave('delivery');

        if ($subAgencyId) {
            $availableQuery->where('agency_id', $subAgencyId);
        } else {
            $mid = $agency->is_main ? $agency->id : (int) $mainAgencyId;
            $availableQuery->whereHas('agency', fn ($q) => $q->where('id', $mid)->orWhere('parent_agency_id', $mid));
        }

        $serviceType = $request->filled('service_type') && in_array($request->service_type, ['AIR', 'SEA'], true) ? $request->service_type : null;
        if ($serviceType) {
            $availableQuery->where('service_type', $serviceType);
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
                return redirect()->route('deliveries.batch', $filterParams)->with('error', 'Nota de entrega no encontrada.');
            }
            if ((int) $deliveryNote->agency_id !== (int) $agency->id) {
                return redirect()->route('deliveries.batch', $filterParams)->with('error', 'La nota de entrega no corresponde a esta agencia.');
            }
        }

        $retirerSessionActive = false;
        $batchRetirerSession = null;
        $deliveredCount = 0;
        if ($deliveryNote) {
            $batchRetirerSession = session(self::SESSION_BATCH_RETIRER);
            $retirerSessionActive = $this->batchRetirerSessionMatches($batchRetirerSession, $deliveryNote, (int) $agency->id, $serviceType);
            $deliveredCount = $deliveryNote->deliveries()->count();
        }

        return view('deliveries.batch', compact(
            'availablePackages',
            'agencyName',
            'agency',
            'filterParams',
            'deliveryNote',
            'retirerSessionActive',
            'batchRetirerSession',
            'deliveredCount'
        ));
    }

    public function storeBatchRetirerSession(Request $request)
    {
        $validated = $request->validate([
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'agency_id' => 'required|exists:agencies,id',
            'service_type' => 'nullable|in:AIR,SEA',
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
                return back()->withInput()->with('error', 'Nota de entrega no encontrada.');
            }
            if ((int) $deliveryNote->agency_id !== (int) $validated['agency_id']) {
                return back()->withInput()->with('error', 'La nota de entrega no corresponde a esta agencia.');
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

        return redirect()->route('deliveries.batch', $redirectParams)
            ->with('success', 'Datos de quien retira guardados. Ya puede escanear los paquetes.');
    }

    public function clearBatchRetirerSession(Request $request)
    {
        $validated = $request->validate([
            'delivery_note_id' => 'required|exists:delivery_notes,id',
            'agency_id' => 'required|exists:agencies,id',
            'service_type' => 'nullable|in:AIR,SEA',
        ]);

        $deliveryNote = DeliveryNote::find((int) $validated['delivery_note_id']);
        if ($deliveryNote && (int) $deliveryNote->agency_id !== (int) $validated['agency_id']) {
            return back()->with('error', 'La nota de entrega no corresponde a esta agencia.');
        }
        $agency = Agency::find((int) $validated['agency_id']);
        $this->ensureUserCanAccessAgency($agency);

        session()->forget(self::SESSION_BATCH_RETIRER);

        $redirectParams = array_filter([
            'agency_id' => (int) $validated['agency_id'],
            'service_type' => $request->filled('service_type') ? $request->service_type : null,
            'delivery_note_id' => (int) $validated['delivery_note_id'],
        ]);

        return redirect()->route('deliveries.batch', $redirectParams)
            ->with('success', 'Indique de nuevo los datos de quien retira para continuar escaneando.');
    }

    /**
     * Reporte de salida imprimible: por nota de entrega (delivery_note_id) o por agencia + fecha.
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
            $deliveryNote = DeliveryNote::with('agency.parent')->findOrFail((int) $request->delivery_note_id);
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

            if ($request->filled('agency_id')) {
                $query->whereHas('preregistration', fn ($q) => $q->where('agency_id', (int) $request->agency_id));
            } else {
                $mid = (int) $request->main_agency_id;
                $query->whereHas('preregistration', function ($q) use ($mid) {
                    $q->whereHas('agency', fn ($q2) => $q2->where('id', $mid)->orWhere('parent_agency_id', $mid));
                });
            }

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
        $scanRetirerSession = session(self::SESSION_SCAN_RETIRER);
        $scanRetirerSessionActive = is_array($scanRetirerSession)
            && filled($scanRetirerSession['delivered_to'] ?? null)
            && filled($scanRetirerSession['retirer_id_number'] ?? null)
            && filled($scanRetirerSession['retirer_phone'] ?? null);

        return view('deliveries.scan', compact('scanRetirerSession', 'scanRetirerSessionActive'));
    }

    public function storeScanRetirerSession(Request $request)
    {
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

        return redirect()->route('deliveries.scan')
            ->with('success', 'Datos de quien retira guardados. Ya puede escanear los códigos warehouse.');
    }

    public function clearScanRetirerSession()
    {
        session()->forget(self::SESSION_SCAN_RETIRER);

        return redirect()->route('deliveries.scan')
            ->with('success', 'Indique de nuevo los datos de quien retira.');
    }

    public function processScan(Request $request)
    {
        $this->mergeBatchRetirerFromSession($request);
        $this->mergeScanRetirerFromSession($request);

        $request->validate([
            'warehouse_code' => 'required|string|size:6|regex:/^\d{6}$/',
            'bulto_index' => 'nullable|integer|min:1|max:255',
            'delivered_to' => 'required|string|max:255',
            'retirer_id_number' => 'nullable|string|max:50',
            'retirer_phone' => 'nullable|string|max:50',
            'invoice_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ], [
            'delivered_to.required' => 'El nombre de quien retira es obligatorio.',
        ]);

        // Calcular agencias permitidas: combinación de la del batch + la del usuario (intersección)
        $userAllowed = $this->userAllowedAgencyIds();
        $batchAllowed = null;
        if ($request->boolean('return_to_batch')) {
            if ($request->filled('agency_id')) {
                $batchAllowed = [(int) $request->agency_id];
            } elseif ($request->filled('main_agency_id')) {
                $mainId = (int) $request->main_agency_id;
                $batchAllowed = Agency::where('id', $mainId)->orWhere('parent_agency_id', $mainId)->pluck('id')->all();
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
            $result = DB::transaction(function () use ($request, $allowedAgencyIds) {
                $candidates = Preregistration::where('warehouse_code', $request->warehouse_code)
                    ->where('status', 'READY')
                    ->whereDoesntHave('delivery')
                    ->orderByRaw('COALESCE(bulto_index, 999) ASC')
                    ->lockForUpdate()
                    ->get();

                if ($candidates->isEmpty()) {
                    $any = Preregistration::where('warehouse_code', $request->warehouse_code)->first();
                    if (! $any) {
                        return ['error' => 'Código de almacén no encontrado.'];
                    }
                    if ($any->status !== 'READY') {
                        return ['error' => 'El paquete no está listo para entrega (debe estar READY).'];
                    }
                    if ($any->delivery) {
                        return ['error' => 'El paquete ya fue entregado.'];
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
                        return ['error' => 'Bulto '.$bultoIndex.'/'.($candidates->first()->bultos_total ?? '?').' no encontrado o ya entregado.'];
                    }
                } else {
                    $preregistration = $candidates->first();
                }

                // Restricción de agencia (incondicional): el usuario solo puede entregar paquetes
                // de su agencia (si es de subagencia) y/o de la agencia del batch.
                if ($allowedAgencyIds !== null && ! in_array((int) $preregistration->agency_id, $allowedAgencyIds, true)) {
                    return ['error' => 'Este paquete no corresponde a esta entrega. Solo se aceptan paquetes de la agencia indicada.'];
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

                // Si la nota fue indicada, validamos que la agencia de la nota coincida con la del paquete
                // (o que sea su agencia principal). Si no coincide, no se enlaza pero la entrega se hace.
                if ($request->filled('delivery_note_id')) {
                    $note = DeliveryNote::find((int) $request->delivery_note_id);
                    if ($note) {
                        $noteAgencyId = (int) $note->agency_id;
                        $pkgAgencyId = (int) $preregistration->agency_id;
                        $pkgParentId = (int) ($preregistration->agency?->parent_agency_id ?? 0);
                        if ($noteAgencyId === $pkgAgencyId || $noteAgencyId === $pkgParentId) {
                            $deliveryData['delivery_note_id'] = $note->id;
                        }
                    }
                }

                $delivery = Delivery::create($deliveryData);
                $preregistration->update(['status' => 'DELIVERED']);

                return ['delivery' => $delivery, 'preregistration' => $preregistration];
            });
        } catch (QueryException $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'unique') || str_contains($msg, 'duplicate')) {
                return back()->with('error', 'Este paquete ya fue entregado.')->withInput();
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

        $this->persistBatchRetirerSession($request);
        $this->persistScanRetirerSession($request);

        if ($request->boolean('return_to_batch')) {
            $params = array_filter([
                'main_agency_id' => $request->main_agency_id,
                'agency_id' => $request->agency_id,
                'delivery_note_id' => $request->delivery_note_id,
                'service_type' => $request->filled('service_type') && in_array($request->service_type, ['AIR', 'SEA'], true)
                    ? $request->service_type
                    : null,
            ]);

            return redirect()->route('deliveries.batch', $params)
                ->with('success', 'Entrega registrada: '.$preregistration->label_name);
        }

        return redirect()->route('deliveries.show', $delivery->id)
            ->with('success', 'Entrega registrada: '.$preregistration->label_name);
    }

    public function show(string $id)
    {
        $delivery = Delivery::with(['preregistration.agency', 'preregistration.agencyClient', 'deliveryNote'])->findOrFail($id);
        $this->ensureUserCanAccessAgency($delivery->preregistration?->agency);

        return view('deliveries.show', compact('delivery'));
    }
}
