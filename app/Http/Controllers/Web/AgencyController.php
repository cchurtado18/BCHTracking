<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AgencyController extends Controller
{
    /** Departamentos de Nicaragua para el dropdown */
    public const NICARAGUA_DEPARTMENTS = [
        'Boaco', 'Carazo', 'Chinandega', 'Chontales', 'Estelí', 'Granada',
        'Jinotega', 'León', 'Madriz', 'Managua', 'Masaya', 'Matagalpa',
        'Nueva Segovia', 'RACN', 'RACS', 'Río San Juan', 'Rivas',
    ];
    public function index(Request $request)
    {
        $query = Agency::with('parent')->withCount(['clients', 'preregistrations']);

        if ($request->has('is_active') && $request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            });
        }

        $agencies = $query->orderBy('name')->paginate(15)->withQueryString();

        // Estadísticas con los mismos filtros
        $statsQuery = Agency::query();
        if ($request->has('is_active') && $request->filled('is_active')) {
            $statsQuery->where('is_active', (bool) $request->is_active);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $statsQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            });
        }
        $statsTotal = $statsQuery->count();
        $statsActive = (clone $statsQuery)->where('is_active', true)->count();
        $statsInactive = (clone $statsQuery)->where('is_active', false)->count();
        $statsSubagencies = (clone $statsQuery)->where('is_main', false)->count();

        return view('agencies.index', compact('agencies', 'statsTotal', 'statsActive', 'statsInactive', 'statsSubagencies'));
    }

    public function create()
    {
        $departments = self::NICARAGUA_DEPARTMENTS;
        $mainAgencies = Agency::mainAgencies()->orderBy('name')->get();
        return view('agencies.create', compact('departments', 'mainAgencies'));
    }

    public function store(Request $request)
    {
        // Normalizar nombre (trim) para validar y guardar el mismo valor; así solo falla si ya existe uno igual
        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'phone' => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
            'address' => $request->filled('address') ? trim((string) $request->input('address')) : null,
            'user_email' => $request->filled('user_email') ? trim((string) $request->input('user_email')) : $request->input('user_email'),
        ]);

        $request->validate([
            'parent_agency_id' => 'required|exists:agencies,id',
            'name' => 'required|string|max:255|unique:agencies,name',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'department' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'user_name' => 'nullable|string|max:255',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required|string|min:8|confirmed',
        ], [
            'parent_agency_id.required' => 'Debe seleccionar si la subagencia es de SkyLink One o de CH LOGISTICS.',
            'parent_agency_id.exists' => 'La agencia principal seleccionada no es válida.',
            'name.required' => 'El nombre de la subagencia es obligatorio.',
            'name.unique' => 'Ya existe una subagencia con ese nombre. Elija otro nombre.',
            'user_email.required' => 'El correo del usuario de la agencia es obligatorio.',
            'user_email.unique' => 'Ya existe un usuario con ese correo. Use otro correo.',
            'user_password.required' => 'La contraseña del usuario es obligatoria.',
            'user_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'user_password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $parent = Agency::find($request->parent_agency_id);
        if (!$parent || !$parent->is_main) {
            return redirect()->back()->withInput()->withErrors(['parent_agency_id' => 'Debe seleccionar SkyLink One o CH LOGISTICS.']);
        }

        $data = $request->only(['name', 'phone', 'address', 'department', 'parent_agency_id']);
        $data['code'] = $this->generateNextAgencyCode();
        $data['is_active'] = true;
        $data['is_main'] = false;

        if ($request->hasFile('logo')) {
            try {
                $data['logo_path'] = $request->file('logo')->store('agencies/logos', 'public');
            } catch (\Throwable $e) {
                \Log::warning('Agency logo upload failed', ['exception' => $e->getMessage()]);
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['logo' => 'No se pudo subir el logo. Intente de nuevo o deje el logo vacío.']);
            }
        }

        try {
            $agency = Agency::create($data);
        } catch (\Throwable $e) {
            \Log::warning('Agency store failed', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'data' => $data]);
            $message = 'No se pudo guardar la agencia. Intente de nuevo.';
            if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'unique')) {
                $message = 'Ya existe una subagencia con ese nombre. Elija otro nombre.';
            }
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => $message]);
        }

        $userName = $request->filled('user_name')
            ? trim((string) $request->user_name)
            : $agency->name;
        $userEmail = trim((string) $request->user_email);
        User::create([
            'name' => $userName,
            'email' => $userEmail,
            'password' => Hash::make($request->user_password),
            'agency_id' => $agency->id,
            'is_admin' => false,
        ]);

        return redirect()->route('agencies.index')->with('success', 'Agencia creada. Se creó el usuario de acceso para la agencia (pueden iniciar sesión con el correo y contraseña indicados).');
    }

    /**
     * Genera el siguiente código de 4 dígitos para una nueva agencia.
     */
    private function generateNextAgencyCode(): string
    {
        try {
            $max = Agency::query()->selectRaw('MAX(CAST(code AS INTEGER)) as m')->value('m');
            $num = $max !== null && $max !== '' ? (int) $max + 1 : 1;
            return str_pad((string) $num, 4, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            \Log::warning('Agency code generation fallback', ['exception' => $e->getMessage()]);
            return '0001';
        }
    }

    public function show(string $id)
    {
        $agency = Agency::withCount(['clients', 'preregistrations'])
            ->with(['clients', 'users', 'parent', 'children' => fn ($q) => $q->withCount('clients')->orderBy('name')])
            ->findOrFail($id);
        return view('agencies.show', compact('agency'));
    }

    public function edit(string $id)
    {
        $agency = Agency::findOrFail($id);
        $departments = self::NICARAGUA_DEPARTMENTS;
        return view('agencies.edit', compact('agency', 'departments'));
    }

    public function update(Request $request, string $id)
    {
        $agency = Agency::findOrFail($id);
        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'phone' => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
            'address' => $request->filled('address') ? trim((string) $request->input('address')) : null,
        ]);
        $request->validate([
            'name' => 'required|string|max:255|unique:agencies,name,' . $agency->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'department' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
            'remove_logo' => 'sometimes|boolean',
        ], [
            'name.required' => 'El nombre de la subagencia es obligatorio.',
            'name.unique' => 'Ya existe otra subagencia con ese nombre. Elija otro nombre.',
        ]);
        $data = $request->only(['name', 'phone', 'address', 'department']);
        if ($request->boolean('remove_logo') && $agency->logo_path) {
            Storage::disk('public')->delete($agency->logo_path);
            $data['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            if ($agency->logo_path) {
                Storage::disk('public')->delete($agency->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('agencies/logos', 'public');
        }
        if ($request->has('is_active')) {
            $data['is_active'] = (bool) $request->is_active;
        }
        $agency->update($data);
        return redirect()->route('agencies.show', $agency->id)->with('success', 'Agencia actualizada.');
    }

    public function toggle(string $id)
    {
        $agency = Agency::findOrFail($id);
        $agency->update(['is_active' => !$agency->is_active]);
        return back()->with('success', $agency->is_active ? 'Agencia activada.' : 'Agencia desactivada.');
    }

    /**
     * Restablecer contraseña del usuario de acceso de la agencia (solo administrador).
     */
    public function resetUserPassword(Request $request, string $agency, string $user)
    {
        $agencyModel = Agency::findOrFail($agency);
        $userModel = User::where('id', $user)->where('agency_id', $agencyModel->id)->firstOrFail();

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $userModel->update(['password' => Hash::make($request->password)]);

        return redirect()->route('agencies.show', $agencyModel->id)
            ->with('success', 'Contraseña actualizada. La subagencia ya puede iniciar sesión con la nueva contraseña.');
    }

    public function destroy(string $id)
    {
        $agency = Agency::findOrFail($id);

        if ($agency->is_main) {
            return redirect()->route('agencies.index')
                ->with('error', 'No se pueden eliminar las agencias principales (SkyLink One y CH LOGISTICS).');
        }

        $packagesCount = Preregistration::where('agency_id', $agency->id)->count();
        if ($packagesCount > 0) {
            return redirect()->route('agencies.index')
                ->with('error', "No se puede eliminar la agencia: tiene {$packagesCount} paquete(s) asignado(s). Reasigne o elimine los paquetes antes.");
        }

        if ($agency->logo_path) {
            Storage::disk('public')->delete($agency->logo_path);
        }

        $agency->delete();
        return redirect()->route('agencies.index')->with('success', 'Subagencia eliminada.');
    }
}
