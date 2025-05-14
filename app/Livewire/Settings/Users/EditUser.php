<?php

namespace App\Livewire\Settings\Users;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class EditUser extends Component
{
    public $userId;
    public $name;
    public $email;
    public $phone;
    public $selectedRoles = [];
    public $status;
    public $roles;
    public $password;
    public $password_confirmation;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255',
        'phone' => 'nullable|string|max:15',
        'selectedRoles' => 'required|array|min:1',
        'selectedRoles.*' => 'exists:roles,id',
        'status' => 'required|boolean',
        'password' => 'nullable|min:8|confirmed'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function mount($userId)
    {
        $user = User::findOrFail($userId);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->status = (bool) $user->is_active;
        $this->roles = Role::all();
        
        // Carrega os perfis do usuário
        $this->selectedRoles = $user->roles()->pluck('roles.id')->toArray();

        Log::info('EditUser mounted', [
            'userId' => $this->userId, 
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'selectedRoles' => $this->selectedRoles,
            'status' => $this->status,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at
        ]);
    }

    public function updatedStatus($value)
    {
        $this->status = (bool) $value;
        Log::info('Status atualizado', ['status' => $this->status]);
    }

    public function save()
    {
        Log::info('Método save foi chamado no EditUser', [
            'userId' => $this->userId, 
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'selectedRoles' => $this->selectedRoles,
            'status' => $this->status,
            'has_password' => !empty($this->password)
        ]);

        $this->validate([
            'email' => 'required|email|unique:users,email,'.$this->userId,
            'name' => 'required',
            'phone' => 'nullable|string|max:15',
            'selectedRoles' => 'required|array|min:1',
            'selectedRoles.*' => 'exists:roles,id',
            'status' => 'required|boolean',
            'password' => 'nullable|min:8|confirmed'
        ]);

        try {
            $user = User::find($this->userId);
            
            if (!$user) {
                Log::error('Usuário não encontrado para edição', ['userId' => $this->userId]);
                session()->flash('error', 'Usuário não encontrado');
                return redirect()->route('settings.users');
            }

            $email_verified_at = null;
            if ($this->status) {
                $email_verified_at = $user->email_verified_at ?? now();
            }

            $userData = [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'is_active' => $this->status,
                'email_verified_at' => $email_verified_at
            ];

            // Atualiza a senha apenas se foi fornecida
            if (!empty($this->password)) {
                $userData['password'] = Hash::make($this->password);
            }

            $user->update($userData);

            // Atualiza os perfis do usuário
            $user->roles()->sync($this->selectedRoles);

            Log::info('Usuário atualizado com sucesso', [
                'userId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $this->selectedRoles,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'password_updated' => !empty($this->password)
            ]);

            session()->flash('message', 'Usuário atualizado com sucesso!');
            return redirect()->route('settings.users');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar usuário', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
            return redirect()->route('settings.users');
=======
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EditUser extends Component
{
    public User $user;
    public $userId;
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $is_admin = false;
    public $selectedRoles = [];

    protected $listeners = ['resetForm'];

    public function mount(User $user)
    {
        $this->resetForm($user);
    }

    public function resetForm($user = null)
    {
        $user = $user ?? $this->user;
        $this->user = $user;
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_admin = $user->is_admin;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function cancel()
    {
        $this->resetForm();
        $this->dispatch('close-modal');
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$this->userId,
            'is_admin' => 'boolean',
            'selectedRoles' => 'array'
        ];

        if (filled($this->password)) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['password_confirmation'] = ['required', 'string'];
        }

        return $rules;
    }

    public function updateUser()
    {
        $validated = $this->validate();

        try {
            DB::beginTransaction();

            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_admin' => $this->is_admin
            ];

            if (filled($this->password)) {
                $userData['password'] = Hash::make($this->password);
            }

            $this->user->update($userData);
            $this->user->roles()->sync($this->selectedRoles);

            DB::commit();

            session()->flash('success', 'Usuário atualizado com sucesso!');
            $this->dispatch('close-modal');
            $this->dispatch('userUpdated');
            
            return redirect()->route('settings.users');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
>>>>>>> remotes/ONLITEC/fix/campo-valor
        }
    }

    public function render()
    {
<<<<<<< HEAD
        Log::info('EditUser renderizado', [
            'status' => $this->status,
            'status_type' => gettype($this->status),
            'selectedRoles' => $this->selectedRoles
        ]);
        return view('livewire.settings.users.edit-user');
=======
        return view('livewire.settings.users.edit-user', [
            'roles' => Role::all()
        ]);
>>>>>>> remotes/ONLITEC/fix/campo-valor
    }
} 