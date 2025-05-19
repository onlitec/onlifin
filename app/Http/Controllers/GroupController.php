<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::with(['users', 'roles'])->paginate(10);
        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        $users = User::all();
        $roles = Role::all();
        return view('groups.create', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'users' => 'array',
            'roles' => 'array',
        ]);

        $group = Group::create($request->only(['name', 'description']));
        $group->users()->sync($request->input('users', []));
        $group->roles()->sync($request->input('roles', []));

        return redirect()->route('groups.index')->with('message', 'Grupo criado com sucesso!');
    }

    public function edit(Group $group)
    {
        $users = User::all();
        $roles = Role::all();
        $groupUsers = $group->users->pluck('id')->toArray();
        $groupRoles = $group->roles->pluck('id')->toArray();
        return view('groups.edit', compact('group', 'users', 'roles', 'groupUsers', 'groupRoles'));
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'users' => 'array',
            'roles' => 'array',
        ]);

        $group->update($request->only(['name', 'description']));
        $group->users()->sync($request->input('users', []));
        $group->roles()->sync($request->input('roles', []));

        return redirect()->route('groups.index')->with('message', 'Grupo atualizado com sucesso!');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return redirect()->route('groups.index')->with('message', 'Grupo excluído com sucesso!');
    }
} 