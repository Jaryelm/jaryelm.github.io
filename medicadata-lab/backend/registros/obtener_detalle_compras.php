<?php
require_once '../../backend/bd/Conexion.php';

$id_compra = $_POST['id_compra'];

try {
    $sql = "SELECT * FROM detalle_compras WHERE id_compra = :id_compra";
    $stmt = $connect->prepare($sql);
    $stmt->execute([':id_compra' => $id_compra]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($detalles);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}