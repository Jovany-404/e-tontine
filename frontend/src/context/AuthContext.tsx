// Contexte d'authentification global

import {
  createContext,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react";
import { authService } from "../services/auth";
import type { LoginCredentials, RegisterData, User } from "../types";

interface AuthContextValue {
  user: User | null;
  login: (credentials: LoginCredentials) => Promise<void>;
  register: (payload: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isAuthenticated, setIsAuthenticated] = useState(() =>
    authService.isAuthenticated(),
  );

  useEffect(() => {
    setUser(authService.getCurrentUser());
    setIsAuthenticated(authService.isAuthenticated());
  }, []);

  const login = async (credentials: LoginCredentials) => {
    try {
      await authService.login(credentials);

      setUser(await authService.validateSession());
      setIsAuthenticated(true);
    } catch (error) {
      authService.clearAuthStorage();
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    }
  };

  const register = async (payload: RegisterData) => {
    try {
      await authService.register(payload);

      setUser(authService.getCurrentUser());
      setIsAuthenticated(true);
    } catch (error) {
      authService.clearAuthStorage();
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    }
  };

  const logout = async () => {
    await authService.logout();
    setUser(null);
    setIsAuthenticated(false);
  };

  const refreshUser = async () => {
    const currentUser = await authService.validateSession();
    setUser(currentUser);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        login,
        register,
        logout,
        refreshUser,
        isAuthenticated,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
