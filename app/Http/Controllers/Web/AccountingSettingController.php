<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingExchangeRate;
use App\Models\AccountingOperatingCost;
use App\Models\AccountingSetting;
use App\Support\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingSettingController extends Controller
{
    public function edit()
    {
        $settings = AccountingSetting::current();
        $currentCosts = AccountingOperatingCost::currentMap();

        $costChanges = AccountingOperatingCost::query()
            ->with('createdBy:id,name')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->limit(40)
            ->get()
            ->map(fn (AccountingOperatingCost $row) => (object) [
                'at' => $row->effective_from,
                'sort' => $row->effective_from->format('Ymd').str_pad((string) $row->id, 10, '0', STR_PAD_LEFT),
                'label' => ServiceType::label($row->service_type),
                'value' => '$'.number_format((float) $row->cost_per_unit, 4).' / '.ServiceType::unit($row->service_type),
                'by' => $row->createdBy?->name,
            ]);

        $rateChanges = AccountingExchangeRate::query()
            ->with('createdBy:id,name')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (AccountingExchangeRate $row) => (object) [
                'at' => $row->effective_from,
                'sort' => $row->effective_from->format('Ymd').str_pad((string) $row->id, 10, '0', STR_PAD_LEFT),
                'label' => 'Tipo de cambio',
                'value' => number_format((float) $row->rate, 4).' C$ / US$',
                'by' => $row->createdBy?->name,
            ]);

        $changes = $costChanges
            ->concat($rateChanges)
            ->sortByDesc('sort')
            ->values()
            ->take(40);

        return view('accounting.settings.edit', compact('settings', 'currentCosts', 'changes'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'exchange_rate' => 'required|numeric|min:0.0001|max:9999',
            'cost_air' => 'nullable|numeric|min:0|max:99999',
            'cost_sea' => 'nullable|numeric|min:0|max:99999',
            'cost_cft' => 'nullable|numeric|min:0|max:99999',
        ], [
            'exchange_rate.required' => 'Indique el tipo de cambio vigente.',
        ]);

        $settings = AccountingSetting::current();
        $rateChanged = round((float) $settings->exchange_rate, 4) !== round((float) $data['exchange_rate'], 4);

        DB::transaction(function () use ($settings, $data, $rateChanged, $request) {
            $settings->update([
                'exchange_rate' => $data['exchange_rate'],
                'updated_by' => $request->user()->id,
            ]);

            if ($rateChanged) {
                AccountingExchangeRate::create([
                    'rate' => (float) $data['exchange_rate'],
                    'effective_from' => now()->toDateString(),
                    'created_by' => $request->user()->id,
                ]);
            }

            $costs = [
                ServiceType::AIR => $data['cost_air'] ?? null,
                ServiceType::SEA => $data['cost_sea'] ?? null,
                ServiceType::CFT => $data['cost_cft'] ?? null,
            ];
            $today = now()->toDateString();

            foreach ($costs as $service => $cost) {
                if ($cost === null || $cost === '') {
                    continue;
                }
                $cost = round((float) $cost, 4);
                $current = AccountingOperatingCost::currentFor($service);
                if ($current && round((float) $current->cost_per_unit, 4) === $cost) {
                    continue;
                }

                AccountingOperatingCost::create([
                    'service_type' => $service,
                    'cost_per_unit' => $cost,
                    'effective_from' => $today,
                    'quantity_unit' => ServiceType::isCft($service) ? 'pie3' : 'lb',
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return redirect()
            ->route('accounting.settings.edit')
            ->with('success', 'Parámetros guardados.');
    }
}
