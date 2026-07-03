<?php
require_once('../../backend/bd/Conexion.php'); // Incluir el archivo de conexión
session_start();
header('Content-Type: application/json');

date_default_timezone_set('America/Tegucigalpa');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    if (empty($data['study_id']) || empty($data['dose_value']) || 
        empty($data['dose_unit']) || empty($data['exposure_time'])) {
        throw new Exception("Todos los campos son requeridos");
    }

    $comments = $data['comments'] ?? '';
    $technician_id = $_SESSION['id'] ?? null;
    $local_time = date('Y-m-d H:i:s');

    // Obtener el nombre del usuario autenticado
    $stmt = $connect->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$technician_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }
    $user_name = $user['name'];

    // Insertar registro de dosis
    $stmt = $connect->prepare("
        INSERT INTO radiation_dose (
            study_id,
            dose_value,
            dose_unit,
            exposure_time,
            comments,
            technician_id,
            user_name,
            recorded_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['study_id'],
        $data['dose_value'],
        $data['dose_unit'],
        $data['exposure_time'],
        $comments,
        $technician_id,
        $user_name,
        $local_time
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Registro de dosis guardado correctamente'
    ]);

    actualizarEstadoEstudio($connect, $data['study_id']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// --- FUNCIÓN CENTRALIZADA PARA ACTUALIZAR ESTADO ---
if (!function_exists('actualizarEstadoEstudio')) {
function actualizarEstadoEstudio($connect, $study_id) {
    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM quality_control WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $qc = $stmt->fetch(PDO::FETCH_ASSOC);
    $tieneQC = $qc && $qc['total'] > 0;

    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM radiation_dose WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $dose = $stmt->fetch(PDO::FETCH_ASSOC);
    $tieneDosis = $dose && $dose['total'] > 0;

    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM study_repeats WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $repeat = $stmt->fetch(PDO::FETCH_ASSOC);
    $hayRepeticion = $repeat && $repeat['total'] > 0;

    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM incidents WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);
    $hayIncidencia = $incident && $incident['total'] > 0;

    $stmt = $connect->prepare("SELECT status FROM worklist WHERE id = ?");
    $stmt->execute([$study_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadoActual = $row ? $row['status'] : null;

    if ($estadoActual === 'pending') {
        $nuevoEstado = 'in_progress';
    } else if ($estadoActual === 'in_progress' || $estadoActual === 'completed') {
        if ($tieneQC && $tieneDosis && !$hayRepeticion && !$hayIncidencia) {
            $nuevoEstado = 'completed';
        } else {
            $nuevoEstado = 'in_progress';
        }
    } else {
        return;
    }

    if ($nuevoEstado !== $estadoActual) {
        $stmt = $connect->prepare("UPDATE worklist SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nuevoEstado, $study_id]);
    }
}
}
?> 