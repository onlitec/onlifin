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
        
        if (!$user->hasPermission('view_all_categories')) {
            if ($user->hasPermission('view_own_categories')) {
                $query->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereNull('user_id'); // Allows viewing system-wide (null user_id) categories
                });
            } else {
                abort(403, 'Você não tem permissão para visualizar categorias.');
            }
        }
        // Admins see all categories, including user-specific ones if they exist with user_id set.
        
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
        return view('categories.create', compact('isAdminView'));
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

        // For now, new categories created by users are associated with them.
        // Admins could potentially create global categories (user_id = null) through a different mechanism or UI if needed.
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
        return view('categories.edit', compact('category', 'isAdminView'));
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

        // Prevent non-admins from changing ownership or making a user-category global.
        // If an admin is editing, they could be allowed to change user_id or set it to null if a UI for that exists.
        // For now, user_id is not part of $validated here for update for simplicity.

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