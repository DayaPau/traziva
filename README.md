# Traziva — Trazabilidad agrícola (Amanecer Biocentro Académico, UDLA)

Plataforma web para registrar y consultar la producción de la granja: plantaciones (cultivo + nave + siembra),
labores culturales, aplicaciones, eventos fitosanitarios, cosechas e Índice de Desempeño del Cultivo (IDC, referencial).
Reemplaza al Excel y al libro de campo; el Excel de Amanecer solo se usa como carga inicial.

**Stack:** Laravel 12 · PHP 8.2+ · MySQL · Blade + Tailwind (Vite) · Alpine.js.
**Documentación:** [`docs/PROMPT_BASE.md`](docs/PROMPT_BASE.md) (producto) · [`docs/sprints.md`](docs/sprints.md) (avance por sprint) ·
[`docs/decisiones.md`](docs/decisiones.md) · [`docs/pendientes.md`](docs/pendientes.md).

## Instalación en Laragon

1. Copia el proyecto a `C:\laragon\www\traziva` y abre la terminal de Laragon en esa carpeta.
2. En el `php.ini` de Laragon habilita `extension=pdo_sqlite` (pruebas) y `extension=zip` (lectura del Excel).
3. `composer install`
4. `copy .env.example .env` y `php artisan key:generate`
5. Crea la base de datos `traziva` (utf8mb4_unicode_ci) y ajusta `DB_*` en `.env`.
6. En `.env` define la contraseña común de los usuarios demo (`TRAZIVA_SEED_PASSWORD=UnaClaveDePrueba1`) y los códigos de verificación para registrarse como coordinador/a o responsable (`TRAZIVA_CODIGO_COORDINADOR=…`, `TRAZIVA_CODIGO_RESPONSABLE=…`). Elige códigos difíciles de adivinar y compártelos solo con esas personas.
7. `php artisan migrate --seed`
8. `npm install && npm run build` (o `npm run dev` mientras desarrollas)
9. Carga inicial desde Excel (opcional): primero simula, luego importa:
   - `php artisan traziva:importar database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx --dry-run`
   - `php artisan traziva:importar database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx`
10. Abre `http://traziva.test` (Laragon) o ejecuta `php artisan serve`.

## Usuarios demo

| Correo | Rol | Puede |
|---|---|---|
| `coordinador@traziva.test` | Coordinador/a | Todo: usuarios y roles, catálogos, plantaciones, labores, cosechas |
| `responsable@traziva.test` | Responsable de producción | Catálogos, plantaciones, labores, cosechas y recalcular IDC |
| `docente@traziva.test` | Docente / investigador | Solo consulta y CSV |
| `estudiante@traziva.test` | Estudiante / practicante | Solo consulta y CSV, con acceso temporal |

Contraseña: la de `TRAZIVA_SEED_PASSWORD`. Volver a ejecutar `php artisan db:seed` la reaplica **solo** a estas cuatro cuentas.
Son cuentas de prueba: **desactívalas antes del piloto real**.

### Registro de cuentas nuevas («Crear cuenta» en el login)
- **Docente y estudiante:** se registran solos y entran de inmediato.
- **Coordinador/a y responsable:** necesitan el código de verificación de su rol (del `.env`).
- **Límite de cuentas activas:** 1 coordinador/a y 2 responsables (`config/traziva.php`). Con el cupo lleno no se puede crear otra, ni con el código correcto.
- Para **cambiar de coordinador/a** (por ejemplo, reemplazar la cuenta demo por la real): `php artisan traziva:cambiar-coordinador --email=correo@udla.edu.ec`.

## Comandos útiles

| Comando | Para qué |
|---|---|
| `php artisan test` | Pruebas automáticas (SQLite en memoria; no toca MySQL) |
| `npm run build` | Compila CSS/JS |
| `php artisan migrate` | Aplica migraciones nuevas |
| `php artisan db:seed` | Roles, categorías de calidad provisionales y usuarios demo |
| `php artisan traziva:importar <xlsx> [--dry-run] [--asignar=ultima\|todas]` | Migración inicial desde el Excel de Amanecer |
| `php artisan db:seed --class=DatosPruebaSeeder` | Crea 3 plantaciones de **prueba** `TEST-…` (IDC alto, medio e incompleto). Ejecutarlo el día de la demo |
| `php artisan traziva:borrar-prueba` | Borra solo los datos de prueba (`es_prueba` + código `TEST-…`); los reales no se tocan |
| `php artisan traziva:cambiar-coordinador --email=…` | Deja a esa persona como único/a coordinador/a activo/a (la crea si no existe) y desactiva a los/las demás |

### Datos reales vs. datos de prueba
- **Reales:** los del Excel de Amanecer (11 plantaciones, 57 labores, 79 aplicaciones, sin cosechas aún) y lo que se registra desde la interfaz.
- **Prueba:** sintéticos, solo para demostrar el IDC. Siempre `es_prueba = true`, código `TEST-…`, insignia «Prueba», filtrables y **excluidos de los totales del panel**.

## Importador (`traziva:importar`)
- Reporte de aceptadas / advertencias / rechazadas en consola y en `storage/app/private/importaciones/*.csv`.
- No corrige en silencio: fechas anteriores a 2018 o a la siembra y dosis ilegibles (`50CC X 2O L`) van a cuarentena; lo dudoso genera advertencia.
- Idempotente (hash de origen): reimportar no duplica.
- `--asignar=ultima|todas`: el Excel no indica nave en labores/aplicaciones; por defecto van a la plantación sembrada más recientemente.

## Estructura (resumen)
- `app/Http/Controllers` — controladores delgados; validación en `app/Http/Requests`.
- `app/Policies` + `AppServiceProvider` (Gates) — autorización; el/la coordinador/a pasa todo con `Gate::before`.
- `app/Services` — lógica de negocio: `Idc\IdcCalculator`, `Importacion\ExcelCultivosImporter`, `Plantaciones\CodigoPlantacion`, `Usuarios\GestionUsuarios`.
- `app/Support/Catalogos.php` — definición de los 6 catálogos administrables.
- `app/Models/Concerns/Auditable.php` — auditoría automática (solo inserción, sin contraseñas).
- `config/traziva.php` — roles, menú por rol y fórmula del IDC (**borrador**).

## Pendientes conocidos
- Fórmula del IDC en **borrador** (`config/traziva.php`), por validar con el tutor y Amanecer.
- Categorías de calidad y metas por cama **provisionales**.
- Ya se registran labores y cosechas desde la interfaz. Aplicaciones, eventos, clima y alertas: Sprint 6. Dashboard con gráficos, CSV y auditoría: Sprint 7. Importación web: Sprint 8.
- SSO institucional fuera del alcance del MVP (autenticación propia).
