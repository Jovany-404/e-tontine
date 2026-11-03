import { useEffect, useState, type FormEvent } from "react";
import axios from "axios";
import { CheckCircle2, Clock3, FileCheck2, Loader2, ShieldCheck } from "lucide-react";
import {
  identityService,
  type IdentityDocumentType,
  type IdentityVerification,
} from "../../services/identity";

function errorMessage(error: unknown): string {
  if (axios.isAxiosError<{ message?: string; errors?: Record<string, string[]> }>(error)) {
    return Object.values(error.response?.data?.errors ?? {}).flat()[0]
      ?? error.response?.data?.message
      ?? "Impossible de transmettre les documents.";
  }
  return "Une erreur inattendue est survenue.";
}

export default function IdentityStatusPanel({
  initialStatus,
}: {
  initialStatus?: IdentityVerification["status"];
}) {
  const [verification, setVerification] = useState<IdentityVerification | null>(null);
  const [documentType, setDocumentType] = useState<IdentityDocumentType>("national_id");
  const [front, setFront] = useState<File | null>(null);
  const [back, setBack] = useState<File | null>(null);
  const [photo, setPhoto] = useState<File | null>(null);
  const [consent, setConsent] = useState(false);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => {
    let cancelled = false;
    void identityService.getMine()
      .then((data) => {
        if (!cancelled) setVerification(data);
      })
      .catch((loadError: unknown) => {
        if (!cancelled) setError(errorMessage(loadError));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => { cancelled = true; };
  }, []);

  const status = verification?.status ?? initialStatus ?? "unverified";
  const canSubmit = status === "unverified" || status === "rejected";

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!front || !photo || (documentType === "national_id" && !back)) {
      setError("Ajoutez votre photo de profil et toutes les faces demandées.");
      return;
    }

    const form = new FormData();
    form.append("document_type", documentType);
    form.append("document_front", front);
    if (documentType === "national_id" && back) form.append("document_back", back);
    form.append("profile_photo", photo);
    form.append("identity_consent", consent ? "1" : "0");

    setSubmitting(true);
    setError("");
    setNotice("");
    try {
      const result = await identityService.submit(form);
      setVerification(result);
      setFront(null);
      setBack(null);
      setPhoto(null);
      setConsent(false);
      setNotice("Votre nouvelle pièce a été envoyée pour vérification.");
    } catch (submitError: unknown) {
      setError(errorMessage(submitError));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <section className="rounded-2xl border border-emerald-950/10 bg-white p-6 shadow-sm lg:col-span-3">
      <div className="flex items-start gap-3">
        <span className="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-800"><ShieldCheck size={21} /></span>
        <div>
          <h2 className="text-lg font-bold text-slate-950">Vérification d’identité</h2>
          <p className="mt-1 text-sm leading-6 text-slate-600">La pièce est examinée par le gérant lorsque vous demandez à rejoindre sa tontine.</p>
        </div>
      </div>

      {loading ? (
        <p className="mt-5 flex items-center gap-2 text-sm text-slate-500"><Loader2 className="animate-spin" size={16} /> Chargement du statut…</p>
      ) : (
        <div className="mt-5">
          <div className={`flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold ${status === "verified" ? "bg-emerald-50 text-emerald-900" : status === "rejected" ? "bg-red-50 text-red-800" : status === "pending" ? "bg-amber-50 text-amber-900" : "bg-slate-50 text-slate-700"}`}>
            {status === "verified" ? <CheckCircle2 size={17} /> : status === "pending" ? <Clock3 size={17} /> : <FileCheck2 size={17} />}
            {status === "verified" ? "Identité vérifiée" : status === "pending" ? "Pièce reçue — vérification en attente" : status === "rejected" ? "Une nouvelle pièce est nécessaire" : "Pièce d’identité à transmettre"}
          </div>

          {verification?.rejection_reason && status === "rejected" && (
            <p className="mt-3 rounded-xl border border-red-100 bg-red-50/60 p-4 text-sm leading-6 text-red-900">
              <strong>Motif du gérant :</strong> {verification.rejection_reason}
            </p>
          )}

          {canSubmit && (
            <form onSubmit={handleSubmit} className="mt-5 space-y-4">
              <div>
                <label htmlFor="identity-type" className="mb-1.5 block text-sm font-semibold text-slate-800">Type de document</label>
                <select id="identity-type" value={documentType} onChange={(event) => {
                  const value = event.target.value as IdentityDocumentType;
                  setDocumentType(value);
                  if (value === "passport") setBack(null);
                }} className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                  <option value="national_id">Carte nationale (recto-verso)</option>
                  <option value="passport">Passeport</option>
                </select>
              </div>
              <div className="grid gap-3 sm:grid-cols-2">
                <FileField label={documentType === "passport" ? "Page identité du passeport" : "Carte nationale — recto"} file={front} onChange={setFront} />
                {documentType === "national_id" && <FileField label="Carte nationale — verso" file={back} onChange={setBack} />}
                <FileField label="Photo de profil" file={photo} onChange={setPhoto} maxSize="5 Mo" />
              </div>
              <label className="flex cursor-pointer items-start gap-3 text-sm leading-6 text-slate-600">
                <input type="checkbox" checked={consent} onChange={(event) => setConsent(event.target.checked)} className="mt-1 h-4 w-4 accent-emerald-800" required />
                <span>J’accepte que ces documents soient consultés de façon privée par le gérant de la tontine concernée.</span>
              </label>
              {error && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
              {notice && <p role="status" className="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
              <button type="submit" disabled={submitting || !consent} className="inline-flex items-center gap-2 rounded-xl bg-emerald-800 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-900 disabled:cursor-not-allowed disabled:opacity-50">
                {submitting ? <Loader2 size={16} className="animate-spin" /> : <FileCheck2 size={16} />}
                {submitting ? "Envoi sécurisé…" : status === "rejected" ? "Transmettre une nouvelle pièce" : "Envoyer ma pièce"}
              </button>
            </form>
          )}
          {error && !canSubmit && <p role="alert" className="mt-3 text-sm text-red-800">{error}</p>}
          {notice && !canSubmit && <p role="status" className="mt-3 text-sm text-emerald-900">{notice}</p>}
        </div>
      )}
    </section>
  );
}

function FileField({
  label,
  file,
  onChange,
  maxSize = "8 Mo",
}: {
  label: string;
  file: File | null;
  onChange: (file: File | null) => void;
  maxSize?: string;
}) {
  return (
    <label className="block rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-sm transition hover:border-emerald-600">
      <span className="block font-semibold text-slate-800">{label} <span className="text-red-600">*</span></span>
      <span className="mt-1 block truncate text-xs text-slate-500">{file?.name ?? `Image JPG, PNG ou WEBP · ${maxSize} max.`}</span>
      <input type="file" accept="image/jpeg,image/png,image/webp" required className="sr-only" onChange={(event) => onChange(event.target.files?.[0] ?? null)} />
    </label>
  );
}
