<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\AuthorizesAgencyAccess;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Prealert;
use App\Support\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrealertController extends Controller
{
    use AuthorizesAgencyAccess;

    public function index(Request $request): View
    {
        $query = Prealert::query()
            ->with(['agency:id,code,name,account_type,is_main,parent_agency_id'])
            ->orderByDesc('id');

        $allowed = $this->userAllowedAgencyIds();
        if ($allowed !== null) {
            $query->whereIn('agency_id', $allowed);
        }

        $statsBase = (clone $query);
        $statsTotal = (clone $statsBase)->count();
        $statsPending = (clone $statsBase)->where('status', Prealert::STATUS_PENDING)->count();
        $statsAir = (clone $statsBase)->where('service_type', ServiceType::AIR)->count();
        $statsSea = (clone $statsBase)->where('service_type', ServiceType::SEA)->count();
        $statsMatched = (clone $statsBase)->where('status', Prealert::STATUS_MATCHED)->count();

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $term = '%'.mb_strtoupper($search, 'UTF-8').'%';
            $query->where(function ($q) use ($term) {
                $q->where('tracking', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        $status = (string) $request->query('status', '');
        if (in_array($status, [Prealert::STATUS_PENDING, Prealert::STATUS_MATCHED, Prealert::STATUS_CANCELLED], true)) {
            $query->where('status', $status);
        }

        $serviceType = strtoupper(trim((string) $request->query('service_type', '')));
        if (in_array($serviceType, [ServiceType::AIR, ServiceType::SEA], true)) {
            $query->where('service_type', $serviceType);
        } else {
            $serviceType = '';
        }

        $prealerts = $query->paginate(30)->withQueryString();

        return view('prealerts.index', [
            'prealerts' => $prealerts,
            'search' => $search,
            'status' => $status,
            'serviceType' => $serviceType,
            'statsTotal' => $statsTotal,
            'statsPending' => $statsPending,
            'statsAir' => $statsAir,
            'statsSea' => $statsSea,
            'statsMatched' => $statsMatched,
            'isClientView' => (bool) $request->user()?->isAgencyUser(),
        ]);
    }

    public function create(): View
    {
        $agencies = $this->agenciesForForm();

        return view('prealerts.create', [
            'agencies' => $agencies,
            'isClientView' => (bool) auth()->user()?->isAgencyUser(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('tracking')) {
            $request->merge(['tracking' => Prealert::normalizeTracking($request->input('tracking'))]);
        }
        if ($request->filled('name')) {
            $request->merge(['name' => Prealert::toUpper($request->input('name'))]);
        }
        if ($request->filled('description')) {
            $request->merge(['description' => Prealert::toUpper($request->input('description'))]);
        }

        $agencyIds = $this->agenciesForForm()->pluck('id')->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'agency_id' => ['required', 'integer', Rule::in($agencyIds)],
            'tracking' => [
                'required',
                'string',
                'min:8',
                'max:48',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('prealerts', 'tracking'),
            ],
            'service_type' => ['required', ServiceType::routeRule()],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Escriba el nombre.',
            'agency_id.required' => 'Seleccione la agencia.',
            'agency_id.in' => 'No tiene permiso para esa agencia.',
            'tracking.required' => 'Escriba el tracking.',
            'tracking.min' => 'El tracking debe tener al menos 8 caracteres.',
            'tracking.regex' => 'El tracking solo puede llevar letras y números.',
            'tracking.unique' => 'Este tracking ya tiene una prealerta.',
            'service_type.required' => 'Seleccione si el servicio es aéreo o marítimo.',
            'service_type.in' => 'Seleccione un servicio válido.',
        ]);

        $this->ensureUserCanAccessAgencyId((int) $data['agency_id']);

        $prealert = Prealert::create([
            'name' => $data['name'],
            'agency_id' => (int) $data['agency_id'],
            'tracking' => $data['tracking'],
            'service_type' => ServiceType::normalize($data['service_type']),
            'description' => $data['description'] ?? null,
            'status' => Prealert::STATUS_PENDING,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('prealerts.show', $prealert)
            ->with('success', 'Prealerta guardada. Cuando el paquete llegue al almacén, el tracking se usará para identificarla.');
    }

    public function lookup(Request $request): JsonResponse
    {
        if ($request->user()?->isAgencyUser()) {
            abort(403);
        }

        $prealert = Prealert::findOpenByTracking($request->query('tracking'));
        if (! $prealert) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'prealert' => $prealert->warehouseNotice(),
        ]);
    }

    public function show(Prealert $prealert): View
    {
        $this->ensureUserCanAccessAgencyId((int) $prealert->agency_id);
        $prealert->load(['agency', 'creator:id,name', 'preregistration:id,warehouse_code,tracking_external,status']);

        return view('prealerts.show', [
            'prealert' => $prealert,
            'isClientView' => (bool) auth()->user()?->isAgencyUser(),
        ]);
    }

    public function destroy(Prealert $prealert): RedirectResponse
    {
        $this->ensureUserCanAccessAgencyId((int) $prealert->agency_id);

        if ($prealert->status === Prealert::STATUS_MATCHED) {
            return redirect()
                ->route('prealerts.show', $prealert)
                ->with('error', 'No se puede eliminar una prealerta que ya ingresó al almacén.');
        }

        $prealert->delete();

        return redirect()
            ->route('prealerts.index')
            ->with('success', 'Prealerta eliminada.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Agency>
     */
    private function agenciesForForm()
    {
        $query = Agency::query()->where('is_active', true)->orderBy('name');
        $allowed = $this->userAllowedAgencyIds();
        if ($allowed !== null) {
            $query->whereIn('id', $allowed);
        }

        return $query->get(['id', 'code', 'name', 'account_type', 'is_main', 'parent_agency_id']);
    }
}
