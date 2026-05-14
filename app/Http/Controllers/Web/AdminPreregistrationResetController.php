<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Preregistration;
use App\Services\PreregistrationAdminResetService;
use Illuminate\Http\Request;

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
}
