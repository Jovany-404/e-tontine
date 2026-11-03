// Service d'authentification

import api from "./api";
import type { LoginCredentials, RegisterData, User } from "../types";

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
    const res = await api.post("/auth/login", credentials);
    const { token, user } = extractAuthPayload(res.data);

    if (token) localStorage.setItem("auth_token", token);
    if (user) localStorage.setItem("user", JSON.stringify(user));

    return res.data;
  },

  // Inscription utilisateur
  register: async (payload: RegisterData) => {
    const formData = new FormData();
    formData.append("name", payload.name);
    formData.append("email", payload.email);
    formData.append("phone", payload.phone ?? "");
    formData.append("password", payload.password);
    formData.append("password_confirmation", payload.password_confirmation);
    formData.append("document_type", payload.document_type);
    formData.append("document_front", payload.document_front);
    if (payload.document_back) {
      formData.append("document_back", payload.document_back);
    }
    formData.append("profile_photo", payload.profile_photo);
    formData.append("identity_consent", payload.identity_consent ? "1" : "0");

    const res = await api.post("/auth/register", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    const { token, user } = extractAuthPayload(res.data);

    if (token) localStorage.setItem("auth_token", token);
    if (user) localStorage.setItem("user", JSON.stringify(user));

    return res.data;
  },

  // Vérifie si la session est réellement valide côté API
  validateSession: async (): Promise<User> => {
    const res = await api.get("/user");
    const user = res.data?.data ?? res.data;

    if (!user || typeof user.id !== "string") {
      throw new Error("La réponse du profil utilisateur est invalide.");
    }

    localStorage.setItem("user", JSON.stringify(user));
    return user as User;
  },

  // Déconnexion
  logout: async () => {
    try {
      await api.post("/auth/logout");
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
