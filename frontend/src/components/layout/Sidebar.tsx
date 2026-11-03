// Menu latéral principal

import { NavLink } from 'react-router-dom'
import { Home, Users, UserCircle2, Shield, LayoutList } from 'lucide-react'

const linkClass = ({ isActive }: { isActive: boolean }) =>
  [
    'flex shrink-0 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium',
    isActive ? 'bg-emerald-50 text-emerald-900 shadow-sm ring-1 ring-emerald-900/5' : 'text-slate-600 hover:bg-amber-50 hover:text-emerald-900',
  ].join(' ')

export default function Sidebar() {
  return (
    <aside className="sticky top-[68px] z-20 w-full shrink-0 border-b border-emerald-950/10 bg-white/90 p-2 backdrop-blur md:top-[73px] md:h-[calc(100vh-73px)] md:w-64 md:border-b-0 md:border-r md:p-4">
      <div className="mb-2 hidden px-3 pt-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 md:block">Votre espace</div>
      <nav className="flex gap-1 overflow-x-auto md:block md:space-y-2">
        <NavLink to="/" end className={linkClass}>
          <Home size={18} />
          Dashboard
        </NavLink>

        <NavLink to="/tontines" className={linkClass}>
          <LayoutList size={18} />
          Tontines
        </NavLink>

        <NavLink to="/members" className={linkClass}>
          <Users size={18} />
          Membres
        </NavLink>

        <NavLink to="/profile" className={linkClass}>
          <UserCircle2 size={18} />
          Profil
        </NavLink>

        <NavLink to="/security" className={linkClass}>
          <Shield size={18} />
          Sécurité
        </NavLink>
      </nav>
    </aside>
  )
}