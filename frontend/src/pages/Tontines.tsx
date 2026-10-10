// Liste des tontines

import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Plus, Trash2, Pencil } from 'lucide-react'
import { tontineService } from '../services/tontines'
import type { Tontine } from '../types'

export default function Tontines() {
  const navigate = useNavigate()
  const [tontines, setTontines] = useState<Tontine[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const load = async () => {
    try {
      const res = await tontineService.getAll()
      setTontines(res.data ?? [])
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur de chargement')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [])

  const handleDelete = async (id: number) => {
    const ok = window.confirm('Voulez-vous vraiment supprimer cette tontine ?')
    if (!ok) return

    try {
      await tontineService.delete(id)
      await load()
    } catch (err: any) {
      setError(err.response?.data?.message || 'Suppression impossible')
    }
  }

  if (loading) return <div className="p-6 text-slate-600">Chargement...</div>

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-slate-900">Tontines</h1>
          <p className="mt-1 text-slate-500">Gestion des tontines</p>
        </div>

        <Link
          to="/tontines/create"
          className="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white hover:bg-sky-700"
        >
          <Plus size={16} />
          Nouvelle tontine
        </Link>
      </div>

      {error && <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      <div className="grid gap-4">
        {tontines.length === 0 ? (
          <div className="rounded-2xl bg-white p-6 shadow-sm">
            <p className="text-sm text-slate-500">Aucune tontine trouvée.</p>
          </div>
        ) : (
          tontines.map((tontine) => (
            <div key={tontine.id} className="rounded-2xl bg-white p-6 shadow-sm">
              <div className="flex items-start justify-between gap-4">
                <div>
                  <h2 className="text-lg font-semibold text-slate-900">{tontine.nom}</h2>
                  <p className="mt-1 text-sm text-slate-500">{tontine.description || 'Aucune description'}</p>
                  <p className="mt-2 text-sm text-slate-600">
                    {tontine.nombre_membres} membres · {tontine.montant_total} FCFA
                  </p>
                </div>

                <span className="rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700">
                  {tontine.statut}
                </span>
              </div>

              <div className="mt-4 flex items-center gap-3">
                <button
                  type="button"
                  onClick={() => navigate(`/tontines/${tontine.id}/edit`)}
                  className="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
                >
                  <Pencil size={16} />
                  Modifier
                </button>

                <button
                  type="button"
                  onClick={() => handleDelete(tontine.id)}
                  className="inline-flex items-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-sm text-red-700 hover:bg-red-50"
                >
                  <Trash2 size={16} />
                  Supprimer
                </button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  )
}