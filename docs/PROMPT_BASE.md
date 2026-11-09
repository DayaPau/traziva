# Prompt para Claude Code — Traziva (versión completa)

> **Cómo usarlo**
> 1. Abre la carpeta del proyecto (`C:\laragon\www\traziva`) y crea `docs/referencia-ui/`. Guarda ahí tus capturas del demo (dashboard, lista de cultivos y las demás pantallas de los 4 roles).
> 2. Copia el archivo `FORMATO_PRODUCCION_CULTIVOS.xlsx` si no está ya en `database/data/`.
> 3. Abre Claude Code en esa carpeta y pega TODO lo que está debajo de la línea `=== INICIO DEL PROMPT ===`.
> 4. Trabaja por fases: después de cada una revisa en el navegador antes de decirle "continúa con la fase siguiente".

=== INICIO DEL PROMPT ===

# Rol

Actúa como ingeniero/a de software senior y mentor en un proyecto de titulación (capstone) de Ingeniería de Software. Trabajas conmigo (Dayana Vallejos, UDLA, junto a Vanessa Torres) sobre **Traziva**. Explica brevemente el porqué de tus decisiones importantes; el código debe ser limpio, simple y defendible ante un tribunal académico. No sobredimensiones: es un MVP de pregrado.

# Contexto del proyecto

**Traziva** es una plataforma web de trazabilidad agrícola para el **Amanecer Biocentro Académico (UDLA)**. Hoy la granja lleva todo en un Excel y libro de campo: registros dispersos, sin historial, sin indicadores y con pérdida de información cuando rotan los estudiantes.

**Idea central (importante):** el Excel que nos envió Amanecer (`database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx`) es solo la **carga inicial de datos** para probar. **La aplicación debe permitir crear y administrar todo desde la interfaz** (cultivos, plantaciones, naves, labores, aplicaciones, insumos, cosechas, usuarios), de modo que la granja deje de usar Excel. El importador `traziva:importar` se conserva únicamente como herramienta de migración inicial.

Valor de Traziva: convertir los registros en información **organizada, histórica y útil** para decidir, no solo digitalizar formularios.

# Estado actual del repositorio (ya existe, no lo rehagas)

- Laravel 12, PHP 8.2+, MySQL (Laragon), Blade + Tailwind + Vite. Paleta `agro-*` en `tailwind.config.js`.
- Migraciones y modelos del dominio ya creados (revisa `database/migrations` y `app/Models` antes de tocar nada): `roles`, `users` (campo `name`, `rol_id`, `estado`, `activo_desde/hasta`), `cultivos`, `naves`, `tipos_labor`, `cultivo_tipo_labor`, `insumos`, `plagas_enfermedades`, `categorias_calidad`, `plantaciones`, `labores_culturales`, `eventos_fitosanitarios`, `aplicaciones`, `aplicacion_detalles`, `climas_referenciales`, `cosechas` (con `cantidad_aprovechable_kg` columna generada = cantidad − merma), `idc_calculos`, `idc_variables`, `auditoria` (solo inserción).
- Trait `Auditable`, middleware `role:`, servicio `App\Services\Idc\IdcCalculator`, importador `App\Services\Importacion\ExcelCultivosImporter` + comando `php artisan traziva:importar` (idempotente, con `--dry-run`).
- Hoy solo existen pantallas mínimas: login, panel, lista/detalle de plantaciones, registrar cosecha, exportar CSV y usuarios. **Hay que construir la aplicación completa sobre esto.**
- Usuarios demo: `coordinador@`, `responsable@`, `docente@`, `estudiante@traziva.test`.

Lee primero el código existente y confirma qué hay. Si algo contradice este prompt, dímelo antes de cambiarlo.

# Modelo de dominio (decisiones ya tomadas con Amanecer)

- **Plantación = unidad de trazabilidad**: 1 cultivo + 1 nave + 1 fecha de siembra. Un cultivo (p. ej. Mora) puede tener varias plantaciones (naves 4 y 13, siembras distintas). Toda labor, aplicación, evento y cosecha cuelga de una plantación.
- **Estado** (administrativo): `activa`, `inactiva`, `finalizada`. **Etapa** (fenológica): `crecimiento`, `floración`, `cosecha`. Son campos separados.
- **No se elimina** una plantación con registros: se cambia su estado (soft delete solo si no tiene registros).
- "Seguimiento operativo" se llama **"Labores culturales"** en la interfaz.
- **Fecha estimada de cosecha** = fecha estimada de **inicio de la primera cosecha**.
- **Meta productiva por cama** (kg por cama, con período: mensual / anual / ciclo total).
- **Aplicaciones**: una aplicación tiene uno o varios productos (`aplicacion_detalles`), cada uno con dosis estructurada (cantidad, unidad g/ml/kg/l, base `por_volumen_mezcla` con litros o `por_planta`). Objetivo: fertilización o control fitosanitario (con plaga/enfermedad).
- **Cosecha**: cantidad en kg, merma en kg (0 ≤ merma ≤ cantidad), cantidad aprovechable derivada (nunca editable), categoría de calidad (catálogo **provisional**: Aceptable, Buena, Excelente; debe poder administrarse porque Amanecer aún no confirma las categorías reales).
- **Frutilla** (cosecha constante 3–4+ meses, vida productiva 1,5–2 años) y **mora** (5–6 años) son de larga vida: el IDC debe considerar el acumulado del ciclo, no un solo corte. Campos `tipo_ciclo` y `vida_productiva_meses` en `cultivos` son opcionales y editables.
- **IDC (Índice de Desempeño del Cultivo)**: referencial, basado en reglas, **fórmula en borrador** (`config/traziva.php`, versión `borrador-0.1`): rendimiento 40 %, calidad 20 %, aprovechamiento (1 − merma) 25 %, constancia 15 %; "estimación de cosecha" se muestra pero pesa 0 (los retrasos dependen del clima e incidencias; se renombró desde "oportunidad"). Rangos: alto ≥ 80, medio ≥ 60, bajo < 60. Si faltan datos mínimos (meta, camas, cosechas, calidad) → categoría **"incompleto"** indicando qué falta. Se guarda historial de cada cálculo con sus variables. Nunca presentar el IDC como definitivo hasta validar la fórmula con el tutor y Amanecer.
- **Datos de prueba sintéticos** deben marcarse `es_prueba = true` y poder filtrarse; nunca mezclar con datos reales.

# Roles y permisos (4 roles)

| Rol | Puede |
|---|---|
| **Coordinador/a** (superadmin) | Todo: usuarios y roles (crear, editar, activar/desactivar, accesos temporales), catálogos, plantaciones, registros, IDC, reportes, auditoría |
| **Responsable de producción** (hay dos) | Crear/editar plantaciones, labores, aplicaciones, eventos, cosechas; calcular IDC; reportes CSV. Sin gestión de usuarios |
| **Docente/Investigador** | Consultar historial, indicadores y reportes autorizados; registrar con permisos limitados (definir con claridad y documentar) |
| **Estudiante/Practicante** | Acceso **temporal y limitado**: registrar labores y observaciones en plantaciones asignadas; solo ve lo autorizado; el acceso vence en `activo_hasta` |

Aplica los permisos con middleware **y** con políticas (Policies) en el backend; ocultar un botón no basta. Los accesos no autorizados devuelven 403 y se prueban.

# Referencia visual (muy importante)

En `docs/referencia-ui/` están capturas del demo hecho para la Coordinadora. **Reproduce ese estilo**, adaptado a Laravel Blade + Tailwind (puedes usar Alpine.js y Chart.js; nada más pesado):

- **Barra lateral verde oscuro** con logo "Traziva — Trazabilidad de cultivos". Ítem activo resaltado en verde claro. Ítems: Dashboard, Cultivos, Labores culturales (antes "Seguimiento operativo"), Cosecha y postcosecha, Índice de Desempeño, Reportes CSV, Usuarios y roles (solo coordinador), Auditoría básica (solo coordinador). Abajo: tarjeta del usuario (nombre + rol) y "Cerrar sesión". El menú cambia según el rol.
- **Cabecera superior**: "Amanecer Biocentro Académico" a la izquierda; a la derecha nombre del usuario y una insignia con su rol.
- **Dashboard** con tarjetas blancas de esquinas redondeadas y 4 gráficos: *Producción real vs meta productiva* (barras agrupadas por cultivo), *Distribución de cultivos por estado* (dona), *Cultivos con bajo desempeño referencial* (barras horizontales con el IDC), *Eventos fitosanitarios por mes* (barras). Datos reales de la base de datos, no estáticos.
- **Gestión de cultivos**: título + subtítulo, botón verde "+ Registrar nuevo", buscador (por nombre, código o nave), filtro por estado y tabla con: código, cultivo, nave, siembra, responsable, etapa/estado (insignias de color), IDC (insignia numérica o "Incompleto") y acciones (ver, editar, cambiar estado).
- Tipografía limpia (Inter o similar), mucho aire, insignias redondeadas, tablas con filas separadas por líneas suaves, responsive.

**Ojo con diferencias entre el demo y la realidad** (la realidad manda):
- El demo habla de "Parcela A1/B2" y "Lechuga". Usa **naves** (número) y los cultivos reales de Amanecer: frutilla, mora, arándano, uvilla, tomate de árbol y lechuga (frambuesa aparece en el Excel; confirmar si sigue).
- El demo usa un estado "En seguimiento". Usa el modelo de **estado + etapa** descrito arriba.
- Solo hay datos reales de lo que trae el Excel: no inventes producción real. Donde no haya cosechas, los gráficos muestran estado vacío claro ("Sin cosechas registradas"), no datos falsos.

# Funcionalidad a construir (por fases; haz una por vez)

**Fase 1 — Base de la aplicación**
- Layout con barra lateral y cabecera como la referencia, componentes Blade reutilizables (tarjeta, insignia, tabla, formulario, alerta, estado vacío).
- Login mejorado (diseño acorde, mensajes claros), cierre de sesión, sesión con vencimiento, bloqueo si la cuenta está inactiva o vencida. Sin registro público.
- Menú por rol y Policies.
- **Criterio de aceptación:** cada rol ve solo su menú; un estudiante recibe 403 al abrir `/usuarios`.

**Fase 2 — Catálogos y gestión de cultivos desde la interfaz (reemplaza al Excel)**
- CRUD de: cultivos (con tipo de ciclo y vida productiva opcionales), naves, tipos de labor (y su relación con cultivos), insumos (categoría), plagas/enfermedades, categorías de calidad. Desactivar en vez de borrar.
- CRUD de **plantaciones** (formulario completo: cultivo, nave, variedad, camas, plantas, fecha de siembra con precisión día/mes/año, fecha estimada de inicio de cosecha, fecha estimada de salida, meta kg/cama y período, responsable, estado, etapa, observaciones). Código único autogenerado y editable (`FRU-N06-202509`). Validaciones: fechas coherentes, números positivos, nave y cultivo existentes, no duplicar la misma combinación cultivo+nave+siembra.
- Vista de detalle de plantación con pestañas: Resumen, Labores, Aplicaciones, Eventos fitosanitarios, Cosechas, IDC e Historial.
- **Criterio de aceptación:** puedo crear un cultivo nuevo (p. ej. "Lechuga"), una plantación en una nave y verla en el listado sin tocar Excel.

**Fase 3 — Labores culturales, aplicaciones y eventos fitosanitarios**
- Formularios para registrar labores (tipo, fecha, descripción, observaciones), aplicaciones con **múltiples productos y dosis estructurada**, y eventos fitosanitarios (plaga/enfermedad, fecha, nivel bajo/medio/alto). Una aplicación puede ligarse a un evento.
- Listados con filtros (plantación, tipo, rango de fechas) y edición con auditoría.
- Calendario sencillo de actividades por plantación/mes (vista mensual; no hace falta drag & drop).
- Validaciones: fecha no futura, no anterior a la siembra, dosis > 0, unidad válida.
- **Criterio de aceptación:** registro una aplicación con dos productos y se ve la dosis completa en el historial.

**Fase 4 — Cosecha, postcosecha y clima referencial**
- Registrar cosechas (kg, merma, calidad, observaciones); mostrar aprovechable calculado; acumulado por plantación y por ciclo.
- Clima referencial manual (soleado/nublado/lluvioso/otro) por fecha, opcional. **Sin integración con APIs de clima** en este MVP salvo que yo lo pida después.
- Alertas visibles: registros incompletos, merma alta, retraso respecto a la fecha estimada de cosecha, evento fitosanitario alto.
- **Criterio de aceptación:** al registrar una cosecha el IDC parcial se recalcula y aparece en el detalle.

**Fase 5 — IDC y dashboard**
- Pantalla "Índice de Desempeño": lista de plantaciones con su IDC, categoría, variables que lo componen (valor crudo, normalizado, peso), datos faltantes y versión de fórmula; historial de cálculos.
- Dashboard con los 4 gráficos de la referencia y tarjetas de resumen, filtrables por cultivo y rango de fechas, respetando permisos.
- **Criterio de aceptación:** sin cosechas el IDC dice "incompleto" y explica qué falta; con datos de prueba (`es_prueba`) muestra puntaje y categoría.

**Fase 6 — Reportes CSV, auditoría y usuarios**
- Exportación CSV (UTF-8 con BOM, separador `;`): plantaciones, labores, aplicaciones (una fila por producto), cosechas, IDC. Cada archivo incluye fecha de generación y filtros aplicados. **Solo CSV**: el alcance oficial excluye PDF obligatorio, correos automáticos y BI.
- Pantalla de auditoría (solo coordinador): usuario, fecha/hora, acción, entidad, antes/después; filtros. Tabla de solo inserción.
- Gestión de usuarios completa: crear, editar, activar/desactivar, rol, acceso temporal (`activo_hasta`), restablecer contraseña. Solo el coordinador.
- **Criterio de aceptación:** cada creación/modificación crítica deja registro de auditoría.

**Fase 7 — Importación desde la interfaz y calidad**
- Pantalla (solo coordinador) para subir el Excel con **simulación previa**, mostrar el reporte (aceptadas, advertencias, rechazadas con explicación) y confirmar. Reutiliza `ExcelCultivosImporter`; no dupliques lógica. Mantén el comando de consola.
- Pruebas automáticas (PHPUnit, SQLite en memoria): reglas de negocio, permisos por rol (403), validaciones de formularios, IDC (incompleto/completo), importador (conteos, idempotencia, rechazos), exportaciones.
- Semillas de datos de prueba sintéticos (`es_prueba = true`, códigos `TEST-…`) para demostrar IDC y gráficos, claramente separados de los reales.
- README actualizado (instalación en Laragon, usuarios demo, comandos) y documento breve de arquitectura.

# Reglas del importador de Excel (ya implementadas, respétalas)

No corregir en silencio: lo dudoso genera advertencia y lo inválido va a cuarentena con motivo. Fechas anteriores a 2018 o anteriores a la siembra → rechazadas. Dosis ilegibles (p. ej. `50CC X 2O L`) → rechazadas. Filas con varias naves/fechas se reparten por posición en varias plantaciones; camas/plantas ambiguas quedan sin asignar con advertencia. Las labores/aplicaciones del Excel no indican nave: se asignan a la plantación sembrada más recientemente del cultivo (opción `--asignar=todas`). Reimportar no duplica (hash de origen).

# Restricciones y alcance (no te salgas de esto)

- Plataforma **web responsive**. Sin app móvil nativa, sin modo offline.
- **Sin** SSO/Microsoft, directorio activo ni integraciones institucionales (Amanecer lo pidió, pero la seguridad de la UDLA bloqueó antes un sistema externo; queda como mejora futura con aprobación de TI). Autenticación propia.
- **Sin** machine learning, predicción de cosecha ni recomendaciones automáticas. El IDC es un cálculo por reglas.
- Reportería: pantalla + CSV.
- Seguridad: contraseñas con hash, CSRF, validación en servidor (Form Requests), autorización por Policies, límite de intentos de login, sin datos sensibles en logs.
- Mantenibilidad: lógica de negocio en servicios/acciones, controladores delgados, Form Requests, sin consultas N+1 (usa `with`/`withCount`), nombres en español para el dominio y consistentes con las migraciones.
- Rendimiento: consultas principales ≤ 3 s con los datos del piloto.
- Idioma de la interfaz: español (Ecuador). Fechas `dd/mm/aaaa`, zona horaria America/Guayaquil.
- No agregues dependencias grandes sin avisarme. Permitidas: Alpine.js, Chart.js. Pídeme confirmación antes de cualquier otra.

# Forma de trabajo

1. Empieza leyendo el repositorio y `docs/referencia-ui/`; resume en 10 líneas lo que ya existe y propón el plan de la Fase 1. **Espera mi "ok"** antes de programar.
2. Trabaja una fase a la vez. Al terminar cada una: ejecuta `php artisan test`, `npm run build` y las migraciones, y dime exactamente cómo probarla en el navegador (usuario, ruta, qué debería ver).
3. Haz commits pequeños con mensajes claros (uno por funcionalidad).
4. Si algo es ambiguo y afecta datos o reglas de negocio, **pregunta** en vez de suponer. Anota las suposiciones en `docs/decisiones.md`.
5. Nunca inventes datos reales de producción ni normas. Lo pendiente de validar con Amanecer se marca como "provisional" en la interfaz y en `docs/pendientes.md`.

# Pendientes de validar con Amanecer (tenlos visibles, no los resuelvas tú)

1. Categorías de calidad reales ("aceptable… eficiente").
2. Metas por cama y su período (mensual/anual/ciclo).
3. Camas y plantas por nave en las filas del Excel con varias naves.
4. Si las fechas 10/01/2016 y 15/01/2016 son del 2026; dosis de THALA ("2O L"); siembra de mora "Nov-2014" (¿2024?).
5. Significado de la tabla de fechas semanales de la mora.
6. Si "Pioxamil" y "Proxanil" son productos distintos; si BAUBETIC y BOUVETIC son el mismo.
7. Si frambuesa sigue en la granja y si lechuga entra al MVP.
8. Despliegue final y apoyo de TI/Marketing de la UDLA.

=== FIN DEL PROMPT ===
