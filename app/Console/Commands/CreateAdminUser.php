<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'onlifin:create-admin 
                            {--email=admin@onlifin.com : Email do usuário administrador}
                            {--password=admin123 : Senha do usuário administrador}
                            {--name=Administrador : Nome do usuário administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Criar usuário administrador para o Onlifin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        $this->info('🚀 Criando usuário administrador...');
        $this->info("📧 Email: {$email}");
        $this->info("👤 Nome: {$name}");

        try {
            // Verificar se usuário já existe
            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                $this->warn("⚠️ Usuário com email {$email} já existe!");
                
                if ($this->confirm('Deseja atualizar a senha?')) {
                    $existingUser->password = Hash::make($password);
                    $existingUser->is_admin = true;
                    $existingUser->is_active = true;
                    $existingUser->save();
                    
                    $this->info('✅ Senha atualizada com sucesso!');
                    $this->info("🔑 Nova senha: {$password}");
                }
                
                return Command::SUCCESS;
            }

            // Criar novo usuário
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->info('✅ Usuário administrador criado com sucesso!');
            $this->info("👤 ID: {$user->id}");
            $this->info("📧 Email: {$user->email}");
            $this->info("🔑 Senha: {$password}");
            
            // Mostrar total de usuários
            $totalUsers = User::count();
            $this->info("📊 Total de usuários no sistema: {$totalUsers}");

            $this->newLine();
            $this->info('🌐 Para acessar a plataforma:');
            $this->info('   URL: ' . config('app.url') . '/login');
            $this->info("   Email: {$email}");
            $this->info("   Senha: {$password}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erro ao criar usuário: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
