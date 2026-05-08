# MEDIDATA — Laboratorio local (XAMPP)

Paquete para **Joan Gallegos (NEXAR)**. Plan de trabajo: [jaryelm.github.io](https://jaryelm.github.io).

## Estructura

| Carpeta / archivo | Uso |
|-------------------|-----|
| **`BD/`** | Scripts SQL para MySQL. |
| **`medicadata-lab/`** | Aplicación PHP lista para `htdocs` (MySQL local + Orthanc de laboratorio). |

## Requisitos

- Windows con **XAMPP** (Apache + MySQL/MariaDB + **PHP 8.x**).
- Git y un editor (VS Code, PhpStorm, etc.).

## 1. Base de datos

1. Inicia **Apache** y **MySQL** en XAMPP.
2. Abre **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Importa **`BD/medic9ue_medi_data.sql`** en la base **`medic9ue_medi_data`** (crear la base vacía primero si hace falta).
4. Opcional — reclutamiento / CV: importa **`BD/medic9ue_postulaciones_esqueleto.sql`**.

Si el `.sql` es muy grande, sube `upload_max_filesize` y `post_max_size` en `php.ini` y reinicia Apache.

**Actualizar el dump** (en la máquina que tiene la BD):

```bat
"C:\xampp\mysql\bin\mysqldump.exe" -u root --single-transaction --routines --triggers medic9ue_medi_data > "Joan Dev\BD\medic9ue_medi_data.sql"
```

(Ajusta usuario y contraseña de MySQL si no usas `root` sin clave.)

## 2. Código

1. Copia la carpeta **`medicadata-lab`** dentro de `C:\xampp\htdocs\` (puedes renombrarla; la URL cambiará con el nombre de la carpeta).
2. Entra en **`http://localhost/medicadata-lab/`** (o el nombre que hayas puesto).
3. Debe cargar el **login** (`frontend/login.php` vía `index.php`).

### Ajustes si algo falla

- **MySQL:** `medicadata-lab/backend/bd/Conexion.php` — host `localhost`, usuario `root`, base `medic9ue_medi_data`. Si tu `root` tiene contraseña, edítala ahí.
- **Orthanc / visor DICOM:** `medicadata-lab/backend/bd/orthanc_laboratorio.config.php` (por defecto `http://127.0.0.1:8042`).
- **CV de postulantes:** archivos en `medicadata-lab/uploads_postulaciones/`.

## 3. Dónde trabajar (RH y Enfermería)

- **`medicadata-lab/frontend/recursos/`** — Recursos humanos (reclutamiento, personal, áreas, etc.).
- **`medicadata-lab/frontend/enfermeria/`** — Módulo enfermería.
- **`medicadata-lab/frontend/servicioalcliente/`** y **`medicadata-lab/frontend/pacientes/`** — flujos clínico/expediente según pantallas vinculadas.

## Checklist rápido

- [ ] MySQL con `medic9ue_medi_data` importada  
- [ ] Carpeta `medicadata-lab` en `htdocs`  
- [ ] Login abre sin error de conexión  
- [ ] Orthanc local configurado si pruebas PACS (por el momento esto no es necesario)

---
*MEDIDATA by NEXAR — entorno local únicamente.*
