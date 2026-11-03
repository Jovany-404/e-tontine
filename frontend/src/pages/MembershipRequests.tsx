import { useEffect, useState } from "react";
import axios from "axios";
import {
  Check,
  CheckCheck,
  Clock3,
  FileImage,
  CreditCard,
  Loader2,
  RefreshCw,
  ShieldCheck,
  UserRound,
  X,
} from "lucide-react";
import { Link, useParams } from "react-router-dom";
import {
  membershipRequestService,
  type ManagerMembershipRequest,
} from "../services/membershipRequests";

type DocumentPreview = Record<string, string>;

function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError<{ message?: string; errors?: Record<string, string[]> }>(error)) {
    const validationMessage = Object.values(error.response?.data?.errors ?? {})
      .flat()
      .find((message) => typeof message === "string");
    return validationMessage
      ?? error.response?.data?.message
      ?? "Une erreur est survenue. Réessayez.";
  }
  return "Une erreur inattendue est survenue.";
}

function identityStatusLabel(status: ManagerMembershipRequest["user"]["identity_status"]): string {
  switch (status) {
    case "verified": return "Identité vérifiée";
    case "rejected": return "Identité refusée";
    case "pending": return "Vérification requise";
    default: return "Pièce manquante";
  }
}

export default function MembershipRequests() {
  const { tontineId = "" } = useParams();
  const [requests, setRequests] = useState<ManagerMembershipRequest[]>([]);
  const [page, setPage] = useState(1);
  const [pageCount, setPageCount] = useState(1);
  const [totalCount, setTotalCount] = useState(0);
  const [reloadKey, setReloadKey] = useState(0);
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState("");
  const [selectedId, setSelectedId] = useState("");
  const [documents, setDocuments] = useState<DocumentPreview>({});
  const [documentLoading, setDocumentLoading] = useState(false);
  const [rejectionReason, setRejectionReason] = useState("");
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => {
    let cancelled = false;
    const loadRequests = async () => {
      try {
        const result = await membershipRequestService.getPendingRequests(tontineId, page);
        if (cancelled) return;
        setRequests(result.items);
        setPage(Math.min(result.page, result.pageCount));
        setPageCount(result.pageCount);
        setTotalCount(result.total);
        setError("");
      } catch (loadError: unknown) {
        if (!cancelled) setError(getErrorMessage(loadError));
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    void loadRequests();
    return () => { cancelled = true; };
  }, [page, reloadKey, tontineId]);

  const reloadRequests = () => {
    setError("");
    setLoading(true);
    setReloadKey((current) => current + 1);
  };

  const changePage = (nextPage: number) => {
    setError("");
    setLoading(true);
    setPage(nextPage);
  };

  useEffect(() => () => {
    Object.values(documents).forEach((url) => URL.revokeObjectURL(url));
  }, [documents]);

  const selectedRequest = requests.find((request) => request.id === selectedId) ?? null;

  const clearDocuments = () => {
    Object.values(documents).forEach((url) => URL.revokeObjectURL(url));
    setDocuments({});
    setSelectedId("");
  };

  const handleViewDocuments = async (request: ManagerMembershipRequest) => {
    clearDocuments();
    setError("");
    setDocumentLoading(true);
    setSelectedId(request.id);
    try {
      const kinds = request.user.identity_document_type === "national_id"
        ? ["front", "back", "profile"] as const
        : ["front", "profile"] as const;
      const results = await Promise.all(kinds.map(async (kind) => {
        const blob = await membershipRequestService.getIdentityFile(
          tontineId,
          request.id,
          kind,
        );
        return [kind, URL.createObjectURL(blob)] as const;
      }));
      const previews = Object.fromEntries(results) as DocumentPreview;
      setDocuments(previews);
    } catch (loadError: unknown) {
      setError(getErrorMessage(loadError));
    } finally {
      setDocumentLoading(false);
    }
  };

  const handleIdentityDecision = async (decision: "approve" | "reject") => {
    if (!selectedRequest) return;
    if (decision === "reject" && rejectionReason.trim().length < 5) {
      setError("Indiquez une raison de refus d’au moins 5 caractères.");
      return;
    }

    setBusyId(selectedRequest.id);
    setError("");
    setNotice("");
    try {
      await membershipRequestService.reviewIdentity(
        tontineId,
        selectedRequest.id,
        decision,
        rejectionReason.trim(),
      );
      clearDocuments();
      setRejectionReason("");
      setNotice(decision === "approve"
        ? "Identité vérifiée. Vous pouvez maintenant décider de l’adhésion."
        : "Pièce refusée. Le candidat pourra transmettre un nouveau document.");
      reloadRequests();
    } catch (reviewError: unknown) {
      setError(getErrorMessage(reviewError));
    } finally {
      setBusyId("");
    }
  };

  const handleMembershipDecision = async (
    request: ManagerMembershipRequest,
    decision: "approve" | "reject",
  ) => {
    setBusyId(request.id);
    setError("");
    setNotice("");
    try {
      if (decision === "approve") {
        await membershipRequestService.approveMembership(tontineId, request.id);
      } else {
        await membershipRequestService.rejectMembership(tontineId, request.id);
      }
      setNotice(decision === "approve" ? "Le membre a rejoint la tontine." : "Demande d’adhésion refusée.");
      reloadRequests();
    } catch (decisionError: unknown) {
      setError(getErrorMessage(decisionError));
    } finally {
      setBusyId("");
    }
  };

  return (
    <div className="mx-auto max-w-5xl space-y-7">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <Link to={`/tontines/${tontineId}`} className="text-sm font-semibold text-emerald-800 hover:text-emerald-950">← Retour à la tontine</Link>
          <h1 className="mt-3 text-3xl font-bold tracking-tight text-slate-950">Demandes d’adhésion</h1>
          <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Vérifiez la pièce d’identité du candidat avant de décider de son adhésion. Les documents sont privés et ne sont pas accessibles aux autres membres.</p>
        </div>
        <button type="button" onClick={reloadRequests} disabled={loading} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-300 disabled:opacity-60">
          <RefreshCw size={16} className={loading ? "animate-spin" : ""} /> Actualiser
        </button>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <div className="rounded-2xl border border-white bg-white p-5 shadow-sm">
          <p className="text-sm text-slate-500">En attente</p>
          <p className="mt-2 text-3xl font-bold text-slate-950">{totalCount}</p>
        </div>
        <div className="rounded-2xl border border-white bg-white p-5 shadow-sm sm:col-span-2">
          <div className="flex items-start gap-3">
            <ShieldCheck className="mt-0.5 text-emerald-700" size={20} />
            <p className="text-sm leading-6 text-slate-600">Une identité vérifiée est nécessaire pour approuver la demande. Vérification et adhésion restent deux décisions distinctes.</p>
          </div>
        </div>
      </div>

      {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>}
      {notice && <div role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</div>}

      {loading ? (
        <div className="flex items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white p-12 text-slate-500"><Loader2 className="animate-spin" size={19} /> Chargement des demandes…</div>
      ) : requests.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
          <CheckCheck className="mx-auto text-emerald-700" size={32} />
          <h2 className="mt-4 text-lg font-bold text-slate-900">Aucune demande en attente</h2>
          <p className="mt-2 text-sm text-slate-500">Les prochaines demandes envoyées avec votre lien d’invitation apparaîtront ici.</p>
        </div>
      ) : (
        <div className="space-y-4">
          {requests.map((request) => {
            const identityVerified = request.user.identity_status === "verified";
            const isBusy = busyId === request.id;
            return (
              <article key={request.id} className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md sm:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                  <div className="flex items-center gap-3">
                    <span className="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-800"><UserRound size={22} /></span>
                    <div>
                      <h2 className="font-bold text-slate-950">{request.user.name}</h2>
                      <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500"><Clock3 size={14} /> Demande du {new Intl.DateTimeFormat("fr-FR", { dateStyle: "medium" }).format(new Date(request.created_at))}</p>
                    </div>
                  </div>
                  <span className={`rounded-full px-3 py-1.5 text-xs font-bold ${identityVerified ? "bg-emerald-100 text-emerald-900" : request.user.identity_status === "rejected" ? "bg-red-100 text-red-800" : "bg-amber-100 text-amber-900"}`}>
                    {identityStatusLabel(request.user.identity_status)}
                  </span>
                </div>

                <div className="mt-5 flex flex-wrap gap-3">
                  {request.user.identity_status === "pending" && (
                    <button type="button" onClick={() => void handleViewDocuments(request)} className="inline-flex items-center gap-2 rounded-xl border border-emerald-800 px-4 py-2.5 text-sm font-semibold text-emerald-900 hover:bg-emerald-50">
                      <CreditCard size={17} /> Consulter les pièces
                    </button>
                  )}
                  {identityVerified && (
                    <>
                      <button type="button" onClick={() => void handleMembershipDecision(request, "approve")} disabled={isBusy} className="inline-flex items-center gap-2 rounded-xl bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-900 disabled:opacity-60">
                        {isBusy ? <Loader2 size={16} className="animate-spin" /> : <Check size={16} />} Approuver l’adhésion
                      </button>
                      <button type="button" onClick={() => void handleMembershipDecision(request, "reject")} disabled={isBusy} className="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-800 hover:bg-red-50 disabled:opacity-60">
                        <X size={16} /> Refuser l’adhésion
                      </button>
                    </>
                  )}
                  {request.user.identity_status === "unverified" && (
                    <p className="text-sm text-slate-500">Le candidat doit envoyer sa pièce d’identité avant que vous puissiez décider.</p>
                  )}
                  {request.user.identity_status === "rejected" && (
                    <p className="text-sm text-slate-500">La pièce a été refusée. Le candidat doit en transmettre une nouvelle avant votre décision.</p>
                  )}
                </div>

                {selectedId === request.id && (
                  <div className="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 sm:p-5">
                    <div className="flex items-center justify-between gap-3">
                      <h3 className="font-bold text-slate-900">Documents confidentiels</h3>
                      <button type="button" onClick={clearDocuments} aria-label="Fermer les documents" className="rounded-lg p-2 text-slate-500 hover:bg-white"><X size={18} /></button>
                    </div>
                    {documentLoading ? (
                      <p className="mt-4 flex items-center gap-2 text-sm text-slate-600"><Loader2 size={16} className="animate-spin" /> Chargement sécurisé…</p>
                    ) : (
                      <>
                        <div className="mt-4 grid gap-4 sm:grid-cols-3">
                          {(["front", "back", "profile"] as const).map((kind) => documents[kind] && (
                            <figure key={kind} className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                              <img src={documents[kind]} alt={kind === "front" ? "Recto de la pièce d’identité" : kind === "back" ? "Verso de la carte nationale" : "Photo de profil"} className="h-44 w-full object-contain p-2" />
                              <figcaption className="flex items-center gap-2 border-t border-slate-100 px-3 py-2 text-xs font-semibold text-slate-600"><FileImage size={14} />{kind === "front" ? "Recto / passeport" : kind === "back" ? "Verso" : "Photo de profil"}</figcaption>
                            </figure>
                          ))}
                        </div>
                        <p className="mt-3 text-xs text-slate-500">Les images sont privées. Fermer ce panneau libère les aperçus chargés sur cet appareil.</p>
                        {request.user.identity_status !== "verified" && (
                          <div className="mt-5 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                            <div>
                              <label htmlFor={`rejection-${request.id}`} className="mb-1.5 block text-sm font-semibold text-slate-800">Motif si vous refusez la pièce</label>
                              <textarea id={`rejection-${request.id}`} value={rejectionReason} onChange={(event) => setRejectionReason(event.target.value)} maxLength={500} rows={2} className="w-full resize-y rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10" placeholder="Ex. document illisible ou expiré" />
                            </div>
                            <button type="button" onClick={() => void handleIdentityDecision("approve")} disabled={isBusy || documentLoading || !documents.front} className="self-end inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-800 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-900 disabled:opacity-50">
                              {isBusy ? <Loader2 size={16} className="animate-spin" /> : <Check size={16} />} Vérifier
                            </button>
                            <button type="button" onClick={() => void handleIdentityDecision("reject")} disabled={isBusy || documentLoading || !documents.front || rejectionReason.trim().length < 5} className="self-end inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-bold text-red-800 hover:bg-red-50 disabled:opacity-50"><X size={16} /> Refuser la pièce</button>
                          </div>
                        )}
                      </>
                    )}
                  </div>
                )}
              </article>
            );
          })}
        </div>
      )}
      {!loading && pageCount > 1 && (
        <nav aria-label="Pages des demandes" className="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3">
          <p className="text-sm text-slate-600">Page {page} sur {pageCount}</p>
          <div className="flex gap-2">
            <button type="button" onClick={() => changePage(Math.max(1, page - 1))} disabled={page <= 1} className="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 disabled:opacity-40">Précédent</button>
            <button type="button" onClick={() => changePage(Math.min(pageCount, page + 1))} disabled={page >= pageCount} className="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 disabled:opacity-40">Suivant</button>
          </div>
        </nav>
      )}
    </div>
  );
}
