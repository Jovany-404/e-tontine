// Liste des cotisations d'une tontine

import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { DollarSign } from 'lucide-react'
import api from '../services/api'

export default function Contributions() {
  const { tontineId } = useParams()
  const [items, setItems] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    const load = async () => {
      try {
        if (!tontineId) return
        const { data } = await api.get(`/tontines/${tontineId}/contributions`)
        setItems(data?.data ?? data ?? [])
      } catch (err: any) {
        setError(err.response?.data?.message || 'Erreur de chargement des cotisations')
      } finally {
        setLoading(false)
      }
    }

    load()
  }, [tontineId])

  if (loading) return <div className="p-6 text-slate-600">Chargement...</div>

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-slate-900">Cotisations</h1>
        <p className="mt-1 text-slate-500">Historique des paiements</p>
      </div>

      {error && <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      <div className="rounded-2xl bg-white shadow-sm">
        {items.length === 0 ? (
          <div className="p-6 text-sm text-slate-500">Aucune cotisation trouvée.</div>
        ) : (
          <div className="divide-y divide-slate-200">
            {items.map((item) => (
              <div key={item.id} className="flex items-center justify-between p-6">
                <div className="flex items-center gap-3">
                  <div className="rounded-xl bg-emerald-50 p-3 text-emerald-700">
                    <DollarSign size={18} />
                  </div>
                  <div>
                    <p className="font-medium text-slate-900">{item.member_name || 'Membre'}</p>
                    <p className="text-sm text-slate-500">{item.date_paiement || item.created_at}</p>
                  </div>
                </div>

                <div className="text-right">
                  <p className="font-semibold text-slate-900">{item.montant} FCFA</p>
                  <p className="text-sm text-slate-500">{item.statut || 'N/A'}</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}