# Informe técnico — Integración reloj biométrico ZKTeco MB360 con MediDATA

**Hospital Medicasa · MediDATA producción**  
**Fecha del informe:** 25 de mayo de 2026  
**Versión:** 1.0  
**Propósito:** Documento para revisión detallada (exportar a PDF) y resolución con asistencia externa (p. ej. Claude).

---

## 1. Resumen ejecutivo

| Elemento | Estado |
|----------|--------|
| Reloj MB360 en LAN (`192.168.1.91`) | Operativo |
| Servidor sede (`medicasa`, IP pública `201.190.11.6`) — lectura ZK | Operativo (~689 marcas) |
| Vista `relojbio.php` en producción leyendo BD | Operativo (~608 registros históricos) |
| Sincronización automática sede → producción vía **HTTP POST** | **Bloqueada** (HTTP 409, WAF anti-bot) |
| Sincronización vía **MySQL remoto** (`run_once_db.php`) | **Pendiente de configuración** — error actual: variables `MEDIDATA_AGENT_DB_*` no cargadas en `/etc/medicasa-biometric-agent.env` |

**Conclusión:** El problema no es el reloj ni el código de lectura ZK. El bloqueo principal es de **infraestructura en hosting compartido** (firewall que rechaza POST del agente). La alternativa profesional sin soporte del proveedor es **insertar directo en MySQL** desde sede. El último error visible en pruebas (06:04:43) se debe a que el archivo de entorno del servidor **no incluye aún** las líneas de base de datos, aunque el repositorio ya las tiene en `medicasa-biometric-agent.env.sede`.

---

## 2. Objetivo del proyecto

1. Leer marcaciones del reloj **ZKTeco MB360** en la red local de la sede.
2. Centralizarlas en la base de datos MySQL de **producción** (`medic9ue_medi_data`, tabla `biometric_marcas`).
3. Mostrarlas en **MediDATA** (`https://medidata.medicasa.hn/frontend/recursos/relojbio.php`).
4. Mantener en **XAMPP local** (cuando la PC está en la misma LAN) la lectura directa al reloj sin depender del agente.

---

## 3. Arquitectura

```
┌─────────────────┐     UDP 4370      ┌──────────────────┐
│  ZKTeco MB360   │ ◄──────────────── │ Servidor sede    │
│ 192.168.1.91    │                   │ medicasa         │
└─────────────────┘                   │ 192.168.1.102    │
                                      │ IP pública       │
                                      │ 201.190.11.6     │
                                      └────────┬─────────┘
                                               │
                    ┌──────────────────────────┼──────────────────────────┐
                    │ Ruta A (bloqueada)       │ Ruta B (recomendada)      │
                    ▼                          ▼                           │
         POST HTTPS agent_biometric_ingest.php    MySQL remoto :3306        │
                    │                          │                           │
                    ▼                          ▼                           │
         ┌──────────────────────────────────────────────────┐            │
         │ Hosting cPanel — medidata.medicasa.hn               │            │
         │ IP web ~162.241.123.45 · MySQL host 162.241.123.41  │            │
         │ BD: medic9ue_medi_data · tabla biometric_marcas   │            │
         └──────────────────────────────────────────────────┘            │
                    │                                                       │
                    ▼                                                       │
         relojbio.php (lee BD; no alcanza IP privada del reloj) ◄──────────┘
```

**Nota de red:** El servidor de producción **no puede** abrir UDP al reloj `192.168.1.91` (red privada). Eso es esperado, no un defecto.

---

## 4. Inventario de componentes

| Componente | Ruta / ubicación | Función |
|------------|------------------|---------|
| Config reloj | `backend/php/reloj_biometrico_config.php` | IP, puerto, variables de entorno |
| Pull ZK | `backend/php/reloj_biometrico_mb360.php` | Lectura UDP librería jmrashed |
| API ingest (HTTP) | `backend/api/agent_biometric_ingest.php` | POST JSON + token Bearer |
| Agente HTTP | `scripts/biometric_agent_linux/run_once.php` | Cron original — falla con 409 |
| Agente MySQL | `scripts/biometric_agent_linux/run_once_db.php` | Cron recomendado |
| Push BD | `backend/php/biometric_agent_push.php` | INSERT IGNORE en `biometric_marcas` |
| Env sede listo | `scripts/biometric_agent_linux/medicasa-biometric-agent.env.sede` | Copiar a `/etc/...` |
| Env en servidor | `/etc/medicasa-biometric-agent.env` | Variables del cron |
| Vista | `frontend/recursos/relojbio.php` | Tabla + DataTables desde BD en producción |
| DDL referencia | `backend/scripts/DDL_biometric_marcas.sql` | Esquema con `created_at` |
| Log cron | `/var/log/medicasa-biometric-agent.log` | Salida cada minuto |
| Conexión prod | `backend/bd/Conexion.php` | Host `162.241.123.41`, usuario `medic9ue_moisesc` |

---

## 5. Cronología de hallazgos

### 5.1 Fase inicial — conectividad reloj

- IP del reloj actualizada de `192.168.1.201` a **`192.168.1.91`**.
- Modo **ADMS** en el reloj apuntaba a otra IP; se corrigió en menú del equipo.
- Diagnóstico en sede: `exit_zk=0`, **689 marcas** leídas correctamente.

### 5.2 Fase HTTP — agente hacia producción

- **GET** `https://medidata.medicasa.hn/backend/api/agent_biometric_ingest.php` → respuesta JSON `"ready": true` (token configurado en Apache).
- **POST** desde sede (`curl` y `run_once.php`) → **HTTP 409 Conflict**.
- Cuerpo de respuesta (no JSON):

```html
<script>document.cookie = "humans_21909=1"; document.location.reload(true)</script>
```

- Interpretación: capa **anti-bot / WAF** del hosting (cookie `humans_*`, típico de protección compartida). Un script PHP/cron **no ejecuta JavaScript** ni puede completar el desafío.
- Intento de mitigación con `backend/api/.htaccess` (ModSecurity off) → **insuficiente** sin acceso WHM/root del hosting.
- Cron cada minuto seguía ejecutando `run_once.php` → log de ~1,5 MB repitiendo 689 marcas + error 409.

### 5.3 Fase vista producción

- `relojbio.php` en producción muestra **608 marcas** desde `biometric_marcas` (datos de ingesta anterior o prueba local).
- Diagnóstico ZK en producción falla al IP privada → **normal**.

### 5.4 Fase MySQL remoto — estado actual

- Se implementó `run_once_db.php` para evitar HTTP.
- Prueba manual **25-may-2026 06:04:43**:
  - OK: `Marcas leídas del MB360: 689`
  - ERROR: `Faltan MEDIDATA_AGENT_DB_HOST o MEDIDATA_AGENT_DB_USER en el entorno del agente`
- Causa: `/etc/medicasa-biometric-agent.env` en sede **no contiene** (o no exporta) las variables `MEDIDATA_AGENT_DB_*`. El usuario ejecutó `set -a; . /etc/...` pero ese archivo sigue siendo la versión antigua sin bloque MySQL.
- Consulta phpMyAdmin con `MAX(created_at)` → error **#1054** porque la tabla en producción puede haberse creado **sin** columna `created_at`; usar **`marca_datetime`**.

---

## 6. Análisis de causa raíz

### Problema 1 — Bloqueo HTTP (crítico para `run_once.php`)

| Pregunta | Respuesta |
|----------|-----------|
| ¿Falla el token? | No — GET `ready:true` lo confirma. |
| ¿Falla el pull ZK? | No — 689 marcas en sede. |
| ¿Qué bloquea? | WAF/anti-bot del hosting en POST. |
| ¿Se puede arreglar sin admin hosting? | No de forma fiable vía `.htaccess` usuario. |
| Alternativa | MySQL remoto desde IP sede. |

### Problema 2 — Variables BD no en `/etc` (crítico para `run_once_db.php`)

| Pregunta | Respuesta |
|----------|-----------|
| ¿Qué dice el error? | Faltan `MEDIDATA_AGENT_DB_HOST` o `MEDIDATA_AGENT_DB_USER`. |
| ¿Por qué? | `/etc/medicasa-biometric-agent.env` no actualizado con el bloque MySQL. |
| Solución inmediata | Copiar `medicasa-biometric-agent.env.sede` → `/etc/...` (comandos en sección 8). |
| Mejora en código | `run_once_db.php` ahora carga también el `.sede` del repo si falta en `/etc` (subir `biometric_agent_env_bootstrap.php`). |

### Problema 3 — Remote MySQL en cPanel (requisito de red)

| Pregunta | Respuesta |
|----------|-----------|
| ¿Qué es? | Lista blanca de IPs que pueden conectar al MySQL del hosting. |
| ¿Quién lo configura? | Usuario cPanel **medic9ue** (no requiere ticket soporte). |
| IP a permitir | **`201.190.11.6`** (IP pública del servidor sede). |
| Sin esto | Conexión rechazada aunque usuario/clave sean correctos. |

---

## 7. Códigos de salida del agente

| Script | exit | Significado |
|--------|------|-------------|
| `run_once.php` | 5 | HTTP no 2xx (típico 409 WAF) |
| `run_once_db.php` | 7 | No hay credenciales BD en entorno / fallo conexión |
| `run_once_db.php` | 8 | Error al insertar |
| `run_once_db.php` | 0 | Éxito — JSON con `inserted` |

---

## 8. Procedimiento de corrección (orden exacto)

### Paso A — Actualizar env en sede (corrige error de captura 06:04)

```bash
sudo cp /home/medicasa/MedicasaDATAUpdate2/scripts/biometric_agent_linux/medicasa-biometric-agent.env.sede /etc/medicasa-biometric-agent.env
sudo chmod 600 /etc/medicasa-biometric-agent.env
grep MEDIDATA_AGENT_DB /etc/medicasa-biometric-agent.env
```

Debe mostrar `MEDIDATA_AGENT_DB_HOST`, `USER`, `PASS`, `NAME`.

### Paso B — cPanel Remote MySQL®

1. Iniciar sesión cPanel cuenta **medic9ue**.
2. **Bases de datos** → **Remote MySQL®**.
3. Añadir host: `201.190.11.6`.
4. Guardar.

### Paso C — Subir archivos nuevos al repo en sede

- `backend/php/biometric_agent_env_bootstrap.php`
- `backend/php/biometric_agent_push.php`
- `scripts/biometric_agent_linux/run_once_db.php` (versión con bootstrap)

### Paso D — Prueba

```bash
php /home/medicasa/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once_db.php
echo exit=$?
```

**Éxito esperado:**

```
2026-05-25 HH:MM:SS Marcas leídas del MB360: 689
{"success":true,"site_code":"Sucursal_1","received":689,"inserted":...,"skipped_or_duplicate":...}
exit=0
```

### Paso E — Cron

```bash
sudo crontab -e
```

Línea debe usar **`run_once_db.php`** (no `run_once.php`).

### Paso F — Verificar en phpMyAdmin

```sql
SELECT COUNT(*) AS total, MAX(marca_datetime) AS ultima
FROM biometric_marcas
WHERE site_code = 'Sucursal_1';
```

---

## 9. Evidencias de pruebas (referencia)

| Prueba | Resultado |
|--------|-----------|
| `run_once.php` / curl POST | HTTP **409**, HTML `humans_21909` |
| `run_once_db.php` tras crontab | Pull OK, MySQL env **faltante** |
| `relojbio.php` producción | ~608 filas desde BD |
| Cron `* * * * *` | Activo; debe apuntar a `run_once_db.php` |
| Log `/var/log/medicasa-biometric-agent.log` | Repetía 409 cada minuto con `run_once.php` |

---

## 10. Variables de entorno (referencia)

| Variable | Valor típico sede |
|----------|-------------------|
| `MEDIDATA_AGENT_REPO_ROOT` | `/home/medicasa/MedicasaDATAUpdate2` |
| `MEDIDATA_AGENT_SITE_CODE` | `Sucursal_1` |
| `MEDIDATA_RELOJ_IP` | `192.168.1.91` |
| `MEDIDATA_RELOJ_PORT` | `4370` |
| `MEDIDATA_AGENT_DB_HOST` | `162.241.123.41` |
| `MEDIDATA_AGENT_DB_NAME` | `medic9ue_medi_data` |
| `MEDIDATA_AGENT_DB_USER` | `medic9ue_moisesc` |
| `MEDIDATA_AGENT_DB_PASS` | (misma que `Conexion.php` producción) |
| `MEDIDATA_AGENT_INGEST_SECRET` | Token largo (solo si se reactiva HTTP) |

---

## 11. Preguntas para asistencia externa (Claude / otro)

Copiar este bloque al chat de apoyo:

1. Tras copiar `medicasa-biometric-agent.env.sede` a `/etc/`, ¿`run_once_db.php` sigue fallando con exit 7 u otro mensaje? (pegar salida completa).
2. ¿Remote MySQL en cPanel ya tiene `201.190.11.6`?
3. Si exit 7 persiste: ¿error es "Access denied" o "Connection refused" o "timed out"?
4. ¿El hosting bloquea también conexiones MySQL salientes desde IP `201.190.11.6` hacia `162.241.123.41:3306`?

---

## 12. Exportar este documento a PDF

**Opción A — Navegador:** Abrir este `.md` en VS Code/Cursor → extensión "Markdown PDF" o pegar en [https://md2pdf.netlify.app](https://md2pdf.netlify.app) → Exportar PDF.

**Opción B — Word:** Abrir en Word (arrastrar el archivo) → Archivo → Guardar como PDF.

**Opción C — Pandoc (si está instalado):**

```bash
pandoc INFORME_INTEGRACION_RELOJ_BIOMETRICO_MEDIDATA.md -o INFORME_RELOJ_BIOMETRICO.pdf
```

---

## 13. Checklist final

- [ ] `/etc/medicasa-biometric-agent.env` contiene `MEDIDATA_AGENT_DB_*`
- [ ] cPanel Remote MySQL: `201.190.11.6`
- [ ] `run_once_db.php` → `exit=0`
- [ ] Cron usa `run_once_db.php`
- [ ] `COUNT(*)` en `biometric_marcas` aumenta tras nuevas marcas
- [ ] `relojbio.php` muestra datos actualizados

---

*Documento generado para MedicasaDATAUpdate2 — integración biométrica MB360 / MediDATA.*
