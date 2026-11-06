# Plan de QA — SKATENEA V1.0.0

## 1. Objetivo

Verificar que la versión 1.0.0 cumple los requisitos funcionales, de seguridad, accesibilidad, rendimiento y compatibilidad definidos en el plan maestro y en los ADR.

## 2. Alcance

### Incluido

- API REST: autenticación, refresh, roles, rifas, boletas, compradores, usuarios, API keys, auditoría, configuración.
- Cliente SPA: login, dashboard, rifas, detalle, boletas, compradores, usuarios, auditoría, perfil, panel de control.
- Sistema visual: 12 temas × 3 modos × 4 geometrías × 6 fondos.
- Base de datos: integridad, estados y transacciones.
- Seguridad: autenticación, autorización, CSRF, XSS, inyección, secretos.
- Accesibilidad: teclado, foco, contraste, semántica.
- Documentación: manuales, OpenAPI y capturas.

### Excluido en esta iteración

- Pruebas de carga masiva y estrés.
- Pentest externo.
- Compatibilidad con navegadores antiguos.
- Pasarela de pago real.
- Envío real de correo y cola de impresión (fase 7).

## 3. Entornos

| Entorno | Backend | Frontend | BD |
|---|---|---|---|
| Local QA | PHP 8.2.12, `php -S` | Vite 8, Chrome headless | SQLite |
| Staging (fase 8) | PHP-FPM, Nginx | Build estático | MySQL 8 |
| Producción | Pendiente | Pendiente | MySQL 8 |

## 4. Matriz de pruebas

| Nivel | Herramienta | Cobertura |
|---|---|---|
| Unitarias PHP | PHPUnit 11 | Validator, RBAC, JWT, PasswordPolicy, Router, UUID |
| Estático PHP | PHPStan 6 + PHPCS PSR-12 | `src`, `public`, `bin` |
| Contrato | OpenAPI 3.1 | 42 rutas documentadas |
| Smoke E2E | Playwright + Chrome | Login, dashboard, panel, rifas, boletas, usuarios, auditoría, 36 temas |
| Visual | Capturas PNG | 10 pantallas + 36 combinaciones |
| Seguridad | Revisión manual + `composer audit` + `npm audit` | Pendiente formal ASVS en fase 8 |
| Accesibilidad | Revisión manual + teclado | Pendiente axe automatizado |
| Performance | Pendiente k6 | Fase 9 |

## 5. Casos críticos

### 5.1 Autenticación

- Login válido devuelve access token y cookies.
- Login inválido devuelve 401 genérico.
- Usuario inactivo no puede ingresar.
- Refresh rota el token y revoca el anterior.
- Reutilización de refresh revoca todas las sesiones.
- Access token expirado se renueva una sola vez.
- Logout limpia cookies y revoca refresh.

### 5.2 Autorización

- Vendedor no opera boletas ajenas.
- Vendedor no elimina ni edita rifas ajenas.
- Líder no aprueba rifas.
- Comprador no accede a usuarios, auditoría ni configuración.
- Super administrador accede a todo.
- Una rifa no se activa sin aprobación.

### 5.3 Negocio

- Rifa: borrador → pendiente → aprobada → activa → cerrada → finalizada.
- Rechazo exige motivo.
- Generación de boletas única.
- Asignación de rangos solo a vendedores asignados.
- Reserva con vencimiento.
- Pago solo desde reserva vigente.
- Monto de pago = precio de boleta.
- Doble venta bloqueada por transición condicional.
- Sorteo solo con boletas pagadas y super admin.
- QR no predecible.

### 5.4 UI

- Header con dropdown: Panel de control, Perfil, Cerrar sesión.
- Panel modal con pestañas filtradas por rol.
- 12 temas × 3 modos.
- Geometrías y fondos.
- Navegación por teclado.
- `prefers-reduced-motion`.
- Alto contraste.

## 6. Criterios de entrada

- Código compilando.
- Migraciones y seeds ejecutables.
- API y cliente levantados.
- Credenciales de desarrollo válidas.

## 7. Criterios de salida

- 100 % de pruebas unitarias en verde.
- 100 % de checks de smoke en verde.
- 0 errores de consola/página en el recorrido.
- 36 capturas de tema/modo generadas.
- Sin hallazgos críticos/altos abiertos.
- Documentación actualizada.

## 8. Evidencia

- `docs/qa/smoke-result.json`.
- `docs/assets/screenshots/` (46 imágenes).
- `servidor/storage/coverage` si hay driver de cobertura.
- `composer audit` y `npm audit`.
- Capturas de API y base de datos en el manual técnico.
