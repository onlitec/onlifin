<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user
                            {--name= : Nome do usuário}
                            {--email= : Email do usuário}
                            {--password= : Senha do usuário}
                            {--admin : Define o usuário como administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Criar um novo usuário no sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obter os valores dos parâmetros ou solicitar interativamente
        $name = $this->option('name') ?? $this->ask('Nome do usuário');
        $email = $this->option('email') ?? $this->ask('Email do usuário');
        $password = $this->option('password') ?? $this->secret('Senha do usuário (mínimo de 8 caracteres)');
        $isAdmin = $this->option('admin') ?? $this->confirm('Usuário é administrador?', false);
        
        // Validar dados
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);
        
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }
        
        // Criar o usuário
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->is_admin = $isAdmin;
        $user->is_active = true;
        $user->email_verified_at = now(); // Marcar o email como verificado
        $user->save();
        
        $this->info("Usuário criado com sucesso!");
        $this->info("ID: {$user->id}");
        $this->info("Nome: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Admin: " . ($user->is_admin ? 'Sim' : 'Não'));
        
        // Atribuir perfil de administrador se for admin
        if ($isAdmin) {
            $adminRole = Role::firstOrCreate(
                ['name' => 'Administrador'],
                ['description' => 'Acesso completo ao sistema']
            );
            
            $user->roles()->attach($adminRole->id);
            $this->info("Perfil de Administrador atribuído.");
        } else {
            $userRole = Role::firstOrCreate(
                ['name' => 'Usuário'],
                ['description' => 'Acesso básico ao sistema']
            );
            
            $user->roles()->attach($userRole->id);
            $this->info("Perfil de Usuário atribuído.");
        }
        
        Log::info("Usuário criado via comando CLI", [
            'id' => $user->id,
            'email' => $user->email,
            'is_admin' => $user->is_admin
        ]);
        
        return Command::SUCCESS;
    }
}
