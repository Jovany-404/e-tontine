// Barre de navigation supérieure

import { Bell } from 'lucide-react'
import type { User } from '../../types'

export default function Navbar({
  user,
  onLogout,
}: {
  user: User | null
  onLogout: () => Promise<void>
}) {
  return (
    <header className="border-b border-slate-200 bg-white px-6 py-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-sky-700">MY TONTINE</h1>

        <div className="flex items-center gap-4">
          <button className="rounded-full p-2 hover:bg-slate-100">
            <Bell size={18} className="text-slate-600" />
          </button>

          <div className="text-right">
            <p className="text-sm font-medium text-slate-900">{user?.nom || user?.email}</p>
            <p className="text-xs text-slate-500">{user?.role || 'Utilisateur'}</p>
          </div>

          <button
            onClick={() => void onLogout()}
            className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
          >
            Déconnexion
          </button>
        </div>
      </div>
    </header>
  )
}