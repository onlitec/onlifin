<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class ApiKeyController extends Controller
{
    public function create()
    {
        return view('api-key.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'name' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $encryptedKey = Crypt::encryptString($validated['api_key']);

        $user->api_key = $encryptedKey; // Armazena a chave criptografada no campo api_key do usuário
        if (isset($validated['name'])) {
            // Se name for fornecido, você pode armazenar em outro lugar ou usá-lo; aqui, assumo que é opcional
        }
        $user->save();

        return redirect()->route('api-key.index')->with('success', 'Chave API salva com sucesso!');
    }
}
