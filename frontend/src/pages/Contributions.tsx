import { useCallback, useEffect, useState, type FormEvent } from 'react'
import axios from 'axios'
import {
  AlertCircle,
  CalendarDays,
  Check,
  CircleDollarSign,
  Clock3,
  Loader2,
  RefreshCw,
  Wallet,
} from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import TontineTabs from '../components/layout/TontineTabs'
import { paymentService, type ContributionSchedule, type PaymentMethod } from '../services/payments'
import { tontineService, type TontineSummary } from '../services/tontines'

interface ApiError {
  message?: string
  errors?: Record<string, string[]>
}

const methodLabels: Record<PaymentMethod, string> = {
  cash: 'Espèces',
  mobile_money: 'Mobile Money',
  transfer: 'Virement',
  other: 'Autre',
}

const statusLabels = {
  pending: 'À payer',
  partial: 'Partiellement payée',
  paid: 'Payée',
} as const

function formatAmount(amount: number): string {
  return `${new Intl.NumberFormat('fr-FR').format(amount)} FCFA`
}

function formatDate(date: string): string {
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(
    new Date(`${date}T00:00:00`),
  )
}

function getErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ApiError>(error)) {
    return error instanceof Error ? error.message : 'Une erreur inattendue est survenue.'
  }

  const validationMessage = Object.values(error.response?.data?.errors ?? {})
    .flat()
    .find((message) => typeof message === 'string')

  return validationMessage
    ?? error.response?.data?.message
    ?? 'Impossible de communiquer avec le serveur.'
}

export default function Contributions() {
  const { tontineId = '' } = useParams()
  const [tontine, setTontine] = useState<TontineSummary | null>(null)
  const [schedules, setSchedules] = useState<ContributionSchedule[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  const [selectedScheduleId, setSelectedScheduleId] = useState<string | null>(null)
  const [amount, setAmount] = useState('')
  const [method, setMethod] = useState<PaymentMethod>('cash')
  const [saving, setSaving] = useState(false)

  const load = useCallback(async () => {
    if (!tontineId) {
      setError('Identifiant de tontine manquant.')
      setLoading(false)
      return
    }

    setLoading(true)
    setError('')

    try {
      const [tontineResponse, nextSchedules] = await Promise.all([
        tontineService.getById(tontineId),
        paymentService.getSchedules(tontineId),
      ])
      const nextTontine = tontineResponse?.data as TontineSummary | undefined

      if (!nextTontine || typeof nextTontine.id !== 'string' || !Array.isArray(nextSchedules)) {
        throw new Error('Réponse invalide lors du chargement des cotisations.')
      }

      setTontine(nextTontine)
      setSchedules(nextSchedules)
    } catch (loadError) {
      setError(getErrorMessage(loadError))
      setTontine(null)
      setSchedules([])
    } finally {
      setLoading(false)
    }
  }, [tontineId])

  useEffect(() => {
    void load()
  }, [load])

  const selectedSchedule = schedules.find((schedule) => schedule.id === selectedScheduleId) ?? null
  const canRecordPayment = tontine?.my_role === 'manager'
  const totalExpected = schedules.reduce((total, schedule) => total + schedule.amount_expected, 0)
  const totalPaid = schedules.reduce((total, schedule) => total + schedule.amount_paid, 0)
  const overdueCount = schedules.filter((schedule) => schedule.is_overdue).length

  const openPaymentForm = (schedule: ContributionSchedule) => {
    setError('')
    setSuccess('')
    setSelectedScheduleId(schedule.id)
    setAmount(String(schedule.remaining_amount))
    setMethod('cash')
  }

  const closePaymentForm = () => {
    setSelectedScheduleId(null)
    setAmount('')
  }

  const handleRecordPayment = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!tontine || !selectedSchedule) return

    const parsedAmount = Number(amount)
    if (!Number.isSafeInteger(parsedAmount) || parsedAmount < 1 || parsedAmount > selectedSchedule.remaining_amount) {
      setError(`Saisis un montant entier compris entre 1 et ${selectedSchedule.remaining_amount} FCFA.`)
      return
    }

    setSaving(true)
    setError('')
    setSuccess('')

    try {
      await paymentService.record(tontine.id, selectedSchedule.id, {
        amount: parsedAmount,
        method,
      })
      closePaymentForm()
      setSuccess('Le paiement a été enregistré.')
      await load()
    } catch (recordError) {
      setError(getErrorMessage(recordError))
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center gap-3 p-6 text-slate-600" role="status">
        <Loader2 className="animate-spin" size={20} aria-hidden="true" />
        Chargement des cotisations…
      </div>
    )
  }

  if (!tontine) {
    return (
      <div className="space-y-4">
        <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
          {error || 'Tontine introuvable.'}
        </div>
        <Link to="/tontines" className="font-medium text-sky-700 hover:text-sky-900">
          Retour aux tontines
        </Link>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <header>
        <Link to={`/tontines/${tontine.id}`} className="text-sm font-medium text-sky-700 hover:text-sky-900">
          {tontine.name}
        </Link>
        <div className="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h1 className="text-3xl font-bold text-slate-900">Cotisations</h1>
            <p className="mt-1 text-slate-600">
              Suivi des échéances et des paiements{canRecordPayment ? ' enregistrés par le gérant' : ' de ta tontine'}.
            </p>
          </div>
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex min-h-10 items-center justify-center gap-2 self-start rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 sm:self-auto"
          >
            <RefreshCw size={16} aria-hidden="true" />
            Actualiser
          </button>
        </div>
      </header>

      <TontineTabs />

      {error && (
        <div className="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
          <AlertCircle className="mt-0.5 shrink-0" size={17} aria-hidden="true" />
          <span>{error}</span>
        </div>
      )}
      {success && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
          <Check size={17} aria-hidden="true" />
          {success}
        </div>
      )}

      <section aria-label="Résumé des cotisations" className="grid gap-4 sm:grid-cols-3">
        <SummaryCard
          icon={<Wallet size={19} aria-hidden="true" />}
          label="Total attendu"
          value={formatAmount(totalExpected)}
        />
        <SummaryCard
          icon={<CircleDollarSign size={19} aria-hidden="true" />}
          label="Total encaissé"
          value={formatAmount(totalPaid)}
        />
        <SummaryCard
          icon={<Clock3 size={19} aria-hidden="true" />}
          label="Échéances en retard"
          value={String(overdueCount)}
          warning={overdueCount > 0}
        />
      </section>

      <section aria-labelledby="schedules-heading" className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
          <h2 id="schedules-heading" className="font-semibold text-slate-900">Échéances de cotisation</h2>
          <p className="mt-1 text-sm text-slate-500">
            {schedules.length} échéance{schedules.length === 1 ? '' : 's'}
          </p>
        </div>

        {schedules.length === 0 ? (
          <div className="px-5 py-10 text-center sm:px-6">
            <CalendarDays className="mx-auto text-slate-400" size={28} aria-hidden="true" />
            <p className="mt-3 font-medium text-slate-900">Aucune échéance pour le moment</p>
            <p className="mt-1 text-sm text-slate-600">
              Les échéances apparaîtront ici lorsque le gérant démarrera le premier cycle.
            </p>
          </div>
        ) : (
          <ul className="divide-y divide-slate-200">
            {schedules.map((schedule) => (
              <li key={schedule.id} className="p-5 sm:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <h3 className="font-semibold text-slate-900">
                        {schedule.member.name} · Tour {schedule.round}
                      </h3>
                      <StatusBadge schedule={schedule} />
                    </div>
                    <p className="mt-1 text-sm text-slate-500">
                      Cycle {schedule.cycle_number} · Échéance le {formatDate(schedule.due_date)}
                      {schedule.is_overdue && ` · ${schedule.days_late} jour${schedule.days_late === 1 ? '' : 's'} de retard`}
                    </p>

                    <div className="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                      <AmountDetail label="À verser" value={formatAmount(schedule.amount_expected)} />
                      <AmountDetail label="Encaissé" value={formatAmount(schedule.amount_paid)} />
                      <AmountDetail label="Reste dû" value={formatAmount(schedule.remaining_amount)} />
                    </div>

                    {schedule.payments.length > 0 && (
                      <ul className="mt-4 space-y-2 border-t border-slate-100 pt-3">
                        {schedule.payments.map((payment) => (
                          <li key={payment.id} className="flex flex-wrap items-center justify-between gap-2 text-sm">
                            <span className="text-slate-600">
                              {payment.paid_at ? formatDate(payment.paid_at) : 'Date inconnue'}
                              {' · '}{methodLabels[payment.method]}
                            </span>
                            <span className="font-medium text-emerald-800">{formatAmount(payment.amount)}</span>
                          </li>
                        ))}
                      </ul>
                    )}
                  </div>

                  {canRecordPayment && schedule.remaining_amount > 0 && (
                    <button
                      type="button"
                      onClick={() => openPaymentForm(schedule)}
                      className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
                    >
                      <CircleDollarSign size={17} aria-hidden="true" />
                      Enregistrer un paiement
                    </button>
                  )}
                </div>

                {selectedScheduleId === schedule.id && (
                  <form
                    onSubmit={(event) => void handleRecordPayment(event)}
                    className="mt-5 rounded-xl border border-sky-200 bg-sky-50 p-4 sm:p-5"
                  >
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
                      <label className="flex-1 text-sm font-medium text-slate-700">
                        Montant encaissé (FCFA)
                        <input
                          type="number"
                          inputMode="numeric"
                          min={1}
                          max={schedule.remaining_amount}
                          step={1}
                          required
                          value={amount}
                          onChange={(event) => setAmount(event.target.value)}
                          className="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-100"
                        />
                      </label>
                      <label className="flex-1 text-sm font-medium text-slate-700">
                        Mode de paiement
                        <select
                          value={method}
                          onChange={(event) => setMethod(event.target.value as PaymentMethod)}
                          className="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-100"
                        >
                          {(Object.entries(methodLabels) as [PaymentMethod, string][]).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                          ))}
                        </select>
                      </label>
                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={closePaymentForm}
                          disabled={saving}
                          className="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                        >
                          Annuler
                        </button>
                        <button
                          type="submit"
                          disabled={saving}
                          className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                          {saving && <Loader2 className="animate-spin" size={16} aria-hidden="true" />}
                          {saving ? 'Enregistrement…' : 'Confirmer'}
                        </button>
                      </div>
                    </div>
                    <p className="mt-2 text-xs text-slate-600">
                      Montant restant : {formatAmount(schedule.remaining_amount)}. Un paiement partiel est accepté.
                    </p>
                  </form>
                )}
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  )
}

function SummaryCard({
  icon,
  label,
  value,
  warning = false,
}: {
  icon: React.ReactNode
  label: string
  value: string
  warning?: boolean
}) {
  return (
    <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
      <div className="flex items-center gap-3">
        <span className={[
          'rounded-xl p-2',
          warning ? 'bg-amber-50 text-amber-700' : 'bg-sky-50 text-sky-700',
        ].join(' ')}>
          {icon}
        </span>
        <div>
          <p className="text-sm text-slate-500">{label}</p>
          <p className="mt-0.5 font-semibold text-slate-900">{value}</p>
        </div>
      </div>
    </div>
  )
}

function AmountDetail({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <p className="text-slate-500">{label}</p>
      <p className="mt-0.5 font-medium text-slate-900">{value}</p>
    </div>
  )
}

function StatusBadge({ schedule }: { schedule: ContributionSchedule }) {
  const style = schedule.is_overdue
    ? 'bg-rose-50 text-rose-800 ring-rose-200'
    : schedule.status === 'paid'
      ? 'bg-emerald-50 text-emerald-800 ring-emerald-200'
      : schedule.status === 'partial'
        ? 'bg-amber-50 text-amber-800 ring-amber-200'
        : 'bg-slate-100 text-slate-700 ring-slate-200'

  return (
    <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${style}`}>
      {schedule.is_overdue ? 'En retard' : statusLabels[schedule.status]}
    </span>
  )
}
