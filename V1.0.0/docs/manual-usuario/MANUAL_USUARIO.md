# Manual de usuario — SKATENEA V1.0.0

**Audiencia:** super administradores, líderes, vendedores y compradores.  
**Objetivo:** operar el sistema de principio a fin sin conocer detalles técnicos.

---

## 1. Ingreso

1. Abre `http://127.0.0.1:5173` (desarrollo) o la URL de producción.
2. Haz clic en **Iniciar sesión**.
3. Escribe tu correo y contraseña.
4. Opcional: usa las cuentas de demostración del entorno local.

> En producción, el administrador te entregará una contraseña temporal. Cámbiala en tu primer ingreso desde **Perfil**.

### 1.1 Recuperar acceso

Si olvidaste tu contraseña, contacta al super administrador. El flujo de recuperación automática se completará en la fase 7 (correo transaccional).

## 2. Roles y alcance

| Rol | Puede hacer |
|---|---|
| **Super administrador** | Todo: aprobar/rechazar rifas, gestionar usuarios, API keys, auditoría y configuración. |
| **Líder** | Crear y editar sus rifas, asignar vendedores, generar boletas, hacer seguimiento. |
| **Vendedor** | Ver sus números, separar, vender y registrar pagos de sus boletas. |
| **Comprador** | Ver rifas activas y consultar las boletas que ha comprado. |

## 3. Panel principal

Al ingresar verás un dashboard adaptado a tu rol:

- **Super administrador:** usuarios, rifas, boletas pagadas y recaudo.
- **Líder:** sus rifas, pendientes de aprobación y recaudo.
- **Vendedor:** boletas disponibles, separadas y pagadas.
- **Comprador:** sus boletas y pagos.

Desde el menú lateral accedes a Rifas, Boletas, Compradores, Aprobaciones, Usuarios, Auditoría y Perfil.

## 4. Ciclo de una rifa

```text
Borrador → Pendiente de aprobación → Aprobada → Activa → Cerrada → Finalizada
                                  ↘ Rechazada
```

### 4.1 Crear una rifa (líder)

1. Ve a **Rifas → Nueva rifa**.
2. Completa nombre, premio, valor de boleta, total de boletas y fechas.
3. Guarda. La rifa queda en **Borrador**.
4. Abre el detalle y pulsa **Enviar a aprobación**.
5. El super administrador la revisará.

### 4.2 Aprobar o rechazar (super administrador)

1. Ve a **Aprobaciones**.
2. Revisa la información de la rifa.
3. Pulsa **Aprobar** o **Rechazar** (con motivo).
4. Una rifa aprobada puede ser activada por el líder.

### 4.3 Activar y generar boletas

1. El líder abre el detalle de la rifa aprobada.
2. Pulsa **Activar rifa**.
3. Pulsa **Generar boletas** (una sola vez).
4. Asigna vendedores y rangos de números.

> El sistema no permite cambiar el total de boletas después de generarlas.

## 5. Boletas y estados

| Estado | Significado | Acción del vendedor |
|---|---|---|
| **Disponible** | Número libre y asignado al vendedor | Separar |
| **Separada** | Cliente apartó, esperando pago | Registrar pago o anular si expira |
| **Pagada** | Pago confirmado | Solo lectura; anulación excepcional |
| **Anulada** | Boleta fuera de circulación | — |
| **Ganadora** | Boleta seleccionada en el sorteo | — |

### 5.1 Separar una boleta

1. Ve a **Boletas**.
2. En una boleta **Disponible**, pulsa **Separar**.
3. Registra nombre, teléfono, correo y documento del comprador.
4. Define las horas de reserva (1–168).
5. Confirma.

La reserva vence automáticamente; si el cliente no paga, puede anularse y volver a estar disponible.

### 5.2 Registrar pago

1. En una boleta **Separada**, pulsa **Pagar**.
2. Selecciona el método: efectivo, transferencia, tarjeta, Nequi, Daviplata u otro.
3. Añade una referencia opcional.
4. Confirma.

El monto se toma del valor de la boleta; no se puede modificar desde la pantalla.

### 5.3 Anular

- Disponible o separada: el vendedor propietario puede anular.
- Pagada: solo el super administrador.
- La acción queda en auditoría.

## 6. Sorteo

Solo el super administrador puede realizar el sorteo:

1. La rifa debe estar **Activa** o **Cerrada**.
2. Debe existir al menos una boleta pagada.
3. Pulsa **Realizar sorteo**.
4. El sistema elige una ganadora con `random_int`, guarda la semilla y el hash.
5. La rifa pasa a **Finalizada**.

> La boleta ganadora queda registrada en la rifa y la semilla permite verificar el resultado.

## 7. Compradores

- **Vendedor/líder/administrador:** pueden buscar por nombre, teléfono, correo o documento y registrar nuevos compradores.
- El vendedor solo ve los compradores que registró o con los que tiene boletas.
- **Comprador:** puede consultar sus boletas desde **Mis boletas**.

## 8. Usuarios (super administrador)

1. Ve a **Usuarios**.
2. Filtra por rol.
3. Pulsa **Nuevo usuario**.
4. Define nombre, correo, teléfono, rol y contraseña temporal.
5. El usuario deberá cambiarla en su primer ingreso.

No es posible desactivar tu propia cuenta ni dejar el sistema sin un super administrador activo.

## 9. Panel de control

Haz clic en tu avatar (arriba a la derecha) y elige **Panel de control**. Allí encuentras:

| Pestaña | Qué permite |
|---|---|
| **Apariencia** | Tema, modo, geometría, fondo y animaciones |
| **Perfil** | Datos personales y contraseña |
| **General** | Nombre de la app, mantenimiento, registro y horas de reserva (super admin) |
| **Usuarios** | Consulta y creación de usuarios (super admin) |
| **Rifas** | Rifas recientes |
| **Boletas** | Tus boletas |
| **Seguridad** | Sesión, cambio de contraseña y cierre de sesiones |
| **API** | Crear y revocar API keys (super admin) |
| **Auditoría** | Eventos recientes |
| **Integraciones** | Correo, webhooks e impresión (fase 7) |
| **Notificaciones** | Preferencias (fase 7) |
| **Sistema** | Versión, stack y acceso a OpenAPI |

## 10. Apariencia

- **12 temas:** Aurora, Neón, Atardecer, Océano, Bosque, Candy, Cyber, Royal, Terracota, Monocromo, Tropical y Galaxia.
- **3 modos:** Claro, Oscuro y Alto contraste.
- **4 geometrías:** Suave, Recta, Píldora y Orgánica.
- **6 fondos:** Aurora, Malla, Ondas, Orbes, Retícula y Estático.
- **Animaciones:** se pueden desactivar.

El botón de sol/luna/contraste del header cambia rápidamente el modo. Tus preferencias se guardan en tu cuenta.

## 11. Accesibilidad

- Navegación completa por teclado (`Tab`, `Shift+Tab`, flechas en pestañas).
- Enlace “Saltar al contenido”.
- Foco visible en todos los controles.
- Modo de alto contraste con bordes sólidos y sin transparencias.
- Respeta `prefers-reduced-motion`.
- Etiquetas y roles ARIA en menús, pestañas y diálogos.

## 12. Preguntas frecuentes

**¿Por qué no veo una rifa?**  
Solo se muestran las rifas activas (público/comprador) o las que te corresponden (líder/vendedor).

**¿Por qué no puedo vender una boleta?**  
Solo el vendedor asignado o el super administrador puede operarla. Verifica que la boleta esté disponible y que la rifa esté activa y dentro de fechas.

**¿Qué pasa si expira una reserva?**  
La boleta debe anularse y volver a separarse. El sistema no libera automáticamente aún; está previsto en la fase 7.

**¿Puedo cambiar el monto del pago?**  
No. El monto se toma del valor de la boleta. Los abonos se definirán en una fase posterior.

**¿Cómo verifico una boleta?**  
El QR contiene un token aleatorio. En producción se abrirá una página pública que muestra solo el estado, sin datos personales.

**¿Dónde veo los errores?**  
El sistema muestra mensajes claros. Si persiste, comparte el `X-Request-Id` con soporte.

## 13. Buenas prácticas de seguridad

- Usa una contraseña única de al menos 12 caracteres.
- No compartas tu cuenta ni tu refresh token.
- Cierra sesión en equipos compartidos.
- El super administrador debe revisar la auditoría periódicamente.
- Reporta accesos extraños de inmediato.
- No uses las credenciales de demostración en producción.

## 14. Soporte

- Manual técnico: `docs/manual-tecnico/MANUAL_TECNICO.md`.
- OpenAPI: `docs/api/openapi.yaml`.
- Estado del sistema: `GET /api/v1/health`.
- Contacto: define aquí el correo y teléfono del equipo de soporte antes de publicar.
