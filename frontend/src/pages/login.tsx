// Page de connexion

import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { Eye, EyeOff, Mail, Lock } from "lucide-react";
import { useAuth } from "../context/AuthContext";

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = (location.state as { from?: Location } | null)?.from;

  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const [formData, setFormData] = useState({
    email: "",
    password: "",
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      await login(formData);
      navigate(
        from ? `${from.pathname}${from.search}${from.hash}` : "/",
        { replace: true },
      );
    } catch (err: any) {
      const message =
        err?.response?.data?.message || err?.message || "Erreur de connexion";

      if (
        err?.code === "ERR_NETWORK" ||
        /network|failed to fetch|connection refused/i.test(message)
      ) {
        setError(
          "Impossible de joindre le serveur. Vérifiez que l’API est démarrée et que l’URL de connexion est correcte.",
        );
        return;
      }

      setError(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#f5f7f1] p-4">
      <div className="pointer-events-none absolute -left-20 top-0 h-72 w-72 rounded-full bg-emerald-300/20 blur-3xl" />
      <div className="pointer-events-none absolute -bottom-24 -right-20 h-96 w-96 rounded-full bg-amber-300/20 blur-3xl" />
      <div className="brand-depth relative w-full max-w-md rounded-[2rem] border border-white/80 bg-white p-8 shadow-[0_30px_80px_-35px_rgba(15,50,34,0.35)] sm:p-10">
        <div className="mb-8 text-center">
          <div className="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-800 text-2xl font-black text-amber-300 shadow-lg shadow-emerald-900/20">E</div>
          <h1 className="mt-4 text-2xl font-black tracking-[0.12em] text-emerald-950">E-TONTINE</h1>
          <p className="mt-2 text-slate-600">Connectez-vous à votre espace sécurisé</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Email
            </label>
            <div className="relative">
              <Mail
                className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                size={18}
              />
              <input
                type="email"
                value={formData.email}
                onChange={(e) =>
                  setFormData({ ...formData, email: e.target.value })
                }
                className="form-input"
                placeholder="votre@email.com"
                required
              />
            </div>
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Mot de passe
            </label>
            <div className="relative">
              <Lock
                className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                size={18}
              />
              <input
                type={showPassword ? "text" : "password"}
                value={formData.password}
                onChange={(e) =>
                  setFormData({ ...formData, password: e.target.value })
                }
                className="form-input pr-12"
                placeholder="••••••••"
                required
              />
              <button
                type="button"
                onClick={() => setShowPassword((v) => !v)}
                aria-label={showPassword ? "Masquer le mot de passe" : "Afficher le mot de passe"}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-800"
              >
                {showPassword ? (
                  <EyeOff size={18} className="text-slate-400" />
                ) : (
                  <Eye size={18} className="text-slate-400" />
                )}
              </button>
            </div>
          </div>

          {error && <div className="text-sm text-red-600">{error}</div>}

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-xl bg-emerald-800 py-3.5 font-semibold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-900 disabled:opacity-60"
          >
            {loading ? "Connexion..." : "Connexion"}
          </button>
        </form>

        <div className="mt-6 text-center">
          <Link
            to="/register"
            state={location.state}
            className="text-sm font-semibold text-emerald-800 hover:text-emerald-950"
          >
            Créer un compte
          </Link>
        </div>
      </div>
    </div>
  );
}
