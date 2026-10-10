// Service des tontines

import api from './api'
import type { CreateTontineData } from '../types'

export interface TontineSummary {
  id: string
  name: string
  description: string | null
  type: 'family' | 'friends' | 'work' | 'other'
  contribution_amount: number
  frequency: 'daily' | 'weekly' | 'monthly'
  start_date: string
  expected_members: number
  rules: string | null
  status: 'draft' | 'active' | 'completed'
  members_count: number
  my_role: 'manager' | 'member'
  manager: {
    id: string
    name: string
  }
}

export const tontineService = {
  // Liste des tontines
  getAll: async () => {
    const res = await api.get('/tontines')
    return res.data
  },

  // Détail d'une tontine
  getById: async (id: string | number) => {
    const res = await api.get(`/tontines/${id}`)
    return res.data
  },

  // Création d'une tontine
  create: async (payload: CreateTontineData) => {
    const res = await api.post('/tontines', payload)
    return res.data
  },

  // Mise à jour d'une tontine
  update: async (id: number, payload: CreateTontineData) => {
    const res = await api.put(`/tontines/${id}`, payload)
    return res.data
  },

  // Suppression d'une tontine
  delete: async (id: number) => {
    const res = await api.delete(`/tontines/${id}`)
    return res.data
  },
}