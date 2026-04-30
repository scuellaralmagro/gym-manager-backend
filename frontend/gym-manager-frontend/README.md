# GymManager Client

Frontend web de GymManager, una aplicación para gestionar usuarios, clases, reservas, perfiles y métricas de un gimnasio.

## Stack

- React 19
- Vite
- TypeScript
- Zustand para el estado de autenticación
- TanStack Query para peticiones, caché e invalidaciones
- Axios como cliente HTTP
- Tailwind CSS para estilos

## Separación Backend/Frontend

El proyecto separa backend y frontend para que cada parte tenga una responsabilidad clara. El backend Laravel expone la API, valida reglas de negocio y gestiona los datos. El frontend React se centra en la experiencia de usuario: pantallas, formularios, calendarios, tablas y feedback visual.

Esta separación facilita trabajar en paralelo, probar cada capa por separado y cambiar la interfaz sin tocar la lógica principal del servidor.

## Requisitos

- Node.js 20 o superior recomendado
- npm
- Backend de GymManager levantado y accesible

## Configuración Local

Instala las dependencias:

```bash
npm install
```

Crea un archivo `.env` en la raíz de este frontend:

```env
VITE_API_URL=http://localhost:8000
```

Levanta el servidor de desarrollo:

```bash
npm run dev
```

Por defecto, Vite mostrará la URL local en consola, normalmente `http://localhost:5173`.

## Scripts Disponibles

```bash
npm run dev
npm run build
npm run lint
npm run preview
```

`npm run build` compila TypeScript y genera la versión de producción en `dist`.
