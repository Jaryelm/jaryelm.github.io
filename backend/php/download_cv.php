<?php
// Conexión a la base de datos
$servername = "162.241.123.41";
$username = "medic9ue_moisesc";
$password = "Mrecords%7";
$dbname = "medic9ue_postulaciones";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID de la URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar el registro en la base de datos para obtener el nombre original del archivo adjunto
$sql = "SELECT cv FROM aplica WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // El nombre del archivo en la base de datos
    $cvPath = $row['cv'];

    // Verificar si el campo cv contiene el nombre del archivo
    if (!empty($cvPath)) {
        // Generar la ruta completa del archivo basado en el nombre obtenido
        $filePath = "/home4/medic9ue/uploads/" . basename($cvPath);

        // Verificar si el archivo existe
        if (file_exists($filePath)) {
            // Configurar los encabezados para la descarga
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($cvPath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            // Leer el archivo y enviar el contenido al navegador
            readfile($filePath);
            exit;
        } else {
            echo "El archivo no existe.";
        }
    } else {
        echo "No se encontró el nombre del archivo en la base de datos.";
    }
} else {
    echo "Registro no encontrado.";
}

$stmt->close();
$conn->close();