import { create } from "zustand";

// Interfaces para guardar el estado de la autenticación y el usuario autenticado.
// AuthUser: información del usuario autenticado.
// AuthState: estado de la autenticación.
// useAuthStore: hook para acceder al estado de la autenticación y el usuario autenticado.

export type AuthUser = {
  id: number;
  nombre: string;
  email: string;
  id_rol: number;
};

type AuthState = {
  user: AuthUser | null;
  isAuthenticated: boolean;
  login: (user: AuthUser) => void;
  logout: () => void;
};

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  isAuthenticated: false,
  login: (user) =>
    set({
      user,
      isAuthenticated: true,
    }),
  logout: () =>
    set({
      user: null,
      isAuthenticated: false,
    }),
}));
