import api from './api'

export type PaymentMethod = 'cash' | 'mobile_money' | 'transfer' | 'other'
export type ScheduleStatus = 'pending' | 'partial' | 'paid'

export interface RecordedPayment {
  id: string
  amount: number
  method: PaymentMethod
  reference: string | null
  note: string | null
  paid_at: string | null
  created_at: string | null
}

export interface ContributionSchedule {
  id: string
  cycle_number: number
  round: number
  due_date: string
  amount_expected: number
  amount_paid: number
  remaining_amount: number
  status: ScheduleStatus
  is_overdue: boolean
  days_late: number
  member: {
    id: string
    name: string
  }
  payments: RecordedPayment[]
}

export interface RecordPaymentData {
  amount: number
  method: PaymentMethod
}

export const paymentService = {
  async getSchedules(tontinePublicId: string): Promise<ContributionSchedule[]> {
    const response = await api.get<{ data: ContributionSchedule[] }>(
      `/tontines/${tontinePublicId}/schedules`,
    )

    return response.data.data
  },

  async record(
    tontinePublicId: string,
    schedulePublicId: string,
    payload: RecordPaymentData,
  ): Promise<void> {
    await api.post(
      `/tontines/${tontinePublicId}/schedules/${schedulePublicId}/payments`,
      payload,
    )
  },
}
