# Migraciones agenda RRHH (Joan — chore/rrhh-interviews)

Ejecutar **en orden** sobre `medic9ue_medi_rrhh_interviews`:

1. `20260613-01-calendar_enhancements.sql` — catálogo tipos, columnas extra, `id_interviewer`
2. `20260613-02-calendar_split_dates.sql` — separa fechas/horas en `rrhh_custom_events`

Si `rrhh_custom_events` aún no existe, ejecutar primero `../DDL-rrhh-custom-events.sql`.

Nota: si `description` ya existe en `rrhh_custom_events`, omitir o comentar esa línea en el script 01.
