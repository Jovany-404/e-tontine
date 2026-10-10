import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { Trophy } from "lucide-react";
import api from "../services/api";
import { useAuth } from "../context/AuthContext";

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

// Page historique / résultats des tirages
export default function Draws() {
  const { tontineId } = useParams();
  const { user } = useAuth();
  const [items, setItems] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [drawing, setDrawing] = useState(false);

  const canDraw = isAdminRole(user?.role);

  useEffect(() => {
    const load = async () => {
      try {
        if (!tontineId) return;
        const { data } = await api.get(`/tontines/${tontineId}/draws`);
        setItems(data?.data ?? data ?? []);
      } catch (err: any) {
        setError(
          err.response?.data?.message ?? "Erreur de chargement des tirages",
        );
      } finally {
        setLoading(false);
      }
    };

    void load();
  }, [tontineId]);

  const handleDraw = async () => {
    if (!tontineId || !canDraw) return;

    setDrawing(true);
    setError("");

    try {
      const endpoints = [
        `/tontines/${tontineId}/draws`,
        `/tontines/${tontineId}/draw`,
      ];
      let lastError: any = null;

      for (const endpoint of endpoints) {
        try {
          const { data } = await api.post(endpoint);
          const nextItems = data?.data ?? data ?? [];
          setItems(Array.isArray(nextItems) ? nextItems : [nextItems]);
          return;
        } catch (err) {
          lastError = err;
        }
      }

      throw lastError;
    } catch (err: any) {
      setError(
        err.response?.data?.message ??
          "Le tirage au sort est indisponible pour cette tontine.",
      );
    } finally {
      setDrawing(false);
    }
  };

  if (loading)
    return <div className="p-6 text-slate-600">Chargement des tirages...</div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-slate-900">Tirages</h1>
          <p className="mt-1 text-slate-500">
            Calendrier, historique et résultats
          </p>
        </div>

        {canDraw && (
          <button
            type="button"
            onClick={() => void handleDraw()}
            disabled={drawing}
            className="rounded-xl bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-60"
          >
            {drawing ? "Tirage..." : "Tirer au sort"}
          </button>
        )}
      </div>

      {error && (
        <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      )}

      <div className="grid gap-4">
        {items.length === 0 ? (
          <div className="rounded-2xl bg-white p-6 shadow-sm">
            <p className="text-sm text-slate-500">Aucun tirage trouvé.</p>
          </div>
        ) : (
          items.map((item) => (
            <div
              key={
                item.id ??
                `${item.winner_name ?? "winner"}-${item.date_tirage ?? item.created_at}`
              }
              className="rounded-2xl bg-white p-6 shadow-sm"
            >
              <div className="flex items-center gap-3">
                <div className="rounded-xl bg-amber-50 p-3 text-amber-700">
                  <Trophy size={18} />
                </div>
                <div>
                  <p className="font-medium text-slate-900">
                    {item.winner_name ?? item.gagnant ?? "Gagnant"}
                  </p>
                  <p className="text-sm text-slate-500">
                    {item.date_tirage ?? item.created_at}
                  </p>
                </div>
              </div>

              <div className="mt-4 text-sm text-slate-600">
                Montant distribué :{" "}
                <span className="font-semibold">
                  {item.montant_distribue ?? item.montant ?? 0} FCFA
                </span>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
