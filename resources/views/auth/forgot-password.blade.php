<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar password - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h1 class="text-2xl font-bold text-center mb-2">Recuperar password</h1>
        <p class="text-gray-600 text-center mb-6">Insere o teu email para receber o link de recuperação.</p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded" role="alert">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-6">
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

            <button
                type="submit"
                class="w-full py-2 px-4 bg-amber-600 text-white font-medium rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                style="min-height: 44px;"
            >
                Enviar link de recuperação
            </button>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-amber-600 hover:underline">Voltar ao login</a>
            </div>
        </form>
    </div>
</body>
</html>
