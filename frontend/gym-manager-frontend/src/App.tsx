import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";

import ProtectedRoute from "./components/ProtectedRoute";
import LoginPage from "./features/auth/Login";
import { useAuthStore } from "./store/authStore";

const ROLE_ADMIN = 1;
const ROLE_ENTRENADOR = 2;
const ROLE_CLIENTE = 3;

function ClienteLayout() {
  return <h1>Zona Cliente (placeholder)</h1>;
}

function EntrenadorLayout() {
  return <h1>Zona Entrenador (placeholder)</h1>;
}

function AdminLayout() {
  return <h1>Zona Admin (placeholder)</h1>;
}

function NotFoundPage() {
  return <h1>404 — Ruta no encontrada</h1>;
}

// Redirijimos la raíz "/" al home del rol por defecto.
function RootRedirect() {
  const { isAuthenticated, user } = useAuthStore();

  if (!isAuthenticated || !user) {
    return <Navigate to="/login" replace />;
  }

  switch (user.id_rol) {
    case ROLE_ADMIN:
      return <Navigate to="/admin" replace />;
    case ROLE_ENTRENADOR:
      return <Navigate to="/entrenador/agenda" replace />;
    case ROLE_CLIENTE:
      return <Navigate to="/cliente/calendario" replace />;
    default:
      return <Navigate to="/login" replace />;
  }
}

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<RootRedirect />} />
        <Route path="/login" element={<LoginPage />} />
        <Route
          path="/cliente/*"
          element={
            <ProtectedRoute allowedRoles={[ROLE_CLIENTE]}>
              <ClienteLayout />
            </ProtectedRoute>
          }
        />

        <Route
          path="/entrenador/*"
          element={
            <ProtectedRoute allowedRoles={[ROLE_ENTRENADOR]}>
              <EntrenadorLayout />
            </ProtectedRoute>
          }
        />

        <Route
          path="/admin/*"
          element={
            <ProtectedRoute allowedRoles={[ROLE_ADMIN]}>
              <AdminLayout />
            </ProtectedRoute>
          }
        />

        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
