// Service d'authentification

import api from "./api";
import type { LoginCredentials, RegisterData } from "../types";

const extractAuthPayload = (payload: any) => {
  const data = payload?.data ?? payload;
  const token =
    data?.token ??
    data?.access_token ??
    payload?.token ??
    payload?.access_token ??
    null;

  const user = data?.user ?? payload?.user ?? null;

  return {
    token:
      typeof token === "string"
        ? token.replace(/^Bearer\s+/i, "").trim()
        : null,
    user,
  };
};

const clearAuthStorage = () => {
  localStorage.removeItem("auth_token");
  localStorage.removeItem("user");
};

export const authService = {
  // Connexion utilisateur
  login: async (credentials: LoginCredentials) => {
    const res = await api.post("/login", credentials);
    const { token, user } = extractAuthPayload(res.data);

    if (token) localStorage.setItem("auth_token", token);
    if (user) localStorage.setItem("user", JSON.stringify(user));

    return res.data;
  },

  // Inscription utilisateur
  register: async (payload: RegisterData) => {
    const res = await api.post("/register", payload);
    const { token, user } = extractAuthPayload(res.data);

    if (token) localStorage.setItem("auth_token", token);
    if (user) localStorage.setItem("user", JSON.stringify(user));

    return res.data;
  },

  // Vérifie si la session est réellement valide côté API
  validateSession: async () => {
    const res = await api.get("/tontines");
    return res.data;
  },

  // Déconnexion
  logout: async () => {
    try {
      await api.post("/logout");
    } finally {
      clearAuthStorage();
    }
  },

  clearAuthStorage,

  // Récupère l'utilisateur stocké localement
  getCurrentUser: () => {
    const raw = localStorage.getItem("user");
    return raw ? JSON.parse(raw) : null;
  },

  // Vérifie si un token est présent
  isAuthenticated: () => !!localStorage.getItem("auth_token"),
};
