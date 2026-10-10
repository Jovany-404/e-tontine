@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-700 to-slate-900 p-8 text-white shadow-xl">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-slate-300">Tableau de bord</p>
                <h1 class="mt-2 text-3xl font-semibold">Vue d’ensemble de la tontine</h1>
                <p class="mt-2 max-w-2xl text-slate-300">Suivez les membres, les paiements et la progression globale de la collecte.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-3xl bg-white/10 px-5 py-4 backdrop-blur-sm">
                    <p class="text-sm text-slate-300">Total tontines</p>
                    <p class="mt-3 text-3xl font-semibold">{{ $data['total_tontines'] }}</p>
                </div>
                <div class="rounded-3xl bg-white/10 px-5 py-4 backdrop-blur-sm">
                    <p class="text-sm text-slate-300">Tontines actives</p>
                    <p class="mt-3 text-3xl font-semibold">{{ $data['tontines_actives'] }}</p>
                </div>
                <div class="rounded-3xl bg-white/10 px-5 py-4 backdrop-blur-sm">
                    <p class="text-sm text-slate-300">Membres</p>
                    <p class="mt-3 text-3xl font-semibold">{{ $data['total_membres'] }}</p>
                </div>
                <div class="rounded-3xl bg-white/10 px-5 py-4 backdrop-blur-sm">
                    <p class="text-sm text-slate-300">Membres en retard</p>
                    <p class="mt-3 text-3xl font-semibold">{{ $data['membres_retard'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-900">Performance financière</p>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Progrès</span>
            </div>
            <div class="mt-6 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Total collecté</p>
                        <p class="mt-2 text-2xl font-semibold">{{ number_format($data['total_colle'], 0, ',', ' ') }} XOF</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Total attendu</p>
                        <p class="mt-2 text-2xl font-semibold">{{ number_format($data['total_expected'], 0, ',', ' ') }} XOF</p>
                    </div>
                </div>
                <div>
                    <div class="overflow-hidden rounded-full bg-slate-100">
                        <div class="h-3 rounded-full bg-gradient-to-r from-emerald-600 to-cyan-500" style="width: {{ $data['progression_global'] }}%"></div>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">{{ $data['progression_global'] }}% du montant attendu</p>
                </div>
            </div>
        </div>

        <div class="xl:col-span-2 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-900">Activités récentes</p>
            </div>
            <div class="mt-6 space-y-3">
                @forelse($data['recent_activities'] as $activity)
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">{{ $activity['action'] }} · {{ ucfirst($activity['entite']) }}</p>
                        <p class="mt-2 text-slate-900">{{ $activity['description'] }}</p>
                        <p class="mt-2 text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($activity['created_at'])->translatedFormat('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="rounded-3xl bg-slate-50 p-4 text-sm text-slate-500">Aucune activité enregistrée récemment.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Données de collecte</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Tontines en attente</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $data['tontines_en_attente'] }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Tontines actives</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $data['tontines_actives'] }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Membres payés</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $data['total_membres'] - $data['membres_retard'] }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Membres en retard</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $data['membres_retard'] }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Paiements et suivi</h2>
            <div class="mt-6">
                <p class="text-sm text-slate-500">Total collecté pour toutes les tontines</p>
                <p class="mt-2 text-3xl font-semibold">{{ number_format($data['total_colle'], 0, ',', ' ') }} XOF</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Estimé restant</p>
                        <p class="mt-2 text-2xl font-semibold">{{ number_format($data['total_expected'] - $data['total_colle'], 0, ',', ' ') }} XOF</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Progression moyenne</p>
                        <p class="mt-2 text-2xl font-semibold">{{ $data['progression_global'] }}%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
