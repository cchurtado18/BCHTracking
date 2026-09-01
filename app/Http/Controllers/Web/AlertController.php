<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PackageAlert;
use App\Services\Alerts\PackageAlertDispatcher;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $rule = $request->string('rule')->toString();
        $query = PackageAlert::query()
            ->open()
            ->with(['preregistration:id,warehouse_code,tracking_external,label_name,service_type,status,agency_id,created_at', 'preregistration.agency:id,code,name'])
            ->orderByDesc('id');

        if ($rule !== '' && isset(PackageAlert::RULES[$rule])) {
            $query->where('rule', $rule);
        }

        $alerts = $query->paginate(40)->withQueryString();
        $openCounts = PackageAlert::query()
            ->open()
            ->selectRaw('rule, COUNT(*) as total')
            ->groupBy('rule')
            ->pluck('total', 'rule');

        return view('alerts.index', [
            'alerts' => $alerts,
            'rule' => $rule,
            'openCounts' => $openCounts,
            'openTotal' => (int) $openCounts->sum(),
        ]);
    }

    public function dispatch(PackageAlertDispatcher $dispatcher)
    {
        try {
            $created = $dispatcher->dispatch(true);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('alerts.index')
                ->with('error', 'No se pudo completar la revisión. Intente de nuevo.');
        }

        if ($created->isNotEmpty()) {
            $emailed = PackageAlert::query()
                ->whereIn('id', $created->pluck('id'))
                ->whereNotNull('emailed_at')
                ->exists();
            if (! $emailed) {
                return redirect()
                    ->route('alerts.index')
                    ->with('error', 'Se detectaron casos nuevos, pero no se pudo enviar el correo. Revise la configuración de MAIL.');
            }
        }

        return redirect()->route('alerts.index')->with('success', 'Revisión hecha. Si había casos nuevos, se avisó a los administradores por correo.');
    }

    public function dismiss(Request $request, PackageAlert $alert)
    {
        if ($alert->resolved_at === null) {
            $alert->update([
                'resolved_at' => now(),
                'dismissed_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('alerts.index', $request->only('rule'))
            ->with('success', 'Alerta marcada como revisada. No se volverá a notificar este caso.');
    }
}
