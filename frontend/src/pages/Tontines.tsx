// Liste des tontines auxquelles l'utilisateur participe.

import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Plus, Users, Wallet } from 'lucide-react'
import { tontineService, type TontineSummary } from '../services/tontines'

const statusLabels: Record<TontineSummary['status'], string> = {
  draft: 'Brouillon',
  active: 'En cours',
  completed: 'Terminée',
}

function formatAmount(amount: number): string {
  return `${new Intl.NumberFormat('fr-FR').format(amount)} FCFA`
}

export default function Tontines() {
  const [tontines, setTontines] = useState<TontineSummary[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false

    const load = async () => {
      try {
        const response = await tontineService.getAll()
        if (!cancelled) setTontines(response?.data ?? [])
      } catch (loadError) {
        if (!cancelled) {
          const message = (loadError as { response?: { data?: { message?: string } } })
            .response?.data?.message
          setError(message || 'Impossible de charger les tontines.')
        }
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void load()
    return () => {
      cancelled = true
    }
  }, [])

  if (loading) {
    return <div className="p-6 text-slate-600" role="status">Chargement des tontines…</div>
  }

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-3xl font-bold text-slate-900">Mes tontines</h1>
          <p className="mt-1 text-slate-500">Groupes dont tu es membre actif.</p>
        </div>
        <Link
          to="/tontines/create"
          className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
        >
          <Plus size={17} aria-hidden="true" />
          Nouvelle tontine
        </Link>
      </header>

      {error && (
        <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
          {error}
        </div>
      )}

      {tontines.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
          <Users className="mx-auto text-slate-400" size={28} aria-hidden="true" />
          <h2 className="mt-3 font-semibold text-slate-900">Aucune tontine pour le moment</h2>
          <p className="mt-1 text-sm text-slate-600">
            Crée une tontine ou utilise un lien d’invitation pour demander à rejoindre un groupe.
          </p>
        </div>
      ) : (
        <ul className="grid gap-4">
          {tontines.map((tontine) => (
            <li key={tontine.id} className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
              <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="min-w-0">
                  <div className="flex flex-wrap items-center gap-3">
                    <h2 className="text-lg font-semibold text-slate-900">{tontine.name}</h2>
                    <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                      {statusLabels[tontine.status]}
                    </span>
                  </div>
                  <p className="mt-1 text-sm text-slate-500">
                    Gérant : {tontine.manager.name} · Rôle : {tontine.my_role === 'manager' ? 'Gérant' : 'Membre'}
                  </p>
                  <div className="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600">
                    <span className="inline-flex items-center gap-2">
                      <Users size={16} aria-hidden="true" />
                      {tontine.members_count} / {tontine.expected_members} membres
                    </span>
                    <span className="inline-flex items-center gap-2">
                      <Wallet size={16} aria-hidden="true" />
                      {formatAmount(tontine.contribution_amount)} par tour
                    </span>
                  </div>
                </div>
                <Link
                  to={`/tontines/${tontine.id}`}
                  className="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
                >
                  Ouvrir la tontine
                </Link>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
