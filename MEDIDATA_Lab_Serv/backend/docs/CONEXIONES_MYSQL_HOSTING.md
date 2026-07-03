# Reducir "Too many connections" en hosting compartido (cPanel)

## Qué está pasando

En hosting compartido, MySQL limita conexiones simultáneas (p. ej. 150–500 según el plan). Cada petición PHP puede abrir una conexión PDO. Si muchos usuarios navegan o hay cron cada minuto, se agota el límite.

En este proyecto ya está aplicado:

- **Una conexión PDO por petición** (`Conexion.php` singleton, sin `ATTR_PERSISTENT`).
- **MySQL vía `localhost`** cuando la web corre en `medidata.medicasa.hn` (mismo servidor cPanel; no usar la IP pública desde PHP).
- **Menos escrituras a `users.last_activity`**: máximo cada **15 minutos** por usuario (antes cada 3 min).
- **`session_write_close()`** tras validar sesión para liberar el candado (otras pestañas no bloquean el worker).

## Qué debes hacer tú (hosting)

1. **cPanel → Select PHP Version → Extensions**  
   Confirma que `pdo_mysql` está activo.

2. **cPanel → MultiPHP INI Editor** (o vía `.user.ini` si el host lo permite):
   ```ini
   ; Máximo de procesos hijos (ajustar según plan; no subir sin límite del proveedor)
   pm.max_children = 8
   pm.start_servers = 2
   pm.max_spare_servers = 3
   pm.max_requests = 300
   ```

3. **Cron del agente biométrico**  
   Debe ejecutarse con el mismo usuario MySQL que la web (no root si el host lo prohíbe). Cada minuto sin cerrar conexión empeora el problema.

4. **Revisar en cPanel → MySQL® → Current Connections**  
   Si ves muchas conexiones `Sleep` de usuario `medic9ue_moisesc`, hay scripts colgados o clientes que no cierran.

5. **Subir archivos**  
   `backend/registros/session_check.php` y `backend/bd/Conexion.php` (si aún no están en producción).

6. **Opcional (pago)**  
   Pedir al proveedor aumentar `max_user_connections` para la cuenta `medic9ue_medi_data`.

## Buenas prácticas en el código

- No abrir `session_start()` en PDFs públicos si no hace falta (usar token o IP allowlist).
- Evitar cron cada minuto si el pull ZK tarda mucho; subir a cada 5–15 minutos.
- No usar `PDO::ATTR_PERSISTENT => true` en hosting compartido.

## Si el error sigue tras subir cambios

1. Espera 2–5 minutos y vuelve a intentar (conexiones `Sleep` pueden tardar en liberarse).
2. Revisa si el mensaje aparece solo en **login** o en **todas** las páginas.
3. En phpMyAdmin del hosting, ejecuta `SHOW PROCESSLIST` y `SHOW STATUS LIKE 'Threads_connected'` para ver quién consume conexiones.

## Contacto con soporte hosting

Pregunta literal: *"Aumentar max_user_connections para medic9ue_medi_data"* y mencionar que la app ya limita actualizaciones de actividad y usa una conexión por request PHP-FPM.
