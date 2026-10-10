// Service des membres

import api from './api'

export const memberService = {
  // Liste des membres
  getAll: async () => {
    const res = await api.get('/members')
    return res.data
  },

  // Création d'un membre
  create: async (payload: Record<string, unknown>) => {
    const res = await api.post('/members', payload)
    return res.data
  },

  // Suppression d'un membre
  delete: async (id: number) => {
    const res = await api.delete(`/members/${id}`)
    return res.data
  },
}