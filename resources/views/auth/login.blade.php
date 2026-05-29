<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h1 class="text-2xl font-bold text-center mb-6">{{ config('app.name') }}</h1>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Honeypot --}}
            <div style="display:none" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off" value="">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    style="font-size: 16px;"
                >
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    style="font-size: 16px;"
                >
            </div>

            <div class="mb-6 flex items-center">
                <input type="checkbox" name="remember" id="remember" class="mr-2">
                <label for="remember" class="text-sm text-gray-600">Manter sessão</label>
            </div>

            <button
                type="submit"
                class="w-full py-2 px-4 bg-amber-600 text-white font-medium rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                style="min-height: 44px;"
            >
                Entrar
            </button>

            <div class="mt-4 text-center">
                <a href="{{ route('password.request') }}" class="text-sm text-amber-600 hover:underline">
                    Esqueceste a password?
                </a>
            </div>
        </form>
    </div>
</body>
</html>
