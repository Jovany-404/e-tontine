@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-10 py-10">
    <section class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 p-12 text-white shadow-2xl ring-1 ring-white/10">
        <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
            <div class="space-y-6">
                <p class="inline-flex rounded-full bg-emerald-500/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-300">Tontine SaaS</p>
                <h1 class="max-w-2xl text-4xl font-semibold tracking-tight text-white sm:text-5xl">Pilotez votre tontine en quelques clics.</h1>
                <p class="max-w-xl text-slate-300">Une interface simple pour suivre les cotisations, les membres et les échéances. Connectez-vous ou créez un compte de démonstration pour accéder au dashboard.</p>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-400">Se connecter</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-6 py-3 text-sm font-semibold text-white hover:bg-white/15">Créer un compte</a>
                </div>
            </div>
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl">
                <p class="text-sm uppercase tracking-[0.24em] text-slate-300">Démonstration</p>
                <div class="mt-6 space-y-4 text-sm text-slate-200">
                    <p>Comptes de test disponibles :</p>
                    <ul class="space-y-2">
                        <li class="rounded-2xl bg-slate-950/70 px-4 py-3">admin@tontine.saas / admin123</li>
                        <li class="rounded-2xl bg-slate-950/70 px-4 py-3">test@tontine.saas / test123</li>
                        <li class="rounded-2xl bg-slate-950/70 px-4 py-3">manager@tontine.saas / manager123</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-3">
        <div class="rounded-[1.75rem] border border-slate-200/10 bg-white/90 p-8 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Suivi des contributions</h2>
            <p class="mt-3 text-sm text-slate-600">Visualisez rapidement les montants collectés, le solde restant et les contributions en retard.</p>
        </div>
        <div class="rounded-[1.75rem] border border-slate-200/10 bg-white/90 p-8 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Gestion des membres</h2>
            <p class="mt-3 text-sm text-slate-600">Suivez chaque membre, son statut et son historique de paiement dans une tontine.</p>
        </div>
        <div class="rounded-[1.75rem] border border-slate-200/10 bg-white/90 p-8 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Rapports instantanés</h2>
            <p class="mt-3 text-sm text-slate-600">Accédez en un clin d'œil aux indicateurs clés pour mieux piloter les collectes.</p>
        </div>
    </section>
</div>
@endsection
