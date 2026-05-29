<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Alterar password - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h1 class="text-2xl font-bold text-center mb-2">Alterar password</h1>
        <p class="text-gray-600 text-center mb-6">Tens de definir uma nova password antes de continuar.</p>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf

            <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Password actual</label>
                <input
                    id="current_password"
                    type="password"
                    name="current_password"
                    required
                    autocomplete="current-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    style="font-size: 16px;"
                >
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nova password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    style="font-size: 16px;"
                >
                <p class="mt-1 text-xs text-gray-500">Minimo 8 caracteres, letras maiusculas e minusculas, e numeros.</p>
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    style="font-size: 16px;"
                >
            </div>

            <button
                type="submit"
                class="w-full py-2 px-4 bg-amber-600 text-white font-medium rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                style="min-height: 44px;"
            >
                Alterar password
            </button>

            <div class="mt-4 text-center">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:underline">Sair</button>
                </form>
            </div>
        </form>
    </div>
</body>
</html>
