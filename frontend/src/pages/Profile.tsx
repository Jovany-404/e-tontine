// Profil utilisateur

import { useAuth } from '../context/AuthContext'
import { User, Mail, Phone, Shield } from 'lucide-react'

export default function Profile() {
  const { user } = useAuth()

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-slate-900">Profil utilisateur</h1>
        <p className="mt-1 text-slate-500">Informations personnelles</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-2xl bg-white p-6 shadow-sm">
          <div className="flex items-center gap-4">
            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
              <User size={28} />
            </div>
            <div>
              <p className="text-lg font-semibold text-slate-900">{user?.nom || 'Utilisateur'}</p>
              <p className="text-sm text-slate-500">{user?.role || 'Rôle non défini'}</p>
            </div>
          </div>

          <div className="mt-6 space-y-4">
            <div className="flex items-center gap-3 text-sm text-slate-600">
              <Mail size={16} />
              <span>{user?.email || 'Email non renseigné'}</span>
            </div>
            <div className="flex items-center gap-3 text-sm text-slate-600">
              <Phone size={16} />
              <span>{user?.telephone || 'Téléphone non renseigné'}</span>
            </div>
            <div className="flex items-center gap-3 text-sm text-slate-600">
              <Shield size={16} />
              <span>{user?.is_active ? 'Compte actif' : 'Compte inactif'}</span>
            </div>
          </div>
        </div>

        <div className="rounded-2xl bg-white p-6 shadow-sm lg:col-span-2">
          <h2 className="text-lg font-semibold text-slate-900">Paramètres</h2>
          <div className="mt-4 grid gap-4 md:grid-cols-2">
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Nom</p>
              <p className="font-medium text-slate-900">{user?.nom || '-'}</p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Email</p>
              <p className="font-medium text-slate-900">{user?.email || '-'}</p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Téléphone</p>
              <p className="font-medium text-slate-900">{user?.telephone || '-'}</p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Statut</p>
              <p className="font-medium text-slate-900">{user?.is_active ? 'Actif' : 'Inactif'}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}