# Sede → MySQL producción (3 pasos)

**cPanel** → Remote MySQL® → permitir host `201.190.11.6`

**Sede SSH:**

```bash
sudo cp /home/medicasa/MedicasaDATAUpdate2/scripts/biometric_agent_linux/medicasa-biometric-agent.env.sede /etc/medicasa-biometric-agent.env
sudo chmod 600 /etc/medicasa-biometric-agent.env
php /home/medicasa/MedicasaDATAUpdate2/scripts/biometric_agent_linux/run_once_db.php
```

**Cron:** ver `scripts/biometric_agent_linux/CRON_SEDE.txt`
