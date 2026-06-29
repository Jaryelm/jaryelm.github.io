<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

$logFile = __DIR__ . "/error.log";
error_log("🚀 Recibiendo datos...\n", 3, $logFile);
error_log(print_r($_POST, true), 3, $logFile);

try {
    // Validar si los campos obligatorios están completos
    $camposObligatorios = [
        'idpa', 'tipo_rh', 'diagnostico_hemoderivados', 'medico_tratante_hemoderivados',
        'enfermero_responsable_hemoderivados', 'cantidad_unidades_hemoderivados', 
        'hora_inicio_hemoderivados', 'hora_finalizacion_hemoderivados', 
        'pa_antes_transfundir', 'fc_antes_transfundir', 'ta_antes_transfundir',
        'fr_antes_transfundir', 'spo2_antes_transfundir'
    ];

    $faltantes = [];
    foreach ($camposObligatorios as $campo) {
        if (empty($_POST[$campo])) {
            $faltantes[] = $campo;
        }
    }

    if (!empty($faltantes)) {
        throw new Exception("Faltan los siguientes campos obligatorios: " . implode(', ', $faltantes));
    }

    // Recibir y sanitizar los datos
    $idpa = intval($_POST['idpa']);
    $tipo_rh = trim($_POST['tipo_rh']);
    $diagnostico_hemoderivados = trim($_POST['diagnostico_hemoderivados']);
    $medico_tratante_hemoderivados = trim($_POST['medico_tratante_hemoderivados']);
    $enfermero_responsable = trim($_POST['enfermero_responsable_hemoderivados']);
    $cantidad_unidades = trim($_POST['cantidad_unidades_hemoderivados']);
    $hora_inicio = $_POST['hora_inicio_hemoderivados'];
    $hora_finalizacion = $_POST['hora_finalizacion_hemoderivados'];
    $pa_antes = trim($_POST['pa_antes_transfundir']);
    $fc_antes = trim($_POST['fc_antes_transfundir']);
    $ta_antes = trim($_POST['ta_antes_transfundir']);
    $fr_antes = trim($_POST['fr_antes_transfundir']);
    $spo2_antes = trim($_POST['spo2_antes_transfundir']);
    
    // Definir valores por defecto para campos opcionales
    $sangre_completa = $_POST['sangre_completa_hemoderivados'] ?? NULL;
    $globulos_rojos = $_POST['globulos_rojos_hemoderivados'] ?? NULL;
    $plasma_normal = $_POST['plasma_normal_hemoderivados'] ?? NULL;
    $plasma_fresco = $_POST['plasma_fresco_congelado_hemoderivados'] ?? NULL;
    $plaquetas = $_POST['plaquetas_hemoderivados'] ?? NULL;
    $plaquetas_aferesis = $_POST['plaquetas_aferesis_hemoderivados'] ?? NULL;
    $crio_precipitado = $_POST['crio_precipitado_hemoderivados'] ?? NULL;
    $otros = $_POST['otros_hemoderivados'] ?? NULL;
    $transfusion_reacciones = $_POST['transfusion_reacciones'] ?? NULL;
    $fecha_registro = date('Y-m-d H:i:s');

    // Insertar en la base de datos
    $sql = "INSERT INTO transfusion_hemoderivados (
        idpa, tipo_rh, diagnostico_hemoderivados, medico_tratante_hemoderivados, 
        enfermero_responsable_hemoderivados, sangre_completa_hemoderivados, 
        globulos_rojos_hemoderivados, plasma_normal_hemoderivados, plasma_fresco_congelado_hemoderivados, 
        plaquetas_hemoderivados, plaquetas_aferesis_hemoderivados, crio_precipitado_hemoderivados, 
        otros_hemoderivados, cantidad_unidades_hemoderivados, hora_inicio_hemoderivados, 
        hora_finalizacion_hemoderivados, pa_antes_transfundir, fc_antes_transfundir, 
        ta_antes_transfundir, fr_antes_transfundir, spo2_antes_transfundir, 
        transfusion_reacciones, fecha_registro
    ) VALUES (
        :idpa, :tipo_rh, :diagnostico_hemoderivados, :medico_tratante_hemoderivados, 
        :enfermero_responsable_hemoderivados, :sangre_completa_hemoderivados, 
        :globulos_rojos_hemoderivados, :plasma_normal_hemoderivados, :plasma_fresco_congelado_hemoderivados, 
        :plaquetas_hemoderivados, :plaquetas_aferesis_hemoderivados, :crio_precipitado_hemoderivados, 
        :otros_hemoderivados, :cantidad_unidades_hemoderivados, :hora_inicio_hemoderivados, 
        :hora_finalizacion_hemoderivados, :pa_antes_transfundir, :fc_antes_transfundir, 
        :ta_antes_transfundir, :fr_antes_transfundir, :spo2_antes_transfundir, 
        :transfusion_reacciones, :fecha_registro
    )";

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':idpa' => $idpa,
        ':tipo_rh' => $tipo_rh,
        ':diagnostico_hemoderivados' => $diagnostico_hemoderivados,
        ':medico_tratante_hemoderivados' => $medico_tratante_hemoderivados,
        ':enfermero_responsable_hemoderivados' => $enfermero_responsable,
        ':sangre_completa_hemoderivados' => $sangre_completa,
        ':globulos_rojos_hemoderivados' => $globulos_rojos,
        ':plasma_normal_hemoderivados' => $plasma_normal,
        ':plasma_fresco_congelado_hemoderivados' => $plasma_fresco,
        ':plaquetas_hemoderivados' => $plaquetas,
        ':plaquetas_aferesis_hemoderivados' => $plaquetas_aferesis,
        ':crio_precipitado_hemoderivados' => $crio_precipitado,
        ':otros_hemoderivados' => $otros,
        ':cantidad_unidades_hemoderivados' => $cantidad_unidades,
        ':hora_inicio_hemoderivados' => $hora_inicio,
        ':hora_finalizacion_hemoderivados' => $hora_finalizacion,
        ':pa_antes_transfundir' => $pa_antes,
        ':fc_antes_transfundir' => $fc_antes,
        ':ta_antes_transfundir' => $ta_antes,
        ':fr_antes_transfundir' => $fr_antes,
        ':spo2_antes_transfundir' => $spo2_antes,
        ':transfusion_reacciones' => $transfusion_reacciones,
        ':fecha_registro' => $fecha_registro
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Registro agregado correctamente."]);
    } else {
        throw new Exception("Error al insertar los datos. No se registraron filas.");
    }

} catch (PDOException $e) {
    error_log("❌ Error en SQL: " . $e->getMessage(), 3, $logFile);
    echo json_encode(["success" => false, "message" => "Error en la base de datos: " . $e->getMessage()]);
}
?>
