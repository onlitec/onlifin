<?php

namespace App\Livewire\Settings\Users;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
    public $is_admin = false;

    protected $listeners = ['resetForm'];
    
    protected $casts = [
        'status' => 'boolean'
    ];

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$this->userId,
            'phone' => 'nullable|string|max:15',
            'selectedRoles' => 'required|array|min:1',
            'selectedRoles.*' => 'exists:roles,id',
            'status' => 'required|boolean',
            'is_admin' => 'boolean'
        ];
        
        if (filled($this->password)) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['password_confirmation'] = ['required', 'string'];
        } else {
            $rules['password'] = 'nullable|min:8|confirmed';
        }
        
        return $rules;
    }

    public function mount($userId)
    {
        $user = User::findOrFail($userId);
        $this->resetForm($user);
    }
    
    public function resetForm($user = null)
    {
        if ($user === null && isset($this->userId)) {
            $user = User::findOrFail($this->userId);
        }
        
        if ($user) {
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone;
            $this->status = (bool) $user->is_active;
            $this->is_admin = $user->is_admin;
            $this->roles = Role::all();
            $this->password = '';
            $this->password_confirmation = '';
            
            // Carrega os perfis do usuário
            $this->selectedRoles = $user->roles()->pluck('roles.id')->toArray();

            Log::info('EditUser resetForm', [
                'userId' => $this->userId, 
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'selectedRoles' => $this->selectedRoles,
                'status' => $this->status,
                'is_active' => $user->is_active,
                'is_admin' => $user->is_admin,
                'email_verified_at' => $user->email_verified_at
            ]);
        }
    }

    public function updatedStatus($value)
    {
        $this->status = (bool) $value;
        Log::info('Status atualizado', ['status' => $this->status]);
    }
    
    public function cancel()
    {
        $this->resetForm();
        $this->dispatch('close-modal');
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
            'is_admin' => $this->is_admin,
            'has_password' => !empty($this->password)
        ]);

        $validated = $this->validate();

        try {
            DB::beginTransaction();
            
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
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $this->phone,
                'is_active' => $this->status,
                'is_admin' => $this->is_admin,
                'email_verified_at' => $email_verified_at
            ];

            // Atualiza a senha apenas se foi fornecida
            if (filled($this->password)) {
                $userData['password'] = Hash::make($this->password);
            }

            $user->update($userData);

            // Atualiza os perfis do usuário
            $user->roles()->sync($this->selectedRoles);
            
            DB::commit();

            Log::info('Usuário atualizado com sucesso', [
                'userId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $this->selectedRoles,
                'is_active' => $user->is_active,
                'is_admin' => $user->is_admin,
                'email_verified_at' => $user->email_verified_at,
                'password_updated' => !empty($this->password)
            ]);

            // Notificar frontend
            $this->dispatch('close-modal');
            $this->dispatch('userUpdated');
            
            session()->flash('success', 'Usuário atualizado com sucesso!');
            return redirect()->route('settings.users');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar usuário', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
            return redirect()->route('settings.users');
        }
    }

    // Alias para manter compatibilidade com chamadas existentes
    public function updateUser()
    {
        return $this->save();
    }
    
    public function render()
    {
        Log::info('EditUser renderizado', [
            'status' => $this->status,
            'status_type' => gettype($this->status),
            'selectedRoles' => $this->selectedRoles
        ]);
        
        return view('livewire.settings.users.edit-user', [
            'roles' => Role::all()
        ]);
    }
}