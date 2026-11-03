// Profil utilisateur

import { useEffect, useState } from 'react'
import { useAuth } from '../context/AuthContext'
import { User, Mail, Phone, Shield, ShieldCheck } from 'lucide-react'
import IdentityStatusPanel from '../components/identity/IdentityStatusPanel'
import { identityService } from '../services/identity'

export default function Profile() {
  const { user } = useAuth()
  const [profilePhoto, setProfilePhoto] = useState<{ status: string; url: string } | null>(null)
  const [photoError, setPhotoError] = useState('')
  const identityStatus = user?.identity_status ?? 'unverified'
  const photoUrl = user?.profile_photo_available && profilePhoto?.status === identityStatus
    ? profilePhoto.url
    : ''
  const identityLabel = {
    unverified: 'Pièce non transmise',
    pending: 'Vérification en attente',
    verified: 'Identité vérifiée',
    rejected: 'Nouvelle pièce demandée',
  }[identityStatus]

  useEffect(() => {
    let objectUrl = ''
    let cancelled = false
    if (!user?.profile_photo_available) return

    void identityService.getProfilePhoto()
      .then((blob) => {
        if (!cancelled) {
          objectUrl = URL.createObjectURL(blob)
          setProfilePhoto({ status: identityStatus, url: objectUrl })
          setPhotoError('')
        }
      })
      .catch(() => {
        if (!cancelled) setPhotoError('La photo de profil ne peut pas être chargée pour le moment.')
      })

    return () => {
      cancelled = true
      if (objectUrl) URL.revokeObjectURL(objectUrl)
    }
  }, [identityStatus, user?.profile_photo_available])

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-slate-900">Profil utilisateur</h1>
        <p className="mt-1 text-slate-500">Informations personnelles</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-2xl bg-white p-6 shadow-sm">
          <div className="flex items-center gap-4">
            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
              {photoUrl ? <img src={photoUrl} alt="Photo de profil" className="h-16 w-16 rounded-2xl object-cover" /> : <User size={28} />}
            </div>
            <div>
              <p className="text-lg font-semibold text-slate-900">{user?.name || user?.nom || 'Utilisateur'}</p>
              <p className="text-sm text-slate-500">{user?.email_verified ? 'Adresse e-mail vérifiée' : 'Adresse e-mail à vérifier'}</p>
            </div>
          </div>
          {photoError && <p role="status" className="mt-3 text-xs text-amber-800">{photoError}</p>}

          <div className="mt-6 space-y-4">
            <div className="flex items-center gap-3 text-sm text-slate-600">
              <Mail size={16} />
              <span>{user?.email || 'Email non renseigné'}</span>
            </div>
            <div className="flex items-center gap-3 text-sm text-slate-600">
              <Phone size={16} />
              <span>{user?.phone || user?.telephone || 'Téléphone non renseigné'}</span>
            </div>
            <div className="flex items-center gap-3 text-sm text-slate-600">
              <Shield size={16} />
              <span className="flex items-center gap-2">
                <ShieldCheck size={16} />
                Identité : {identityLabel}
              </span>
            </div>
          </div>
        </div>

        <div className="rounded-2xl bg-white p-6 shadow-sm lg:col-span-2">
          <h2 className="text-lg font-semibold text-slate-900">Paramètres</h2>
          <div className="mt-4 grid gap-4 md:grid-cols-2">
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Nom</p>
              <p className="font-medium text-slate-900">{user?.name || user?.nom || '-'}</p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Email</p>
              <p className="font-medium text-slate-900">{user?.email || '-'}</p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Téléphone</p>
              <p className="font-medium text-slate-900">{user?.phone || user?.telephone || '-'}</p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm text-slate-500">Statut</p>
              <p className="font-medium text-slate-900">{user?.email_verified ? 'Vérifié' : 'À vérifier'}</p>
            </div>
            <div className="rounded-xl bg-emerald-50 p-4">
              <p className="text-sm text-emerald-800">Identité</p>
              <p className="mt-1 font-semibold text-emerald-950">{identityLabel}</p>
            </div>
          </div>
        </div>
      </div>
      <IdentityStatusPanel initialStatus={user?.identity_status} />
    </div>
  )
}