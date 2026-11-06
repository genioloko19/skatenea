# Servidor — SKATENEA API

API REST en PHP 8.2 con PDO, POO y arquitectura por capas. Sin framework completo: router, contenedor y middleware propios, pequeños y testeables.

## Estructura

```
servidor/
├── public/index.php          Front controller
├── config/bootstrap.php      Composición de dependencias
├── routes/api.php            Contrato de rutas
├── src/
│   ├── Core/                 Config, PDO, Router, Request/Response, Validator, RateLimiter, Logger
│   ├── Auth/                 JWT, refresh, API keys, RBAC, cookies, políticas
│   ├── Domain/               Entidades e interfaces de repositorio
│   ├── Application/          Casos de uso y servicios de negocio
│   ├── Infrastructure/       Repositorios PDO
│   └── Http/                 Controllers y middleware
├── bin/migrate.php           Migración del esquema
├── bin/seed.php              Datos de desarrollo
├── tests/Unit                Pruebas unitarias
└── storage/                  Logs y rate limit (fuera del control de versiones)
```

## Capas y dependencias

```
HTTP → Application → Domain
                 ↑
          Infrastructure (PDO)
```

- `Domain` no conoce HTTP, PDO ni React.
- `Application` orquesta reglas de negocio y transacciones.
- `Infrastructure` implementa las interfaces del dominio.
- `Http` traduce HTTP a casos de uso.

## Arranque

```powershell
composer install
Copy-Item .env.example .env
php -r "echo bin2hex(random_bytes(48)), PHP_EOL;"  # pegar en JWT_SECRET
php bin/migrate.php --fresh
php bin/seed.php
php -S 127.0.0.1:8000 -t public public/index.php
```

## Variables principales

| Variable | Descripción |
|---|---|
| `DB_DRIVER` | `sqlite` o `mysql` |
| `DB_SQLITE_PATH` | Ruta del archivo SQLite (desarrollo) |
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | Conexión MySQL |
| `DB_SSL_CA` | CA para TLS de MySQL (producción) |
| `JWT_SECRET` | Secreto de firma, mínimo 32 bytes |
| `JWT_ACCESS_TTL` | Vida del access token (900 s) |
| `REFRESH_TTL` | Vida del refresh token (30 días) |
| `COOKIE_SECURE` | `true` en producción con HTTPS |
| `CORS_ALLOWED_ORIGINS` | Orígenes permitidos |
| `RATE_LIMIT_MAX` / `WINDOW` | Límite global por IP |

## Autenticación

1. `POST /api/v1/auth/login` valida credenciales y devuelve access token + cookies.
2. El cliente envía `Authorization: Bearer <jwt>`.
3. Al expirar, `POST /api/v1/auth/refresh` rota el refresh token y devuelve un nuevo access token.
4. `POST /api/v1/auth/logout` revoca el refresh y limpia cookies.

Detalles en `../docs/adr/0002-auth-jwt.md`.

## API keys

`POST /api/v1/api-keys` (solo super administrador) devuelve la clave en claro **una única vez**. El servidor guarda `sha256(key)`, prefijo, scopes y expiración. Se envían con `X-API-Key`.

## Pruebas y calidad

```powershell
composer test
composer lint
composer stan
composer quality
```

- PHPUnit 11.
- PHPStan nivel 6.
- PSR-12.

## Seguridad operativa

- No exponer `vendor/`, `storage/` ni `.env`.
- Servir solo `public/` y configurar el rewrite de Apache/Nginx.
- Usar HTTPS, `COOKIE_SECURE=true` y HSTS.
- Rotar `JWT_SECRET` y las credenciales de BD.
- Revisar `storage/logs/app.log` con retención.
- Ejecutar `composer audit` en CI.
