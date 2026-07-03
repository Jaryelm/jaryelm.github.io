<?php
require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/registros/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo '<option value="" disabled selected>Error de conexión</option>';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM schedules WHERE deleted = 0 ORDER BY name ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<option value="" disabled selected>Seleccione un horario...</option>';
    foreach ($rows as $row) {
        echo '<option value="' . (int)$row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
} catch (Throwable $e) {
    echo '<option value="" disabled>Error al cargar horarios</option>';
}
?>