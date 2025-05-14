<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Configurar canal de log para garantir que os logs sejam salvos
        config(['logging.default' => 'daily']);

        // Validar os dados
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:15',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            // Log para debug
            Log::info('Tentando criar usuário via UserController', [
                'name' => $request->name,
                'email' => $request->email,
                'status' => $request->has('status') ? 'Ativo' : 'Inativo'
            ]);

            // Criar o usuário
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = Hash::make($request->password);
            $user->is_active = $request->has('status') ? 1 : 0;
            $user->email_verified_at = $request->has('status') ? now() : null;
            
            // Salvar o usuário (método save retorna boolean)
            $success = $user->save();
            
            if (!$success) {
                throw new \Exception('Não foi possível salvar o usuário no banco de dados.');
            }

            // Adicionar papel ao usuário
            if ($request->role_id) {
                $user->roles()->attach($request->role_id);
            }

            Log::info('Usuário criado com sucesso', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'method' => 'UserController@store'
            ]);

            return redirect('/users')
                ->with('message', 'Usuário criado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário via UserController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
