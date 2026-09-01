<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingRateCard;
use App\Models\Agency;
use App\Support\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingRateCardController extends Controller
{
    public function index(Request $request)
    {
        $query = Agency::query()
            ->with('parent:id,name,code')
            ->where('is_active', true)
            ->where('is_main', false)
            ->where('account_type', '!=', Agency::TYPE_ROOT)
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            });
        }

        $agencyIds = (clone $query)->pluck('id');
        $currentByAgency = AccountingRateCard::query()
            ->whereNull('effective_to')
            ->whereIn('agency_id', $agencyIds)
            ->get()
            ->groupBy('agency_id');

        $complete = 0;
        $pending = 0;
        foreach ($agencyIds as $agencyId) {
            $set = $currentByAgency->get($agencyId, collect())->pluck('service_type')->unique()->count();
            if ($set >= count(ServiceType::ALL)) {
                $complete++;
            } else {
                $pending++;
            }
        }

        $agencies = $query->paginate(25)->withQueryString();

        return view('accounting.rates.index', compact('agencies', 'currentByAgency', 'complete', 'pending'));
    }

    public function show(Agency $agency)
    {
        if ($agency->isRootAccount()) {
            return redirect()
                ->route('accounting.rates.index')
                ->with('error', 'SkyLink One no tiene tarifa propia. Defina el precio en cada cliente.');
        }

        $agency->load('parent:id,name,code');

        $current = [];
        foreach (ServiceType::ALL as $service) {
            $current[$service] = AccountingRateCard::currentFor($agency->id, $service);
        }

        return view('accounting.rates.show', compact('agency', 'current'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'effective_from' => 'required|date',
            'price_air' => 'nullable|numeric|min:0|max:99999',
            'price_sea' => 'nullable|numeric|min:0|max:99999',
            'price_cft' => 'nullable|numeric|min:0|max:99999',
        ], [
            'agency_id.required' => 'Seleccione el cliente.',
            'effective_from.required' => 'Indique desde cuándo rige.',
        ]);

        $agency = Agency::findOrFail((int) $data['agency_id']);
        if ($agency->isRootAccount()) {
            return redirect()
                ->route('accounting.rates.index')
                ->with('error', 'SkyLink One no tiene tarifa propia. Defina el precio en cada cliente.');
        }

        $prices = [
            ServiceType::AIR => $request->filled('price_air') ? (float) $data['price_air'] : null,
            ServiceType::SEA => $request->filled('price_sea') ? (float) $data['price_sea'] : null,
            ServiceType::CFT => $request->filled('price_cft') ? (float) $data['price_cft'] : null,
        ];

        if (collect($prices)->filter(fn ($v) => $v !== null)->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['price_air' => 'Indique al menos el precio de un servicio.']);
        }

        $from = $data['effective_from'];
        $saved = 0;

        DB::transaction(function () use ($prices, $agency, $from, $request, &$saved) {
            foreach ($prices as $service => $price) {
                if ($price === null) {
                    continue;
                }
                if ($this->upsertClientPrice($agency->id, $service, $price, $from, $request->user()->id)) {
                    $saved++;
                }
            }
        });

        $message = $saved > 0
            ? 'Tarifa del cliente actualizada. Los cambios anteriores quedaron en el histórico.'
            : 'No hubo cambios: el precio es el mismo que el vigente.';

        return redirect()
            ->route('accounting.rates.show', $agency)
            ->with('success', $message);
    }

    public function history(Request $request)
    {
        $query = AccountingRateCard::query()
            ->with(['agency:id,code,name', 'createdBy:id,name'])
            ->orderByDesc('effective_from')
            ->orderByDesc('id');

        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', strtoupper((string) $request->service_type));
        }

        $rates = $query->paginate(25)->withQueryString();
        $agencies = Agency::query()
            ->where('is_main', false)
            ->where('account_type', '!=', Agency::TYPE_ROOT)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('accounting.rates.history', compact('rates', 'agencies'));
    }

    /**
     * Cierra la vigente y abre una nueva si el precio cambió. Costo interno queda en 0
     * (el costo de operación se carga en rentabilidad / parámetros).
     */
    private function upsertClientPrice(int $agencyId, string $service, float $price, string $from, int $userId): bool
    {
        $current = AccountingRateCard::query()
            ->where('agency_id', $agencyId)
            ->where('service_type', $service)
            ->whereNull('effective_to')
            ->orderByDesc('effective_from')
            ->first();

        if ($current && (float) $current->price_per_lb === $price) {
            return false;
        }

        if ($current) {
            $closeAt = \Carbon\Carbon::parse($from)->subDay()->toDateString();
            $current->update([
                'effective_to' => max($closeAt, $current->effective_from->toDateString()),
            ]);
        }

        AccountingRateCard::create([
            'agency_id' => $agencyId,
            'service_type' => $service,
            'price_per_lb' => $price,
            'cost_per_lb' => 0,
            'currency' => 'USD',
            'effective_from' => $from,
            'effective_to' => null,
            'created_by' => $userId,
        ]);

        return true;
    }
}
