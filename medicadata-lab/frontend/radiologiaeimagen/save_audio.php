<?php
require_once('../../backend/bd/Conexion.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio']) && isset($_POST['study_id'])) {
    $study_id = $_POST['study_id'];
    $audio = $_FILES['audio'];
    $targetDir = '../../backend/audios/';
    $fileName = $study_id . '_' . time() . '.webm';
    $filePath = $targetDir . $fileName;
    move_uploaded_file($audio['tmp_name'], $filePath);

    // Actualizar la ruta en la base de datos
    $stmt = $connect->prepare("UPDATE radiology_reports SET audio_url = ? WHERE study_id = ?");
    $stmt->execute([$fileName, $study_id]);

    echo json_encode(['success' => true, 'file' => $fileName]);
} else {
    echo json_encode(['success' => false]);
}
?>
