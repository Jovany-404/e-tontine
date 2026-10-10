import api from './api'

export interface DrawResult {
  id: string
  cycle_number: number
  round: number
  amount: number
  drawn_at: string | null
  winner: {
    id: string
    name: string
  }
}

export interface CreatedDraw {
  draw: DrawResult
  cycle_completed: boolean
}

export const drawService = {
  async getAll(tontinePublicId: string): Promise<DrawResult[]> {
    const response = await api.get<{ data: DrawResult[] }>(
      `/tontines/${tontinePublicId}/draws`,
    )

    return response.data.data
  },

  async drawNext(tontinePublicId: string): Promise<CreatedDraw> {
    const response = await api.post<{ data: CreatedDraw }>(
      `/tontines/${tontinePublicId}/draws`,
    )

    return response.data.data
  },
}
