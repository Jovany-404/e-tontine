// Layout global avec Outlet pour les routes enfants

import { Outlet } from 'react-router-dom'
import Navbar from './Navbar'
import Sidebar from './Sidebar'
import { useAuth } from '../../context/AuthContext'

export default function Layout() {
  const { user, logout } = useAuth()

  return (
    <div className="min-h-screen bg-slate-50">
      <Navbar user={user} onLogout={logout} />

      <div className="flex">
        <Sidebar />

        <main className="ml-64 flex-1 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}