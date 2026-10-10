// Modification d'une tontine

import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { tontineService } from '../services/tontines'
import type { CreateTontineData } from '../types'

export default function TontineEdit() {
  const { id } = useParams()
  const navigate = useNavigate()

  const [form, setForm] = useState<CreateTontineData>({
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

  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    const load = async () => {
      if (!id) return

      try {
        const res = await tontineService.getById(Number(id))
        const t = res.data

        if (t) {
          setForm({
            nom: t.nom,
            description: t.description || '',
            montant_total: t.montant_total,
            montant_membre: t.montant_membre,
            nombre_membres: t.nombre_membres,
            nombre_cycles: t.nombre_cycles,
            date_debut: t.date_debut,
            date_fin: t.date_fin || '',
            type_rotation: t.type_rotation,
            delai_paiement_jours: t.delai_paiement_jours,
            notification_active: t.notification_active,
          })
        }
      } catch (err: any) {
        setError(err.response?.data?.message || 'Erreur de chargement')
      } finally {
        setLoading(false)
      }
    }

    load()
  }, [id])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!id) return

    setSaving(true)
    setError('')

    try {
      const payload: CreateTontineData = {
        ...form,
        montant_total: Number(form.montant_total),
        montant_membre: Number(form.montant_membre),
        nombre_membres: Number(form.nombre_membres),
        nombre_cycles: Number(form.nombre_cycles),
        delai_paiement_jours: Number(form.delai_paiement_jours),
      }

      await tontineService.update(Number(id), payload)
      navigate(`/tontines/${id}`)
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur de mise à jour')
    } finally {
      setSaving(false)
    }
  }

  if (loading) return <div className="p-6 text-slate-600">Chargement du formulaire...</div>

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-slate-900">Modifier la tontine</h1>
          <p className="mt-1 text-slate-500">Mise à jour des informations</p>
        </div>

        <Link
          to={id ? `/tontines/${id}` : '/tontines'}
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
              value={form.nom}
              onChange={(e) => setForm({ ...form, nom: e.target.value })}
              className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
              required
            />
          </div>

          <div className="md:col-span-2">
            <label className="mb-2 block text-sm font-medium text-slate-700">Description</label>
            <textarea
              value={form.description || ''}
              onChange={(e) => setForm({ ...form, description: e.target.value })}
              className="min-h-28 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
            />
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">Montant total</label>
            <input
              type="number"
              value={form.montant_total}
              onChange={(e) => setForm({ ...form, montant_total: Number(e.target.value) })}
              className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
              required
            />
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">Montant par membre</label>
            <input
              type="number"
              value={form.montant_membre}
              onChange={(e) => setForm({ ...form, montant_membre: Number(e.target.value) })}
              className="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500"
              required
            />
          </div>
        </div>

        <div className="mt-6">
          <button
            type="submit"
            disabled={saving}
            className="rounded-xl bg-sky-600 px-5 py-3 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-60"
          >
            {saving ? 'Enregistrement...' : 'Sauvegarder'}
          </button>
        </div>
      </form>
    </div>
  )
}