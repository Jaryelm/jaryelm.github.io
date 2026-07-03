<?php
require '../../backend/bd/Conexion.php';
require_once '../../backend/registros/rrhh_guard.php';

echo '<option value="" disabled selected>Seleccione un puesto declarado...</option>';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    return;
}

try {
    $sql = "SELECT pd.id, p.name AS name
            FROM positions_details pd
            INNER JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id
            WHERE pd.deleted = 0
            ORDER BY p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<option value="' . (int) $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
} catch (Throwable $e) {
    error_log('positions_declared.php: ' . $e->getMessage());
}
