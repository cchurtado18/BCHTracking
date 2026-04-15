<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Preregistration;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    /** Módulo público: consulta por código de almacén o tracking (sin autenticación). */
    public function index(Request $request): View
    {
        $code = trim((string) $request->query('code', ''));
        $preregistrations = collect();
        $notFound = false;

        if ($code !== '') {
            $isSixDigits = preg_match('/^\d{6}$/', $code);
            if ($isSixDigits) {
                $preregistrations = Preregistration::with(['agency', 'delivery'])
                    ->where('warehouse_code', $code)
                    ->orderByRaw('COALESCE(bulto_index, 999) ASC')
                    ->get();
            } else {
                $preregistrations = Preregistration::with(['agency', 'delivery'])
                    ->where('tracking_external', $code)
                    ->orderByRaw('COALESCE(bulto_index, 999) ASC')
                    ->get();
            }
            if ($preregistrations->isEmpty()) {
                $notFound = true;
            }
        }

        return view('tracking.index', [
            'code' => $code,
            'preregistrations' => $preregistrations,
            'notFound' => $notFound,
        ]);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'RECEIVED_MIAMI' => 'Recibido en Miami',
            'IN_TRANSIT' => 'En tránsito a Nicaragua',
            'IN_WAREHOUSE_NIC' => 'En almacén Nicaragua',
            'READY' => 'Listo para retiro',
            'DELIVERED' => 'Entregado',
            'CANCELLED' => 'Cancelado',
            default => $status,
        };
    }

    /** Orden de estados para la línea de tiempo (1 = primero). */
    private const TIMELINE_ORDER = [
        'RECEIVED_MIAMI' => 1,
        'IN_TRANSIT' => 2,
        'IN_WAREHOUSE_NIC' => 3,
        'READY' => 4,
        'DELIVERED' => 5,
        'CANCELLED' => 0,
    ];

    /**
     * Pasos de la línea de tiempo para mostrar en la vista de tracking.
     * Cada paso: key, label, is_completed, is_current, timestamp (Carbon|null).
     * Se fuerza America/New_York (Miami) para que las fechas siempre se muestren en hora de Miami.
     */
    public static function timelineSteps(Preregistration $p, ?string $timezone = null): array
    {
        $status = $p->status ?? 'RECEIVED_MIAMI';
        $currentOrder = self::TIMELINE_ORDER[$status] ?? 1;
        $tz = $timezone ?? config('app.timezone', 'America/New_York');

        $steps = [
            [
                'key' => 'RECEIVED_MIAMI',
                'label' => 'RECIBIDO EN MIAMI',
                'is_completed' => true,
                'is_current' => $currentOrder === 1,
                'timestamp' => $p->created_at?->timezone($tz),
            ],
            [
                'key' => 'IN_TRANSIT',
                'label' => 'EN TRÁNSITO',
                'is_completed' => $currentOrder >= 2,
                'is_current' => $currentOrder === 2,
                'timestamp' => null,
            ],
            [
                'key' => 'IN_WAREHOUSE_NIC',
                'label' => 'LLEGADA A OFICINA CENTRAL',
                'is_completed' => $currentOrder >= 3,
                'is_current' => $currentOrder === 3,
                'timestamp' => $p->received_nic_at?->timezone($tz),
            ],
            [
                'key' => 'READY',
                'label' => 'LISTO PARA LA ENTREGA',
                'is_completed' => $currentOrder >= 4,
                'is_current' => $currentOrder === 4,
                'timestamp' => $p->ready_at?->timezone($tz),
            ],
            [
                'key' => 'DELIVERED',
                'label' => 'ENTREGADO',
                'is_completed' => $currentOrder >= 5,
                'is_current' => $currentOrder === 5,
                'timestamp' => $p->relationLoaded('delivery') && $p->delivery
                    ? $p->delivery->delivered_at?->timezone($tz)
                    : null,
            ],
        ];

        return $steps;
    }

    public static function serviceLabel(string $serviceType): string
    {
        return match (strtoupper((string) $serviceType)) {
            'AIR' => 'Aéreo ✈️',
            'SEA' => 'Marítimo 🚢',
            default => $serviceType ?: '—',
        };
    }
}
