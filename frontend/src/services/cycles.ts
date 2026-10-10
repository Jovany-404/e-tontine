import api from './api'

export interface StartedCycle {
  number: number
  status: 'open'
  started_at: string
  members_count: number
  schedules_count: number
}

export const cycleService = {
  async startFirst(tontinePublicId: string): Promise<StartedCycle> {
    const response = await api.post<{ data: StartedCycle }>(
      `/tontines/${tontinePublicId}/cycles/start`,
    )

    return response.data.data
  },
}
