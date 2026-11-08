# Decisiones y suposiciones del proyecto

Registro de decisiones que afectan datos, reglas de negocio o seguridad.
Cada una indica quién la tomó y cuándo, para poder defenderla ante el tribunal.

## D-01 · Plantación como unidad de trazabilidad
- **Decisión:** 1 plantación = 1 cultivo + 1 nave + 1 fecha de siembra. Labores, aplicaciones, eventos y cosechas cuelgan de una plantación.
- **Origen:** reunión con Amanecer (previa al 08/11/2026).

## D-02 · Registro público con aprobación
- **Decisión:** la pantalla de inicio ofrece "Ingresar" y "Solicitar acceso". Quien se registra elige el rol que solicita (coordinador/a, responsable, docente o estudiante), pero la cuenta nace en estado **pendiente** y no puede ingresar hasta que un/a coordinador/a la apruebe en *Usuarios y roles*.
- **Por qué:** el equipo pidió registro con elección de rol. Sin aprobación, cualquier persona podría crearse una cuenta de coordinador/a (acceso total), lo que contradice el requisito de seguridad. La aprobación mantiene el control en manos de Amanecer.
- **Detalles:** el registro tiene límite de 3 intentos cada 10 minutos por IP; contraseña de mínimo 8 caracteres con letras y números; se audita la acción `registro` y la acción `aprobar`.
- **Origen:** Dayana Vallejos, 08/11/2026.
- **Pendiente (Fase 6):** el/la coordinador/a podrá cambiar el rol solicitado y fijar `activo_hasta` antes de aprobar. Hoy aprueba con el rol solicitado.

## D-03 · Matriz de permisos por rol
| Acción | Coordinador/a | Responsable | Docente | Estudiante |
|---|:-:|:-:|:-:|:-:|
| Ver dashboard, plantaciones e historial | ✔ | ✔ | ✔ | ✔ |
| Crear/editar plantaciones | ✔ | ✔ | ✘ | ✘ |
| Registrar labores, aplicaciones, eventos, cosechas | ✔ | ✔ | ✘ | ✘ |
| Recalcular IDC | ✔ | ✔ | ✘ | ✘ |
| Descargar reportes CSV | ✔ | ✔ | ✔ | ✔ |
| Catálogos (cultivos, naves, insumos…) | ✔ | ✔ (Fase 2) | ✘ | ✘ |
| Usuarios y roles, aprobar cuentas | ✔ | ✘ | ✘ | ✘ |
| Auditoría | ✔ | ✘ | ✘ | ✘ |

- **Docente y estudiante solo consultan**: no crean, modifican ni eliminan nada. Ambos pueden descargar CSV.
- **Estudiante:** su acceso es temporal (vence en `activo_hasta`). Ve la misma información que el docente.
- **Cambio respecto al prompt inicial:** el prompt proponía que el estudiante registrara labores en plantaciones asignadas y que el docente tuviera "registro limitado". Se descartó; por eso no se creó la tabla de asignación `plantacion_user`.
- **Implementación:** doble control. (1) Middleware `role:` en grupos de rutas; (2) Policies (`PlantacionPolicy`, `UserPolicy`) y Gates (`exportar`, `ver-auditoria`) en cada ruta con `->can()`. El/la coordinador/a pasa todas las comprobaciones mediante `Gate::before`. Ocultar botones en la vista es solo comodidad, no seguridad.
- **Origen:** Dayana Vallejos, 08/11/2026.

## D-04 · Vigencia de la cuenta en cada petición
- **Decisión:** el middleware `cuenta.vigente` revisa en cada petición que la cuenta esté activa y dentro de `activo_desde`/`activo_hasta`. Si el/la coordinador/a la desactiva, o vence el acceso temporal, la sesión se cierra en la siguiente petición con un mensaje explicativo.
- **Sesión:** expira tras 120 min de inactividad (`SESSION_LIFETIME`). "Mantener la sesión" usa la cookie *remember* de Laravel, igualmente sujeta a la verificación de vigencia.

## D-05 · Menú por rol desde configuración
- **Decisión:** el menú lateral se define una sola vez en `config/traziva.php` (`menu`), con los roles que ven cada ítem. Los módulos de fases futuras aparecen deshabilitados con la etiqueta "Próx.".

## D-06 · Interfaz
- Español (Ecuador), fechas `dd/mm/aaaa`, zona horaria `America/Guayaquil`.
- Tipografía Inter (texto) y Fraunces (títulos), cargadas desde Google Fonts. Si la red de la UDLA bloquea Google Fonts, la interfaz cae a las fuentes del sistema sin romperse.
- Dependencias de interfaz: Alpine.js (menú en celulares, cierre de alertas). Chart.js se agregará en la Fase 5.

## D-07 · Entorno de pruebas
- Las pruebas usan SQLite en memoria y no tocan la base MySQL. En Laragon se habilitaron las extensiones `pdo_sqlite` y `zip` en `php.ini` (respaldo: `php.ini.bak-traziva`). `zip` también es necesaria para leer el Excel con PhpSpreadsheet.
