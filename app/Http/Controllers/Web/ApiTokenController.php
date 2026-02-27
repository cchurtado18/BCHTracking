<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = auth()->user()->tokens()->orderByDesc('created_at')->get();

        return view('api-tokens.index', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = auth()->user()->createToken($request->name);

        return redirect()->route('api-tokens.index')
            ->with('success', 'Token creado. Cópialo ahora; no se mostrará de nuevo.')
            ->with('new_token_plain', $token->plainTextToken);
    }

    public function destroy(Request $request, string $tokenId)
    {
        auth()->user()->tokens()->where('id', $tokenId)->delete();

        return redirect()->route('api-tokens.index')->with('success', 'Token revocado.');
    }
}
