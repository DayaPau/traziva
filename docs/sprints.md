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

## Sprint 5 — Base reconstruida (9 – 20 nov 2026)

**Objetivo:** que la granja pueda administrar usuarios, catálogos y plantaciones desde la interfaz, con permisos por rol, sobre el nuevo modelo de datos (plantación = cultivo + nave + siembra).

**Rama:** `sprint-5`. Nota: las fases 1 y 2 se programaron el 08/11/2026, antes de adoptar la regla de una rama por sprint; esos commits se movieron a `sprint-5` y `main` volvió al estado inicial, para que `main` solo contenga sprints cerrados.

### Historias

| ID | Historia | Esfuerzo | Estado |
|---|---|---|---|
| S5-01 | Layout con barra lateral, cabecera y menú por rol | M | Hecha |
| S5-02 | Login claro y seguro | S | Hecha |
| S5-03 | Cada rol ve solo lo que le corresponde | M | Hecha |
| S5-04 | Administrar usuarios y roles | M | En curso |
| S5-05 | Administrar catálogos | M | Hecha |
| S5-06 | Registrar plantación desde la interfaz | L | Hecha |
| S5-07 | Ver y buscar plantaciones | M | En curso (ajuste IDC «Incompleto») |
| S5-08 | Detalle de plantación con pestañas | M | En curso (textos de estados vacíos) |
| S5-09 | Cambiar estado sin perder historial | S | En curso (cambio rápido de etapa) |
| S5-10 | Pruebas y documentación del sprint | M | En curso |

### Criterios ajustados en el Sprint Planning (09/11/2026)
- **S5-02:** el criterio «sin registro público» se reemplaza por **registro con aprobación** (D-02): cualquiera solicita acceso eligiendo rol, pero la cuenta queda *pendiente* y no entra hasta que el/la coordinador/a la aprueba.
- **Roles:** docente y estudiante **solo consultan** (D-03). Pendiente de validar con Amanecer (difiere del prompt base); impacta el Sprint 6.

### Decisiones del sprint
D-01 a D-10 en [`decisiones.md`](decisiones.md).

### Deuda técnica
- (se completa al cierre)

### Riesgos
- Amanecer aún no valida categorías de calidad ni metas por cama: el IDC seguirá «incompleto» en datos reales.
- Diferencia de permisos de docente/estudiante con el prompt base (D-03) podría cambiar el Sprint 6.
