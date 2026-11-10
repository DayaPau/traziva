# Pendientes de validar con Amanecer

Estos puntos **no se resuelven en el código**. Lo que dependa de ellos se marca como "provisional" en la interfaz.

1. Categorías de calidad reales ("aceptable… eficiente"). Hoy: Aceptable, Buena, Excelente (**provisional**).
2. Metas por cama y su período (mensual / anual / ciclo total).
3. Camas y plantas por nave en las filas del Excel que tienen varias naves.
4. Si las fechas 10/01/2016 y 15/01/2016 son del 2026; dosis de THALA ("2O L"); siembra de mora "Nov-2014" (¿2024?).
5. Significado de la tabla de fechas semanales de la mora.
6. Si "Pioxamil" y "Proxanil" son productos distintos; si BAUBETIC y BOUVETIC son el mismo.
7. Si frambuesa sigue en la granja y si lechuga entra al MVP.
8. Despliegue final y apoyo de TI/Marketing de la UDLA.

## Pendientes con el tutor
- Validar la fórmula del IDC (`config/traziva.php`, versión `borrador-0.1`).

## Pendientes internos del equipo
- Confirmar con Amanecer quién aprueba las solicitudes de acceso (¿solo la coordinadora?). Ver D-02 en `decisiones.md`.
- **Permisos de docente y estudiante (D-03):** hoy ambos solo consultan. El prompt base indicaba que el docente registra "con permisos limitados" y el estudiante registra labores en plantaciones asignadas. Confirmar con Amanecer antes del Sprint 6.
- **Registro público con aprobación (D-02):** confirmar que Amanecer está de acuerdo con que cualquiera pueda solicitar acceso.

## Observaciones del IDC para el tutor (fórmula borrador-0.1)
- **Períodos muy cortos inflan el rendimiento:** el rendimiento compara lo cosechado contra la meta *del período evaluado* (desde la primera cosecha hasta hoy). Con una sola cosecha reciente el período es de 1–2 días y la meta proporcional es pequeña, así que el rendimiento llega a 100 % y el IDC puede salir «alto» con muy pocos datos. Ejemplo verificado: 5 kg cosechados al día siguiente de la siembra con meta 8 kg/cama/mes y 6 camas → IDC 80 (alto). Opciones a discutir: período mínimo de evaluación (p. ej. 30 días), o exigir un número mínimo de cortes antes de dar puntaje.
- El IDC parcial evalúa hasta «hoy»: si no se registran cosechas nuevas, el puntaje baja con los días (la meta del período crece). Es coherente con un indicador de seguimiento, pero debe explicarse al usuario.
