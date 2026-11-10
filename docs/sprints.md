# Traziva — Registro de sprints

Scrum adaptado a un equipo de 2 personas (Dayana Vallejos y Vanessa Torres) con Amanecer Biocentro Académico como cliente/auspiciante.
Sprints de 2 semanas; una rama por sprint (`sprint-N`) que se fusiona a `main` al cerrar.
Fuente de verdad del producto: [`PROMPT_BASE.md`](PROMPT_BASE.md). Decisiones: [`decisiones.md`](decisiones.md). Pendientes con Amanecer: [`pendientes.md`](pendientes.md).

## Definition of Done (aplica a toda historia)
Funciona en el navegador con el rol correcto · validaciones en servidor (Form Requests) · permisos con middleware **y** Policy ·
acciones críticas auditadas · `php artisan test` en verde · `npm run build` sin errores · sin consultas N+1 ·
interfaz en español y acorde a la referencia visual · documentada en este archivo.

## Calendario (línea base reprogramada)

| Sprint | Fechas | Objetivo | Fase de `PROMPT_BASE` | Estado |
|---|---|---|---|---|
| 0 | 31 ago – 11 sep | Levantar y definir | — | Cumplido |
| 1 | 14 – 25 sep | Base funcional (dominio anterior) | 1 y parte de 2 | Parcial; se rehízo en Sprint 5 |
| 2 | 28 sep – 9 oct | Seguimiento operativo | 2 y 3 | Reprogramado → Sprint 6 |
| 3 | 12 – 23 oct | Cosecha e historial | 4 | Reprogramado → Sprint 6 |
| 4 | 26 oct – 6 nov | Análisis referencial | 5 y 6 | Reprogramado → Sprint 7 |
| **5** | **9 – 20 nov** | **Base reconstruida** | **1 y 2** | **En curso** |
| 6 | 23 nov – 4 dic | Seguimiento y cosecha | 3 y 4 | Planificado |
| 7 | 7 – 18 dic | Análisis | 5 y 6 | Planificado |
| 8 | 21 dic – 8 ene | Calidad | 7 | Planificado |
| 9 | 11 – 22 ene | Validación con Amanecer | — | Planificado |
| 10 | 25 ene – 5 feb | Cierre y despliegue piloto | — | Planificado |

---

## Primer avance ante el jurado (14 nov 2026) — rama `avance-nov14`

**Objetivo:** un recorrido de punta a punta que funcione sin errores y se pueda demostrar en vivo. Se adelantaron del Sprint 6 las labores y las cosechas (solo lo necesario para el guion).

| # | Entregable | Estado |
|---|---|---|
| 1 | Layout con barra lateral, cabecera y menú por rol | Hecho (Sprint 5) |
| 2 | Login seguro | Hecho (Sprint 5) — se mantiene el registro con aprobación (D-02, confirmado) |
| 3 | Permisos con middleware y Policies, 403 probados | Hecho |
| 4 | Plantaciones: crear, editar, listar, buscar, detalle con pestañas | Hecho (Sprint 5) |
| 5 | Cambio de estado/etapa auditado; no se borra con registros | Hecho (Sprint 5) |
| 6 | Labores culturales: registrar, editar, listado con filtros | Hecho — solo coordinador/a y responsable (D-14) |
| 7 | Cosechas: crear y editar, aprovechable en vivo, acumulados, IDC recalculado | Hecho |
| 8 | IDC visible: puntaje, categoría, variables, faltantes, versión, recalcular | Hecho |
| 9 | Datos reales (11/57/79 verificado) y `DatosPruebaSeeder` (alto 91,3 · medio 68,9 · incompleto) | Hecho |
| 10 | Pruebas y documentación | Hecho — 64 pruebas en verde |

**Fuera de este avance:** aplicaciones con varios productos, eventos fitosanitarios, clima, alertas, calendario, gráficos del dashboard, importación web, CSV nuevos, pantalla de auditoría.

### Guion de demostración (verificado en el navegador el 09/11/2026)
Preparación: `php artisan db:seed --class=DatosPruebaSeeder` (el mismo día de la presentación, porque el IDC evalúa hasta «hoy»).

1. `coordinador@traziva.test` → Dashboard: totales reales (11 plantaciones, 57 labores, 79 aplicaciones) y aviso de 3 plantaciones de prueba.
2. **Cultivos** → listado con las 11 plantaciones de Amanecer (IDC «Incompleto»: aún no hay cosechas reales).
3. **Registrar nueva plantación** → cultivo, nave, fecha, camas, meta kg/cama y período → el código se genera solo → Registrar.
4. En el detalle: pestaña **Labores** → registrar una; pestaña **Cosechas** → registrar una con calidad (probar merma > cantidad para mostrar la validación).
5. Pestaña **IDC** → puntaje y variables; **Recalcular IDC**.
6. Cerrar sesión → `estudiante@traziva.test`: menú reducido, sin formularios; abrir `/usuarios` → 403.
7. Volver como coordinador → Cultivos → filtro «Solo datos de prueba»: TEST-01 (91, alto), TEST-02 (69, medio), TEST-03 (Incompleto, explica qué falta).

### Decisiones
D-14 (labores y cosechas) y D-15 (datos de demostración) en [`decisiones.md`](decisiones.md).

### Riesgos para la presentación
- **IDC con pocos datos:** con una sola cosecha reciente el IDC puede salir «alto» (ver `pendientes.md`). Si el jurado pregunta: es un borrador por validar; mostrar el caso TEST-02 y explicar las variables.
- Si se presenta otro día distinto al de la siembra de datos de prueba, volver a ejecutar `DatosPruebaSeeder` (los puntajes dependen de la fecha).
- Si la red de la sala bloquea Google Fonts, la interfaz usa tipografías del sistema (funciona igual).
- La plantación creada en vivo queda en la base real: después de la demo se puede eliminar si no tiene registros, o cambiar su estado.

## Sprint 5 — Base reconstruida (9 – 20 nov 2026)

**Objetivo:** que la granja pueda administrar usuarios, catálogos y plantaciones desde la interfaz, con permisos por rol, sobre el nuevo modelo de datos (plantación = cultivo + nave + siembra).

**Rama:** `sprint-5`. Nota: las fases 1 y 2 se programaron el 08/11/2026, antes de adoptar la regla de una rama por sprint; esos commits se movieron a `sprint-5` y `main` volvió al estado inicial, para que `main` solo contenga sprints cerrados.

### Historias

| ID | Historia | Esfuerzo | Estado |
|---|---|---|---|
| S5-01 | Layout con barra lateral, cabecera y menú por rol | M | Hecha |
| S5-02 | Login claro y seguro | S | Hecha |
| S5-03 | Cada rol ve solo lo que le corresponde | M | Hecha |
| S5-04 | Administrar usuarios y roles | M | Hecha |
| S5-05 | Administrar catálogos | M | Hecha |
| S5-06 | Registrar plantación desde la interfaz | L | Hecha |
| S5-07 | Ver y buscar plantaciones | M | Hecha |
| S5-08 | Detalle de plantación con pestañas | M | Hecha |
| S5-09 | Cambiar estado sin perder historial | S | Hecha |
| S5-10 | Pruebas y documentación del sprint | M | Hecha |

### Criterios ajustados en el Sprint Planning (09/11/2026)
- **S5-02:** el criterio «sin registro público» se reemplaza por **registro con aprobación** (D-02): cualquiera solicita acceso eligiendo rol, pero la cuenta queda *pendiente* y no entra hasta que el/la coordinador/a la aprueba.
- **Roles:** docente y estudiante **solo consultan** (D-03). Pendiente de validar con Amanecer (difiere del prompt base); impacta el Sprint 6.

### Decisiones del sprint
D-01 a D-13 en [`decisiones.md`](decisiones.md).

### Cómo probar cada historia (contraseña: `TRAZIVA_SEED_PASSWORD` del `.env`)
| ID | Usuario | Ruta | Qué debe verse |
|---|---|---|---|
| S5-01 | coordinador | `/dashboard` | Barra lateral verde con 9 ítems, insignia de rol en la cabecera; en celular, menú ☰ |
| S5-02 | (sin sesión) | `/login`, `/registro` | Errores claros; 7.º intento en 1 min bloqueado; solicitud queda «pendiente» y no entra |
| S5-03 | estudiante | `/usuarios`, `/plantaciones/crear` | Página 403 «Acceso no autorizado»; menú sin Catálogos/Usuarios/Auditoría |
| S5-04 | coordinador | `/usuarios` | Registrar, Editar (rol, estado, vigencia), Restablecer contraseña; aprobar pendientes |
| S5-05 | responsable | `/catalogos/cultivos` | Pestañas de 6 catálogos; crear/editar/desactivar; aviso «Provisional» en calidades |
| S5-06 | responsable | `/plantaciones/crear` | Código sugerido al elegir cultivo/nave/fecha; errores si fechas incoherentes o duplicado |
| S5-07 | docente | `/plantaciones` | Buscador, filtros, insignias estado/etapa, IDC «Incompleto»; sin botones de edición |
| S5-08 | cualquiera | `/plantaciones/{id}` | 7 pestañas; vacías con mensaje claro; aplicaciones con dosis completa |
| S5-09 | responsable | `/plantaciones` → «Estado ▾» | Cambia estado y etapa; aparece en pestaña Historial; «Eliminar» solo sin registros |
| S5-10 | — | consola | `php artisan test` → 49 pruebas en verde; `npm run build` sin errores |

### Resultados al cierre (09/11/2026)
- `php artisan test`: **49 pruebas, 275 verificaciones, todas en verde** (AccesoYPermisos 13, CatalogosYPlantaciones 18, Usuarios 10, Traziva 8).
- `npm run build`: sin errores. Migraciones: 7 aplicadas en MySQL.
- Verificación en navegador (coordinador y estudiante, escritorio y 375 px): layout, usuarios, listado, detalle con pestañas, menú de estado/etapa, 403.

### Deuda técnica
- `CosechaController` valida en línea (sin Form Request) y `PlantacionController::exportar` arma el CSV en el controlador → se rehacen en Sprint 6 (cosechas) y Sprint 7 (CSV).
- El formulario de catálogos consulta las opciones de relación desde la vista (`catalogos/form`): mover al controlador.
- Los campos de fecha usan el selector nativo del navegador: en un navegador en inglés se ven como `mm/dd/yyyy` (los datos guardados y mostrados sí son `dd/mm/aaaa`).
- Cambiar de pestaña con un enlace `#pestaña` en la misma página no la activa (solo al cargar).
- Tipografías desde Google Fonts: si la red de la UDLA lo bloquea se usan las del sistema.

### Riesgos
- Amanecer aún no valida categorías de calidad ni metas por cama: el IDC seguirá «incompleto» en datos reales.
- Diferencia de permisos de docente/estudiante con el prompt base (D-03) podría cambiar el Sprint 6.
- Las cuentas demo `@traziva.test` deben desactivarse antes del piloto (Sprint 10).
- El repositorio aún no tiene copia remota (GitHub): riesgo de pérdida.

### Retrospectiva
- **Funcionó:** definir el catálogo una sola vez y reutilizar componentes Blade aceleró el sprint; las pruebas por rol detectaron errores antes del navegador.
- **No funcionó:** se programó en `main` antes de fijar la regla de ramas; la verificación visual llegó tarde por no tener contraseñas demo comunes.
- **Mejorar:** verificar en navegador al terminar cada historia (ya hay contraseña común); subir el repositorio a GitHub; validar con Amanecer las decisiones D-02/D-03 antes de planificar.
- **Mejorar:** editar archivos con el editor y no con `sed` cuando hay barras invertidas (causó retrabajo en modelos y vistas).
- **Hallazgo:** la auditoría podía guardar el hash de contraseñas al auditar usuarios; se corrigió antes de habilitarla.

---

## Sprint 6 — Seguimiento y cosecha (23 nov – 4 dic) · PROPUESTA

**Objetivo:** registrar desde la interfaz todo el seguimiento de una plantación (labores, aplicaciones, eventos, cosechas, clima) con alertas visibles, para dejar de usar el libro de campo.

| ID | Historia | Esfuerzo |
|---|---|---|
| S6-01 | Registrar labores culturales (tipo según el cultivo, fecha no futura ni anterior a la siembra) | M |
| S6-02 | Registrar eventos fitosanitarios (plaga/enfermedad, fecha, nivel bajo/medio/alto) | S |
| S6-03 | Registrar aplicaciones con varios productos y dosis estructurada; ligar a un evento | L |
| S6-04 | Pantalla «Labores culturales»: listados con filtros (plantación, tipo, fechas) y edición auditada | M |
| S6-05 | Registrar cosechas (Form Request): aprovechable calculado, acumulado por plantación y ciclo, IDC recalculado | M |
| S6-06 | Pantalla «Cosecha y postcosecha» y clima referencial manual | M |
| S6-07 | Alertas: registros incompletos, merma alta, retraso de cosecha, evento alto | M |
| S6-08 | Calendario mensual de actividades por plantación | M (opcional si falta capacidad) |
| S6-09 | Pruebas y documentación del sprint | M |

**Dependencias / preguntas antes de empezar:**
1. Confirmar D-03 (docente y estudiante solo consultan) o definir qué registran.
2. Umbral provisional de «merma alta» (propuesta: > 15 % de lo cosechado) y de «retraso» (propuesta: > 14 días después del inicio estimado sin cosecha). Se marcarán como provisionales en `config/traziva.php`.

