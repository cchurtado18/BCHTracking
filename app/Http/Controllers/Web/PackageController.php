<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Preregistration;
use App\Services\PackageProcessingService;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(protected PackageProcessingService $packageService)
    {
    }

    public function index(Request $request)
    {
        if ($request->has('clear_filters')) {
            session()->forget('packages_index_filters');
            return redirect()->route('packages.index');
        }

        $filterKeys = ['search', 'service_type', 'intake_type', 'status', 'agency_id'];
        $hasParams = $request->hasAny($filterKeys);
        if (! $hasParams && session()->has('packages_index_filters')) {
            return redirect()->route('packages.index', session('packages_index_filters'));
        }
        if ($hasParams) {
            session(['packages_index_filters' => $request->only($filterKeys)]);
        }

        $query = Preregistration::query();

        if (auth()->user() && auth()->user()->isAgencyUser()) {
            $query->where('agency_id', auth()->user()->agency_id);
        }

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

        $packages = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $agenciesForFilter = Agency::where('is_active', true)->orderBy('name')->get();

        // Estadísticas con los mismos filtros (para la vista principal)
        $statsQuery = Preregistration::query();
        if (auth()->user() && auth()->user()->isAgencyUser()) {
            $statsQuery->where('agency_id', auth()->user()->agency_id);
        }
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
        $statsTotal = $statsQuery->count();
        $statsAir = (clone $statsQuery)->where('service_type', 'AIR')->count();
        $statsSea = (clone $statsQuery)->where('service_type', 'SEA')->count();
        $statsReady = (clone $statsQuery)->where('status', 'READY')->count();
        $statsDelivered = (clone $statsQuery)->where('status', 'DELIVERED')->count();

        return view('packages.index', compact('packages', 'agenciesForFilter', 'statsTotal', 'statsAir', 'statsSea', 'statsReady', 'statsDelivered'));
    }

    public function show(string $id)
    {
        $package = Preregistration::with(['photos', 'agency', 'consolidationItem.consolidation'])->findOrFail($id);
        if (auth()->user() && auth()->user()->isAgencyUser() && (int) $package->agency_id !== (int) auth()->user()->agency_id) {
            abort(403, 'No tiene permiso para ver este paquete.');
        }
        $package->photos->each(fn ($p) => $p->url = asset('storage/' . $p->path));
        return view('packages.show', compact('package'));
    }

    public function showProcess(string $id)
    {
        $package = Preregistration::findOrFail($id);
        if (auth()->user() && auth()->user()->isAgencyUser() && (int) $package->agency_id !== (int) auth()->user()->agency_id) {
            abort(403, 'No tiene permiso para procesar este paquete.');
        }
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
        $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'verified_weight_lbs' => 'required|numeric|min:0.01',
        ]);
        try {
            $this->packageService->processPackage(
                $package,
                (int) $request->agency_id,
                (float) $request->verified_weight_lbs
            );
            return redirect()->route('packages.show', $package->id)
                ->with('success', 'Paquete procesado. Estado: READY.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function reprintLabel(string $id)
    {
        $package = Preregistration::findOrFail($id);
        if (auth()->user() && auth()->user()->isAgencyUser() && (int) $package->agency_id !== (int) auth()->user()->agency_id) {
            abort(403, 'No tiene permiso para reimprimir la etiqueta de este paquete.');
        }
        try {
            $this->packageService->reprintLabel($package);
            return back()->with('success', 'Etiqueta reimprimida.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
