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
    
// Medidas de Seguridad - Decodificar JSON y formatear correctamente
$medidas_seguridad = isset($_POST['medidas_seguridad']) ? json_decode($_POST['medidas_seguridad'], true) : [];

if (!is_array($medidas_seguridad)) {
    $medidas_seguridad = [];
}

$medidas_seguridad_guardar = json_encode($medidas_seguridad, JSON_UNESCAPED_UNICODE);
    
$actividad_muscular = htmlspecialchars($_POST['actividad_muscular'] ?? '', ENT_QUOTES, 'UTF-8');
$respiracion = htmlspecialchars($_POST['respiracion'] ?? '', ENT_QUOTES, 'UTF-8');
$circulacion = htmlspecialchars($_POST['circulacion'] ?? '', ENT_QUOTES, 'UTF-8');
$estado_conciencia = htmlspecialchars($_POST['estado_conciencia'] ?? '', ENT_QUOTES, 'UTF-8');
$coloracion = htmlspecialchars($_POST['coloracion'] ?? '', ENT_QUOTES, 'UTF-8');
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

// Sala de Recuperación - Decodificar JSON y formatear correctamente
$sala_recuperacion = isset($_POST['sala_recuperacion']) ? json_decode($_POST['sala_recuperacion'], true) : [];

if (!is_array($sala_recuperacion)) {
    $sala_recuperacion = [];
}

$sala_recuperacion_guardar = json_encode($sala_recuperacion, JSON_UNESCAPED_UNICODE);

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
    ':medidas_seguridad' => $medidas_seguridad_guardar,
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
    ':sala_recuperacion' => $sala_recuperacion_guardar,
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