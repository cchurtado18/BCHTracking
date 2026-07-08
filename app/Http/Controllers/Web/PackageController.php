<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAgencyAccess;
use App\Models\Agency;
use App\Models\Preregistration;
use App\Services\PackageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    use AuthorizesAgencyAccess;

    public function __construct(protected PackageProcessingService $packageService) {}

    private function scopePackagesForCurrentUser($query)
    {
        $allowed = $this->userAllowedAgencyIds();
        if ($allowed !== null) {
            $query->whereIn('agency_id', $allowed);
        }

        return $query;
    }

    public function index(Request $request)
    {
        if ($request->has('clear_filters')) {
            session()->forget('packages_index_filters');

            return redirect()->route('packages.index');
        }

        // Guardar filtros + página para que "Volver" restaure exactamente el listado.
        $filterKeys = ['search', 'service_type', 'intake_type', 'status', 'agency_id', 'date_from', 'date_to'];
        $hasFilterParams = $request->hasAny($filterKeys);
        $hasPage = $request->has('page');

        if (! $hasFilterParams && ! $hasPage && session()->has('packages_index_filters')) {
            return redirect()->route('packages.index', session('packages_index_filters'));
        }

        if ($hasFilterParams || $hasPage) {
            // Si solo cambia la página, conservar filtros ya guardados.
            $stored = $hasFilterParams
                ? $request->only($filterKeys)
                : array_intersect_key(session('packages_index_filters', []), array_flip($filterKeys));

            // Solo conservar página > 1. Un nuevo filtro (sin page) vuelve a la página 1.
            if ($hasPage && (int) $request->input('page') > 1) {
                $stored['page'] = (int) $request->input('page');
            }

            session(['packages_index_filters' => array_filter(
                $stored,
                fn ($value) => $value !== null && $value !== ''
            )]);
        }

        $query = Preregistration::with('agency');
        $this->scopePackagesForCurrentUser($query);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }
        if ($request->filled('intake_type')) {
            $query->where('intake_type', $request->intake_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_external', 'like', "%{$search}%")
                    ->orWhere('warehouse_code', 'like', "%{$search}%")
                    ->orWhere('label_name', 'like', "%{$search}%");
            });
        }
        if (! auth()->user() || ! auth()->user()->isAgencyUser()) {
            if ($request->filled('agency_id') && (int) $request->agency_id > 0) {
                $query->where('agency_id', (int) $request->agency_id);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $packages = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $agenciesForFilter = Agency::where('is_active', true)->orderBy('name')->get();

        // Estadísticas con los mismos filtros (para la vista principal)
        $statsQuery = Preregistration::query();
        $this->scopePackagesForCurrentUser($statsQuery);
        if ($request->filled('status')) {
            $statsQuery->where('status', $request->status);
        }
        if ($request->filled('service_type')) {
            $statsQuery->where('service_type', $request->service_type);
        }
        if ($request->filled('intake_type')) {
            $statsQuery->where('intake_type', $request->intake_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $statsQuery->where(function ($q) use ($search) {
                $q->where('tracking_external', 'like', "%{$search}%")
                    ->orWhere('warehouse_code', 'like', "%{$search}%")
                    ->orWhere('label_name', 'like', "%{$search}%");
            });
        }
        if (! auth()->user() || ! auth()->user()->isAgencyUser()) {
            if ($request->filled('agency_id') && (int) $request->agency_id > 0) {
                $statsQuery->where('agency_id', (int) $request->agency_id);
            }
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
        $statsReady = (clone $statsQuery)->where('status', 'READY')->count();
        $statsDelivered = (clone $statsQuery)->where('status', 'DELIVERED')->count();

        return view('packages.index', compact('packages', 'agenciesForFilter', 'statsTotal', 'statsAir', 'statsSea', 'statsReady', 'statsDelivered'));
    }

    public function show(string $id)
    {
        $package = Preregistration::with(['photos', 'agency', 'consolidationItem.consolidation', 'delivery'])->findOrFail($id);
        $this->ensureUserCanAccessPreregistration($package);
        $package->photos->each(fn ($p) => $p->url = asset('storage/'.$p->path));

        return view('packages.show', compact('package'));
    }

    public function showProcess(string $id)
    {
        $package = Preregistration::with('agency')->findOrFail($id);
        $this->ensureUserCanAccessPreregistration($package);
        if ($package->status !== 'IN_WAREHOUSE_NIC') {
            return redirect()->route('packages.show', $package->id)
                ->with('error', 'Solo se pueden procesar paquetes en almacén Nicaragua.');
        }
        $agencies = Agency::where('is_active', true)->orderBy('name')->get();

        return view('packages.process', compact('package', 'agencies'));
    }

    public function process(Request $request, string $id)
    {
        $package = Preregistration::findOrFail($id);
        $this->ensureUserCanAccessPreregistration($package);
        $request->validate([
            'agency_id' => [
                Rule::requiredIf(fn () => $package->agency_id === null),
                'nullable',
                'exists:agencies,id',
            ],
            'verified_weight_lbs' => 'required|numeric|min:0.01',
        ]);
        $agencyId = $request->filled('agency_id')
            ? (int) $request->agency_id
            : (int) $package->agency_id;
        if ($agencyId <= 0) {
            return back()
                ->withErrors(['agency_id' => 'Seleccione una agencia: este paquete aún no tiene agencia asignada.'])
                ->withInput();
        }
        try {
            $this->packageService->processPackage(
                $package,
                $agencyId,
                (float) $request->verified_weight_lbs
            );

            return redirect()
                ->route('packages.show', $package->id)
                ->with('success', 'Paquete procesado (READY). Si no aparece el diálogo de impresión, use «Imprimir etiqueta».')
                ->with('open_label_autoprint', true);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function reprintLabel(string $id)
    {
        $package = Preregistration::findOrFail($id);
        $this->ensureUserCanAccessPreregistration($package);
        try {
            $this->packageService->reprintLabel($package);

            return back()->with('success', 'Etiqueta reimprimida.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
