// Layout global avec Outlet pour les routes enfants

import { Outlet } from 'react-router-dom'
import Navbar from './Navbar'
import Sidebar from './Sidebar'
import { useAuth } from '../../context/AuthContext'

export default function Layout() {
  const { user, logout } = useAuth()

  return (
    <div className="min-h-screen bg-[#f5f7f1]">
      <Navbar user={user} onLogout={logout} />

      <div className="flex flex-col md:flex-row">
        <Sidebar />

        <main className="min-w-0 flex-1 p-5 sm:p-7 lg:p-9">
          <Outlet />
        </main>
      </div>
    </div>
  )
}