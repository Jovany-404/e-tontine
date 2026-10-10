// Client HTTP central pour toute l'application

import axios from "axios";

const rawBaseUrl =
  import.meta.env.VITE_API_URL?.trim() || "http://localhost:8001/api/v1";

const api = axios.create({
  baseURL: rawBaseUrl.replace(/\/+$/, ""),
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Ajoute automatiquement le token Bearer à chaque requête
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("auth_token");
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
    config.headers["X-Requested-With"] = "XMLHttpRequest";
    config.headers["Accept"] = "application/json";
  }
  return config;
});

// Gère les erreurs d'authentification
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const url = error.config?.url ?? "";
      const isAuthRequest = ["/login", "/register", "/logout"].some((route) =>
        url.includes(route),
      );
      const hasToken = !!localStorage.getItem("auth_token");

      if (!isAuthRequest && hasToken) {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user");

        if (!window.location.pathname.startsWith("/login")) {
          window.location.assign("/login");
        }
      }
    }
    return Promise.reject(error);
  },
);

export default api;
