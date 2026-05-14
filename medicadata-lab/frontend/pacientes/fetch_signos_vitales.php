<?php
require_once('../../backend/bd/Conexion.php');

try {
    $idpa = $_GET['idpa'];
    if (empty($idpa)) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    $sql = "SELECT * FROM signos_vitales WHERE idpa = :idpa ORDER BY created_at DESC";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($records);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
