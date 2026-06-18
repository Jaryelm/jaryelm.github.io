<?php
require_once('../../backend/bd/Conexion.php');
session_start();
header('Content-Type: application/json');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

try {
    // Verificar si la solicitud es POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Método no permitido
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Leer los datos JSON del cuerpo de la solicitud
    $data = json_decode(file_get_contents('php://input'), true);

    // Obtener los datos del formulario
    $study_id = $data['study_id'] ?? null;
    $technician_id = $_SESSION['id'] ?? null;
    $image_quality = $data['image_quality'] ?? null;
    $positioning_quality = $data['positioning_quality'] ?? null;
    $needs_repeat = isset($data['needs_repeat']) ? (int)$data['needs_repeat'] : 0;
    $comments = $data['comments'] ?? '';

    // Validar los datos
    if (
        empty($study_id) ||
        empty($technician_id) ||
        empty($image_quality) ||
        empty($positioning_quality) ||
        !in_array($image_quality, ['excellent', 'acceptable', 'poor', 'unacceptable']) ||
        !in_array($positioning_quality, ['correct', 'suboptimal', 'incorrect'])
    ) {
        http_response_code(400); // Petición incorrecta
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos']);
        exit;
    }

    // Obtener el nombre del usuario autenticado
    $stmt = $connect->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$technician_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404); // Usuario no encontrado
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }

    $user_name = $user['name']; // Nombre del usuario

    // Obtener la hora local de Tegucigalpa
    $local_time = date('Y-m-d H:i:s'); // Formato: Año-Mes-Día Hora:Minutos:Segundos

    // Verificar si ya existe un registro para este study_id
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM quality_control WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        // Actualizar el registro existente
        $updateStmt = $connect->prepare("
            UPDATE quality_control 
            SET 
                technician_id = ?, 
                image_quality = ?, 
                positioning_quality = ?, 
                needs_repeat = ?, 
                comments = ?, 
                created_at = ?,
                user_name = ?
            WHERE study_id = ?
        ");
        $updateStmt->execute([
            $technician_id,
            $image_quality,
            $positioning_quality,
            $needs_repeat,
            $comments,
            $local_time, // Usar la hora local de Tegucigalpa
            $user_name,  // Guardar el nombre del usuario
            $study_id
        ]);

        // Después de guardar o actualizar el control de calidad exitosamente
        actualizarEstadoEstudio($connect, $study_id);

        echo json_encode(['success' => true, 'message' => 'Control de calidad actualizado correctamente']);
    } else {
        // Insertar un nuevo registro
        $insertStmt = $connect->prepare("
            INSERT INTO quality_control (
                study_id,
                technician_id,
                image_quality,
                positioning_quality,
                needs_repeat,
                comments,
                created_at,
                user_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $study_id,
            $technician_id,
            $image_quality,
            $positioning_quality,
            $needs_repeat,
            $comments,
            $local_time, // Usar la hora local de Tegucigalpa
            $user_name   // Guardar el nombre del usuario
        ]);

        // Después de guardar o actualizar el control de calidad exitosamente
        actualizarEstadoEstudio($connect, $study_id);

        echo json_encode(['success' => true, 'message' => 'Control de calidad guardado correctamente']);
    }
} catch (Exception $e) {
    // Manejar errores
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'message' => 'Error al guardar el control de calidad']);
}

// --- FUNCIÓN CENTRALIZADA PARA ACTUALIZAR ESTADO ---
function actualizarEstadoEstudio($connect, $study_id) {
    // Obtener si existe control de calidad
    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM quality_control WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $qc = $stmt->fetch(PDO::FETCH_ASSOC);
    $tieneQC = $qc && $qc['total'] > 0;

    // Obtener si existe dosis
    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM radiation_dose WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $dose = $stmt->fetch(PDO::FETCH_ASSOC);
    $tieneDosis = $dose && $dose['total'] > 0;

    // Verificar si hay repetición pendiente
    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM study_repeats WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $repeat = $stmt->fetch(PDO::FETCH_ASSOC);
    $hayRepeticion = $repeat && $repeat['total'] > 0;

    // Verificar si hay incidencia abierta
    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM incidents WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);
    $hayIncidencia = $incident && $incident['total'] > 0;

    // Obtener estado actual
    $stmt = $connect->prepare("SELECT status FROM worklist WHERE id = ?");
    $stmt->execute([$study_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadoActual = $row ? $row['status'] : null;

    // Lógica de transición de estados
    if ($estadoActual === 'pending') {
        // Si se hace cualquier acción relevante, pasa a in_progress
        $nuevoEstado = 'in_progress';
    } else if ($estadoActual === 'in_progress' || $estadoActual === 'completed') {
        // Solo puede pasar a completed si hay QC y dosis y no hay repetición ni incidencia
        if ($tieneQC && $tieneDosis && !$hayRepeticion && !$hayIncidencia) {
            $nuevoEstado = 'completed';
        } else {
            $nuevoEstado = 'in_progress';
        }
    } else {
        // No cambiar si está cancelado
        return;
    }

    // Actualizar solo si hay cambio
    if ($nuevoEstado !== $estadoActual) {
        $stmt = $connect->prepare("UPDATE worklist SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nuevoEstado, $study_id]);
    }
}
?>