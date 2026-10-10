// Service des tontines

import api from './api'
import type { CreateTontineData } from '../types'

export const tontineService = {
  // Liste des tontines
  getAll: async () => {
    const res = await api.get('/tontines')
    return res.data
  },

  // Détail d'une tontine
  getById: async (id: number) => {
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