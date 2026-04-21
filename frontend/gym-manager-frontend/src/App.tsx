import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";

import ProtectedRoute from "./components/ProtectedRoute";
import { ROLE_ADMIN, ROLE_CLIENTE, ROLE_ENTRENADOR } from "./config/navigation";
import LoginPage from "./features/auth/Login";
import MainLayout from "./layouts/MainLayout";
import { useAuthStore } from "./store/authStore";

// Placeholder temporal mientras no existan las páginas reales en /features.
function Placeholder({ title }: { title: string }) {
  return (
    <section>
      <h1 className="text-2xl font-medium tracking-tight">{title}</h1>
      <p className="mt-2 text-sm text-[rgba(4,14,32,0.69)]">
        Pantalla pendiente de implementación.
      </p>
    </section>
  );
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
      return <Navigate to="/cliente" replace />;
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

        {/* Zona Cliente */}
        <Route
          path="/cliente"
          element={
            <ProtectedRoute allowedRoles={[ROLE_CLIENTE]}>
              <MainLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<Placeholder title="Inicio" />} />
          <Route
            path="calendario"
            element={<Placeholder title="Calendario de clases" />}
          />
          <Route
            path="reservas"
            element={<Placeholder title="Mis reservas" />}
          />
          <Route path="perfil" element={<Placeholder title="Mi perfil" />} />
        </Route>

        {/* Zona Entrenador */}
        <Route
          path="/entrenador"
          element={
            <ProtectedRoute allowedRoles={[ROLE_ENTRENADOR]}>
              <MainLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<Navigate to="agenda" replace />} />
          <Route path="agenda" element={<Placeholder title="Mi agenda" />} />
          <Route path="perfil" element={<Placeholder title="Mi perfil" />} />
        </Route>

        {/* Zona Admin */}
        <Route
          path="/admin"
          element={
            <ProtectedRoute allowedRoles={[ROLE_ADMIN]}>
              <MainLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<Placeholder title="Dashboard" />} />
          <Route
            path="usuarios"
            element={<Placeholder title="Gestión de usuarios" />}
          />
          <Route
            path="clases"
            element={<Placeholder title="Gestión de oferta / clases" />}
          />
          <Route
            path="reservas"
            element={<Placeholder title="Gestión de reservas" />}
          />
          <Route
            path="informes"
            element={<Placeholder title="Informes y métricas" />}
          />
        </Route>

        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
