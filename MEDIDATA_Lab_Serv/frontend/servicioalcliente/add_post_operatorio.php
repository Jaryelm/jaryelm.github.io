<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    // Depuración: Guardar datos recibidos en log
    file_put_contents('debug_periodo_post_operatorio.log', print_r($_POST, true));

    // Validar los datos recibidos
    if (empty($_POST['procesado_por']) || empty($_POST['idpa'])) {
        throw new Exception("Todos los campos obligatorios no están completos.");
    }

    // Obtener la fecha y hora actual en Tegucigalpa
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    $created_at = date('Y-m-d H:i:s'); // Generar la fecha y hora en la zona horaria local

    $procesadoPor = htmlspecialchars($_POST['procesado_por'], ENT_QUOTES, 'UTF-8');
    $idpa = intval($_POST['idpa']);

    // Evaluación del Riesgo de Caídas
    $riesgo_caidas = htmlspecialchars($_POST['riesgo_caidas'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Medidas de Seguridad
$medidas_seguridad = isset($_POST['medidas_seguridad']) && is_array($_POST['medidas_seguridad']) 
    ? json_encode($_POST['medidas_seguridad'], JSON_UNESCAPED_UNICODE) 
    : json_encode([]);
    
    $actividad_muscular = isset($_POST['actividad_muscular']) ? (int) $_POST['actividad_muscular'] : null;
    $respiracion = isset($_POST['respiracion']) ? (int) $_POST['respiracion'] : null;
    $circulacion = isset($_POST['circulacion']) ? (int) $_POST['circulacion'] : null;
    $estado_conciencia = isset($_POST['estado_conciencia']) ? (int) $_POST['estado_conciencia'] : null;
    $coloracion = isset($_POST['coloracion']) ? (int) $_POST['coloracion'] : null;
    $alta_si = htmlspecialchars($_POST['alta_si'] ?? '', ENT_QUOTES, 'UTF-8');
    $alta_no = htmlspecialchars($_POST['alta_no'] ?? '', ENT_QUOTES, 'UTF-8');
    $a_cuarto = htmlspecialchars($_POST['a_cuarto'] ?? '', ENT_QUOTES, 'UTF-8');
    $a_domicilio = htmlspecialchars($_POST['a_domicilio'] ?? '', ENT_QUOTES, 'UTF-8');
    $hora_alta = htmlspecialchars($_POST['hora_alta'] ?? '', ENT_QUOTES, 'UTF-8');

    // Evaluación del Dolor
    $hora_dolor = htmlspecialchars($_POST['hora_dolor'] ?? '', ENT_QUOTES, 'UTF-8');
    $grado_dolor = intval($_POST['grado_dolor'] ?? 0);
    $localizacion_dolor = htmlspecialchars($_POST['localizacion_dolor'] ?? '', ENT_QUOTES, 'UTF-8');
    $actividad_dolor = htmlspecialchars($_POST['actividad_dolor'] ?? '', ENT_QUOTES, 'UTF-8');

    // Sala de Recuperación
    $sala_recuperacion = isset($_POST['sala_recuperacion']) && is_array($_POST['sala_recuperacion']) 
    ? json_encode($_POST['sala_recuperacion'], JSON_UNESCAPED_UNICODE) 
    : json_encode([]);
    
    // Insertar en la tabla con la fecha y hora local de PHP
    $sql = "INSERT INTO periodo_post_operativo 
    (fecha, hora, procesado_por, idpa, riesgo_caidas, medidas_seguridad, 
    actividad_muscular, respiracion, circulacion, estado_conciencia, coloracion, 
    alta_si, alta_no, a_cuarto, a_domicilio, hora_alta, 
    hora_dolor, grado_dolor, localizacion_dolor, actividad_dolor, sala_recuperacion, created_at) 
    VALUES (:fecha, :hora, :procesadoPor, :idpa, :riesgo_caidas, :medidas_seguridad, 
    :actividad_muscular, :respiracion, :circulacion, :estado_conciencia, :coloracion, 
    :alta_si, :alta_no, :a_cuarto, :a_domicilio, :hora_alta, 
    :hora_dolor, :grado_dolor, :localizacion_dolor, :actividad_dolor, :sala_recuperacion, :created_at)";

$stmt = $connect->prepare($sql);
$stmt->execute([
':fecha' => $fecha,
':hora' => $hora,
':procesadoPor' => $procesadoPor,
':idpa' => $idpa,
':riesgo_caidas' => $riesgo_caidas,
':medidas_seguridad' => $medidas_seguridad,
':actividad_muscular' => $actividad_muscular,
':respiracion' => $respiracion,
':circulacion' => $circulacion,
':estado_conciencia' => $estado_conciencia,
':coloracion' => $coloracion,
':alta_si' => $alta_si,
':alta_no' => $alta_no,
':a_cuarto' => $a_cuarto,
':a_domicilio' => $a_domicilio,
':hora_alta' => $hora_alta,
':hora_dolor' => $hora_dolor,
':grado_dolor' => $grado_dolor,
':localizacion_dolor' => $localizacion_dolor,
':actividad_dolor' => $actividad_dolor,
':sala_recuperacion' => $sala_recuperacion,
':created_at' => $created_at 
]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => "Registro guardado correctamente."]);
    } else {
        echo json_encode(["error" => "La consulta SQL no insertó datos."]);
    }
} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    echo json_encode(["error" => "Error al guardar en la base de datos."]);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>