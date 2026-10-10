// Types partagés par toute l'application

export interface User {
  id: number
  email: string
  nom: string | null
  telephone: string | null
  role: string
  is_active: boolean
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  email: string
  password: string
  nom: string
  telephone?: string
}

export interface AuthResponse {
  success: boolean
  message?: string
  data?: {
    token: string
    user: User
  }
}

export interface Tontine {
  id: number
  user_id: number
  nom: string
  description: string | null
  montant_total: number
  montant_membre: number
  nombre_membres: number
  nombre_cycles: number
  date_debut: string
  date_fin: string | null
  statut: 'en_attente' | 'active' | 'archivee' | 'close'
  type_rotation: 'chrono' | 'aleatoire'
  delai_paiement_jours: number
  notification_active: boolean
  progression: number
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
  type_rotation?: 'chrono' | 'aleatoire'
  delai_paiement_jours?: number
  notification_active?: boolean
}

export interface Member {
  id: number
  nom: string
  prenom: string | null
  email: string | null
  telephone: string
  adresse: string | null
  profession: string | null
  is_active: boolean
}

export interface CreateMemberData {
  nom: string
  prenom?: string
  email?: string
  telephone: string
  adresse?: string
  profession?: string
}

export interface ApiResponse<T> {
  success: boolean
  message?: string
  data: T
}