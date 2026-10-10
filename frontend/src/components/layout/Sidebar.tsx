// Menu latéral principal

import { NavLink } from 'react-router-dom'
import { Home, Users, UserCircle2, Shield, LayoutList } from 'lucide-react'

const linkClass = ({ isActive }: { isActive: boolean }) =>
  [
    'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium',
    isActive ? 'bg-sky-50 text-sky-700' : 'text-slate-600 hover:bg-slate-100',
  ].join(' ')

export default function Sidebar() {
  return (
    <aside className="fixed left-0 top-[73px] h-[calc(100vh-73px)] w-64 border-r border-slate-200 bg-white p-4">
      <nav className="space-y-2">
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