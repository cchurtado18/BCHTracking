<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Preregistration;
use App\Services\PreregistrationAdminResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;

class AdminPreregistrationResetController extends Controller
{
    public function __construct(private PreregistrationAdminResetService $resetService) {}

    /**
     * Solo administradores (ruta con middleware admin).
     * Devuelve el paquete a Miami: elimina ítem del saco, estado RECEIVED_MIAMI, limpia NIC/READY y audita.
     */
    public function resetToMiami(Request $request, string $id)
    {
        $preregistration = Preregistration::with(['consolidationItem.consolidation', 'delivery'])->findOrFail($id);

        $request->validate([
            'admin_reset_reason' => 'required|string|min:15|max:2000',
            'admin_reset_confirm' => 'accepted',
            'return_to' => 'nullable|string|in:package',
        ], [
            'admin_reset_reason.required' => 'Describa el motivo del cambio (mínimo 15 caracteres).',
            'admin_reset_reason.min' => 'El motivo debe tener al menos 15 caracteres.',
            'admin_reset_confirm.accepted' => 'Debe marcar la casilla de confirmación para continuar.',
        ]);

        try {
            $this->resetService->resetToMiami($preregistration, (string) $request->input('admin_reset_reason'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->input('return_to') === 'package') {
            return redirect()->route('packages.show', $preregistration->id)
                ->with('success', 'Paquete devuelto a “Recibido en Miami”, desvinculado del saco y registro de auditoría creado. Puede volver a armar saco y enviar.');
        }

        return redirect()->route('preregistrations.show', $preregistration->id)
            ->with('success', 'Paquete devuelto a “Recibido en Miami”, desvinculado del saco y registro de auditoría creado. Puede volver a armar saco y enviar.');
    }

    /**
     * Solo administradores: cambia el tipo de ingreso entre COURIER y DROP_OFF.
     */
    public function updateIntakeType(Request $request, string $id)
    {
        $preregistration = Preregistration::findOrFail($id);

        $validated = $request->validate([
            'intake_type' => 'required|in:COURIER,DROP_OFF',
            'return_to' => 'nullable|string|in:package',
        ], [
            'intake_type.required' => 'Seleccione el tipo de ingreso.',
            'intake_type.in' => 'El tipo de ingreso debe ser Courier o Drop Off.',
        ]);

        $newType = $validated['intake_type'];
        $oldType = $preregistration->intake_type;

        if ($oldType === $newType) {
            return $this->redirectAfterIntakeTypeChange($request, $preregistration)
                ->with('success', 'El tipo de ingreso ya era '.($newType === 'COURIER' ? 'Courier' : 'Drop Off').'.');
        }

        $oldValues = [
            'intake_type' => $oldType,
            'receipt_note_id' => $preregistration->receipt_note_id,
        ];

        $updates = ['intake_type' => $newType];
        // Las notas de recepción aplican a Drop Off; al pasar a Courier se desvincula.
        if ($newType === 'COURIER' && $preregistration->receipt_note_id) {
            $updates['receipt_note_id'] = null;
        }

        $preregistration->update($updates);
        $preregistration->refresh();

        $fromLabel = $oldType === 'COURIER' ? 'Courier' : 'Drop Off';
        $toLabel = $newType === 'COURIER' ? 'Courier' : 'Drop Off';
        $code = $preregistration->warehouse_code ?? $preregistration->tracking_external ?? (string) $preregistration->id;

        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => 'preregistration',
            'auditable_id' => $preregistration->id,
            'action' => 'admin_change_intake_type',
            'summary' => mb_substr("Admin: tipo de ingreso {$fromLabel} → {$toLabel} | Código/ref: {$code}", 0, 500),
            'old_values' => $oldValues,
            'new_values' => [
                'intake_type' => $preregistration->intake_type,
                'receipt_note_id' => $preregistration->receipt_note_id,
            ],
            'ip_address' => RequestFacade::ip(),
        ]);

        return $this->redirectAfterIntakeTypeChange($request, $preregistration)
            ->with('success', "Tipo de ingreso actualizado: {$fromLabel} → {$toLabel}.");
    }

    private function redirectAfterIntakeTypeChange(Request $request, Preregistration $preregistration)
    {
        if ($request->input('return_to') === 'package') {
            return redirect()->route('packages.show', $preregistration->id);
        }

        return redirect()->route('preregistrations.show', $preregistration->id);
    }
}
