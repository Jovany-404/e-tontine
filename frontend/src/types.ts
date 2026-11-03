// Types globaux de l'application

export interface User {
  id: string | number
  name?: string
  nom?: string
  email: string
  email_verified?: boolean
  phone?: string | null
  profile_photo_available?: boolean
  identity_status?: 'unverified' | 'pending' | 'verified' | 'rejected'
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
  document_type: 'national_id' | 'passport'
  document_front: File
  document_back?: File
  profile_photo: File
  identity_consent: boolean
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