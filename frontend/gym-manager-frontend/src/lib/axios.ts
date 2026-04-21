import axios from "axios";

import { useAuthStore } from "../store/authStore";

export const AUTH_TOKEN_STORAGE_KEY = "gym-manager.authToken";

const api = axios.create({
  baseURL: "http://localhost:8000",
  withCredentials: true,
});

// Si ya hay token en el localStorage, lo reinyectamos al arrancar
const persistedToken =
  typeof window !== "undefined"
    ? window.localStorage.getItem(AUTH_TOKEN_STORAGE_KEY)
    : null;

if (persistedToken) {
  api.defaults.headers.common.Authorization = `Bearer ${persistedToken}`;
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;

    if (status === 401 || status === 419) {
      useAuthStore.getState().logout();

      if (typeof window !== "undefined") {
        window.localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
        delete api.defaults.headers.common.Authorization;

        if (window.location.pathname !== "/login") {
          window.location.assign("/login");
        }
      }
    }

    return Promise.reject(error);
  },
);

export default api;
