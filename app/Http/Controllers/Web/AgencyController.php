<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $query = Agency::with(['parent', 'users:id,agency_id,email'])->withCount(['clients', 'preregistrations']);

        if ($request->has('is_active') && $request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }
        $agencies = $query->orderBy('name')->paginate(25)->withQueryString();

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
        if ($request->filled('account_type')) {
            $statsQuery->where('account_type', $request->account_type);
        }
        $statsTotal = $statsQuery->count();
        $statsActive = (clone $statsQuery)->where('is_active', true)->count();
        $statsInactive = (clone $statsQuery)->where('is_active', false)->count();
        $statsSubagencies = (clone $statsQuery)->where('account_type', Agency::TYPE_SUBAGENCY)->count();
        $statsDirectClients = (clone $statsQuery)->where('account_type', Agency::TYPE_DIRECT_CLIENT)->count();

        return view('agencies.index', compact('agencies', 'statsTotal', 'statsActive', 'statsInactive', 'statsSubagencies', 'statsDirectClients'));
    }

    public function create()
    {
        $departments = self::NICARAGUA_DEPARTMENTS;
        $parentOptions = Agency::parentCandidates()->get();
        $slo = Agency::query()
            ->where(function ($q) {
                $q->where('is_main', true)->orWhere('account_type', Agency::TYPE_ROOT);
            })
            ->orderByDesc('is_main')
            ->first();
        $subagencyParents = $parentOptions
            ->filter(fn (Agency $agency) => ! $agency->is_main && $agency->account_type !== Agency::TYPE_ROOT)
            ->values();

        return view('agencies.create', compact('departments', 'parentOptions', 'slo', 'subagencyParents'));
    }

    public function store(Request $request)
    {
        // Normalizar nombre (trim) para validar y guardar el mismo valor; así solo falla si ya existe uno igual
        $email = mb_strtolower(trim((string) $request->input('user_email', '')));
        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'phone' => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
            'address' => $request->filled('address') ? trim((string) $request->input('address')) : null,
            'user_email' => $email !== '' ? $email : $request->input('user_email'),
        ]);

        $accountType = (string) $request->input('account_type');
        $slo = Agency::query()
            ->where(function ($q) {
                $q->where('is_main', true)->orWhere('account_type', Agency::TYPE_ROOT);
            })
            ->orderByDesc('is_main')
            ->first();

        if ($accountType === Agency::TYPE_DIRECT_CLIENT && $slo) {
            $request->merge(['parent_agency_id' => $slo->id]);
        } elseif ($accountType === Agency::TYPE_SUBAGENCY && $request->input('subagency_scope') === 'slo' && $slo) {
            $request->merge(['parent_agency_id' => $slo->id]);
        }

        $request->validate([
            'account_type' => 'required|in:subagency,direct_client',
            'subagency_scope' => 'nullable|in:slo,nested',
            'parent_agency_id' => $accountType === Agency::TYPE_DIRECT_CLIENT
                ? 'nullable|exists:agencies,id'
                : 'required|exists:agencies,id',
            'name' => 'required|string|max:255|unique:agencies,name',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'department' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'user_name' => 'nullable|string|max:255',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required|string|min:8|confirmed',
        ], [
            'account_type.required' => 'Indique si es subagencia o cliente de SkyLink One.',
            'parent_agency_id.required' => 'Debe seleccionar a quién pertenece esta cuenta.',
            'parent_agency_id.exists' => 'La cuenta padre seleccionada no es válida.',
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe una cuenta con ese nombre. Elija otro nombre.',
            'user_email.required' => 'El correo de acceso es obligatorio.',
            'user_email.unique' => $this->userEmailTakenMessage($email),
            'user_password.required' => 'La contraseña es obligatoria.',
            'user_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'user_password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $parent = Agency::find($request->parent_agency_id);
        $accountType = (string) $request->account_type;

        if ($accountType === Agency::TYPE_DIRECT_CLIENT) {
            if (! $slo) {
                return redirect()->back()->withInput()->withErrors(['account_type' => 'No se encontró SkyLink One para asignar el cliente.']);
            }
            $parent = $slo;
        } elseif (! $parent || $parent->isDirectClient()) {
            return redirect()->back()->withInput()->withErrors(['parent_agency_id' => 'Una subagencia debe pertenecer a SkyLink One o a otra agencia/subagencia (no a un cliente propio de SLO).']);
        }

        $data = $request->only(['name', 'phone', 'address', 'department']);
        $data['parent_agency_id'] = $parent->id;
        $data['code'] = Agency::nextAvailableNumericCode();
        $data['is_active'] = true;
        $data['is_main'] = false;
        $data['account_type'] = $accountType;
        $data['billing_email'] = trim((string) $request->user_email);

        if ($accountType !== Agency::TYPE_DIRECT_CLIENT && $request->hasFile('logo')) {
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
            $agency = DB::transaction(function () use ($request, $data) {
                $agency = Agency::create($data);
                $userName = trim((string) $request->input('user_name', ''));
                if ($userName === '' || filter_var($userName, FILTER_VALIDATE_EMAIL)) {
                    $userName = $agency->name;
                }
                User::create([
                    'name' => $userName,
                    'email' => trim((string) $request->user_email),
                    'password' => $request->user_password,
                    'agency_id' => $agency->id,
                    'is_admin' => false,
                ]);

                return $agency;
            });
        } catch (\Throwable $e) {
            \Log::warning('Agency store failed', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'data' => $data]);
            $message = 'No se pudo guardar la agencia. Intente de nuevo.';
            $field = 'name';
            if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'unique')) {
                if (str_contains(mb_strtolower($e->getMessage()), 'email') || str_contains(mb_strtolower($e->getMessage()), 'users')) {
                    $field = 'user_email';
                    $message = $this->userEmailTakenMessage(trim((string) $request->user_email));
                } else {
                    $message = 'Ya existe una cuenta con ese nombre. Elija otro nombre.';
                }
            }

            return redirect()->back()
                ->withInput()
                ->withErrors([$field => $message]);
        }

        return redirect()->route('agencies.index')->with('success', 'Cliente creado. Ya puede iniciar sesión con el correo y contraseña indicados.');
    }

    public function show(string $id)
    {
        $agency = Agency::withCount(['clients', 'preregistrations'])
            ->with(['clients', 'users', 'parent', 'children' => fn ($q) => $q->withCount('clients')->orderBy('name')])
            ->findOrFail($id);

        // Bloque Contabilidad (solo lectura): tarifas vigentes y saldo pendiente
        $currentRates = \App\Models\AccountingRateCard::query()
            ->where('agency_id', $agency->id)
            ->whereNull('effective_to')
            ->orderBy('service_type')
            ->get();
        $openBalance = round(
            \App\Models\AccountingInvoice::query()
                ->where('agency_id', $agency->id)
                ->whereIn('status', ['issued', 'partially_paid'])
                ->get(['id', 'total_usd', 'amount_paid'])
                ->sum(fn ($i) => $i->balanceUsd()),
            2
        );

        return view('agencies.show', compact('agency', 'currentRates', 'openBalance'));
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
            'name' => 'required|string|max:255|unique:agencies,name,'.$agency->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'department' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
            'remove_logo' => 'sometimes|boolean',
            'credit_limit_usd' => 'nullable|numeric|min:0|max:9999999',
            'credit_days' => 'nullable|integer|min:0|max:365',
            'tax_id' => 'nullable|string|max:50',
            'billing_contact_name' => 'nullable|string|max:120',
            'billing_contact_phone' => 'nullable|string|max:40',
            'billing_email' => 'nullable|email|max:255',
        ], [
            'name.required' => $agency->isDirectClient() ? 'El nombre del cliente es obligatorio.' : 'El nombre de la subagencia es obligatorio.',
            'name.unique' => $agency->isDirectClient()
                ? 'Ya existe otra cuenta con ese nombre. Elija otro nombre.'
                : 'Ya existe otra subagencia con ese nombre. Elija otro nombre.',
        ]);
        $data = $request->only(['name', 'phone', 'address', 'department', 'credit_limit_usd', 'credit_days', 'tax_id', 'billing_contact_name', 'billing_contact_phone', 'billing_email']);
        if (! $agency->isDirectClient()) {
            if ($request->boolean('remove_logo') && $agency->logo_path) {
                Storage::disk('public')->delete($agency->logo_path);
                $data['logo_path'] = null;
            } elseif ($request->hasFile('logo')) {
                if ($agency->logo_path) {
                    Storage::disk('public')->delete($agency->logo_path);
                }
                $data['logo_path'] = $request->file('logo')->store('agencies/logos', 'public');
            }
        }
        if ($request->has('is_active')) {
            $data['is_active'] = (bool) $request->is_active;
        }
        $agency->update($data);

        return redirect()->route('agencies.show', $agency->id)->with('success', $agency->isDirectClient() ? 'Cliente actualizado.' : 'Subagencia actualizada.');
    }

    public function toggle(string $id)
    {
        $agency = Agency::findOrFail($id);
        $agency->update(['is_active' => ! $agency->is_active]);

        return back()->with('success', $agency->is_active ? 'Agencia activada.' : 'Agencia desactivada.');
    }

    public function createAccess(Agency $agency)
    {
        if ($agency->users()->exists()) {
            $user = $agency->users()->orderBy('id')->first();

            return redirect()->route('agencies.users.edit', [$agency, $user]);
        }

        return view('agencies.edit-access', [
            'agency' => $agency,
            'accessUser' => null,
        ]);
    }

    public function storeAccess(Request $request, Agency $agency)
    {
        if ($agency->users()->exists()) {
            return redirect()->route('agencies.show', $agency)
                ->with('error', 'Esta cuenta ya tiene un acceso. Edítelo desde la ficha.');
        }

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'El nombre de acceso es obligatorio.',
            'email.required' => 'El correo de acceso es obligatorio.',
            'email.unique' => $this->userEmailTakenMessage((string) $request->input('email')),
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'agency_id' => $agency->id,
            'is_admin' => false,
        ]);

        if (! $agency->billing_email) {
            $agency->update(['billing_email' => $request->email]);
        }

        return redirect()->route('agencies.show', $agency)
            ->with('success', 'Acceso creado. El cliente ya puede entrar con ese correo y contraseña.');
    }

    public function editAccess(Agency $agency, User $user)
    {
        $this->ensureAgencyAccessUser($agency, $user);

        return view('agencies.edit-access', [
            'agency' => $agency,
            'accessUser' => $user,
        ]);
    }

    public function updateAccess(Request $request, Agency $agency, User $user)
    {
        $this->ensureAgencyAccessUser($agency, $user);

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ];
        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Password::defaults()];
        }

        $request->validate($rules, [
            'name.required' => 'El nombre de acceso es obligatorio.',
            'email.required' => 'El correo de acceso es obligatorio.',
            'email.unique' => $this->userEmailTakenMessage((string) $request->input('email')),
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $oldEmail = (string) $user->email;
        $user->fill([
            'name' => $request->name,
            'email' => $request->email,
            'is_admin' => false,
        ]);
        if ($request->filled('password')) {
            $user->password = $request->password;
        }
        $user->save();

        if ($agency->billing_email && strcasecmp((string) $agency->billing_email, $oldEmail) === 0) {
            $agency->update(['billing_email' => $request->email]);
        }

        return redirect()->route('agencies.show', $agency)
            ->with('success', 'Acceso del cliente actualizado.');
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
            ->with('success', $agencyModel->isDirectClient()
                ? 'Contraseña actualizada. El cliente ya puede iniciar sesión con la nueva contraseña.'
                : 'Contraseña actualizada. La subagencia ya puede iniciar sesión con la nueva contraseña.');
    }

    public function destroy(string $id)
    {
        $agency = Agency::findOrFail($id);

        if ($agency->is_main || $agency->account_type === Agency::TYPE_ROOT) {
            return redirect()->route('agencies.index')
                ->with('error', 'No se puede eliminar SkyLink One.');
        }

        $packagesCount = Preregistration::where('agency_id', $agency->id)->count();
        if ($packagesCount > 0) {
            return redirect()->route('agencies.index')
                ->with('error', 'No se puede eliminar la cuenta: tiene '.$packagesCount.' paquete(s) asignado(s). Reasigne o elimine los paquetes antes.');
        }

        $invoicesCount = $agency->accountingInvoices()->count();
        $paymentsCount = $agency->accountingPayments()->count();
        $creditNotesCount = $agency->creditNotes()->count();
        if ($invoicesCount + $paymentsCount + $creditNotesCount > 0) {
            return redirect()->route('agencies.index')
                ->with('error', 'No se puede eliminar la cuenta: tiene historial de facturas, cobros o notas de crédito.');
        }

        DB::transaction(function () use ($agency) {
            $agency->users()->delete();
            if ($agency->logo_path) {
                Storage::disk('public')->delete($agency->logo_path);
            }
            $agency->delete();
        });

        return redirect()->route('agencies.index')->with('success', 'Cuenta eliminada.');
    }

    private function ensureAgencyAccessUser(Agency $agency, User $user): void
    {
        if ((int) $user->agency_id !== (int) $agency->id) {
            abort(404);
        }
    }

    private function userEmailTakenMessage(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return 'Ya existe un usuario con ese correo. Use otro correo para el acceso.';
        }

        $existing = User::query()
            ->with('agency:id,code,name')
            ->whereRaw('lower(email) = ?', [$email])
            ->first();

        if (! $existing) {
            return 'Ya existe un usuario con ese correo. Use otro correo para el acceso.';
        }

        if ($existing->agency) {
            return "Ese correo ya lo usa {$existing->name} (cuenta {$existing->agency->code} · {$existing->agency->name}). Use otro correo para el acceso de este cliente.";
        }

        if ($existing->is_admin) {
            return "Ese correo ya lo usa el administrador {$existing->name}. Use otro correo para el acceso de este cliente.";
        }

        return "Ese correo ya lo usa el usuario {$existing->name} en Usuarios. Use otro correo, o cambie el de ese usuario si ya no aplica.";
    }
}
