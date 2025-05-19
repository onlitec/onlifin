@extends('layouts.app')

@section('title', 'Editar Grupo')

@section('content')
<div class="py-6">
    <div class="max-w-xl mx-auto">
        <h2 class="text-2xl font-bold mb-4">Editar Grupo</h2>
        <form action="{{ route('groups.update', $group) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="name" id="name" class="form-input mt-1 block w-full" required value="{{ old('name', $group->name) }}">
                @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                <input type="text" name="description" id="description" class="form-input mt-1 block w-full" value="{{ old('description', $group->description) }}">
                @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="users" class="block text-sm font-medium text-gray-700">Usuários</label>
                <select name="users[]" id="users" class="form-multiselect mt-1 block w-full" multiple>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ in_array($user->id, old('users', $groupUsers)) ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label for="roles" class="block text-sm font-medium text-gray-700">Perfis</label>
                <select name="roles[]" id="roles" class="form-multiselect mt-1 block w-full" multiple>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ in_array($role->id, old('roles', $groupRoles)) ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end">
                <a href="{{ route('groups.index') }}" class="btn btn-secondary mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection 