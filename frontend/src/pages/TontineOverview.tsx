// Vue d'ensemble et démarrage du premier cycle d'une tontine.

import { useCallback, useEffect, useState } from 'react'
import axios from 'axios'
import { CalendarDays, CircleDollarSign, Loader2, Play, Users } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import { cycleService } from '../services/cycles'
import { tontineService, type TontineSummary } from '../services/tontines'

interface ApiError {
  message?: string
  errors?: Record<string, string[]>
}

function getErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ApiError>(error)) {
    return 'Une erreur inattendue est survenue.'
  }

  const validationMessage = Object.values(error.response?.data?.errors ?? {})
    .flat()
    .find((message) => typeof message === 'string')

  return validationMessage
    ?? error.response?.data?.message
    ?? 'Impossible de communiquer avec le serveur.'
}

function formatAmount(amount: number): string {
  return `${new Intl.NumberFormat('fr-FR').format(amount)} FCFA`
}

function formatDate(date: string): string {
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(
    new Date(`${date}T00:00:00`),
  )
}

const frequencyLabels: Record<TontineSummary['frequency'], string> = {
  daily: 'Quotidienne',
  weekly: 'Hebdomadaire',
  monthly: 'Mensuelle',
}

const statusLabels: Record<TontineSummary['status'], string> = {
  draft: 'Brouillon',
  active: 'En cours',
  completed: 'Terminée',
}

export default function TontineOverview() {
  const { id = '' } = useParams()
  const [tontine, setTontine] = useState<TontineSummary | null>(null)
  const [loading, setLoading] = useState(true)
  const [starting, setStarting] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const loadTontine = useCallback(async () => {
    if (!id) {
      setError('Identifiant de tontine manquant.')
      setLoading(false)
      return
    }

    setLoading(true)
    setError('')

    try {
      const response = await tontineService.getById(id)
      const data = response?.data

      if (!data || typeof data.id !== 'string') {
        throw new Error('Réponse invalide lors du chargement de la tontine.')
      }

      setTontine(data as TontineSummary)
    } catch (loadError) {
      setError(getErrorMessage(loadError))
      setTontine(null)
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => {
    void loadTontine()
  }, [loadTontine])

  const handleStartCycle = async () => {
    if (!tontine || tontine.status !== 'draft' || tontine.my_role !== 'manager') return

    const confirmed = window.confirm(
      'Démarrer le premier cycle ? La tontine passera en cours et les échéances seront créées pour tous les membres actifs.',
    )

    if (!confirmed) return

    setStarting(true)
    setError('')
    setSuccess('')

    try {
      const cycle = await cycleService.startFirst(tontine.id)
      setTontine((current) => current ? { ...current, status: 'active' } : current)
      setSuccess(
        `Cycle ${cycle.number} démarré. ${cycle.schedules_count} échéances ont été créées pour ${cycle.members_count} membres.`,
      )
    } catch (startError) {
      setError(getErrorMessage(startError))
    } finally {
      setStarting(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center gap-3 p-6 text-slate-600" role="status">
        <Loader2 className="animate-spin" size={20} aria-hidden="true" />
        Chargement de la tontine…
      </div>
    )
  }

  if (!tontine) {
    return (
      <div className="space-y-4">
        <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
          {error || 'Tontine introuvable.'}
        </div>
        <Link to="/tontines" className="font-medium text-sky-700 hover:text-sky-900">
          Retour aux tontines
        </Link>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <p className="text-sm font-medium text-slate-500">
            Gérée par {tontine.manager.name}
          </p>
          <h1 className="mt-1 text-3xl font-bold text-slate-900">{tontine.name}</h1>
          <p className="mt-2 max-w-2xl text-slate-600">
            {tontine.description || 'Aucune description'}
          </p>
        </div>
        <span className="inline-flex w-fit rounded-full bg-sky-50 px-3 py-1.5 text-sm font-semibold text-sky-800">
          {statusLabels[tontine.status]}
        </span>
      </header>

      {error && (
        <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
          {error}
        </div>
      )}
      {success && (
        <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
          {success}
        </div>
      )}

      <section aria-label="Informations de la tontine" className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <InfoCard
          icon={<CircleDollarSign size={20} aria-hidden="true" />}
          label="Cotisation par membre et par tour"
          value={formatAmount(tontine.contribution_amount)}
        />
        <InfoCard
          icon={<Users size={20} aria-hidden="true" />}
          label="Membres actifs"
          value={`${tontine.members_count} sur ${tontine.expected_members}`}
        />
        <InfoCard
          icon={<CalendarDays size={20} aria-hidden="true" />}
          label={`Fréquence · début prévu`}
          value={`${frequencyLabels[tontine.frequency]} · ${formatDate(tontine.start_date)}`}
        />
      </section>

      {tontine.status === 'draft' && tontine.my_role === 'manager' && (
        <section className="rounded-2xl border border-sky-200 bg-sky-50 p-5 sm:p-6">
          <h2 className="text-lg font-semibold text-slate-900">Prêt à démarrer ?</h2>
          <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
            Le démarrage active la tontine et crée les échéances de chaque tour pour les membres actifs.
            Vérifie les membres et les paramètres avant de continuer.
          </p>
          <button
            type="button"
            onClick={() => void handleStartCycle()}
            disabled={starting}
            className="mt-5 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 font-semibold text-white transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
          >
            {starting
              ? <Loader2 className="animate-spin" size={18} aria-hidden="true" />
              : <Play size={18} aria-hidden="true" />}
            {starting ? 'Démarrage du cycle…' : 'Démarrer le premier cycle'}
          </button>
        </section>
      )}
    </div>
  )
}

function InfoCard({
  icon,
  label,
  value,
}: {
  icon: React.ReactNode
  label: string
  value: string
}) {
  return (
    <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
      <div className="flex items-start gap-3">
        <span className="rounded-xl bg-sky-50 p-2 text-sky-700">{icon}</span>
        <div>
          <p className="text-sm text-slate-500">{label}</p>
          <p className="mt-1 font-semibold text-slate-900">{value}</p>
        </div>
      </div>
    </div>
  )
}
