<?php
require '../../backend/bd/Conexion.php';

echo '<option value="" disabled selected>Seleccione un puesto declarado...</option>';

try {
    $sql = "SELECT 
                mri.id,
                mdp.name AS name
            FROM 
                positions_details mri
                INNER JOIN medic9ue_medi_data.positions mdp ON mri.id_positions = mdp.id
            WHERE mri.deleted = false
            ORDER BY mdp.name ASC";
            
    $stmt = $connect_rrhh->prepare($sql);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
} catch (Exception $e) {
    error_log("Error in positions_declared.php: " . $e->getMessage());
}
?>
