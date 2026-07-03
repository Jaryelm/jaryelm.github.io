<?php
require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/registros/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo '<option value="" disabled selected>Error de conexión</option>';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, level_name, position_category, min_salary, max_salary FROM salary_levels WHERE deleted = 0 ORDER BY level_name ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<option value="" disabled selected>Seleccione un nivel salarial...</option>';
    foreach ($rows as $row) {
        $label = htmlspecialchars($row['level_name'] . ' - ' . $row['position_category'] . ' (L. ' . number_format($row['min_salary'], 2) . ' - L. ' . number_format($row['max_salary'], 2) . ')');
        echo '<option value="' . (int)$row['id'] . '">' . $label . '</option>';
    }
} catch (Throwable $e) {
    echo '<option value="" disabled>Error al cargar niveles</option>';
}
?>