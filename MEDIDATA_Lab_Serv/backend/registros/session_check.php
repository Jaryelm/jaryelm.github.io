<?php
// Iniciar la sesión
session_start();

// Establecer zona horaria de Honduras
date_default_timezone_set('America/Tegucigalpa');

if (!function_exists('medidata_session_json_api_script')) {
    /**
     * Scripts que deben responder JSON (fetch / DataTables) si la BD falla,
     * en lugar de redirigir al login con HTML.
     */
    function medidata_session_json_api_script(): ?string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = strtolower(basename((string) $script));
        $allowed = [
            'registrar_partida_manual.php',
            'get_partidas_manuales.php',
            'fetch_collaborators.php',
            'fetch_postulaciones_aplica.php',
            'fetch_biometric_marcas.php',
            'fetch_rrhh_vacantes_abiertas.php',
            'rrhh_aplica_incorporar.php',
            'rrhh_aplica_descartar.php',
            'rrhh_aplica_reasignar.php',
            'rrhh_candidato_estado.php',
        ];
        return in_array($base, $allowed, true) ? $base : null;
    }
}

if (!function_exists('medidata_session_emit_json_db_unavailable')) {
    function medidata_session_emit_json_db_unavailable(?string $apiScript): bool
    {
        if ($apiScript === null || headers_sent()) {
            return false;
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(503);
        $msg = 'Base de datos temporalmente no disponible. Intente de nuevo en un momento.';
        if ($apiScript === 'get_partidas_manuales.php') {
            echo json_encode([
                'draw' => intval($_GET['draw'] ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $msg,
            ]);
        } elseif ($apiScript === 'fetch_collaborators.php') {
            echo json_encode(['error' => $msg]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $msg,
            ]);
        }
        return true;
    }
}

// Conexión con ruta absoluta desde este archivo (evita $connect indefinido si el CWD del servidor no es el esperado)
try {
    require_once __DIR__ . '/../bd/Conexion.php';
} catch (Throwable $e) {
    error_log('session_check Conexion: ' . $e->getMessage());
    if (medidata_session_emit_json_db_unavailable(medidata_session_json_api_script())) {
        exit();
    }
    $msgLower = strtolower($e->getMessage());
    if (strpos($msgLower, 'too many connections') !== false || strpos($msgLower, '[1040]') !== false) {
        $_SESSION['errMsg'] = 'El servidor de datos está al límite de conexiones. Espere unos segundos e intente de nuevo.';
    } else {
        $_SESSION['errMsg'] = 'No se pudo conectar a la base de datos. Contacte a Soporte TI.';
    }
    header('Location: ../../frontend/login.php');
    exit();
}

if (!isset($connect) || !($connect instanceof PDO)) {
    error_log('session_check: $connect no es PDO tras Conexion.php');
    if (medidata_session_emit_json_db_unavailable(medidata_session_json_api_script())) {
        exit();
    }
    $_SESSION['errMsg'] = 'Conexión a base de datos no disponible.';
    header('Location: ../../frontend/login.php');
    exit();
}

try {
    // Verificar autenticación de usuario
    if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
        cerrarSesion();
    }

    $userId = (int) $_SESSION['id'];

    // Inactividad: 5 horas sin uso (usa marca en sesión; evita lectura/escritura en cada request)
    $inactivityLimit = 5 * 60 * 60;
    // En hosting compartido: no actualizar last_activity en cada página (reduce conexiones MySQL)
    $dbActivitySyncInterval = 15 * 60;

    $now = time();

    if (!isset($_SESSION['last_activity_ts'])) {
        $_SESSION['last_activity_ts'] = $now;
    }

    if (($now - (int) $_SESSION['last_activity_ts']) > $inactivityLimit) {
        cerrarSesion();
    }

    $_SESSION['last_activity_ts'] = $now;

    $lastDbSync = (int) ($_SESSION['last_activity_db_sync_ts'] ?? 0);
    if ($lastDbSync === 0 || ($now - $lastDbSync) >= $dbActivitySyncInterval) {
        $localTime = date('Y-m-d H:i:s');
        $updateStmt = $connect->prepare('UPDATE users SET last_activity = ? WHERE id = ?');
        $updateStmt->execute([$localTime, $userId]);
        $_SESSION['last_activity_db_sync_ts'] = $now;
    }

    $id = $_SESSION['id'] ?? null;
    $name = $_SESSION['name'] ?? 'Invitado';

    // Liberar candado de sesión (otras pestañas del mismo usuario no quedan bloqueadas)
    if (function_exists('session_write_close')) {
        session_write_close();
    }

} catch (PDOException $e) {
    error_log('session_check PDO: ' . $e->getMessage());
    if (medidata_session_emit_json_db_unavailable(medidata_session_json_api_script())) {
        exit();
    }
    $_SESSION['errMsg'] = 'Error en la base de datos. Por favor contacte a Soporte TI.';
    header('Location: ../../frontend/login.php');
    exit();
}

function cerrarSesion() {
    // Limpiar la sesión completamente
    $_SESSION = [];
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/'); // Borra cookie de sesión

    echo '<script>
        localStorage.clear();
        sessionStorage.clear();
        window.location.href = "../../frontend/login.php";
    </script>';
    exit();
}