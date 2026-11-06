# Contribución

## Ramas

- `main`: estable.
- `feat/<tema>`: nuevas funcionalidades.
- `fix/<tema>`: correcciones.
- `docs/<tema>`: documentación.
- `test/<tema>`: pruebas.

## Commits

Conventional Commits:

```
feat(auth): agregar rotación de refresh token
fix(tickets): impedir venta de boleta ajena
docs(api): documentar endpoint de sorteo
test(rbac): cubrir permisos de líder
chore(deps): actualizar dependencias
```

## Antes de abrir un PR

```powershell
cd servidor
composer quality

cd ..\cliente
npm run lint
npm run test
npm run build
npm run qa:smoke
```

- [ ] Sin secretos en el diff.
- [ ] Migraciones probadas (si aplica).
- [ ] Pruebas agregadas/actualizadas.
- [ ] OpenAPI actualizado.
- [ ] Manual actualizado.
- [ ] Sin errores de consola en QA.
- [ ] Capturas actualizadas (si cambia UI).

## Estilo

- PHP: PSR-12, PHPStan 6, tipos estrictos.
- TypeScript: `strict`, sin `any` innecesario.
- CSS: tokens y variables; no colores hardcodeados en componentes.
- Accesibilidad: HTML semántico, ARIA correcto, foco visible.
- Seguridad: consultas preparadas, escape de salida, autorización explícita.

## Revisión

Cada PR debe incluir:

1. Contexto y alcance.
2. Evidencia de pruebas.
3. Riesgos y rollback.
4. Capturas si cambia la UI.
5. Checklist de seguridad.
