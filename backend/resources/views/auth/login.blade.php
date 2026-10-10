@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-xl rounded-3xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
    <h1 class="text-3xl font-semibold text-slate-900">Connexion</h1>
    <p class="mt-2 text-sm text-slate-500">Utilisez un compte de démonstration pour accéder au dashboard.</p>

    <form action="{{ route('login.store') }}" method="POST" class="mt-8 space-y-6">
        @csrf
        <div class="grid gap-4">
            <label class="block">
                <span class="text-sm font-medium text-slate-700">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </label>
            <label class="block">
                <span class="text-sm font-medium text-slate-700">Mot de passe</span>
                <input type="password" name="password" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </label>
        </div>
        <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-white hover:bg-slate-800">Se connecter</button>
    </form>

    <div class="mt-6 rounded-3xl bg-slate-50 p-4 text-sm text-slate-600">
        <p class="font-semibold text-slate-800">Comptes de démonstration :</p>
        <ul class="mt-3 space-y-2">
            <li>admin@tontine.saas / admin123</li>
            <li>test@tontine.saas / test123</li>
            <li>manager@tontine.saas / manager123</li>
        </ul>
    </div>
</div>
@endsection
