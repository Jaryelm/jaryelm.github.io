# Agente biométrico (sede física Linux → MediDATA central)

**Pasos ordenados MediDATA oficial (prod [medidata.medicasa.hn](https://medidata.medicasa.hn/), local XAMPP, servidor `201.190.11.6`):**

→ **[GUIA_AGENTE_BIOMETRICO_MEDICASA.md](./GUIA_AGENTE_BIOMETRICO_MEDICASA.md)**

→ **Pruebas en servidor físico (192.168.1.102 / 201.190.11.6):** [PRUEBAS_SERVIDOR_FISICO_BIOMETRICO.md](./PRUEBAS_SERVIDOR_FISICO_BIOMETRICO.md)

---

Sirve cuando el servidor web de MediDATA **no** llega por UDP al MB360 pero hay un equipo **en la LAN del reloj** (ej. servidor `medicasa` `192.168.1.102` junto al reloj `192.168.1.91`). Ese equipo hace **ZK pull** y envía los datos por **HTTPS** al mismo MediDATA que usás en laboratorio/producción.

## 1. Base de datos (servidor MediDATA central)

Ejecutá el script SQL en la BD del entorno donde recibís la API (localhost o hosting):

```text
backend/scripts/DDL_biometric_marcas.sql
```

## 2. Secreto y Apache / PHP-FPM en el servidor web

Definí un token largo aleatorio igual en **dos lugares**:

- En el servidor **web** (donde está `backend/api/agent_biometric_ingest.php`), variable de entorno:

  **`MEDIDATA_AGENT_INGEST_SECRET`**

Ejemplos:

- Apache: `SetEnv MEDIDATA_AGENT_INGEST_SECRET "aquí-token-largo"`
- PHP-FPM: `env[MEDIDATA_AGENT_INGEST_SECRET] = ...` en pool
- systemd + nginx php-fpm: `Environment=MEDIDATA_AGENT_INGEST_SECRET=...`

Reiniciá el servicio web tras cambiar env.

La URL típica de ingesta será:

```text
https://TU_DOMINIO/ruta/MedicasaDATAUpdate2/backend/api/agent_biometric_ingest.php
```

Probá con `curl` desde la sede (sustituí valores):

```bash
curl -sS -X POST "https://TU_DOMINIO/.../agent_biometric_ingest.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{"site_code":"Sucursal_1","records":[["test","1","Check-in","2026-01-01 12:00:00"]]}'
```

Deberías ver JSON `success: true` y `inserted: 1` (o `skipped` si duplicado).

## 3. Servidor físico en sede (Ubuntu, ej. `201.190.11.6` / LAN `192.168.1.102`)

### 3.1 Paquetes

```bash
sudo apt update
sudo apt install -y php-cli php-curl php-mysql php-xml php-mbstring
sudo phpenmod sockets   # o habilitar extension=sockets en php.ini CLI
php -m | grep sockets
```

### 3.2 Código del proyecto

Cloná o rsync el repositorio a una ruta fija, por ejemplo:

```text
/opt/MedicasaDATAUpdate2
```

El agente solo necesita al menos `backend/php/` y `backend/sdk/zkteco/`.

### 3.3 Variables de entorno del agente

Creá `/etc/medicasa-biometric-agent.env` (permisos 600, root):

```bash
MEDIDATA_AGENT_REPO_ROOT=/opt/MedicasaDATAUpdate2
MEDIDATA_AGENT_INGEST_URL=https://TU_DOMINIO/.../backend/api/agent_biometric_ingest.php
MEDIDATA_AGENT_INGEST_SECRET=aquí-el-mismo-token-que-el-servidor-web
MEDIDATA_AGENT_SITE_CODE=Sucursal_1
MEDIDATA_RELOJ_IP=192.168.1.91
MEDIDATA_RELOJ_PORT=4370
# Opcional: MEDIDATA_AGENT_DEVICE_SERIAL=...
# Solo pruebas con certificados dudosos: MEDIDATA_AGENT_VERIFY_SSL=0
```

### 3.4 Cron cada 1 minuto

```bash
sudo crontab -e
```

Línea (ajustá ruta al `run_once.php` del repo):

```cron
* * * * * set -a; . /etc/medicasa-biometric-agent.env; set +a; /usr/bin/php /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once.php >> /var/log/medicasa-biometric-agent.log 2>&1
```

### 3.5 Prueba manual

```bash
set -a; source /etc/medicasa-biometric-agent.env; set +a
/usr/bin/php /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once.php
```

## 4. Laboratorio local (XAMPP)

- Misma tabla `biometric_marcas` en tu MySQL local.
- Mismo `MEDIDATA_AGENT_INGEST_SECRET` en `php.ini` / entorno de Apache si probás el endpoint en `localhost`.
- El agente en la **sede** puede apuntar la `MEDIDATA_AGENT_INGEST_URL` a **producción** o a un túnel/ngrok hacia tu PC; lo habitual es **producción** como único central.

## 5. Siguiente paso (pantalla `relojbio.php`)

Hoy la pantalla hace pull directo al reloj. Cuando quieras unificar vista, se puede leer desde `biometric_marcas` filtrando por `site_code` en lugar de (o además de) pull local. Eso es un cambio de UI aparte.

---

**Resumen:** el pull ZK corre **solo** en el Linux de sede; MediDATA central solo recibe **POST JSON** autenticado y guarda en **`biometric_marcas`**. Laboratorio y producción comparten el mismo contrato de API y el mismo esquema de tabla.
