<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $typeFilter = $request->query('type', 'all');
        $query = Category::orderBy('name');

        if ($typeFilter === 'income') {
            $query->where('type', 'income');
        } elseif ($typeFilter === 'expense') {
            $query->where('type', 'expense');
        }
        
        // SISTEMA DE AUTORIZAÇÃO HIERÁRQUICO PARA CATEGORIAS:
        // 1. view_all_categories: Administradores podem ver todas as categorias
        // 2. view_own_categories: Usuários podem ver apenas suas próprias categorias + categorias do sistema
        if (!$user->hasPermission('view_all_categories')) {
            if ($user->hasPermission('view_own_categories')) {
                // CORREÇÃO DE AUTORIZAÇÃO: Permite ver categorias próprias + categorias do sistema (user_id null)
                $query->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)  // Categorias do usuário
                      ->orWhereNull('user_id');       // Categorias do sistema (compartilhadas)
                });
            } else {
                // SEGURANÇA: Bloqueia acesso se não tem nenhuma permissão de visualização
                abort(403, 'Você não tem permissão para visualizar categorias.');
            }
        }
        // PERMISSÃO TOTAL: Administradores com 'view_all_categories' veem todas as categorias
        
        $categories = $query->paginate(10)->appends(['type' => $typeFilter]);
        $isAdminView = $user->hasRole('Administrador');
        
        return view('categories.index', compact('categories', 'isAdminView', 'typeFilter'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->hasPermission('create_categories')) {
            abort(403, 'Você não tem permissão para criar categorias.');
        }
        $isAdminView = $user->hasRole('Administrador');
        
        // CORREÇÃO: Buscar categorias existentes para validação JavaScript
        $existingCategories = Category::where('user_id', $user->id)
            ->orWhereNull('user_id')
            ->get(['name', 'type'])
            ->toArray();
        
        return view('categories.create', compact('isAdminView', 'existingCategories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermission('create_categories')) {
            abort(403, 'Você não tem permissão para criar categorias.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        // CORREÇÃO: Preservar o título original sem alterações
        $categoryName = trim($validated['name']);
        
        // CORREÇÃO: Verificação mais rigorosa de duplicatas (case-insensitive)
        $existingCategory = Category::where(function($query) use ($categoryName) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($categoryName)]);
            })
            ->where('type', $validated['type'])
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereNull('user_id');
            })
            ->first();

        if ($existingCategory) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => 'Já existe uma categoria com este nome e tipo. Categoria existente: "' . $existingCategory->name . '"']);
        }

        // CORREÇÃO: Garantir que o nome seja preservado exatamente como digitado
        $validated['name'] = $categoryName;
        $validated['user_id'] = $user->id;

        Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(Category $category)
    {
        $user = Auth::user();
        $canEditAll = $user->hasPermission('edit_all_categories');
        $canEditOwn = $user->hasPermission('edit_own_categories');

        // Allow edit if user can edit all, or if it's their own category, 
        // or if it's a global category (user_id is null) and they have permission to edit own (implicitly system categories).
        // More specific permission for global categories like 'edit_global_categories' could be added.
        if (!($canEditAll || ($canEditOwn && $category->user_id === $user->id) || ($canEditOwn && is_null($category->user_id)) )) {
            abort(403, 'Você não tem permissão para editar esta categoria.');
        }
        
        $isAdminView = $user->hasRole('Administrador');
        
        // CORREÇÃO: Buscar categorias existentes para validação JavaScript
        $existingCategories = Category::where('user_id', $user->id)
            ->orWhereNull('user_id')
            ->get(['id', 'name', 'type'])
            ->toArray();
        
        return view('categories.edit', compact('category', 'isAdminView', 'existingCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $user = Auth::user();
        $canEditAll = $user->hasPermission('edit_all_categories');
        $canEditOwn = $user->hasPermission('edit_own_categories');

        if (!($canEditAll || ($canEditOwn && $category->user_id === $user->id) || ($canEditOwn && is_null($category->user_id)) )) {
            abort(403, 'Você não tem permissão para atualizar esta categoria.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        // CORREÇÃO: Preservar o título original sem alterações
        $categoryName = trim($validated['name']);
        
        // CORREÇÃO: Verificação mais rigorosa de duplicatas (case-insensitive)
        // excluindo a categoria atual da verificação
        $existingCategory = Category::where(function($query) use ($categoryName) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($categoryName)]);
            })
            ->where('type', $validated['type'])
            ->where('id', '!=', $category->id)
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereNull('user_id');
            })
            ->first();

        if ($existingCategory) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => 'Já existe uma categoria com este nome e tipo. Categoria existente: "' . $existingCategory->name . '"']);
        }

        // CORREÇÃO: Garantir que o nome seja preservado exatamente como digitado
        $validated['name'] = $categoryName;

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Category $category)
    {
        $user = Auth::user();
        $canDeleteAll = $user->hasPermission('delete_all_categories');
        $canDeleteOwn = $user->hasPermission('delete_own_categories');

        if (!($canDeleteAll || ($canDeleteOwn && $category->user_id === $user->id) || ($canDeleteOwn && is_null($category->user_id)) )) {
            abort(403, 'Você não tem permissão para excluir esta categoria.');
        }
        
        try {
            // Prevent deletion of categories in use by transactions.
            if ($category->transactions()->count() > 0) {
                return redirect()->route('categories.index')
                    ->with('error', 'Não foi possível excluir esta categoria. Ela está em uso em transações.');
            }
            $category->delete();
            return redirect()->route('categories.index')
                ->with('success', 'Categoria excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('categories.index')
                ->with('error', 'Não foi possível excluir esta categoria. Ela pode estar em uso em transações.');
        }
    }
}