<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Tontine SaaS') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css">
    @endif
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen font-sans">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="text-lg font-semibold text-slate-900">Tontine SaaS</a>
                <nav class="flex items-center gap-4 text-sm text-slate-600">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-full px-4 py-2 bg-slate-100 text-slate-900 hover:bg-slate-200">Dashboard</a>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="rounded-full px-4 py-2 bg-emerald-500 text-white hover:bg-emerald-600">Déconnexion</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full px-4 py-2 bg-slate-100 text-slate-900 hover:bg-slate-200">Connexion</a>
                        <a href="{{ route('register') }}" class="rounded-full px-4 py-2 bg-slate-100 text-slate-900 hover:bg-slate-200">Inscription</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="flex-1 mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-900">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-900">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
