<?php
require '../../backend/bd/Conexion.php';
require_once '../../backend/registros/rrhh_guard.php';

echo '<option value="0">Seleccione una Plaza de Trabajo</option>';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    return;
}

try {
    $sql = "SELECT vp.id, CONCAT(vp.vacant_name, ' (', COALESCE(p.name, 'Sin puesto'), ')') AS label
            FROM vacant_positions vp
            LEFT JOIN positions_details pd ON vp.id_position = pd.id
            LEFT JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id
            WHERE vp.deleted = 0 AND vp.end_date >= CURDATE()
            ORDER BY vp.vacant_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<option value="' . (int) $row['id'] . '">' . htmlspecialchars($row['label']) . '</option>';
    }
} catch (Throwable $e) {
    error_log('cat_vacants_positions.php: ' . $e->getMessage());
}
