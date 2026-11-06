# SKATENEA V1.0.0

Sistema de Gestión de Rifas (SGR) multi-rifa con arquitectura cliente/servidor separada.

- **Servidor:** PHP 8.2 + PDO + POO + SOLID · API REST `/api/v1` · JWT + refresh rotativo + API keys
- **Cliente:** React 19 + TypeScript + Vite · glassmorphism · 12 temas × 3 modos · HTML semántico
- **Base de datos:** MySQL 8+/MariaDB 10.6+ · SQLite para desarrollo/pruebas
- **Documentación:** OpenAPI 3.1, manual técnico, manual de usuario, QA y capturas

> Estado: **V1.0.0 alfa endurecida**. No desplegar con datos reales sin completar las fases 7–9 del plan maestro y la revisión legal.

---

## 1. Estructura

```
V1.0.0/
├── servidor/     API PHP, dominio, infraestructura, pruebas
├── cliente/      SPA React/TypeScript, sistema de diseño y QA visual
├── bd/           Esquema portable, migraciones y seeds
└── docs/         Plan, ADR, OpenAPI, manuales, QA y capturas
```

## 2. Inicio rápido (Windows + XAMPP)

### 2.1 Servidor

```powershell
cd V1.0.0\servidor
composer install
Copy-Item .env.example .env
# Genera un JWT_SECRET seguro y edítalo en .env:
php -r "echo bin2hex(random_bytes(48)), PHP_EOL;"
php bin/migrate.php --fresh
php bin/seed.php
php -S 127.0.0.1:8000 -t public public/index.php

# En otra terminal: workers de correo, webhooks e impresión
php bin/mail-worker.php
php bin/webhook-worker.php
php bin/print-worker.php
```

API: `http://127.0.0.1:8000/api/v1/health`

### 2.2 Cliente

```powershell
cd V1.0.0\cliente
npm install
npm run dev
```

Cliente: `http://127.0.0.1:5173` (proxy `/api` → `127.0.0.1:8000`).

### 2.3 Credenciales de desarrollo

| Rol | Correo | Contraseña |
|---|---|---|
| Super administrador | `admin@skatenea.local` | `Admin#2026!` |
| Líder | `lider@skatenea.local` | `Lider#2026!` |
| Vendedor | `vendedor@skatenea.local` | `Vendedor#2026!` |
| Comprador | `comprador@skatenea.local` | `Comprador#2026!` |

> Solo para desarrollo. Cambiar y eliminar los seeds antes de cualquier despliegue.

## 3. Capturas

| Pantalla | Captura |
|---|---|
| Landing pública | `docs/assets/screenshots/01-public-landing.png` |
| Login | `docs/assets/screenshots/02-login.png` |
| Dashboard | `docs/assets/screenshots/03-dashboard-admin.png` |
| Panel de control | `docs/assets/screenshots/04-control-panel.png` |
| Rifas | `docs/assets/screenshots/06-raffles.png` |
| Detalle de rifa | `docs/assets/screenshots/07-raffle-detail.png` |
| Boletas | `docs/assets/screenshots/08-tickets.png` |
| Temas | `docs/assets/screenshots/theme-<tema>-<modo>.png` |

Los 12 temas en los 3 modos están en `docs/assets/screenshots/`.

## 4. Scripts

### Servidor

```powershell
cd servidor
composer test        # PHPUnit
composer lint        # PSR-12
composer stan        # PHPStan nivel 6
composer quality     # lint + stan + test
php bin/migrate.php --fresh
php bin/seed.php
php bin/mail-worker.php --once
php bin/webhook-worker.php --once
php bin/print-worker.php --once
php bin/maintenance.php
```

### Cliente

```powershell
cd cliente
npm run dev
npm run build
npm run lint         # TypeScript --noEmit
npm run test         # Vitest
npm run qa:smoke     # QA visual con Chrome + capturas
npm run qa:e2e       # E2E por roles
npm run qa:axe       # Accesibilidad WCAG 2.2 AA
npm run qa:perf      # Performance base con ApacheBench
```

## 5. Seguridad implementada

- PDO con consultas preparadas y transacciones.
- JWT HS256 de 15 minutos, refresh token opaco rotativo en cookie HttpOnly.
- CSRF double-submit para refresh.
- RBAC por permisos y verificación de propiedad de recursos.
- Rate limiting global y específico para login/registro/refresh/recuperación.
- Bloqueo temporal de cuenta tras intentos fallidos.
- Recuperación de contraseña con token de un solo uso y revocación de sesiones.
- Hash de contraseñas con `password_hash` (bcrypt/Argon2 según PHP).
- Validación de entrada y escape de salida.
- Auditoría append-only de operaciones críticas.
- API keys con prefijo, hash, scopes, expiración y revocación.
- Webhooks con firma HMAC y protección SSRF.
- Impresión térmica desacoplada en cola.
- PDF con QR verificable y exportaciones CSV.
- Cabeceras de seguridad y CORS por lista blanca.
- Secretos fuera del código vía `.env`.

Ver `../INFORMES/V0.0.1/` para la auditoría que originó este refactor.

## 6. Documentación

- [Plan maestro](docs/plan/PLAN_MAESTRO.md)
- [ADRs](docs/adr/)
- [OpenAPI](docs/api/openapi.yaml)
- [Manual técnico](docs/manual-tecnico/MANUAL_TECNICO.md)
- [Integraciones](docs/manual-tecnico/INTEGRACIONES.md)
- [Manual de usuario](docs/manual-usuario/MANUAL_USUARIO.md)
- [Plan de QA](docs/qa/QA_PLAN.md)
- [Resultados de QA](docs/qa/QA_RESULTS.md)
- [E2E](docs/qa/E2E_REPORT.md)
- [Accesibilidad](docs/qa/ACCESSIBILITY_REPORT.md)
- [Performance](docs/qa/PERFORMANCE_REPORT.md)
- [Checklist de release](docs/qa/RELEASE_CHECKLIST.md)
- [Matriz de cumplimiento](docs/compliance/MATRIZ_CUMPLIMIENTO.md)
- [Modelo de amenazas](docs/security/MODELO_AMENAZAS.md)
- [Checklist ASVS](docs/security/ASVS_CHECKLIST.md)
- [Base de datos](bd/README.md)
- [Servidor](servidor/README.md)
- [Cliente](cliente/README.md)

## 7. Estado del roadmap

| Fase | Estado |
|---|---|
| 0 — Gobierno y estructura | ✅ |
| 1 — BD y dominio | ✅ base implementada |
| 2 — Núcleo API | ✅ |
| 3 — Identidad, acceso y auditoría | ✅ base implementada |
| 4 — Rifas, boletas y pagos | ✅ flujo principal |
| 5 — Shell de cliente y diseño | ✅ |
| 6 — Portales por rol | ✅ base funcional |
| 7 — Integraciones | ✅ correo, PDF/QR, impresión en cola, webhooks, exportaciones |
| 8 — Endurecimiento y cumplimiento | 🟡 técnico implementado; faltan pentest, MFA, revisión legal y Coljuegos |
| 9 — QA complejo y release | ✅ QA smoke/E2E/axe/perf; 🟡 release y push pendientes |

## 8. Licencia

Propietaria. Todos los derechos reservados. El uso, copia o distribución requiere autorización del titular.
