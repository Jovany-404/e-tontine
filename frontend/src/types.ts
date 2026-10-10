// Types globaux de l'application

export interface User {
  id: string | number
  name?: string
  nom?: string
  email: string
  email_verified?: boolean
  phone?: string | null
  telephone?: string
  role?: string
  is_active?: boolean
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  name: string
  email: string
  phone?: string
  password: string
  password_confirmation: string
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