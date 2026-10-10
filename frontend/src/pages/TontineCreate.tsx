// Création d'une tontine

import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { tontineService } from '../services/tontines'
import type { CreateTontineData } from '../types'

export default function TontineCreate() {
  const navigate = useNavigate()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const [formData, setFormData] = useState<CreateTontineData>({
    nom: '',
    description: '',
    montant_total: 0,
    montant_membre: 0,
    nombre_membres: 0,
    nombre_cycles: 0,
    date_debut: '',
    date_fin: '',
    type_rotation: 'chrono',
    delai_paiement_jours: 5,
    notification_active: true,
  })

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError('')

    try {
      const res = await tontineService.create(formData)
      navigate(`/tontines/${res.data?.id}`)
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur de création')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-slate-900">Créer une tontine</h1>
          <p className="mt-1 text-slate-500">Nouvelle tontine</p>
        </div>

        <Link
          to="/tontines"
          className="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
        >
          Retour
        </Link>
      </div>

      <form onSubmit={handleSubmit} className="rounded-2xl bg-white p-6 shadow-sm">
        {error && <div className="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

        <div className="grid gap-4 md:grid-cols-2">
          <div className="md:col-span-2">
            <label className="mb-2 block text-sm font-medium text-slate-700">Nom</label>
            <input
              type="text"
              value={formData.nom}
              onChange={(e) => setFormData({ ...formData, nom: e.target.value })}
              className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
              required
            />
          </div>

          <div className="md:col-span-2">
            <label className="mb-2 block text-sm font-medium text-slate-700">Description</label>
            <textarea
              value={formData.description || ''}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              className="min-h-28 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
            />
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">Montant total</label>
            <input
              type="number"
              value={formData.montant_total}
              onChange={(e) => setFormData({ ...formData, montant_total: Number(e.target.value) })}
              className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
              required
            />
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">Montant par membre</label>
            <input
              type="number"
              value={formData.montant_membre}
              onChange={(e) => setFormData({ ...formData, montant_membre: Number(e.target.value) })}
              className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
              required
            />
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">Nombre de membres</label>
            <input
              type="number"
              value={formData.nombre_membres}
              onChange={(e) => setFormData({ ...formData, nombre_membres: Number(e.target.value) })}
              className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
              required
            />
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">Date de début</label>
            <input
              type="date"
              value={formData.date_debut}
              onChange={(e) => setFormData({ ...formData, date_debut: e.target.value })}
              className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
              required
            />
          </div>
        </div>

        <div className="mt-6">
          <button
            type="submit"
            disabled={loading}
            className="rounded-xl bg-sky-600 px-5 py-3 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-60"
          >
            {loading ? 'Création...' : 'Créer'}
          </button>
        </div>
      </form>
    </div>
  )
}