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

## D-08 · Catálogos genéricos y desactivación (Fase 2)
- **Decisión:** los 6 catálogos (cultivos, naves, tipos de labor, insumos, plagas/enfermedades, categorías de calidad) se definen en `App\Support\Catalogos` y los atiende un único `CatalogoController` con dos vistas. Tienen la misma forma; seis controladores casi idénticos serían duplicación.
- **Nada se borra:** se desactiva (`activo = false`). Un registro inactivo no se ofrece en formularios nuevos, pero se conserva en el historial y en las plantaciones que ya lo usan.
- **Quién:** coordinador/a y responsable de producción (Gate `gestionar-catalogos`). Cada cambio queda auditado.
- **Categorías de calidad:** marcadas como **provisionales** en la interfaz (pendiente #1).

## D-09 · Código de plantación
- **Formato:** `SIGLA-NAVE-AAAAMM`, p. ej. `FRU-N06-202509` (`SN` sin nave, `SF` sin fecha; solo `AAAA` si la siembra se conoce por año). Si ya existe, se agrega `-2`, `-3`…
- **Sigla:** campo editable del cultivo; por defecto, las 3 primeras letras del nombre. La regla vive en `CodigoPlantacion` y la usan tanto el formulario como el importador (no hay lógica duplicada).
- El código se sugiere automáticamente en el formulario y el usuario puede editarlo (mayúsculas, números y guiones; único).

## D-10 · Reglas del formulario de plantación
- Cultivo, nave, fecha de siembra y estado son obligatorios al crear. Al **editar** una plantación importada que no tenía nave o fecha, esos campos pueden seguir vacíos, para no bloquear la corrección de otros datos (el importador las dejó vacías a propósito por ser dudosas).
- Fecha de siembra: no futura y no anterior a 2018 (misma regla que el importador). Si la precisión es "mes" se guarda el día 1; si es "año", el 1 de enero.
- Inicio estimado de cosecha ≥ siembra; salida estimada > siembra y ≥ inicio de cosecha.
- Camas, plantas y meta > 0. La meta y su período van juntos (uno exige el otro).
- No puede repetirse la combinación cultivo + nave + fecha de siembra.
- Responsable: un usuario activo con rol coordinador/a o responsable.
- Eliminación: solo plantaciones **sin** labores, aplicaciones, eventos ni cosechas (soft delete). Con registros, se cambia el estado.

## D-11 · Gestión de usuarios (Sprint 5, S5-04)
- Solo el/la coordinador/a crea, edita, aprueba, activa/desactiva y restablece contraseñas.
- **Restablecer contraseña:** el/la coordinador/a fija una contraseña nueva y la comunica por un canal seguro. No hay correo automático (fuera del alcance). Se cierra la sesión recordada del usuario.
- **Invariantes:** nadie puede desactivarse ni cambiarse el rol a sí mismo; siempre queda al menos un/a coordinador/a activo/a. Viven en `GestionUsuarios`.
- **Acceso temporal:** `activo_desde`/`activo_hasta`. Al crear, `activo_hasta` no puede estar en el pasado; al editar sí (es la forma de cortar el acceso de inmediato).
- **Pendientes:** el/la coordinador/a puede cambiar el rol solicitado antes de aprobar. Una cuenta no puede volver a «pendiente».
- **Auditoría:** el modelo `User` es auditable; la auditoría **nunca** guarda campos ocultos (`password`, `remember_token`) y no deja filas vacías. Acciones con nombre propio: `registro`, `aprobar`, `restablecer_clave`.

## D-12 · IDC en el listado (Sprint 5, S5-07)
- Una plantación sin cálculo de IDC se muestra como **«Incompleto»**. Es correcto porque el IDC se recalcula con cada cosecha guardada: sin cálculo ⇒ sin cosechas ⇒ incompleto por definición.

## D-13 · Usuarios demo (Sprint 5)
- `TRAZIVA_SEED_PASSWORD` en `.env` fija la contraseña de las 4 cuentas `@traziva.test` (también si ya existían), para que el equipo pruebe con la misma. No se imprime en consola. Nunca toca otras cuentas.

## D-14 · Labores y cosechas desde la interfaz (avance 14-nov)
- Registran y editan: coordinador/a y responsable. **Docente y estudiante solo consultan** (confirmado por Dayana el 09/11/2026; el prompt del avance decía que el estudiante registraba labores).
- Labor: tipo del catálogo (solo los asociados al cultivo; si el cultivo no tiene asociados, todos los activos), fecha no futura y no anterior a la siembra, descripción. Se registra desde la pestaña «Labores» del detalle; la pantalla «Labores culturales» del menú es para consultar y filtrar.
- Cosecha: fecha no futura y no anterior a la siembra, cantidad > 0, 0 ≤ merma ≤ cantidad (vacía = 0), calidad del catálogo provisional. El aprovechable lo calcula la base de datos y se muestra en vivo en el formulario. Guardar o editar recalcula el IDC parcial.
- Si un formulario tiene errores, se vuelve a la misma pestaña del detalle con los datos ingresados.

## D-15 · Datos de demostración (avance 14-nov)
- `DatosPruebaSeeder` crea 3 plantaciones `TEST-01-ALTO`, `TEST-02-MEDIO`, `TEST-03-INCOMPLETO` con `es_prueba = true`, sin nave (para no crear naves ficticias) y con fechas relativas al día de ejecución. Volver a ejecutarlo reemplaza las anteriores.
- `php artisan traziva:borrar-prueba` borra solo plantaciones `es_prueba = true` con código `TEST-…` y sus registros en cascada.
- El panel (dashboard) **no suma** datos de prueba en sus totales; en las tablas se ven con la insignia «Prueba» y se filtran con «Solo datos reales / de prueba».
