import axios from 'axios'

import { useAuthStore } from '../store/authStore'

const api = axios.create({
  baseURL: 'http://localhost:8000',
  // Activo withCredentials porque así evito desajustes de CSRF con Laravel Sanctum:
  // el navegador adjunta automáticamente cookies de sesión y la cookie XSRF en cada request.
  withCredentials: true,
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status

    // Si Sanctum invalida sesión/token (401/419), limpio el estado global y fuerzo volver al login.
    if (status === 401 || status === 419) {
      useAuthStore.getState().logout()

      if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
        window.location.assign('/login')
      }
    }

    return Promise.reject(error)
  },
)

export default api
