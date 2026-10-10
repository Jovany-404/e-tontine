// Détail complet d'une tontine

import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { tontineService } from '../services/tontines'
import type { Tontine } from '../types'

export default function TontineDetail() {
  const { id } = useParams()
  const [tontine, setTontine] = useState<Tontine | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    const load = async () => {
      if (!id) return

      try {
        const res = await tontineService.getById(Number(id))
        setTontine(res.data ?? null)
      } catch (err: any) {
        setError(err.response?.data?.message || 'Erreur de chargement')
      } finally {
        setLoading(false)
      }
    }

    load()
  }, [id])

  if (loading) return <div className="p-6 text-slate-600">Chargement...</div>

  if (error) {
    return <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
  }

  if (!tontine) return <div className="rounded-2xl bg-white p-6 shadow-sm">Tontine introuvable.</div>

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-slate-900">{tontine.nom}</h1>
          <p className="mt-1 text-slate-500">Détail complet de la tontine</p>
        </div>

        <div className="flex items-center gap-3">
          <Link
            to={`/tontines/${tontine.id}/edit`}
            className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
          >
            Modifier
          </Link>
          <Link
            to="/tontines"
            className="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
          >
            Retour
          </Link>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-2xl bg-white p-6 shadow-sm lg:col-span-2">
          <h2 className="text-lg font-semibold text-slate-900">Informations</h2>

          <div className="mt-4 grid gap-4 md:grid-cols-2">
            <div>
              <p className="text-sm text-slate-500">Description</p>
              <p className="font-medium text-slate-900">
                {tontine.description || 'Aucune description'}
              </p>
            </div>

            <div>
              <p className="text-sm text-slate-500">Statut</p>
              <p className="font-medium text-slate-900">{tontine.statut}</p>
            </div>

            <div>
              <p className="text-sm text-slate-500">Montant total</p>
              <p className="font-medium text-slate-900">{tontine.montant_total} FCFA</p>
            </div>

            <div>
              <p className="text-sm text-slate-500">Montant par membre</p>
              <p className="font-medium text-slate-900">{tontine.montant_membre} FCFA</p>
            </div>
          </div>
        </div>

        <div className="rounded-2xl bg-white p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-900">Progression</h2>

          <div className="mt-4">
            <div className="mb-2 flex items-center justify-between text-sm">
              <span className="text-slate-500">Avancement</span>
              <span className="font-medium text-slate-900">{tontine.progression || 0}%</span>
            </div>

            <div className="h-3 w-full rounded-full bg-slate-200">
              <div
                className="h-3 rounded-full bg-sky-600"
                style={{ width: `${tontine.progression || 0}%` }}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}