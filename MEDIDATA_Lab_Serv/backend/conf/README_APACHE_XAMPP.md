## Apache XAMPP (Windows) — secreto del agente biométrico

Tu `httpd.conf` ya carga **`mod_env`** (`LoadModule env_module ...`). Dos formas válidas:

### Opción A (recomendada en desarrollo): archivo `.env` local ignorado por Git

1. Copiar `backend/php/biometric_ingest_secret.local.env.example` → `backend/php/biometric_ingest_secret.local.env`
2. Poner tu token en una sola línea `MEDIDATA_AGENT_INGEST_SECRET=...`
3. Abrir de nuevo `http://localhost/MedicasaDATAUpdate2/backend/api/agent_biometric_ingest.php` → debe mostrar `ready: true`.

No hace falta tocar `httpd.conf` si usás solo la opción A.

### Opción B (Apache global): IncludeOptional + conf

1. Copiar `backend/conf/apache_medidata_agent_secret.conf.example` → `backend/conf/apache_medidata_agent_secret.conf`
2. Sustituir el placeholder del `SetEnv` por tu mismo hex.
3. Al final de `C:\xampp\apache\conf\httpd.conf` agregar:

   ```apache
   IncludeOptional "C:/xampp/htdocs/MedicasaDATAUpdate2/backend/conf/apache_medidata_agent_secret.conf"
   ```

4. Reiniciar Apache desde el panel XAMPP.
