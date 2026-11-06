# Checklist de release — V1.0.0

## Código

- [x] `composer quality` en verde.
- [x] `npm run lint` en verde.
- [x] `npm run test` en verde.
- [x] `npm run build` en verde.
- [x] `composer audit` sin avisos.
- [x] `npm audit --omit=dev` sin vulnerabilidades.
- [x] Migraciones y seeds ejecutables.
- [x] `.env` no versionado.
- [x] `V0.0.1/` e `INFORMES/` fuera del repositorio por `.gitignore`.

## QA

- [x] Smoke UI 47/47.
- [x] E2E 23/23.
- [x] Axe 0 graves/críticos.
- [x] Performance base ejecutada.
- [x] Capturas actualizadas.
- [x] PDF con QR validado.
- [x] Webhook HMAC validado.
- [x] Impresión en cola validada.
- [x] Recuperación de contraseña validada.
- [ ] Pentest externo.
- [ ] Pruebas Firefox/Safari/Edge.

## Documentación

- [x] OpenAPI actualizado (56 rutas).
- [x] Manual técnico.
- [x] Manual de usuario.
- [x] Plan y resultados de QA.
- [x] Matriz de cumplimiento.
- [x] Política de privacidad y términos (plantillas).
- [x] Guías de despliegue y hardening.

## Operación

- [ ] Staging desplegado.
- [ ] TLS/HSTS.
- [ ] Backups probados.
- [ ] Workers en systemd/supervisor.
- [ ] Alertas y monitoreo.
- [ ] Revisión legal.
- [ ] Autorización Coljuegos.
- [ ] `git push` y release en GitHub.

## Release

- [ ] Tag `v1.0.0`.
- [ ] Notas de release.
- [ ] Artefactos (zip servidor/cliente/bd/docs).
- [ ] Publicación en GitHub.
