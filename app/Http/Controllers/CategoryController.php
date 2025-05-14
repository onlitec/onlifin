<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Verifica se o usuário é administrador
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Obtém o filtro de tipo da query string (padrão: todos)
        $typeFilter = $request->query('type', 'all');
        
        // Query base
        $query = Category::orderBy('name');
        
        // Aplica filtro por tipo se necessário
        if ($typeFilter === 'income') {
            $query->where('type', 'income');
        } elseif ($typeFilter === 'expense') {
            $query->where('type', 'expense');
        }
        
        // Se não for admin, filtra por usuário
        if (!$isAdmin) {
            $query->where('user_id', auth()->id());
        }
        
        $categories = $query->paginate(10)->appends(['type' => $typeFilter]);
        
        return view('categories.index', compact('categories', 'isAdmin', 'typeFilter'));
    }

    public function create()
    {
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        return view('categories.create', compact('isAdmin'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['user_id'] = auth()->id();

        $category = Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(Category $category)
    {
        // Verifica se o usuário é administrador
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a categoria não pertencer ao usuário, aborta
        if (!$isAdmin && $category->user_id !== auth()->id()) {
            abort(403);
        }
        
        return view('categories.edit', compact('category', 'isAdmin'));
    }

    public function update(Request $request, Category $category)
    {
        // Verifica se o usuário é administrador
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a categoria não pertencer ao usuário, aborta
        if (!$isAdmin && $category->user_id !== auth()->id()) {
            abort(403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Category $category)
    {
        // Verifica se o usuário é administrador
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a categoria não pertencer ao usuário, aborta
        if (!$isAdmin && $category->user_id !== auth()->id()) {
            abort(403);
        }
        
        try {
            $category->delete();
            return redirect()->route('categories.index')
                ->with('success', 'Categoria excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('categories.index')
                ->with('error', 'Não foi possível excluir esta categoria. Ela pode estar em uso em transações.');
        }
    }
} 