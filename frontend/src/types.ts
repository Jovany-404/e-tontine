// Types globaux de l'application

export interface User {
  id: number
  nom?: string
  email: string
  telephone?: string
  role?: string
  is_active?: boolean
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  nom: string
  email: string
  telephone?: string
  password: string
}

export interface Tontine {
  id: number
  nom: string
  description?: string
  statut: string
  montant_total: number
  montant_membre: number
  nombre_membres: number
  nombre_cycles: number
  date_debut: string
  date_fin?: string
  type_rotation: 'chrono' | 'aleatoire'
  delai_paiement_jours: number
  notification_active: boolean
  progression?: number
}

export interface CreateTontineData {
  nom: string
  description?: string
  montant_total: number
  montant_membre: number
  nombre_membres: number
  nombre_cycles: number
  date_debut: string
  date_fin?: string
  type_rotation: 'chrono' | 'aleatoire'
  delai_paiement_jours: number
  notification_active: boolean
}