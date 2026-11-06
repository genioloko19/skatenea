# Cliente — SKATENEA SPA

Interfaz React 19 + TypeScript + Vite, completamente separada del servidor.

## Principios

- **Semántica:** `<header>`, `<aside>`, `<main>`, `<footer>`, `<nav>` y landmarks ARIA.
- **Glassmorphism:** superficies translúcidas, blur, profundidad y microinteracciones.
- **12 temas × 3 modos:** claro, oscuro y alto contraste.
- **Geometrías variables:** suave, recta, píldora y orgánica.
- **Fondos dinámicos:** aurora, malla, ondas, orbes, retícula y estático.
- **Accesibilidad:** foco visible, navegación por teclado, `prefers-reduced-motion`, alto contraste real.

## Estructura

```
cliente/
├── src/
│   ├── api/            Cliente HTTP tipado y contratos
│   ├── auth/           Contexto de sesión JWT
│   ├── theme/          Temas, modos, geometrías y persistencia
│   ├── components/
│   │   ├── layout/     AppShell, Header, Sidebar, Background
│   │   ├── controlpanel/  Modal con pestañas
│   │   └── ui/         Button, Field, Card, Modal, Tabs, Toast, etc.
│   ├── pages/          Landing, login, registro, dashboard, rifas, boletas…
│   ├── routes/         Rutas protegidas y permisos
│   ├── lib/            Utilidades de formato y clases
│   └── styles/         Tokens, base, componentes y páginas
├── scripts/qa-smoke.mjs  QA visual con Chrome + capturas
└── public/             Recursos estáticos
```

## Arranque

```powershell
npm install
npm run dev
```

El proxy de Vite reenvía `/api` a `http://127.0.0.1:8000`.

Para un backend distinto:

```powershell
$env:VITE_API_URL = 'https://api.example.com/api/v1'
npm run build
```

## Scripts

| Script | Descripción |
|---|---|
| `npm run dev` | Servidor de desarrollo |
| `npm run build` | Type-check + build de producción |
| `npm run lint` | TypeScript sin emitir |
| `npm run test` | Vitest |
| `npm run qa:smoke` | Smoke test + 46 capturas |

## Panel de control

El header incluye un dropdown con:

- **Panel de control** → modal con pestañas: Apariencia, Perfil, General, Usuarios, Rifas, Boletas, Seguridad, API, Auditoría, Integraciones, Notificaciones y Sistema.
- **Perfil del usuario** → `/app/profile`.
- **Cerrar sesión** → revoca el refresh token.

## Temas

| Tema | Identidad |
|---|---|
| Aurora | Verde/violeta boreal |
| Neón | Magenta/cian eléctrico |
| Atardecer | Cálido cinematográfico |
| Océano | Azul profundo |
| Bosque | Natural y sereno |
| Candy | Lúdico y vibrante |
| Cyber | Futurista |
| Royal | Violeta y oro |
| Terracota | Artesanal |
| Monocromo | Editorial |
| Tropical | Solar y festivo |
| Galaxia | Cósmico |

Cada tema se combina con `light`, `dark` y `contrast`.

## QA visual

```powershell
npm run qa:smoke
```

Genera:

- `../docs/qa/smoke-result.json`
- `../docs/assets/screenshots/01-public-landing.png` … `10-audit.png`
- `../docs/assets/screenshots/theme-<tema>-<modo>.png` (36 capturas)
