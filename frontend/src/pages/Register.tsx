import { useEffect, useRef, useState, type ChangeEvent, type FormEvent } from 'react'
import axios from 'axios'
import { ArrowRight, Check, CreditCard, Eye, EyeOff, FileCheck2, Lock, Mail, Phone, ShieldCheck, UserRound, UserRoundPlus } from 'lucide-react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

type DocumentType = 'national_id' | 'passport'
type Preview = { profile?: string; front?: string; back?: string }

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const from = (location.state as { from?: Location } | null)?.from

  const [showPassword, setShowPassword] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [documentType, setDocumentType] = useState<DocumentType>('national_id')
  const [documentFront, setDocumentFront] = useState<File | null>(null)
  const [documentBack, setDocumentBack] = useState<File | null>(null)
  const [profilePhoto, setProfilePhoto] = useState<File | null>(null)
  const [consent, setConsent] = useState(false)
  const [preview, setPreview] = useState<Preview>({})
  const previewUrls = useRef<Preview>({})
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  })

  useEffect(() => {
    return () => Object.values(previewUrls.current).forEach((url) => url && URL.revokeObjectURL(url))
  }, [])

  const handleFileChange = (
    key: keyof Preview,
    file: File | null,
    setFile: (file: File | null) => void,
  ) => {
    const previousUrl = previewUrls.current[key]
    if (previousUrl) URL.revokeObjectURL(previousUrl)

    const nextUrl = file ? URL.createObjectURL(file) : undefined
    previewUrls.current = { ...previewUrls.current, [key]: nextUrl }
    setPreview((current) => ({ ...current, [key]: nextUrl }))
    setFile(file)
  }

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!documentFront || !profilePhoto || (documentType === 'national_id' && !documentBack)) {
      setError('Ajoutez votre photo de profil et toutes les faces demandées de votre pièce.')
      return
    }

    setLoading(true)
    setError('')
    try {
      await register({
        ...formData,
        phone: formData.phone || undefined,
        document_type: documentType,
        document_front: documentFront,
        document_back: documentType === 'national_id' ? documentBack ?? undefined : undefined,
        profile_photo: profilePhoto,
        identity_consent: consent,
      })
      navigate(from ? `${from.pathname}${from.search}${from.hash}` : '/', { replace: true })
    } catch (submissionError: unknown) {
      const response = axios.isAxiosError<{ message?: string; errors?: Record<string, string[]> }>(submissionError)
        ? submissionError.response?.data
        : undefined
      const validationMessage = Object.values(response?.errors ?? {}).flat()[0]
      setError(validationMessage ?? response?.message ?? "L'inscription n'a pas pu aboutir. Réessayez.")
    } finally {
      setLoading(false)
    }
  }

  const handleDocumentTypeChange = (type: DocumentType) => {
    setDocumentType(type)
    if (type === 'passport') handleFileChange('back', null, setDocumentBack)
  }

  return (
    <main className="min-h-screen overflow-hidden bg-[#f5f7f1] px-4 py-8 sm:px-6 lg:py-12">
      <div className="pointer-events-none fixed -left-24 -top-24 h-80 w-80 rounded-full bg-emerald-300/20 blur-3xl" />
      <div className="pointer-events-none fixed -bottom-24 -right-24 h-96 w-96 rounded-full bg-amber-300/20 blur-3xl" />
      <div className="brand-depth relative mx-auto grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-[0_30px_80px_-35px_rgba(15,50,34,0.35)] lg:grid-cols-[0.85fr_1.15fr]">
        <aside className="relative hidden overflow-hidden bg-[#075b3d] p-10 text-white lg:flex lg:flex-col lg:justify-between">
          <div className="absolute -right-20 top-32 h-72 w-72 rounded-full border-[36px] border-white/5" />
          <div className="absolute -bottom-20 -left-10 h-64 w-64 rounded-full border-[28px] border-amber-300/10" />
          <div className="relative">
            <Link to="/" className="inline-flex items-center gap-3 text-lg font-black tracking-[0.12em]">
              <span className="grid h-11 w-11 place-items-center rounded-2xl bg-amber-300 text-emerald-950 shadow-lg shadow-black/20"><span className="text-xl">E</span></span>
              E-TONTINE
            </Link>
            <div className="mt-24 max-w-sm">
              <p className="text-xs font-bold uppercase tracking-[0.24em] text-amber-300">Ensemble, on avance</p>
              <h1 className="mt-5 text-4xl font-bold leading-tight">La confiance commence par une identité vérifiée.</h1>
              <p className="mt-5 leading-7 text-emerald-50/75">Votre pièce est chiffrée et consultable uniquement par le gérant d’une tontine que vous demandez à rejoindre.</p>
            </div>
          </div>
          <div className="relative flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <ShieldCheck className="shrink-0 text-amber-300" size={22} />
            <p className="text-sm leading-6 text-emerald-50/80">Les pièces ne sont jamais publiées ni partagées avec les autres membres.</p>
          </div>
        </aside>

        <section className="p-6 sm:p-10 lg:p-12">
          <div className="mb-8 flex items-center justify-between">
            <div className="lg:hidden">
              <p className="font-black tracking-[0.12em] text-emerald-900">E-TONTINE</p>
              <p className="mt-1 text-xs text-slate-500">La confiance commence ici</p>
            </div>
            <span className="ml-auto flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800"><ShieldCheck size={15} /> Inscription sécurisée</span>
          </div>

          <div className="mb-8">
            <p className="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Votre espace E-TONTINE</p>
            <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">Créer un compte</h2>
            <p className="mt-2 text-sm leading-6 text-slate-500">Renseignez vos informations puis ajoutez une pièce d’identité valide.</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-7">
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="full-name" className="mb-2 block text-sm font-semibold text-slate-800">Nom complet</label>
                <div className="relative">
                  <UserRound className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                  <input id="full-name" value={formData.name} onChange={(event) => setFormData({ ...formData, name: event.target.value })} className="form-input" autoComplete="name" minLength={2} maxLength={120} required />
                </div>
              </div>
              <div>
                <label htmlFor="email" className="mb-2 block text-sm font-semibold text-slate-800">Adresse e-mail</label>
                <div className="relative">
                  <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                  <input id="email" type="email" value={formData.email} onChange={(event) => setFormData({ ...formData, email: event.target.value })} className="form-input" autoComplete="email" required />
                </div>
              </div>
              <div>
                <label htmlFor="phone" className="mb-2 block text-sm font-semibold text-slate-800">Téléphone <span className="font-normal text-slate-400">(facultatif)</span></label>
                <div className="relative">
                  <Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                  <input id="phone" type="tel" value={formData.phone} onChange={(event) => setFormData({ ...formData, phone: event.target.value })} className="form-input" autoComplete="tel" />
                </div>
              </div>
              <div>
                <label htmlFor="password" className="mb-2 block text-sm font-semibold text-slate-800">Mot de passe</label>
                <div className="relative">
                  <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                  <input id="password" type={showPassword ? 'text' : 'password'} value={formData.password} onChange={(event) => setFormData({ ...formData, password: event.target.value })} className="form-input pr-12" autoComplete="new-password" minLength={10} required />
                  <button type="button" aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'} onClick={() => setShowPassword((visible) => !visible)} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">{showPassword ? <EyeOff size={18} /> : <Eye size={18} />}</button>
                </div>
                <p className="mt-1.5 text-xs text-slate-500">10 caractères minimum, avec majuscule, minuscule et chiffre.</p>
              </div>
              <div className="sm:col-span-2">
                <label htmlFor="password-confirmation" className="mb-2 block text-sm font-semibold text-slate-800">Confirmer le mot de passe</label>
                <div className="relative">
                  <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                  <input id="password-confirmation" type="password" value={formData.password_confirmation} onChange={(event) => setFormData({ ...formData, password_confirmation: event.target.value })} className="form-input" autoComplete="new-password" minLength={10} required />
                </div>
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 sm:p-6">
              <div className="flex items-start gap-3">
                <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-800"><CreditCard size={20} /></span>
                <div>
                  <h3 className="font-bold text-slate-900">Vérification d’identité</h3>
                  <p className="mt-1 text-sm leading-6 text-slate-500">Choisissez un document. Pour une carte nationale, les deux faces sont requises.</p>
                </div>
              </div>
              <div className="mt-5 grid grid-cols-2 gap-3">
                {([
                  ['national_id', 'Carte nationale'],
                  ['passport', 'Passeport'],
                ] as const).map(([value, label]) => (
                  <button key={value} type="button" onClick={() => handleDocumentTypeChange(value)} className={`flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-sm font-semibold transition ${documentType === value ? 'border-emerald-700 bg-emerald-50 text-emerald-900 ring-2 ring-emerald-700/10' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-400'}`}>
                    {documentType === value && <Check size={16} />}{label}
                  </button>
                ))}
              </div>
              <div className="mt-5 grid gap-4 sm:grid-cols-2">
                <IdentityFileInput id="document-front" label={documentType === 'passport' ? 'Page identité du passeport' : 'Carte — recto'} file={documentFront} onChange={(file) => handleFileChange('front', file, setDocumentFront)} imagePreview={preview.front} />
                {documentType === 'national_id' && <IdentityFileInput id="document-back" label="Carte — verso" file={documentBack} onChange={(file) => handleFileChange('back', file, setDocumentBack)} imagePreview={preview.back} />}
                <IdentityFileInput id="profile-photo" label="Photo de profil" file={profilePhoto} onChange={(file) => handleFileChange('profile', file, setProfilePhoto)} imagePreview={preview.profile} />
              </div>
              <label className="mt-5 flex cursor-pointer items-start gap-3 rounded-xl bg-white p-3.5 text-sm leading-6 text-slate-600 ring-1 ring-slate-200">
                <input type="checkbox" checked={consent} onChange={(event) => setConsent(event.target.checked)} className="mt-1 h-4 w-4 rounded border-slate-300 accent-emerald-700" required />
                <span>J’accepte le traitement sécurisé de ces documents pour vérifier mon identité auprès du gérant de la tontine que je demande à rejoindre.</span>
              </label>
            </div>

            {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>}

            <button type="submit" disabled={loading || !consent} className="group flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-800 px-5 py-3.5 font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-900 disabled:cursor-not-allowed disabled:opacity-50">
              <UserRoundPlus size={18} />{loading ? 'Création du compte…' : 'Créer mon compte'}<ArrowRight size={17} className="transition group-hover:translate-x-1" />
            </button>
          </form>

          <p className="mt-7 text-center text-sm text-slate-500">Vous avez déjà un compte ? <Link to="/login" state={location.state} className="font-bold text-emerald-800 hover:text-emerald-950">Se connecter</Link></p>
          <div className="mt-7 flex items-center justify-center gap-2 text-xs text-slate-400"><span className="h-2 w-2 rounded-full bg-emerald-600" /><span className="h-2 w-2 rounded-full bg-amber-400" /><span className="h-2 w-2 rounded-full bg-red-600" /> Pensé au Sénégal, pour les communautés.</div>
        </section>
      </div>
    </main>
  )
}

function IdentityFileInput({
  id,
  label,
  file,
  onChange,
  imagePreview,
}: {
  id: string;
  label: string;
  file: File | null;
  onChange: (file: File | null) => void;
  imagePreview?: string;
}) {
  return (
    <div>
      <label htmlFor={id} className="mb-2 block text-sm font-semibold text-slate-800">{label} <span className="text-red-600">*</span></label>
      <label htmlFor={id} className="group flex min-h-24 cursor-pointer items-center gap-4 rounded-2xl border border-dashed border-slate-300 bg-white p-3 transition hover:border-emerald-600 hover:bg-emerald-50/50">
        {imagePreview
          ? <img src={imagePreview} alt={`Aperçu : ${label}`} className="h-16 w-16 rounded-xl object-cover ring-1 ring-slate-200" />
          : <span className="grid h-16 w-16 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-800"><FileCheck2 size={22} /></span>}
        <span className="min-w-0">
          <span className="block truncate text-sm font-semibold text-slate-800">{file?.name ?? 'Choisir une image'}</span>
          <span className="mt-1 block text-xs text-slate-500">JPG, PNG ou WEBP · {id === 'profile-photo' ? '5 Mo max.' : '8 Mo max.'}</span>
        </span>
        <input
          id={id}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          className="sr-only"
          required
          onChange={(event: ChangeEvent<HTMLInputElement>) => onChange(event.target.files?.[0] ?? null)}
        />
      </label>
    </div>
  )
}
