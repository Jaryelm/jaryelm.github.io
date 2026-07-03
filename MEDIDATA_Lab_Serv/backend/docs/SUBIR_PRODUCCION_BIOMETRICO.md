# Subir a producción — agente biométrico (medidata.medicasa.hn)

Ruta en hosting: `/home4/medic9ue/medidata.medicasa.hn`

## Archivos a subir por FTP (desde el repo)

| Archivo local | Destino en producción |
|---------------|------------------------|
| `backend/api/.htaccess` | `backend/api/.htaccess` (**nuevo**) |
| `backend/api/agent_biometric_ingest.php` | igual ruta (si cambió) |
| `frontend/recursos/relojbio.php` | igual ruta |
| `scripts/biometric_agent_linux/run_once.php` | igual ruta (actualizar en sede también) |

## Raíz del sitio — `.htaccess`

**No** reemplazar el `.htaccess` completo del servidor.

1. Abrir `/home4/medic9ue/medidata.medicasa.hn/.htaccess`
2. Si ya existe `MEDIDATA_AGENT_INGEST_SECRET`, solo añadir la línea `MEDIDATA_RELJO_DB_SITE`
3. Si no existe el bloque, copiar desde `backend/scripts/htaccess_produccion_biometrico.fragment` y pegar el token real del agente en sede

## Servidor sede (medicasa)

Tras subir `run_once.php`, en SSH:

```bash
php /home/medicasa/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once.php
```

## Verificación

1. `https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php` → `"ready":true`
2. POST desde sede → JSON `"success":true` (no HTML 409)
3. `https://medidata.medicasa.hn/frontend/recursos/relojbio.php` → tabla con marcas desde `biometric_marcas`

Si el POST devuelve **HTTP 409** y HTML `humans_*`, el WAF del hosting bloquea bots. Sin soporte WHM, use **MySQL remoto desde sede**: ver `backend/docs/INGESTA_MYSQL_DESDE_SEDE.md` y `run_once_db.php`.
