// Sécurité du compte

import { ShieldCheck, KeyRound, LogOut } from 'lucide-react'
import { useAuth } from '../context/AuthContext'

export default function Security() {
  const { logout } = useAuth()

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-slate-900">Sécurité</h1>
        <p className="mt-1 text-slate-500">Accès et session</p>
      </div>

      <div className="grid gap-4">
        <div className="rounded-2xl bg-white p-6 shadow-sm">
          <div className="flex items-start gap-4">
            <div className="rounded-2xl bg-emerald-50 p-3 text-emerald-700">
              <ShieldCheck size={20} />
            </div>
            <div>
              <h2 className="text-lg font-semibold text-slate-900">Session active</h2>
              <p className="mt-1 text-sm text-slate-500">Votre session est protégée par token.</p>
            </div>
          </div>
        </div>

        <div className="rounded-2xl bg-white p-6 shadow-sm">
          <div className="flex items-start gap-4">
            <div className="rounded-2xl bg-sky-50 p-3 text-sky-700">
              <KeyRound size={20} />
            </div>
            <div>
              <h2 className="text-lg font-semibold text-slate-900">Mot de passe</h2>
              <p className="mt-1 text-sm text-slate-500">
                Ajoutez ici le changement de mot de passe si votre backend le supporte.
              </p>
            </div>
          </div>
        </div>

        <div className="rounded-2xl bg-white p-6 shadow-sm">
          <div className="flex items-center justify-between gap-4">
            <div>
              <h2 className="text-lg font-semibold text-slate-900">Déconnexion</h2>
              <p className="mt-1 text-sm text-slate-500">Fermez la session sur cet appareil.</p>
            </div>

            <button
              type="button"
              onClick={logout}
              className="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-medium text-white hover:bg-red-700"
            >
              <LogOut size={16} />
              Se déconnecter
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}