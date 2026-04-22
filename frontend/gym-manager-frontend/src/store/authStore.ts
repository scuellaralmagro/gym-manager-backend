import { create } from "zustand";

// Usamos Zustand (una librería de estado para React) para guardar el estado de la autenticación y el usuario autenticado.

// Interfaces para guardar el estado de la autenticación y el usuario autenticado.
// AuthUser: información del usuario autenticado.
// AuthState: estado de la autenticación.
// useAuthStore: hook para acceder al estado de la autenticación y el usuario autenticado.

export type AuthUser = {
  id: number;
  nombre: string;
  apellidos: string;
  email: string;
  telefono: string | null;
  id_rol: number;
};

type AuthState = {
  user: AuthUser | null;
  isAuthenticated: boolean;
  login: (user: AuthUser) => void;
  updateUser: (patch: Partial<AuthUser>) => void;
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
  updateUser: (patch) =>
    set((state) =>
      state.user ? { user: { ...state.user, ...patch } } : state,
    ),
  logout: () =>
    set({
      user: null,
      isAuthenticated: false,
    }),
}));
