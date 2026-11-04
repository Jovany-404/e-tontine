// Barre de navigation supérieure

import { ShieldCheck } from 'lucide-react'
import type { User } from '../../types'

export default function Navbar({
  user,
  onLogout,
}: {
  user: User | null
  onLogout: () => Promise<void>
}) {
  return (
    <header className="sticky top-0 z-30 border-b border-emerald-950/10 bg-white/90 px-5 py-3.5 shadow-[0_3px_20px_-15px_rgba(5,46,28,0.3)] backdrop-blur-xl sm:px-7">
      <div className="flex items-center justify-between">
        <h1 className="flex items-center gap-3 text-lg font-black tracking-[0.11em] text-emerald-950">
          <span className="grid h-10 w-10 place-items-center rounded-xl bg-emerald-800 text-lg text-amber-300 shadow-md shadow-emerald-900/20">E</span>
          E-TONTINE
        </h1>

        <div className="flex items-center gap-4">
          <span className="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-900 sm:inline-flex"><ShieldCheck size={15} /> Espace sécurisé</span>
          <div className="text-right">
            <p className="max-w-40 truncate text-sm font-semibold text-slate-900">{user?.name || user?.nom || user?.email}</p>
            <p className="text-xs text-slate-500">{user?.email_verified ? 'Compte vérifié' : 'Espace personnel'}</p>
          </div>

          <button
            onClick={() => void onLogout()}
            className="rounded-xl bg-emerald-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-950"
          >
            Déconnexion
          </button>
        </div>
      </div>
    </header>
  )
}