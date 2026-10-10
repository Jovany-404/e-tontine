// Vue d'ensemble d'une tontine avec onglets

import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { DollarSign, Users, TrendingUp, Wallet, Clock3 } from "lucide-react";
import { tontineService } from "../services/tontines";
import api from "../services/api";
import type { Tontine } from "../types";
import TontineTabs from "../components/layout/TontineTabs";

const formatMoney = (value: number) =>
  `${new Intl.NumberFormat("fr-FR").format(value || 0)} FCFA`;

export default function TontineOverview() {
  const { id } = useParams();
  const [tontine, setTontine] = useState<Tontine | null>(null);
  const [contributions, setContributions] = useState<any[]>([]);
  const [draws, setDraws] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      if (!id) return;

      try {
        const res = await tontineService.getById(Number(id));
        setTontine(res.data ?? null);

        const [contribRes, drawsRes] = await Promise.all([
          api
            .get(`/tontines/${id}/contributions`)
            .catch(() => ({ data: { data: [] } })),
          api
            .get(`/tontines/${id}/draws`)
            .catch(() => ({ data: { data: [] } })),
        ]);

        setContributions(contribRes?.data?.data ?? contribRes?.data ?? []);
        setDraws(drawsRes?.data?.data ?? drawsRes?.data ?? []);
      } finally {
        setLoading(false);
      }
    };

    void load();
  }, [id]);

  if (loading) return <div className="p-6 text-slate-600">Chargement...</div>;
  if (!tontine)
    return <div className="p-6 text-slate-600">Tontine introuvable.</div>;

  const progression = Number(tontine.progression ?? 0);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-slate-900">{tontine.nom}</h1>
        <p className="mt-1 text-slate-500">Vue d'ensemble de la tontine</p>
      </div>

      <div className="rounded-2xl bg-white shadow-sm">
        <TontineTabs />

        <div className="space-y-6 p-6">
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <SummaryCard
              label="Montant total"
              value={formatMoney(Number(tontine.montant_total ?? 0))}
              icon={<DollarSign className="text-emerald-600" />}
            />
            <SummaryCard
              label="Montant / membre"
              value={formatMoney(Number(tontine.montant_membre ?? 0))}
              icon={<Wallet className="text-sky-600" />}
            />
            <SummaryCard
              label="Membres"
              value={String(tontine.nombre_membres ?? 0)}
              icon={<Users className="text-violet-600" />}
            />
            <SummaryCard
              label="Progression"
              value={`${Math.min(100, Math.max(0, progression))}%`}
              icon={<TrendingUp className="text-amber-600" />}
            />
          </div>

          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <div className="flex items-center justify-between gap-3">
              <div>
                <p className="text-sm text-slate-500">Statut</p>
                <p className="text-lg font-semibold text-slate-900">
                  {tontine.statut}
                </p>
              </div>
              <div className="flex items-center gap-2 rounded-full bg-white px-3 py-2 text-sm text-slate-600">
                <Clock3 size={16} />
                {tontine.date_debut}
              </div>
            </div>

            <div className="mt-4">
              <div className="mb-2 flex items-center justify-between text-sm text-slate-600">
                <span>Avancement</span>
                <span>{Math.min(100, Math.max(0, progression))}%</span>
              </div>
              <div className="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                <div
                  className="h-full rounded-full bg-sky-600"
                  style={{
                    width: `${Math.min(100, Math.max(0, progression))}%`,
                  }}
                />
              </div>
            </div>
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <Panel
              title="Dernières cotisations"
              emptyMessage="Aucune cotisation enregistrée."
            >
              {contributions.length === 0 ? null : (
                <div className="space-y-3">
                  {contributions.slice(0, 5).map((item, index) => (
                    <div
                      key={`${item.id ?? index}-${item.date_paiement ?? item.created_at}`}
                      className="flex items-center justify-between rounded-xl bg-slate-50 p-3"
                    >
                      <div>
                        <p className="font-medium text-slate-900">
                          {item.member_name ?? item.member ?? "Membre"}
                        </p>
                        <p className="text-xs text-slate-500">
                          {item.date_paiement ?? item.created_at}
                        </p>
                      </div>
                      <span className="font-semibold text-emerald-700">
                        {formatMoney(Number(item.montant ?? 0))}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </Panel>

            <Panel
              title="Historique des tirages"
              emptyMessage="Aucun tirage enregistré."
            >
              {draws.length === 0 ? null : (
                <div className="space-y-3">
                  {draws.slice(0, 5).map((item, index) => (
                    <div
                      key={`${item.id ?? index}-${item.date_tirage ?? item.created_at}`}
                      className="rounded-xl bg-amber-50 p-3"
                    >
                      <p className="font-medium text-slate-900">
                        {item.winner_name ?? item.gagnant ?? "Gagnant"}
                      </p>
                      <p className="text-xs text-slate-600">
                        {item.date_tirage ?? item.created_at}
                      </p>
                      <p className="mt-1 text-sm font-semibold text-amber-700">
                        {formatMoney(
                          Number(item.montant_distribue ?? item.montant ?? 0),
                        )}
                      </p>
                    </div>
                  ))}
                </div>
              )}
            </Panel>
          </div>
        </div>
      </div>
    </div>
  );
}

function SummaryCard({
  label,
  value,
  icon,
}: {
  label: string;
  value: string;
  icon: React.ReactNode;
}) {
  return (
    <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
      <div className="flex items-center justify-between gap-3">
        <div>
          <p className="text-sm text-slate-500">{label}</p>
          <p className="mt-2 text-2xl font-bold text-slate-900">{value}</p>
        </div>
        <div className="rounded-xl bg-slate-100 p-2">{icon}</div>
      </div>
    </div>
  );
}

function Panel({
  title,
  emptyMessage,
  children,
}: {
  title: string;
  emptyMessage: string;
  children: React.ReactNode;
}) {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-5">
      <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
      <div className="mt-4">
        {children && (Array.isArray(children) ? children.length > 0 : true) ? (
          children
        ) : (
          <p className="text-sm text-slate-500">{emptyMessage}</p>
        )}
      </div>
    </div>
  );
}
