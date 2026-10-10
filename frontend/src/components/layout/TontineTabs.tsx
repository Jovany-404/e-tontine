// Onglets de navigation pour le détail d'une tontine

import { Link, useLocation, useParams } from 'react-router-dom'

export default function TontineTabs() {
  const { id } = useParams()
  const location = useLocation()

  const base = `/tontines/${id}`
  const current = location.pathname

  const tabs = [
    { label: 'Aperçu', to: base },
    { label: 'Cotisations', to: `${base}/contributions` },
    { label: 'Tirages', to: `${base}/draws` },
  ]

  return (
    <div className="border-b border-slate-200">
      <div className="flex gap-2 overflow-x-auto">
        {tabs.map((tab) => {
          const active = current === tab.to
          return (
            <Link
              key={tab.to}
              to={tab.to}
              className={[
                'whitespace-nowrap rounded-t-xl px-4 py-3 text-sm font-medium',
                active
                  ? 'border-b-2 border-sky-600 text-sky-700'
                  : 'text-slate-500 hover:text-slate-900',
              ].join(' ')}
            >
              {tab.label}
            </Link>
          )
        })}
      </div>
    </div>
  )
}