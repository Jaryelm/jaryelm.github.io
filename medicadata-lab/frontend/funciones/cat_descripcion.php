<?php
require '../../backend/bd/Conexion.php';

// Realizamos la consulta de los nombres de productos (descripción)
$stmt = $connect->prepare('SELECT DISTINCT descripcion FROM detalle_compras ORDER BY descripcion ASC');
$stmt->execute();

$descripciones = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $descripciones[] = $row['descripcion'];
}

// Devolvemos los datos como un JSON para ser utilizado en el frontend
echo json_encode($descripciones);