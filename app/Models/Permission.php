<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'description', 'category'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Retorna o nome da permissão em português para exibição.
     */
    public function getDisplayNameAttribute()
    {
        $map = [
            'view_users' => 'Visualizar usuários',
            'create_users' => 'Criar usuários',
            'edit_users' => 'Editar usuários',
            'delete_users' => 'Excluir usuários',
            'view_roles' => 'Visualizar perfis',
            'manage_roles' => 'Gerenciar perfis',
            'view_all_transactions' => 'Visualizar todas as transações',
            'view_own_transactions' => 'Visualizar próprias transações',
            'create_transactions' => 'Criar transações',
            'edit_all_transactions' => 'Editar todas as transações',
            'edit_own_transactions' => 'Editar próprias transações',
            'delete_all_transactions' => 'Excluir todas as transações',
            'delete_own_transactions' => 'Excluir próprias transações',
            'mark_as_paid_all_transactions' => 'Marcar todas as transações como pagas',
            'mark_as_paid_own_transactions' => 'Marcar próprias transações como pagas',
            'view_all_accounts' => 'Visualizar todas as contas',
            'view_own_accounts' => 'Visualizar próprias contas',
            'create_accounts' => 'Criar contas',
            'edit_all_accounts' => 'Editar todas as contas',
            'edit_own_accounts' => 'Editar próprias contas',
            'delete_all_accounts' => 'Excluir todas as contas',
            'delete_own_accounts' => 'Excluir próprias contas',
            'view_all_categories' => 'Visualizar todas as categorias',
            'view_own_categories' => 'Visualizar próprias categorias',
            'create_categories' => 'Criar categorias',
            'edit_all_categories' => 'Editar todas as categorias',
            'edit_own_categories' => 'Editar próprias categorias',
            'delete_all_categories' => 'Excluir todas as categorias',
            'delete_own_categories' => 'Excluir próprias categorias',
            'view_reports' => 'Visualizar relatórios',
            'manage_backups' => 'Gerenciar backups',
            'manage_settings' => 'Gerenciar configurações',
        ];
        return $map[$this->name] ?? $this->name;
    }
} 