# Pruebas en servidor físico (201.190.11.6 → LAN 192.168.1.102)

Servidor en el mismo segmento que el reloj **192.168.1.91**. El flujo MediDATA es:

1. **Pull ZK UDP** desde `192.168.1.102` hacia el reloj.
2. **POST HTTPS** a `agent_biometric_ingest.php` en producción.
3. Filas en MySQL **`biometric_marcas`** (`site_code` = `MEDIDATA_AGENT_SITE_CODE`).
4. Opcional: ver en `relojbio.php` con `MEDIDATA_RELJO_DB_SITE` en el servidor web.

---

## 1. Conectarse por SSH

Desde su PC (con VPN si aplica):

```bash
ssh usuario@201.190.11.6
# o directamente si está en LAN:
ssh usuario@192.168.1.102
```

Comprobar IP LAN:

```bash
hostname -I
# Debe incluir 192.168.1.102
```

---

## 2. Sincronizar código del proyecto

El agente necesita al menos:

- `backend/php/`
- `backend/sdk/zkteco/`
- `scripts/biometric_agent_linux/`

Ejemplo:

```bash
sudo mkdir -p /opt/MedicasaDATAUpdate2
# Desde su PC con el repo actualizado (ajuste usuario/ruta):
# rsync -avz --exclude .git /ruta/MedicasaDATAUpdate2/ usuario@192.168.1.102:/opt/MedicasaDATAUpdate2/
```

---

## 3. PHP CLI + extensiones

```bash
sudo apt update
sudo apt install -y php-cli php-curl php-xml php-mbstring
sudo phpenmod sockets 2>/dev/null || true
php -m | grep -E 'curl|sockets'
```

Ambas extensiones deben aparecer.

---

## 4. Archivo de entorno del agente

```bash
sudo cp /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/medicasa-biometric-agent.env.example \
  /etc/medicasa-biometric-agent.env
sudo chmod 600 /etc/medicasa-biometric-agent.env
sudo nano /etc/medicasa-biometric-agent.env
```

Valores mínimos:

| Variable | Valor típico |
|----------|----------------|
| `MEDIDATA_AGENT_REPO_ROOT` | `/opt/MedicasaDATAUpdate2` |
| `MEDIDATA_AGENT_INGEST_URL` | `https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php` |
| `MEDIDATA_AGENT_INGEST_SECRET` | **Igual** que Apache en producción |
| `MEDIDATA_AGENT_SITE_CODE` | `Sucursal_1` (o el que usen) |
| `MEDIDATA_RELOJ_IP` | `192.168.1.91` |
| `MEDIDATA_RELOJ_PORT` | `4370` |

---

## 5. Auditoría automática (recomendado)

```bash
chmod +x /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/server_audit.sh
/opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/server_audit.sh
```

Interpretación:

| Resultado | Significado |
|-----------|-------------|
| PING OK | ICMP al reloj (no garantiza ZK) |
| TCP OK | Puerto 4370 alcanzable |
| ZK UDP OK | La librería hizo handshake — **listo para pull** |
| ZK UDP FAIL | Revisar MB360: clave «Conexión a PC», ADMS, firmware |
| GET ingest `ready: true` | Producción tiene el secreto configurado |
| Registros obtenidos > 0 | Hay marcas en el reloj |

Prueba de envío real a producción:

```bash
/opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/server_audit.sh --run-agent
```

---

## 6. Pruebas manuales paso a paso

### 6.1 Red

```bash
ping -c 3 192.168.1.91
nc -zv -w 3 192.168.1.91 4370
# o: timeout 3 bash -c 'echo >/dev/tcp/192.168.1.91/4370' && echo TCP_OK
```

### 6.2 Diagnóstico ZK (misma lógica que MediDATA)

```bash
set -a; . /etc/medicasa-biometric-agent.env; set +a
php /opt/MedicasaDATAUpdate2/backend/php/zk_mb360_connect_diagnose.php
echo "exit=$?"
# exit=0 → ZK OK
```

### 6.3 Agente una corrida

```bash
set -a; . /etc/medicasa-biometric-agent.env; set +a
php /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once.php
echo "exit=$?"
```

Códigos de salida `run_once.php`:

| exit | Causa |
|------|--------|
| 0 | OK (o lista vacía sin error ZK) |
| 2 | Error pull ZK |
| 4 | cURL no llegó a producción |
| 5 | HTTP 4xx/5xx en ingest |
| 6 | JSON sin `success: true` |

### 6.4 Ingest en producción (sin reloj)

```bash
curl -sS "https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php"
# Esperado: {"endpoint":"agent_biometric_ingest","ready":true,...}
```

### 6.5 Captura UDP (solo si ZK falla y TCP OK)

```bash
sudo timeout 15 tcpdump -i any -n host 192.168.1.91 and port 4370
# En otra terminal ejecutar zk_mb360_connect_diagnose.php
# Debe verse tráfico UDP bidireccional; si solo sale del servidor → el reloj no responde ZK
```

---

## 7. Cron

```bash
sudo crontab -e
```

Línea:

```cron
* * * * * set -a; . /etc/medicasa-biometric-agent.env; set +a; /usr/bin/php /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once.php >> /var/log/medicasa-biometric-agent.log 2>&1
```

Verificar:

```bash
sudo crontab -l | grep run_once
sudo tail -f /var/log/medicasa-biometric-agent.log
```

---

## 8. Base de datos producción

Ejecutar en MySQL de **medidata.medicasa.hn**:

`backend/scripts/DDL_biometric_marcas.sql`

Consulta de verificación:

```sql
SELECT site_code, COUNT(*) AS n, MAX(marca_datetime) AS ultima
FROM biometric_marcas
GROUP BY site_code;
```

---

## 9. Servidor web producción

En Apache del hosting, mismo token que `/etc/medicasa-biometric-agent.env`:

Ver `backend/scripts/medidata_agent_Apache_SetEnv.example.conf`

Reiniciar Apache tras cambiar `SetEnv`.

En laboratorio, opcional en `biometric_ingest_secret.local.env`:

`MEDIDATA_RELJO_DB_SITE=Sucursal_1`

---

## 10. Orden de corrección típico

1. `server_audit.sh` → corregir sockets / env / IP reloj.
2. Si ZK falla → software ZKTeco oficial en `192.168.1.102`; luego clave PC y desactivar ADMS si aplica.
3. Si ZK OK y registros = 0 → marcar en el reloj o revisar memoria del equipo.
4. Si pull OK y POST falla → `ready`, SSL, URL ingest, token idéntico.
5. Si POST OK y no hay filas en BD → DDL + `site_code` + duplicados (`INSERT IGNORE`).
