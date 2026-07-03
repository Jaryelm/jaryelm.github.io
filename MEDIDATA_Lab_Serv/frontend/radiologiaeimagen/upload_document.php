<?php
require_once('../../backend/bd/Conexion.php'); // Incluir el archivo de conexión
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

try {
    // Verificar si la solicitud es POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido.");
    }

    // Validar que se haya enviado un archivo
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No se ha seleccionado ningún archivo o ocurrió un error al subirlo.");
    }

    // Obtener datos del formulario
    $dni = trim($_POST['dni']);
    $file = $_FILES['file'];

    // Validar DNI
    if (empty($dni)) {
        throw new Exception("El DNI es obligatorio.");
    }

    // Validar tipo y tamaño del archivo
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Tipo de archivo no permitido. Solo se permiten PDF, DOC y DOCX.");
    }

    if ($file['size'] > $maxFileSize) {
        throw new Exception("El archivo excede el tamaño máximo permitido (5 MB).");
    }

    // Datos del archivo
    $fileName = $file['name'];
    $fileContent = file_get_contents($file['tmp_name']);
    $fileType = $file['type'];

    // Usar la conexión desde Conexion.php
    global $connect;

    // Verificar si ya existe un archivo adjunto para este DNI
    $checkStmt = $connect->prepare("SELECT COUNT(*) AS total FROM radiologiaeimagen WHERE dni = :dni");
    $checkStmt->bindParam(':dni', $dni, PDO::PARAM_STR);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        // Enviar una advertencia clara al usuario
        echo json_encode([
            "success" => false,
            "type" => "warning",
            "message" => "Ya existe un archivo adjunto para este paciente. Este registro es único y no puede duplicarse. Si necesitas modificarlo, contacta al soporte TI de MEDIDATA."
        ]);
        exit;
    }

    // Insertar el archivo en la base de datos
    $stmt = $connect->prepare("INSERT INTO radiologiaeimagen (dni, nombre_archivo, archivo, tipo_documento, fecha_carga) VALUES (:dni, :nombre_archivo, :archivo, :tipo_documento, NOW())");
    $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
    $stmt->bindParam(':nombre_archivo', $fileName, PDO::PARAM_STR);
    $stmt->bindParam(':archivo', $fileContent, PDO::PARAM_LOB);
    $stmt->bindParam(':tipo_documento', $fileType, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "type" => "success", "message" => "Documento cargado exitosamente."]);
    } else {
        throw new Exception("Error al guardar el documento en la base de datos.");
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "type" => "error", "message" => $e->getMessage()]);
}
?>