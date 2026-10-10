// Liste des membres

import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Plus, Trash2, Pencil } from 'lucide-react'
import { memberService } from '../services/members'

export default function Members() {
  const navigate = useNavigate()
  const [members, setMembers] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const load = async () => {
    try {
      const res = await memberService.getAll()
      setMembers(res.data ?? [])
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
    const ok = window.confirm('Voulez-vous vraiment supprimer ce membre ?')
    if (!ok) return

    try {
      await memberService.delete(id)
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
          <h1 className="text-3xl font-bold text-slate-900">Membres</h1>
          <p className="mt-1 text-slate-500">Gestion des membres</p>
        </div>

        <Link
          to="/members/create"
          className="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white hover:bg-sky-700"
        >
          <Plus size={16} />
          Nouveau membre
        </Link>
      </div>

      {error && <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {members.length === 0 ? (
          <div className="rounded-2xl bg-white p-6 shadow-sm">
            <p className="text-sm text-slate-500">Aucun membre trouvé.</p>
          </div>
        ) : (
          members.map((member) => (
            <div key={member.id} className="rounded-2xl bg-white p-6 shadow-sm">
              <p className="text-lg font-semibold text-slate-900">
                {member.nom} {member.prenom || ''}
              </p>
              <p className="mt-1 text-sm text-slate-500">{member.email || 'Email non renseigné'}</p>
              <p className="mt-2 text-sm text-slate-600">{member.telephone}</p>

              <div className="mt-4 flex items-center gap-3">
                <button
                  type="button"
                  onClick={() => navigate(`/members/${member.id}/edit`)}
                  className="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
                >
                  <Pencil size={16} />
                  Modifier
                </button>

                <button
                  type="button"
                  onClick={() => handleDelete(member.id)}
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