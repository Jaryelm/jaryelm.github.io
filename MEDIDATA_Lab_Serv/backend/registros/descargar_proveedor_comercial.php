<?php
header('Content-Type: application/json');
require_once '../../backend/bd/Conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        $sql = "SELECT archivo_constancia_comercial FROM proveedor_comercial WHERE id = :id";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $archivo_ruta = $_SERVER['DOCUMENT_ROOT'] . $result['archivo_constancia_comercial'];

            if (file_exists($archivo_ruta)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($archivo_ruta) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($archivo_ruta));
                readfile($archivo_ruta);
                exit;
            } else {
                echo json_encode(['error' => 'El archivo no está disponible.']);
            }
        } else {
            echo json_encode(['error' => 'No se encontró el registro.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'ID no proporcionado.']);
}
