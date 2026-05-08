<?php
// Iniciar la sesión
session_start();

// Establecer zona horaria de Honduras
date_default_timezone_set('America/Tegucigalpa');

// Conexión con ruta absoluta desde este archivo (evita $connect indefinido si el CWD del servidor no es el esperado)
try {
    require_once __DIR__ . '/../bd/Conexion.php';
} catch (Throwable $e) {
    error_log('session_check Conexion: ' . $e->getMessage());
    $_SESSION['errMsg'] = 'No se pudo conectar a la base de datos. Contacte a Soporte TI.';
    header('Location: ../../frontend/login.php');
    exit();
}

if (!isset($connect) || !($connect instanceof PDO)) {
    error_log('session_check: $connect no es PDO tras Conexion.php');
    $_SESSION['errMsg'] = 'Conexión a base de datos no disponible.';
    header('Location: ../../frontend/login.php');
    exit();
}

try {
    // Verificar autenticación de usuario
    if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
        cerrarSesion();
    }

    // Obtener el ID del usuario actual
    $userId = $_SESSION['id'];

    // Consultar la última actividad del usuario desde la base de datos
    $stmt = $connect->prepare("SELECT last_activity FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Establecer límite de inactividad a una hora (3600 segundos)
    $inactivityLimit = 60 * 60; // 1 hora en segundos

    if ($user && isset($user['last_activity'])) {
        $lastActivity = strtotime($user['last_activity']);
        $currentTime = time();

        // Verificar inactividad y cerrar sesión si se excede el límite
        if (($currentTime - $lastActivity) > $inactivityLimit) {
            cerrarSesion();
        }
    }

    // Generar la hora local en formato MySQL (YYYY-MM-DD HH:MM:SS)
    $localTime = date('Y-m-d H:i:s');

    // Actualizar el tiempo de última actividad en la base de datos
    $updateStmt = $connect->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
    $updateStmt->execute([$localTime, $userId]);

    // Variables de usuario para usar en páginas internas
    $id = $_SESSION['id'] ?? null;
    $name = $_SESSION['name'] ?? 'Invitado';

} catch (PDOException $e) {
    // Manejar errores de la base de datos
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