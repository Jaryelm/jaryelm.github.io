<?php
require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/registros/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo '<option value="" disabled selected>Error: BD RRHH no disponible</option>';
    exit;
}

echo '<option value="" disabled selected>Seleccione un departamento...</option>';

try {
    $stmt = $pdo->prepare("SELECT id, name, head_departament FROM departaments WHERE status = 'Activo' ORDER BY name ASC");
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<option value="' . (int) $row['id'] . '" data-boss="' . htmlspecialchars($row['head_departament']) . '">' . htmlspecialchars($row['name']) . '</option>';
    }
} catch (Throwable $e) {
    error_log('cat_departaments.php: ' . $e->getMessage());
}
?>