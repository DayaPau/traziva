# Traziva — Sistema de trazabilidad agrícola (Amanecer Biocentro Académico, UDLA)

Laravel 12 · PHP 8.2+ · MySQL · Blade + Tailwind (Vite).

## Puesta en marcha (Laragon + MySQL)

1. Copia el proyecto a `C:\laragon\www\traziva` y abre la terminal de Laragon en esa carpeta.
2. `composer install`
3. `copy .env.example .env` y `php artisan key:generate`
4. Crea la base de datos `traziva` (HeidiSQL o Laragon → Base de datos). Ajusta `DB_*` en `.env` si usas otra clave.
5. `php artisan migrate --seed` — imprime la contraseña de los 4 usuarios demo (`coordinador@traziva.test`, `responsable@…`, `docente@…`, `estudiante@…`). Para fijarla: `TRAZIVA_SEED_PASSWORD=` en `.env`.
6. `npm install && npm run build` (o `npm run dev`)
7. Prueba la importación sin guardar: `php artisan traziva:importar database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx --dry-run`
8. Importa de verdad: `php artisan traziva:importar database/data/FORMATO_PRODUCCION_CULTIVOS.xlsx`
9. `php artisan serve` (o `http://traziva.test` con Laragon) e ingresa.

## Importador (`traziva:importar`)
- Reporte de aceptadas / advertencias / rechazadas en consola y en `storage/app/private/importaciones/*.csv`.
- No corrige en silencio: fechas anteriores a 2018 o a la siembra, dosis ilegibles (`50CC X 2O L`) van a cuarentena; fusiones dudosas generan advertencia.
- Idempotente (hash de origen): reimportar no duplica.
- `--asignar=ultima|todas`: el Excel no indica nave en labores/aplicaciones; por defecto van a la plantación sembrada más recientemente.

## Pruebas
`php artisan test` (SQLite en memoria; requiere la extensión `pdo_sqlite`).

## Pendientes conocidos
- Fórmula del IDC en **borrador** (`config/traziva.php`), validar con tutor y Amanecer.
- Catálogo de calidad **provisional**; formularios de labores/aplicaciones/eventos fitosanitarios; importación vía web.
- SSO Microsoft fuera del alcance del MVP (autenticación propia).
