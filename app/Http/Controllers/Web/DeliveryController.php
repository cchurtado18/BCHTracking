<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('clear_agency')) {
            session()->forget(['deliveries_index_agency_id', 'deliveries_index_service_type']);
            return redirect()->route('deliveries.index');
        }

        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : null;
        $serviceType = $request->filled('service_type') && in_array($request->service_type, ['AIR', 'SEA'], true) ? $request->service_type : null;
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

        // Lista única de agencias para el selector: principales + subagencias (con indicador)
        $mainAgencies = Agency::mainAgencies()->where('is_active', true)->orderBy('name')->get();
        $subAgencies = Agency::where('is_main', false)->where('is_active', true)->with('parent')->orderBy('name')->get();
        $agenciesForSelect = collect()
            ->merge($mainAgencies->map(fn ($a) => (object) ['id' => $a->id, 'name' => $a->name . ' (Agencia principal)', 'is_main' => true]))
            ->merge($subAgencies->map(fn ($a) => (object) ['id' => $a->id, 'name' => $a->name . ($a->parent ? ' — ' . $a->parent->name : ''), 'is_main' => false]))
            ->sortBy('name')
            ->values();

        $selectedAgency = $agencyId > 0 ? Agency::find($agencyId) : null;

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

        // Entregas realizadas (notas): filtro opcional por agencia seleccionada
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
            ->with(['agency', 'deliveries' => function ($q) use ($deliveryFilter) {
                $deliveryFilter($q);
                $q->orderBy('delivered_at')->limit(1)->with('preregistration.agency');
            }])
            ->when($selectedAgency, fn ($q) => $q->whereHas('deliveries', $deliveryFilter))
            ->orderByDesc(\DB::raw('(SELECT MAX(delivered_at) FROM deliveries WHERE deliveries.delivery_note_id = delivery_notes.id)'));

        $deliveryNotes = $notesQuery->paginate(15)->withQueryString();

        $deliveriesSinNota = Delivery::with('preregistration.agency.parent')
            ->whereNull('delivery_note_id')
            ->when($selectedAgency, function ($q) use ($deliveryFilter) {
                $deliveryFilter($q);
            })
            ->orderBy('delivered_at', 'desc')
            ->paginate(15, ['*'], 'sin_nota_page')
            ->withQueryString();

        return view('deliveries.index', compact(
            'deliveryNotes',
            'deliveriesSinNota',
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
     * Acepta agency_id (puede ser agencia principal o subagencia) o main_agency_id/agency_id por compatibilidad.
     */
    public function batch(Request $request)
    {
        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : null;
        $mainAgencyId = $request->filled('main_agency_id') ? (int) $request->main_agency_id : null;

        if ($agencyId > 0) {
            $agency = Agency::find($agencyId);
            if (!$agency) {
                return redirect()->route('deliveries.index')->with('error', 'Agencia no encontrada.');
            }
            $mainAgencyId = $agency->is_main ? $agency->id : null;
            $subAgencyId = $agency->is_main ? null : $agency->id;
        } elseif ($mainAgencyId > 0) {
            $agency = Agency::find($mainAgencyId);
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
        $agencyName = $agency ? $agency->name : 'Agencia';
        $filterParams = array_filter(['agency_id' => $agency->id, 'service_type' => $serviceType]);

        if ($request->filled('delivery_note_id')) {
            $deliveryNote = DeliveryNote::find((int) $request->delivery_note_id);
            if (!$deliveryNote) {
                return redirect()->route('deliveries.batch', $filterParams)->with('error', 'Nota de entrega no encontrada.');
            }
        } else {
            $deliveryNote = DeliveryNote::create([
                'code' => DeliveryNote::generateCode(),
                'agency_id' => $agency?->id,
            ]);
            return redirect()->route('deliveries.batch', array_merge($filterParams, ['delivery_note_id' => $deliveryNote->id]));
        }

        return view('deliveries.batch', compact(
            'availablePackages',
            'agencyName',
            'agency',
            'filterParams',
            'deliveryNote'
        ));
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
            $deliveries = Delivery::with('preregistration.agency', 'preregistration.agencyClient', 'deliveryNote')
                ->where('delivery_note_id', $deliveryNote->id)
                ->orderBy('delivered_at')
                ->get();
            $agency = $deliveryNote->agency;
            $agencyName = $agency ? $agency->name : 'Agencia';
            $date = $deliveries->first()?->delivered_at?->toDateString() ?? $date;
            $deliveryNotesInReport = collect([$deliveryNote]);
        } else {
            $query = Delivery::with('preregistration.agency', 'preregistration.agencyClient', 'deliveryNote')
                ->whereDate('delivered_at', $date);

            if ($request->filled('agency_id')) {
                $query->whereHas('preregistration', fn ($q) => $q->where('agency_id', (int) $request->agency_id));
                $agency = Agency::with('parent')->find((int) $request->agency_id);
            } else {
                $mid = (int) $request->main_agency_id;
                $query->whereHas('preregistration', function ($q) use ($mid) {
                    $q->whereHas('agency', fn ($q2) => $q2->where('id', $mid)->orWhere('parent_agency_id', $mid));
                });
                $agency = Agency::with('parent')->find($mid);
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

        // Encomienda familiar (CH LOGISTICS): usar diseño "Nota de cobro" igual al de la factura CH Logistics
        if ($agency && $agency->isChLogistics()) {
            $firstPrereg = $first?->preregistration;
            $clientName = $firstPrereg?->agencyClient?->full_name ?? $firstPrereg?->label_name ?? $retiradoPor ?? '—';
            $clientPhone = $firstPrereg?->agencyClient?->phone ?? $retiradoTelefono ?? '—';
            $clientAddress = '—';
            $deliveryAddress = $agency->address ?? null;
            $deliveryPhone = $agency->phone ?? null;
            return view('deliveries.print-report-nota-cobro', compact(
                'deliveries', 'agencyName', 'agency', 'date', 'deliveryNote',
                'clientName', 'clientPhone', 'clientAddress', 'deliveryAddress', 'deliveryPhone'
            ));
        }

        return view('deliveries.print-report', compact('deliveries', 'agencyName', 'agency', 'date', 'retiradoPor', 'retiradoCedula', 'retiradoTelefono', 'deliveryNote', 'deliveryNotesInReport'));
    }

    public function scan()
    {
        return view('deliveries.scan');
    }

    public function processScan(Request $request)
    {
        $request->validate([
            'warehouse_code' => 'required|string|size:6|regex:/^\d{6}$/',
            'bulto_index' => 'nullable|integer|min:1|max:255',
            'delivered_to' => 'required|string|max:255',
            'retirer_id_number' => 'required|string|max:50',
            'retirer_phone' => 'required|string|max:50',
            'delivery_type' => 'required|in:PICKUP,DELIVERY',
            'notes' => 'nullable|string|max:500',
        ], [
            'delivered_to.required' => 'El nombre de quien retira es obligatorio.',
            'retirer_id_number.required' => 'El número de cédula de quien retira es obligatorio.',
            'retirer_phone.required' => 'El número telefónico de quien retira es obligatorio.',
        ]);

        // Varios bultos pueden compartir el mismo warehouse_code (ej. 1/11, 2/11…). Identificar por bulto_index.
        $candidates = Preregistration::where('warehouse_code', $request->warehouse_code)
            ->where('status', 'READY')
            ->whereDoesntHave('delivery')
            ->orderByRaw('COALESCE(bulto_index, 999) ASC')
            ->get();

        if ($candidates->isEmpty()) {
            $any = Preregistration::where('warehouse_code', $request->warehouse_code)->first();
            if (!$any) {
                return back()->with('error', 'Código de almacén no encontrado.')->withInput();
            }
            if ($any->status !== 'READY') {
                return back()->with('error', 'El paquete no está listo para entrega (debe estar READY).')->withInput();
            }
            if ($any->delivery) {
                return back()->with('error', 'El paquete ya fue entregado.')->withInput();
            }
            return back()->with('error', 'No hay paquetes pendientes con ese código.')->withInput();
        }

        if ($candidates->count() > 1) {
            $bultoIndex = $request->filled('bulto_index') ? (int) $request->bulto_index : null;
            if ($bultoIndex === null) {
                return back()->with('error', 'Varios bultos con este código. Indique cuál entregó (ej. 1/11, 2/11…).')->withInput();
            }
            $preregistration = $candidates->firstWhere('bulto_index', $bultoIndex);
            if (!$preregistration) {
                return back()->with('error', 'Bulto ' . $bultoIndex . '/' . ($candidates->first()->bultos_total ?? '?') . ' no encontrado o ya entregado.')->withInput();
            }
        } else {
            $preregistration = $candidates->first();
        }

        if ($request->boolean('return_to_batch')) {
            $allowedAgencyIds = null;
            if ($request->filled('agency_id')) {
                $allowedAgencyIds = [(int) $request->agency_id];
            } elseif ($request->filled('main_agency_id')) {
                $mainId = (int) $request->main_agency_id;
                $allowedAgencyIds = Agency::where('id', $mainId)->orWhere('parent_agency_id', $mainId)->pluck('id')->all();
            }
            if ($allowedAgencyIds !== null && ! in_array((int) $preregistration->agency_id, $allowedAgencyIds, true)) {
                return back()->with('error', 'Este paquete no corresponde a esta entrega. Solo se aceptan paquetes de la lista.')->withInput();
            }
        }

        $deliveryData = [
            'preregistration_id' => $preregistration->id,
            'delivered_at' => now(),
            'delivered_to' => $request->delivered_to,
            'retirer_id_number' => $request->retirer_id_number,
            'retirer_phone' => $request->retirer_phone,
            'delivery_type' => $request->delivery_type,
            'notes' => $request->notes,
        ];
        if ($request->filled('delivery_note_id')) {
            $note = DeliveryNote::find((int) $request->delivery_note_id);
            if ($note) {
                $deliveryData['delivery_note_id'] = $note->id;
            }
        }
        $delivery = Delivery::create($deliveryData);

        $preregistration->update(['status' => 'DELIVERED']);

        if ($request->boolean('return_to_batch')) {
            $params = array_filter([
                'main_agency_id' => $request->main_agency_id,
                'agency_id' => $request->agency_id,
                'delivery_note_id' => $request->delivery_note_id,
            ]);
            return redirect()->route('deliveries.batch', $params)
                ->with('success', 'Entrega registrada: ' . $preregistration->label_name);
        }

        return redirect()->route('deliveries.show', $delivery->id)
            ->with('success', 'Entrega registrada: ' . $preregistration->label_name);
    }

    public function show(string $id)
    {
        $delivery = Delivery::with('preregistration.agency')->findOrFail($id);
        return view('deliveries.show', compact('delivery'));
    }
}
