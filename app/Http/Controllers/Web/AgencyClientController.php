<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyClient;
use Illuminate\Http\Request;

class AgencyClientController extends Controller
{
    public function index(string $agency_id)
    {
        $agency = Agency::with('parent')->findOrFail($agency_id);
        $query = $agency->clients();

        if (request()->filled('is_active') !== null && request()->filled('is_active') !== '') {
            $query->where('is_active', (bool) request('is_active'));
        }
        if (request()->filled('search')) {
            $query->where('full_name', 'like', '%' . request('search') . '%');
        }

        $clients = $query->orderBy('full_name')->paginate(15)->withQueryString();

        $statsTotal = $agency->clients()->count();
        $statsActive = $agency->clients()->where('is_active', true)->count();
        $statsInactive = $agency->clients()->where('is_active', false)->count();

        return view('agency-clients.index', compact('agency', 'clients', 'statsTotal', 'statsActive', 'statsInactive'));
    }

    public function create(string $agency_id)
    {
        $agency = Agency::findOrFail($agency_id);
        return view('agency-clients.create', compact('agency'));
    }

    public function store(Request $request, string $agency_id)
    {
        $agency = Agency::findOrFail($agency_id);
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);
        $agency->clients()->create($request->only(['full_name', 'phone']));
        return redirect()->route('agency-clients.index', $agency->id)->with('success', 'Cliente creado.');
    }

    public function show(string $id)
    {
        $client = AgencyClient::with('agency')->findOrFail($id);
        return view('agency-clients.show', compact('client'));
    }

    public function edit(string $id)
    {
        $client = AgencyClient::findOrFail($id);
        return view('agency-clients.edit', compact('client'));
    }

    public function update(Request $request, string $id)
    {
        $client = AgencyClient::findOrFail($id);
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);
        $data = $request->only(['full_name', 'phone']);
        if ($request->has('is_active')) {
            $data['is_active'] = (bool) $request->is_active;
        }
        $client->update($data);
        return redirect()->route('agency-clients.show', $client->id)->with('success', 'Cliente actualizado.');
    }

    public function toggle(string $id)
    {
        $client = AgencyClient::findOrFail($id);
        $client->update(['is_active' => !$client->is_active]);
        return back()->with('success', $client->is_active ? 'Cliente activado.' : 'Cliente desactivado.');
    }
}
