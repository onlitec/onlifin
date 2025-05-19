@extends('layouts.app')

@section('title', 'Novo Grupo')

@section('content')
<div class="py-6">
    <div class="max-w-xl mx-auto">
        <h2 class="text-2xl font-bold mb-4">Novo Grupo</h2>
        <form action="{{ route('groups.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="name" id="name" class="form-input mt-1 block w-full" required value="{{ old('name') }}">
                @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                <input type="text" name="description" id="description" class="form-input mt-1 block w-full" value="{{ old('description') }}">
                @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="users" class="block text-sm font-medium text-gray-700">Usuários</label>
                <select name="users[]" id="users" class="form-multiselect mt-1 block w-full" multiple>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label for="roles" class="block text-sm font-medium text-gray-700">Perfis</label>
                <select name="roles[]" id="roles" class="form-multiselect mt-1 block w-full" multiple>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
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