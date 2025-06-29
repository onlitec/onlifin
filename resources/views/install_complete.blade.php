@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-16 p-6 bg-green-50 border border-green-200 rounded-lg">
    <h2 class="text-2xl font-semibold mb-4 text-green-800">Instalação concluída com sucesso!</h2>
    <p class="mb-4 text-green-700">Use as credenciais abaixo para fazer login:</p>
    <ul class="list-none mb-4">
        @foreach($credentials as $cred)
            <li class="flex items-center justify-between mb-2">
                <div><span class="font-medium">{{ $cred['email'] }}</span> / <span class="font-medium">{{ $cred['password'] }}</span></div>
                <a href="{{ route('login', ['email' => $cred['email'], 'password' => $cred['password']]) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Entrar como {{ $cred['email'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endsection 