import { useCallback, useEffect, useState } from 'react'
import axios from 'axios'
import { AlertCircle, CalendarDays, Loader2, RefreshCw, Trophy } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import TontineTabs from '../components/layout/TontineTabs'
import { drawService, type DrawResult } from '../services/draws'
import { tontineService, type TontineSummary } from '../services/tontines'

interface ApiError {
  message?: string
  errors?: Record<string, string[]>
}

function formatAmount(amount: number): string {
  return `${new Intl.NumberFormat('fr-FR').format(amount)} FCFA`
}

function formatDate(date: string | null): string {
  if (!date) return 'Date inconnue'

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(date))
}

function getErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ApiError>(error)) {
    return error instanceof Error ? error.message : 'Une erreur inattendue est survenue.'
  }

  const validationMessage = Object.values(error.response?.data?.errors ?? {})
    .flat()
    .find((message) => typeof message === 'string')

  return validationMessage
    ?? error.response?.data?.message
    ?? 'Impossible de communiquer avec le serveur.'
}

export default function Draws() {
  const { tontineId = '' } = useParams()
  const [tontine, setTontine] = useState<TontineSummary | null>(null)
  const [draws, setDraws] = useState<DrawResult[]>([])
  const [loading, setLoading] = useState(true)
  const [drawing, setDrawing] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const load = useCallback(async () => {
    if (!tontineId) {
      setError('Identifiant de tontine manquant.')
      setLoading(false)
      return
    }

    setLoading(true)
    setError('')

    try {
      const [tontineResponse, nextDraws] = await Promise.all([
        tontineService.getById(tontineId),
        drawService.getAll(tontineId),
      ])
      const nextTontine = tontineResponse?.data as TontineSummary | undefined

      if (!nextTontine || typeof nextTontine.id !== 'string' || !Array.isArray(nextDraws)) {
        throw new Error('Réponse invalide lors du chargement des tirages.')
      }

      setTontine(nextTontine)
      setDraws(nextDraws)
    } catch (loadError) {
      setError(getErrorMessage(loadError))
      setTontine(null)
      setDraws([])
    } finally {
      setLoading(false)
    }
  }, [tontineId])

  useEffect(() => {
    void load()
  }, [load])

  const handleDraw = async () => {
    if (!tontine || tontine.my_role !== 'manager' || tontine.status !== 'active' || drawing) return

    setDrawing(true)
    setError('')
    setSuccess('')

    try {
      const result = await drawService.drawNext(tontine.id)
      setSuccess(
        result.cycle_completed
          ? `Cycle terminé. ${result.draw.winner.name} reçoit ${formatAmount(result.draw.amount)}.`
          : `Tour ${result.draw.round} tiré : ${result.draw.winner.name} reçoit ${formatAmount(result.draw.amount)}.`,
      )
      await load()
    } catch (drawError) {
      setError(getErrorMessage(drawError))
    } finally {
      setDrawing(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center gap-3 p-6 text-slate-600" role="status">
        <Loader2 className="animate-spin" size={20} aria-hidden="true" />
        Chargement des tirages…
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

  const canDraw = tontine.my_role === 'manager' && tontine.status === 'active'

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <header>
        <Link to={`/tontines/${tontine.id}`} className="text-sm font-medium text-sky-700 hover:text-sky-900">
          {tontine.name}
        </Link>
        <div className="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h1 className="text-3xl font-bold text-slate-900">Tirages</h1>
            <p className="mt-1 text-slate-600">Résultats des bénéficiaires par cycle et par tour.</p>
          </div>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => void load()}
              className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
            >
              <RefreshCw size={16} aria-hidden="true" />
              Actualiser
            </button>
            {canDraw && (
              <button
                type="button"
                onClick={() => void handleDraw()}
                disabled={drawing}
                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600"
              >
                {drawing && <Loader2 className="animate-spin" size={16} aria-hidden="true" />}
                {drawing ? 'Tirage en cours…' : 'Tirer le prochain bénéficiaire'}
              </button>
            )}
          </div>
        </div>
      </header>

      <TontineTabs />

      {error && (
        <div className="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
          <AlertCircle className="mt-0.5 shrink-0" size={17} aria-hidden="true" />
          <span>{error}</span>
        </div>
      )}
      {success && (
        <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
          {success}
        </div>
      )}

      {draws.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center">
          <CalendarDays className="mx-auto text-slate-400" size={28} aria-hidden="true" />
          <h2 className="mt-3 font-semibold text-slate-900">Aucun tirage pour le moment</h2>
          <p className="mt-1 text-sm text-slate-600">
            {canDraw
              ? 'Le gérant pourra lancer le tirage lorsque toutes les cotisations du tour seront encaissées.'
              : 'Les résultats apparaîtront ici après le lancement des tirages par le gérant.'}
          </p>
        </div>
      ) : (
        <ol className="space-y-4">
          {draws.map((draw) => (
            <li key={draw.id} className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
              <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex min-w-0 items-center gap-3">
                  <span className="rounded-xl bg-amber-50 p-3 text-amber-700">
                    <Trophy size={19} aria-hidden="true" />
                  </span>
                  <div className="min-w-0">
                    <p className="text-sm text-slate-500">
                      Cycle {draw.cycle_number} · Tour {draw.round}
                    </p>
                    <h2 className="mt-0.5 truncate font-semibold text-slate-900">
                      {draw.winner.name}
                    </h2>
                    <p className="mt-0.5 text-sm text-slate-500">{formatDate(draw.drawn_at)}</p>
                  </div>
                </div>
                <div className="sm:text-right">
                  <p className="text-sm text-slate-500">Cagnotte distribuée</p>
                  <p className="mt-0.5 text-lg font-bold text-slate-900">{formatAmount(draw.amount)}</p>
                </div>
              </div>
            </li>
          ))}
        </ol>
      )}
    </div>
  )
}
