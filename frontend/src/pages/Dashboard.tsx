// Tableau de bord principal

import { useEffect, useMemo, useState } from "react";
import {
  Users,
  TrendingUp,
  Calendar,
  DollarSign,
  CreditCard,
  ArrowUpRight,
} from "lucide-react";
import { useAuth } from "../context/AuthContext";
import api from "../services/api";
import { tontineService } from "../services/tontines";

const normalizeRole = (role?: string) =>
  String(role ?? "")
    .trim()
    .toLowerCase()
    .replace(/[^a-z]/g, "");

const isAdminRole = (role?: string) =>
  [
    "admin",
    "superadmin",
    "gestionnaire",
    "manager",
    "organisateur",
    "president",
  ].includes(normalizeRole(role));

const getProgress = (tontine: any) => {
  const explicit = Number(tontine?.progression ?? tontine?.pourcentage ?? 0);
  if (!Number.isNaN(explicit) && explicit > 0) {
    return Math.min(100, Math.max(0, explicit));
  }

  const total = Number(tontine?.montant_total ?? 0);
  const paid = Number(
    tontine?.montant_cotise ??
      tontine?.montant_recolte ??
      tontine?.montant_collecte ??
      0,
  );

  if (total > 0) {
    return Math.min(100, Math.max(0, (paid / total) * 100));
  }

  return 0;
};

const formatMoney = (value: number) =>
  `${new Intl.NumberFormat("fr-FR").format(value)} FCFA`;

export default function Dashboard() {
  const { user } = useAuth();
  const [tontines, setTontines] = useState<any[]>([]);
  const [historyByTontine, setHistoryByTontine] = useState<
    Record<number, any[]>
  >({});
  const [loading, setLoading] = useState(true);

  const isAdmin = isAdminRole(user?.role);

  useEffect(() => {
    const load = async () => {
      try {
        const response = await tontineService.getAll();
        const payload = response?.data ?? response ?? [];
        const items = Array.isArray(payload)
          ? payload
          : Array.isArray(payload?.data)
            ? payload.data
            : Array.isArray(payload?.tontines)
              ? payload.tontines
              : [];

        setTontines(items);

        if (!isAdmin && user?.id) {
          const memberTontines = items.filter((tontine: any) => {
            const ownerId = Number(
              tontine?.user_id ?? tontine?.creator_id ?? tontine?.admin_id ?? 0,
            );
            if (ownerId && ownerId === Number(user.id)) return true;

            const members =
              tontine?.members ??
              tontine?.participants ??
              tontine?.membres ??
              [];
            return members.some((member: any) => {
              const memberId = Number(
                member?.user_id ?? member?.id ?? member?.membre_id ?? 0,
              );
              const memberEmail = String(member?.email ?? "").toLowerCase();
              const currentEmail = String(user.email ?? "").toLowerCase();
              return (
                (memberId > 0 && memberId === Number(user.id)) ||
                (memberEmail && currentEmail && memberEmail === currentEmail)
              );
            });
          });

          const contributions = await Promise.all(
            memberTontines.map(async (tontine: any) => {
              try {
                const res = await api.get(
                  `/tontines/${tontine.id}/contributions`,
                );
                return [tontine.id, res?.data?.data ?? res?.data ?? []];
              } catch {
                return [tontine.id, []];
              }
            }),
          );

          setHistoryByTontine(Object.fromEntries(contributions));
        }
      } finally {
        setLoading(false);
      }
    };

    void load();
  }, [isAdmin, user?.email, user?.id]);

  const memberTontines = useMemo(() => {
    if (isAdmin) return tontines;

    if (!user?.id) return [];

    return tontines.filter((tontine: any) => {
      const ownerId = Number(
        tontine?.user_id ?? tontine?.creator_id ?? tontine?.admin_id ?? 0,
      );
      if (ownerId && ownerId === Number(user.id)) return true;

      const members =
        tontine?.members ?? tontine?.participants ?? tontine?.membres ?? [];
      return members.some((member: any) => {
        const memberId = Number(
          member?.user_id ?? member?.id ?? member?.membre_id ?? 0,
        );
        const memberEmail = String(member?.email ?? "").toLowerCase();
        const currentEmail = String(user.email ?? "").toLowerCase();
        return (
          (memberId > 0 && memberId === Number(user.id)) ||
          (memberEmail && currentEmail && memberEmail === currentEmail)
        );
      });
    });
  }, [isAdmin, tontines, user?.email, user?.id]);

  const adminSummary = useMemo(() => {
    const totalTontines = tontines.length;
    const activeTontines = tontines.filter(
      (t) => String(t?.statut ?? "").toLowerCase() === "active",
    ).length;
    const totalMembres = tontines.reduce(
      (sum, tontine) =>
        sum + Number(tontine?.nombre_membres ?? tontine?.membres_count ?? 0),
      0,
    );
    const progressionGlobal =
      totalTontines > 0
        ? Math.round(
            tontines.reduce((sum, tontine) => sum + getProgress(tontine), 0) /
              totalTontines,
          )
        : 0;

    return { totalTontines, activeTontines, totalMembres, progressionGlobal };
  }, [tontines]);

  const memberSummary = useMemo(() => {
    const contributionList = memberTontines.flatMap((tontine) => {
      const items = historyByTontine[tontine.id] ?? [];
      return items.filter((item) => {
        const memberId = Number(
          item?.user_id ?? item?.member_id ?? item?.membre_id ?? 0,
        );
        const memberEmail = String(
          item?.member_email ?? item?.email ?? "",
        ).toLowerCase();
        const currentEmail = String(user?.email ?? "").toLowerCase();
        return (
          (memberId > 0 &&
            user?.id !== undefined &&
            user?.id !== null &&
            Number(user.id) > 0 &&
            memberId === Number(user.id)) ||
          (memberEmail && currentEmail && memberEmail === currentEmail)
        );
      });
    });

    const totalCotise = contributionList.reduce(
      (sum, item) => sum + Number(item?.montant ?? 0),
      0,
    );
    const totalTarget = memberTontines.reduce(
      (sum, tontine) =>
        sum + Number(tontine?.montant_total ?? tontine?.montant_membre ?? 0),
      0,
    );
    const averageProgress =
      memberTontines.length > 0
        ? Math.round(
            memberTontines.reduce(
              (sum, tontine) => sum + getProgress(tontine),
              0,
            ) / memberTontines.length,
          )
        : 0;

    return {
      totalCotise,
      totalTarget,
      remaining: Math.max(0, totalTarget - totalCotise),
      averageProgress,
      contributionList,
    };
  }, [historyByTontine, memberTontines, user?.email, user?.id]);

  if (loading) return <div className="p-6 text-slate-600">Chargement...</div>;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-slate-900">Dashboard</h1>
        <p className="mt-1 text-slate-500">
          {isAdmin
            ? "Vue d’administration de toutes les tontines"
            : "Suivi de votre participation"}
        </p>
      </div>

      {isAdmin ? (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <StatCard
            title="Tontines"
            value={String(adminSummary.totalTontines)}
            icon={<Users className="text-sky-600" />}
          />
          <StatCard
            title="Actives"
            value={String(adminSummary.activeTontines)}
            icon={<TrendingUp className="text-emerald-600" />}
          />
          <StatCard
            title="Membres"
            value={String(adminSummary.totalMembres)}
            icon={<Calendar className="text-violet-600" />}
          />
          <StatCard
            title="Progression"
            value={`${adminSummary.progressionGlobal}%`}
            icon={<DollarSign className="text-amber-600" />}
          />
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <StatCard
            title="Mes tontines"
            value={String(memberTontines.length)}
            icon={<Users className="text-sky-600" />}
          />
          <StatCard
            title="Cotisé"
            value={formatMoney(memberSummary.totalCotise)}
            icon={<CreditCard className="text-emerald-600" />}
          />
          <StatCard
            title="Restant"
            value={formatMoney(memberSummary.remaining)}
            icon={<ArrowUpRight className="text-violet-600" />}
          />
          <StatCard
            title="Avancement"
            value={`${memberSummary.averageProgress}%`}
            icon={<TrendingUp className="text-amber-600" />}
          />
        </div>
      )}

      <div className="grid gap-6 xl:grid-cols-2">
        <div className="rounded-2xl bg-white p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-900">
            {isAdmin ? "Vue générale des tontines" : "Mes tontines"}
          </h2>
          <div className="mt-4 space-y-4">
            {(isAdmin ? tontines : memberTontines).length === 0 ? (
              <p className="text-sm text-slate-500">Aucune tontine trouvée.</p>
            ) : (
              (isAdmin ? tontines : memberTontines).map((tontine) => (
                <div
                  key={tontine.id}
                  className="rounded-xl border border-slate-200 p-4"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <p className="font-semibold text-slate-900">
                        {tontine.nom}
                      </p>
                      <p className="text-sm text-slate-500">
                        {tontine.statut || "Non défini"}
                      </p>
                    </div>
                    <span className="rounded-full bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700">
                      {getProgress(tontine).toFixed(0)}%
                    </span>
                  </div>

                  <div className="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-slate-200">
                    <div
                      className="h-full rounded-full bg-sky-600"
                      style={{
                        width: `${Math.min(100, getProgress(tontine))}%`,
                      }}
                    />
                  </div>

                  <div className="mt-3 flex items-center justify-between text-sm text-slate-600">
                    <span>{tontine.nombre_membres ?? 0} membres</span>
                    <span>
                      {formatMoney(Number(tontine?.montant_total ?? 0))}
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        <div className="rounded-2xl bg-white p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-900">
            {isAdmin ? "Suivi opérationnel" : "Historique de cotisations"}
          </h2>

          {isAdmin ? (
            <div className="mt-4 space-y-3 text-sm text-slate-600">
              <div className="rounded-xl bg-slate-50 p-4">
                <p className="text-slate-500">Tontines actives</p>
                <p className="mt-2 text-2xl font-bold text-slate-900">
                  {adminSummary.activeTontines}
                </p>
              </div>
              <div className="rounded-xl bg-slate-50 p-4">
                <p className="text-slate-500">Progression moyenne</p>
                <p className="mt-2 text-2xl font-bold text-slate-900">
                  {adminSummary.progressionGlobal}%
                </p>
              </div>
            </div>
          ) : (
            <div className="mt-4 space-y-3">
              {memberSummary.contributionList.length === 0 ? (
                <p className="text-sm text-slate-500">
                  Aucune cotisation enregistrée pour le moment.
                </p>
              ) : (
                memberSummary.contributionList
                  .slice(0, 5)
                  .map((item, index) => (
                    <div
                      key={`${item?.id ?? index}-${item?.created_at ?? item?.date_paiement ?? index}`}
                      className="rounded-xl bg-slate-50 p-3"
                    >
                      <div className="flex items-center justify-between gap-4">
                        <div>
                          <p className="font-medium text-slate-900">
                            {item?.member_name ?? user?.nom ?? "Cotisation"}
                          </p>
                          <p className="text-xs text-slate-500">
                            {item?.date_paiement ??
                              item?.created_at ??
                              "Date non renseignée"}
                          </p>
                        </div>
                        <span className="font-semibold text-emerald-700">
                          {formatMoney(Number(item?.montant ?? 0))}
                        </span>
                      </div>
                    </div>
                  ))
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function StatCard({
  title,
  value,
  icon,
}: {
  title: string;
  value: string;
  icon: React.ReactNode;
}) {
  return (
    <div className="rounded-2xl bg-white p-6 shadow-sm">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-slate-500">{title}</p>
          <p className="text-3xl font-bold text-slate-900">{value}</p>
        </div>
        <div className="rounded-xl bg-slate-100 p-2">{icon}</div>
      </div>
    </div>
  );
}
