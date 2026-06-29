<?php
header('Content-Type: application/json');
require_once '../../backend/bd/Conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        $stmt = $connect->prepare("SELECT archivo_constancia FROM proveedor_data WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['archivo_constancia']) {
            $archivoRuta = dirname(__DIR__, 2) . '/uploads/' . basename($result['archivo_constancia']);
            
            if (file_exists($archivoRuta)) {
                // Configura los headers para la descarga
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf'); // Cambia el tipo si es necesario
                header('Content-Disposition: attachment; filename="' . basename($result['archivo_constancia']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($archivoRuta));
                flush();
                
                readfile($archivoRuta); // Envía el archivo al navegador
                exit;
            } else {
                echo json_encode(['error' => 'El archivo no existe.']);
                exit;
            }
        } else {
            echo json_encode(['error' => 'El archivo no está disponible.']);
            exit;
        }
    } else {
        echo json_encode(['error' => 'ID inválido.']);
        exit;
    }
}
