import { useEffect, useState } from "react";
import axios from "axios";
import {
  CalendarDays,
  CheckCircle2,
  Clock3,
  Loader2,
  LogIn,
  MailCheck,
  Users,
  UserPlus,
  Wallet,
  XCircle,
} from "lucide-react";
import { Link, useLocation, useParams } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import {
  membershipRequestService,
  type InvitationPreview,
  type MembershipRequestStatus,
} from "../services/membershipRequests";

interface ApiErrorPayload {
  message?: string;
  code?: string;
  errors?: Record<string, string[]>;
}

function errorMessage(error: unknown): string {
  if (axios.isAxiosError<ApiErrorPayload>(error)) {
    const payload = error.response?.data;
    const validationMessage = Object.values(payload?.errors ?? {})
      .flat()
      .find((message) => typeof message === "string");

    return validationMessage
      ?? payload?.message
      ?? "Impossible de communiquer avec le serveur. Réessaie dans quelques instants.";
  }

  return "Une erreur inattendue est survenue. Réessaie dans quelques instants.";
}

function formatAmount(amount: number): string {
  return new Intl.NumberFormat("fr-FR").format(amount);
}

function formatDate(date: string): string {
  return new Intl.DateTimeFormat("fr-FR", { dateStyle: "long" }).format(
    new Date(`${date}T00:00:00`),
  );
}

const frequencyLabels: Record<InvitationPreview["frequency"], string> = {
  daily: "Quotidienne",
  weekly: "Hebdomadaire",
  monthly: "Mensuelle",
};

const typeLabels: Record<InvitationPreview["type"], string> = {
  family: "Famille",
  friends: "Amis",
  work: "Travail",
  other: "Autre",
};

export default function JoinPage() {
  const { token = "" } = useParams();
  const location = useLocation();
  const { user, isAuthenticated, refreshUser } = useAuth();
  const [invitation, setInvitation] = useState<InvitationPreview | null>(null);
  const [request, setRequest] = useState<MembershipRequestStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [requestLoading, setRequestLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [resending, setResending] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => {
    let cancelled = false;

    const loadInvitation = async () => {
      setLoading(true);
      setError("");

      if (!/^[A-Za-z0-9]{48}$/.test(token)) {
        setError("Ce lien d’invitation est invalide ou n’est plus disponible.");
        setLoading(false);
        return;
      }

      try {
        const data = await membershipRequestService.getInvitation(token);
        if (!cancelled) setInvitation(data);
      } catch {
        if (!cancelled) {
          setError("Ce lien d’invitation est invalide ou n’est plus disponible.");
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    void loadInvitation();

    return () => {
      cancelled = true;
    };
  }, [token]);

  useEffect(() => {
    let cancelled = false;

    const loadMyRequest = async () => {
      if (!invitation || !isAuthenticated || user?.email_verified !== true) {
        setRequest(null);
        return;
      }

      setRequestLoading(true);
      setError("");

      try {
        const data = await membershipRequestService.getMyRequest(invitation.tontine_public_id);
        if (!cancelled) setRequest(data);
      } catch (loadError) {
        if (!cancelled) setError(errorMessage(loadError));
      } finally {
        if (!cancelled) setRequestLoading(false);
      }
    };

    void loadMyRequest();

    return () => {
      cancelled = true;
    };
  }, [invitation, isAuthenticated, user?.email_verified]);

  const handleSubmit = async () => {
    if (!invitation) return;

    setSubmitting(true);
    setError("");
    setNotice("");

    try {
      const submitted = await membershipRequestService.submit(
        invitation.tontine_public_id,
        token,
      );
      setRequest(submitted);
      setNotice("Ta demande a été transmise au gérant de la tontine.");
    } catch (submitError) {
      if (axios.isAxiosError<ApiErrorPayload>(submitError)
        && submitError.response?.data?.code === "email_not_verified") {
        setError("Vérifie ton adresse email avant d’envoyer une demande d’adhésion.");
      } else {
        setError(errorMessage(submitError));
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleResendVerification = async () => {
    setResending(true);
    setError("");
    setNotice("");

    try {
      setNotice(await membershipRequestService.resendVerification());
    } catch (resendError) {
      setError(errorMessage(resendError));
    } finally {
      setResending(false);
    }
  };

  const handleRefreshVerification = async () => {
    setRefreshing(true);
    setError("");
    setNotice("");

    try {
      await refreshUser();
      setNotice("Statut de vérification actualisé.");
    } catch (refreshError) {
      setError(errorMessage(refreshError));
    } finally {
      setRefreshing(false);
    }
  };

  const frequency = invitation ? frequencyLabels[invitation.frequency] : "";
  const type = invitation ? typeLabels[invitation.type] : "";
  const returnState = { from: location };

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
      <section
        aria-labelledby="join-title"
        className="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-xl shadow-slate-200/70"
      >
        <div className="bg-gradient-to-br from-sky-700 to-cyan-600 px-6 py-8 text-white sm:px-10 sm:py-10">
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-sky-100">
            Invitation e-Tontine
          </p>
          <h1 id="join-title" className="mt-3 text-3xl font-bold sm:text-4xl">
            Rejoins une tontine
          </h1>
          <p className="mt-3 max-w-xl text-sm leading-6 text-sky-50 sm:text-base">
            Découvre les informations du groupe avant d’envoyer ta demande. Le lien seul
            ne t’ajoute pas à la tontine : le gérant doit l’accepter.
          </p>
        </div>

        <div className="space-y-6 px-6 py-7 sm:px-10 sm:py-9">
          {loading && (
            <div className="flex items-center gap-3 py-10 text-slate-600" role="status">
              <Loader2 className="animate-spin" size={20} />
              <span>Chargement de l’invitation…</span>
            </div>
          )}

          {!loading && error && !invitation && (
            <div className="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800" role="alert">
              {error}
            </div>
          )}

          {!loading && invitation && (
            <>
              <div>
                <p className="text-sm font-medium text-slate-500">Gérée par {invitation.manager_name}</p>
                <h2 className="mt-1 text-2xl font-bold text-slate-900">{invitation.name}</h2>
                <span className="mt-3 inline-flex rounded-full bg-sky-50 px-3 py-1 text-sm font-medium text-sky-800">
                  {type}
                </span>
              </div>

              <dl className="grid gap-3 sm:grid-cols-2">
                <div className="flex items-start gap-3 rounded-2xl bg-slate-50 p-4">
                  <Wallet className="mt-0.5 shrink-0 text-sky-700" size={20} aria-hidden="true" />
                  <div>
                    <dt className="text-sm text-slate-500">Cotisation</dt>
                    <dd className="mt-1 font-semibold text-slate-900">{formatAmount(invitation.contribution_amount)} FCFA</dd>
                  </div>
                </div>
                <div className="flex items-start gap-3 rounded-2xl bg-slate-50 p-4">
                  <CalendarDays className="mt-0.5 shrink-0 text-sky-700" size={20} aria-hidden="true" />
                  <div>
                    <dt className="text-sm text-slate-500">Fréquence · début</dt>
                    <dd className="mt-1 font-semibold text-slate-900">{frequency} · {formatDate(invitation.start_date)}</dd>
                  </div>
                </div>
                <div className="flex items-start gap-3 rounded-2xl bg-slate-50 p-4 sm:col-span-2">
                  <Users className="mt-0.5 shrink-0 text-sky-700" size={20} aria-hidden="true" />
                  <div>
                    <dt className="text-sm text-slate-500">Membres inscrits</dt>
                    <dd className="mt-1 font-semibold text-slate-900">
                      {invitation.members_count} sur {invitation.expected_members}
                    </dd>
                  </div>
                </div>
              </dl>

              {error && (
                <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                  {error}
                </div>
              )}
              {notice && (
                <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                  {notice}
                </div>
              )}

              {!isAuthenticated && (
                <div className="space-y-4 border-t border-slate-100 pt-6">
                  <p className="text-sm leading-6 text-slate-600">
                    Connecte-toi ou crée un compte pour demander à rejoindre ce groupe.
                    Ta demande reviendra automatiquement sur cette invitation.
                  </p>
                  <div className="flex flex-col gap-3 sm:flex-row">
                    <Link
                      to="/login"
                      state={returnState}
                      className="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 font-semibold text-white transition hover:bg-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
                    >
                      <LogIn size={18} aria-hidden="true" />
                      Se connecter
                    </Link>
                    <Link
                      to="/register"
                      state={returnState}
                      className="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-800 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
                    >
                      <UserPlus size={18} aria-hidden="true" />
                      Créer un compte
                    </Link>
                  </div>
                </div>
              )}

              {isAuthenticated && user?.email_verified !== true && (
                <div className="space-y-4 border-t border-slate-100 pt-6">
                  <p className="text-sm leading-6 text-slate-600">
                    Vérifie l’adresse email de ton compte pour envoyer une demande d’adhésion.
                    Consulte ta boîte mail ou demande un nouveau lien.
                  </p>
                  <div className="flex flex-col gap-3 sm:flex-row">
                    <button
                      type="button"
                      onClick={() => void handleResendVerification()}
                      disabled={resending || refreshing}
                      className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 font-semibold text-white transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
                    >
                      {resending
                        ? <Loader2 className="animate-spin" size={18} aria-hidden="true" />
                        : <MailCheck size={18} aria-hidden="true" />}
                      Renvoyer le lien de vérification
                    </button>
                    <button
                      type="button"
                      onClick={() => void handleRefreshVerification()}
                      disabled={resending || refreshing}
                      className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-800 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
                    >
                      {refreshing && <Loader2 className="animate-spin" size={18} aria-hidden="true" />}
                      J’ai vérifié mon email
                    </button>
                  </div>
                </div>
              )}

              {isAuthenticated && user?.email_verified === true && (
                <div className="border-t border-slate-100 pt-6">
                  {requestLoading && (
                    <p className="flex items-center gap-2 text-sm text-slate-600" role="status">
                      <Loader2 className="animate-spin" size={18} />
                      Vérification du statut de ta demande…
                    </p>
                  )}

                  {!requestLoading && request?.status === "pending" && (
                    <div className="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900" role="status">
                      <Clock3 className="mt-0.5 shrink-0" size={20} aria-hidden="true" />
                      <div>
                        <p className="font-semibold">Demande en attente</p>
                        <p className="mt-1 text-sm">Le gérant doit encore examiner ta demande.</p>
                      </div>
                    </div>
                  )}

                  {!requestLoading && request?.status === "approved" && (
                    <div className="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900" role="status">
                      <CheckCircle2 className="mt-0.5 shrink-0" size={20} aria-hidden="true" />
                      <div>
                        <p className="font-semibold">Demande acceptée</p>
                        <p className="mt-1 text-sm">Tu es maintenant membre de cette tontine.</p>
                      </div>
                    </div>
                  )}

                  {!requestLoading && request?.status === "rejected" && (
                    <div className="mb-4 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-900" role="status">
                      <XCircle className="mt-0.5 shrink-0" size={20} aria-hidden="true" />
                      <div>
                        <p className="font-semibold">Demande refusée</p>
                        <p className="mt-1 text-sm">Tu peux envoyer une nouvelle demande au gérant.</p>
                      </div>
                    </div>
                  )}

                  {!requestLoading && request?.status !== "approved" && (
                    <button
                      type="button"
                      onClick={() => void handleSubmit()}
                      disabled={submitting || request?.status === "pending"}
                      className="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 font-semibold text-white transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 sm:w-auto"
                    >
                      {submitting
                        ? <Loader2 className="animate-spin" size={18} aria-hidden="true" />
                        : <UserPlus size={18} aria-hidden="true" />}
                      {submitting
                        ? "Envoi de la demande…"
                        : request?.status === "pending"
                          ? "Demande envoyée"
                          : request?.status === "rejected"
                            ? "Renvoyer une demande"
                            : "Demander à rejoindre"}
                    </button>
                  )}
                </div>
              )}
            </>
          )}
        </div>
      </section>
    </main>
  );
}
