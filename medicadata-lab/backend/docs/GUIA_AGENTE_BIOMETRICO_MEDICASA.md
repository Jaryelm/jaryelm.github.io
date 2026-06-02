# Guía paso a paso — agente biométrico (Sede física + MediDATA central)

Escenario MediDATA oficial:

| Entorno | URL | Base de datos (`backend/bd/Conexion.php`) |
|--------|-----|--------------------------------------------|
| **Producción** | [https://medidata.medicasa.hn/](https://medidata.medicasa.hn/) | Host remoto cuando `HTTP_HOST` **no** es `localhost` / `127.0.0.1` |
| **Laboratorio local (XAMPP)** | típico `http://localhost/MedicasaDATAUpdate2/` | MySQL **localhost** mismo esquema `medic9ue_medi_data` |

Servidor físico en sede (Ubuntu, hostname `medicasa`):

- IP LAN hacia el reloj biométrico: ejemplo `192.168.1.102`
- IP pública SSH/acceso: `201.190.11.6`
<<<<<<< Updated upstream
- Reloj MB360 LAN: ejemplo `192.168.1.201:4370`
=======
- Reloj MB360 LAN: ejemplo `192.168.1.91:4370`
>>>>>>> Stashed changes

El agente solo necesita salida HTTPS hacia MediDATA (**no** abrir puertos entrantes desde Internet hacia el agente por este flujo).

---

## Parte A — Producción (`medidata.medicasa.hn`)

### A1. Subir código

Desplegar en el servidor de hosting el proyecto actualizado, incluyendo al menos:

- `backend/api/agent_biometric_ingest.php`
- `backend/bd/Conexion.php` (sin cambios obligatorios)
- Referencia DDL en `backend/scripts/DDL_biometric_marcas.sql`

### A2. Crear tabla en MySQL **de producción**

Ejecutá el SQL **sobre la misma base** que usa la app en producción (`medic9ue_medi_data` en el host definido para `medidata.medicasa.hn`):

Archivo:

```text
backend/scripts/DDL_biometric_marcas.sql
```

(phpMyAdmin, MySQL Workbench o `mysql` CLI desde un host permitido.)

### A3. Definir secreto del agente (`MEDIDATA_AGENT_INGEST_SECRET`)

Generá un token largo (mínimo 24 caracteres recomendados 40+ aleatorios):

```bash
openssl rand -hex 32
```

Instalalo como variable de entorno **accesible para PHP**:

- **Apache**: `SetEnv MEDIDATA_AGENT_INGEST_SECRET "…token…"` en el VirtualHost de `medidata.medicasa.hn`, **o**
- **PHP-FPM**: `env[MEDIDATA_AGENT_INGEST_SECRET] = …` en el pool correspondiente,

y **reiniciá** Apache / PHP-FPM.

**Checklist — mismo valor en todos lados (copiá un solo token; no uses la contraseña de MySQL de `Conexion.php`):**

| Lugar | Variable / archivo |
|--------|-------------------|
| Servidor físico sede | `MEDIDATA_AGENT_INGEST_SECRET` en `/etc/medicasa-biometric-agent.env` |
| Producción web `medidata.medicasa.hn` | Apache `SetEnv` o pool PHP-FPM: `MEDIDATA_AGENT_INGEST_SECRET` |
| Laboratorio local (opcional) | Misma línea `SetEnv` en el Apache de XAMPP si probás el ingest en `localhost` |
| Registro interno TI | `credenciales.txt` en la raíz del proyecto (está en `.gitignore`; no subir a Git) |

Ejemplo de línea Apache (producción o XAMPP): plantilla en `backend/scripts/medidata_agent_Apache_SetEnv.example.conf`.

### A4. Confirmar URL del punto de ingest

Probá en el navegador (solo verificación rápida):

```text
https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php
```

Deberías ver JSON tipo:

```json
{"endpoint":"agent_biometric_ingest","ready":true,...}
```

- Si `ready`: **false** → el secreto no está llegando al proceso PHP (*SetEnv/FPM incorrecto*).

Si tu hospedaje monta la app dentro de una subcarpeta (poco habitual para este dominio), añadila al path antes de `/backend/api/...`.

**URL que usarás en el agente físico:**

```text
https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php
```

### A5. Prueba POST desde cualquier equipo (opcional antes del cron)

Sustituí `TOKEN_HEX` por el mismo valor que `MEDIDATA_AGENT_INGEST_SECRET`:

```bash
curl -sS -X POST "https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN_HEX" \
  -H "X-MEDIDATA-AGENT-TOKEN: TOKEN_HEX" \
  -d '{"site_code":"Sucursal_1","records":[["prueba_ping","999","Fingerprint","2099-01-01 00:00:00"]]}'
```

Respuesta esperada: `"success":true` y `"inserted":` 1 la primera vez; al repetir, `skipped_or_duplicate` aumenta por la llave UNIQUE.

---

## Parte B — Laboratorio local (XAMPP Windows)

Objetivo: misma tabla y mismo endpoint contra **tu MySQL local** cuando `HTTP_HOST` es `localhost`.

### B1. DDL local

Ejecutá el mismo `DDL_biometric_marcas.sql` en tu BD local `medic9ue_medi_data`.

### B2. Secreto en laboratorio — dos caminos equivalentes

1. **Archivo local (ideal XAMPP / sin tocar Apache):**
   - Copiar `backend/php/biometric_ingest_secret.local.env.example` → `backend/php/biometric_ingest_secret.local.env`
   - Una línea: `MEDIDATA_AGENT_INGEST_SECRET=TU_HEX_IGUAL_QUE_EL_AGENTE` (este archivo está en `.gitignore`)
   - `agent_biometric_ingest.php` lo lee antes del GET/`ready`

2. **Apache `mod_env`** (opcional si ya cargado en `httpd.conf` línea típica `LoadModule env_module`):
   - Ver `backend/conf/README_APACHE_XAMPP.md` y copia opcional `apache_medidata_agent_secret.conf`

### B2bis. Ejemplo sólo «SetEnv» clásico (si preferís sólo Apache)

Ejemplo (`httpd.conf` o incluidos de `<VirtualHost *:80>` para localhost):

```apache
SetEnv MEDIDATA_AGENT_INGEST_SECRET "mismo-esquema-que-en-produ-o-otro-para-pruebas"
```

Reiniciar Apache desde el panel XAMPP.

### B3. URL local del ingest

Según donde tengas el proyecto:

```text
http://localhost/MedicasaDATAUpdate2/backend/api/agent_biometric_ingest.php
```

(si el docroot fuera otro, ajustá la ruta).

Comprobación GET igual que producción (`ready`/JSON).

### B4. Probar ingest local con `curl` (Git Bash / WSL)

Misma llamada POST que arriba cambiando la URL al `localhost/...`.

`Conexion.php` elegirá **DB local** automáticamente porque `HTTP_HOST` será `localhost`.

---

## Parte C — Servidor físico Ubuntu (sede, `201.190.11.6` LAN + reloj)

### C1. PHP CLI y extensiones

```bash
sudo apt update
sudo apt install -y php-cli php-curl php-xml php-mbstring
sudo phpenmod sockets 2>/dev/null || sudo sed -i 's/^;extension=sockets/extension=sockets/' /etc/php/*/cli/php.ini
php -m | grep -E 'curl|sockets'
```

### C2. Copiar código del proyecto

Por ejemplo Git o rsync hacia `/opt/MedicasaDATAUpdate2` con al menos:

- `backend/php/`
- `backend/sdk/zkteco/`
- `scripts/biometric_agent_linux/run_once.php`

### C3. Archivo de entorno (`/etc/medicasa-biometric-agent.env`)

Permisos estrictos: `sudo chmod 600 /etc/medicasa-biometric-agent.env`

Contenido (ajustá token y rutas):

```bash
MEDIDATA_AGENT_REPO_ROOT=/opt/MedicasaDATAUpdate2

# Producción MediDATA central
MEDIDATA_AGENT_INGEST_URL=https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php
MEDIDATA_AGENT_INGEST_SECRET=pégue-aquí-el-mismo-valor-de-producción

MEDIDATA_AGENT_SITE_CODE=Sucursal_1

# Reloj en esta LAN
<<<<<<< Updated upstream
MEDIDATA_RELOJ_IP=192.168.1.201
=======
MEDIDATA_RELOJ_IP=192.168.1.91
>>>>>>> Stashed changes
MEDIDATA_RELOJ_PORT=4370
```

(No guardes comillas alrededor del token salvo caracteres especiales.)

### C4. Prueba manual antes del cron

```bash
sudo chmod +x /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once.php
set -a; source /etc/medicasa-biometric-agent.env; set +a
/usr/bin/php /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once.php
```

Si el pull falla: revisión de red/firewall LAN; si el POST falla: SSL, DNS o URL del ingest.

Para certificados de prueba inseguros (solo temporal):

```bash
export MEDIDATA_AGENT_VERIFY_SSL=0
```

### C5. Cron cada 1 minuto (root recomendado)

```bash
sudo crontab -e
```

Línea:

```cron
* * * * * set -a; . /etc/medicasa-biometric-agent.env; set +a; /usr/bin/php /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once.php >> /var/log/medicasa-biometric-agent.log 2>&1
```

Log:

```bash
sudo tail -f /var/log/medicasa-biometric-agent.log
```

---

## Parte D — Verificación final

1. Producción GET: [`https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php`](https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php) → `ready: true`.
2. En BD producción (`biometric_marcas`): aparecen filas `site_code = Sucursal_1` según marca real del reloj.
3. Local: DDL + GET local + opcional POST de prueba contra `localhost`.
4. Agente físico sin errores repetidos en el log tras varios minutos de cron (cada minuto hay una corrida nueva).

---

## Recordatorio `Conexion.php`

- Una petición a **`medidata.medicasa.hn`** usa la configuración **`$dbProduccion`**.
- Una petición a **`localhost`** usa **`$dbLocal`**.

No hace falta duplicar lógicas en el agente: él solo hace HTTPS; quién decide qué BD es el **hostname** del `MEDIDATA_AGENT_INGEST_URL`.

La pantalla interna **`frontend/recursos/relojbio.php`** puede seguir haciendo pull directo solo donde la red alcance el reloj; la tabla `biometric_marcas` almacena lo que llega desde la sede mediante el agente.

