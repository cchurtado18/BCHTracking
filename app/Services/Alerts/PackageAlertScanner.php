<?php

namespace App\Services\Alerts;

use App\Models\PackageAlert;
use App\Models\Preregistration;
use App\Support\ServiceType;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

class PackageAlertScanner
{
    /**
     * Detecta alertas nuevas, cierra las que ya no aplican y no vuelve a abrir
     * un caso que el administrador ya marcó como revisado con la misma huella.
     *
     * @return Collection<int, PackageAlert>
     */
    public function scan(?CarbonInterface $now = null): Collection
    {
        $now = $now ? $now->copy() : now();
        $findings = $this->stuckWarehouse($now)->concat($this->splitLots());
        $openFingerprints = $findings->pluck('fingerprint')->all();

        PackageAlert::query()
            ->open()
            ->whereNotIn('fingerprint', $openFingerprints ?: ['__none__'])
            ->update(['resolved_at' => $now]);

        $created = collect();
        foreach ($findings as $finding) {
            $existing = PackageAlert::query()
                ->where('fingerprint', $finding['fingerprint'])
                ->first();

            if ($existing) {
                if ($existing->resolved_at === null || $existing->dismissed_by !== null) {
                    continue;
                }

                $existing->update([
                    'rule' => $finding['rule'],
                    'preregistration_id' => $finding['preregistration_id'],
                    'status_at_open' => $finding['status_at_open'],
                    'message' => $finding['message'],
                    'resolved_at' => null,
                    'emailed_at' => null,
                ]);
                $created->push($existing->fresh());
                continue;
            }

            try {
                $created->push(PackageAlert::create($finding));
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        return $created;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function stuckWarehouse(CarbonInterface $now): Collection
    {
        $airLimit = $now->copy()->subHours((int) config('alerts.air_hours', 24));
        $seaLimit = $now->copy()->subDays((int) config('alerts.sea_days', 3));
        $out = collect();

        $packages = Preregistration::query()
            ->with('agency:id,code,name')
            ->whereIn('status', config('alerts.warehouse_statuses', ['RECEIVED_MIAMI', 'IN_WAREHOUSE_NIC']))
            ->get();

        foreach ($packages as $package) {
            $since = $this->warehouseSince($package);
            if (! $since) {
                continue;
            }

            $isAir = ServiceType::normalize($package->service_type) === ServiceType::AIR;
            $limit = $isAir ? $airLimit : $seaLimit;
            if ($since->gt($limit)) {
                continue;
            }

            $rule = $isAir ? PackageAlert::RULE_STUCK_AIR : PackageAlert::RULE_STUCK_SEA;
            $wait = $isAir
                ? (int) $since->diffInHours($now).' h'
                : (int) $since->diffInDays($now).' días';

            $out->push($this->finding(
                $package,
                $rule,
                $rule.'|'.$package->id.'|'.$package->status,
                'Lleva '.$wait.' en '.PackageAlert::statusLabel($package->status).' sin cambiar de estado.'
            ));
        }

        return $out;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function splitLots(): Collection
    {
        $delivered = Preregistration::query()
            ->where('status', 'DELIVERED')
            ->whereNotNull('agency_id')
            ->get(['id', 'agency_id', 'service_type', 'created_at']);

        $cohorts = $delivered
            ->groupBy(fn (Preregistration $p) => $p->agency_id.'|'.$p->service_type.'|'.$p->created_at->toDateString())
            ->keys();

        $out = collect();
        foreach ($cohorts as $key) {
            [$agencyId, $service, $receivedOn] = explode('|', $key, 3);
            $leftBehind = Preregistration::query()
                ->with('agency:id,code,name')
                ->where('agency_id', $agencyId)
                ->where('service_type', $service)
                ->whereDate('created_at', $receivedOn)
                ->where('status', '!=', 'DELIVERED')
                ->get();

            if ($leftBehind->isEmpty()) {
                continue;
            }

            $deliveredCount = $delivered
                ->filter(fn (Preregistration $p) => $p->agency_id == $agencyId
                    && $p->service_type === $service
                    && $p->created_at->toDateString() === $receivedOn)
                ->count();

            foreach ($leftBehind as $package) {
                $out->push($this->finding(
                    $package,
                    PackageAlert::RULE_SPLIT_LOT,
                    PackageAlert::RULE_SPLIT_LOT.'|'.$package->id.'|'.$receivedOn,
                    'Se recibieron el '.$this->formatDate($receivedOn).' junto con '.$deliveredCount.' paquete'
                    .($deliveredCount === 1 ? '' : 's').' ya entregado'
                    .($deliveredCount === 1 ? '' : 's').'. Este sigue en '.PackageAlert::statusLabel($package->status).'.'
                ));
            }
        }

        return $out;
    }

    private function warehouseSince(Preregistration $package): ?CarbonInterface
    {
        if ($package->status === 'IN_WAREHOUSE_NIC') {
            return $package->received_nic_at ?? $package->updated_at;
        }

        return $package->created_at;
    }

    /**
     * @return array<string, mixed>
     */
    private function finding(Preregistration $package, string $rule, string $fingerprint, string $message): array
    {
        $code = $package->warehouse_code ?: ($package->tracking_external ?: '#'.$package->id);

        return [
            'rule' => $rule,
            'fingerprint' => $fingerprint,
            'preregistration_id' => $package->id,
            'status_at_open' => $package->status,
            'message' => $code.' · '.$message,
        ];
    }

    private function formatDate(string $iso): string
    {
        return \Carbon\Carbon::parse($iso)->format('d/m/Y');
    }
}
