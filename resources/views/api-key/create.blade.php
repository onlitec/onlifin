@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Adicionar Chave API</h1>
    <form action="{{ route('api-key.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="api_key">Chave API</label>
            <input type="text" name="api_key" id="api_key" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="name">Nome Opcional</label>
            <input type="text" name="name" id="name" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>
@endsection
