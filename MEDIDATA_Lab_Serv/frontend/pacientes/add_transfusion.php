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
        if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
            $faltantes[] = $campo;
        }
    }

    if (!empty($faltantes)) {
        throw new Exception("Faltan los siguientes campos obligatorios: " . implode(', ', $faltantes));
    }

    // Recibir y sanitizar los datos
    function obtenerValor($campo) {
        return isset($_POST[$campo]) ? trim($_POST[$campo]) : NULL;
    }

    $idpa = intval($_POST['idpa']);
    $tipo_rh = obtenerValor('tipo_rh');
    $diagnostico_hemoderivados = obtenerValor('diagnostico_hemoderivados');
    $medico_tratante_hemoderivados = obtenerValor('medico_tratante_hemoderivados');
    $enfermero_responsable = obtenerValor('enfermero_responsable_hemoderivados');
    $cantidad_unidades = obtenerValor('cantidad_unidades_hemoderivados');
    $hora_inicio = obtenerValor('hora_inicio_hemoderivados');
    $hora_finalizacion = obtenerValor('hora_finalizacion_hemoderivados');
    $pa_antes = obtenerValor('pa_antes_transfundir');
    $fc_antes = obtenerValor('fc_antes_transfundir');
    $ta_antes = obtenerValor('ta_antes_transfundir');
    $fr_antes = obtenerValor('fr_antes_transfundir');
    $spo2_antes = obtenerValor('spo2_antes_transfundir');

    // Definir valores opcionales
    $camposOpcionales = [
        'sangre_completa_hemoderivados', 'globulos_rojos_hemoderivados', 'plasma_normal_hemoderivados', 
        'plasma_fresco_congelado_hemoderivados', 'plaquetas_hemoderivados', 'plaquetas_aferesis_hemoderivados', 
        'crio_precipitado_hemoderivados', 'otros_hemoderivados', 'transfusion_reacciones',
        'pa_30minutos_iniciar', 'fc_30minutos_iniciar', 'ta_30minutos_iniciar', 'fr_30minutos_iniciar', 'spo2_30minutos_iniciar',
        'pa_1hora_iniciar', 'fc_1hora_iniciar', 'ta_1hora_iniciar', 'fr_1hora_iniciar', 'spo2_1hora_iniciar',
        'pa_2horas_iniciar', 'fc_2horas_iniciar', 'ta_2horas_iniciar', 'fr_2horas_iniciar', 'spo2_2horas_iniciar',
        'pa_3horas_iniciar', 'fc_3horas_iniciar', 'ta_3horas_iniciar', 'fr_3horas_iniciar', 'spo2_3horas_iniciar'
    ];

    $valoresOpcionales = [];
    foreach ($camposOpcionales as $campo) {
        $valoresOpcionales[$campo] = obtenerValor($campo);
    }

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
        transfusion_reacciones, fecha_registro,
        pa_30minutos_iniciar, fc_30minutos_iniciar, ta_30minutos_iniciar, fr_30minutos_iniciar, spo2_30minutos_iniciar,
        pa_1hora_iniciar, fc_1hora_iniciar, ta_1hora_iniciar, fr_1hora_iniciar, spo2_1hora_iniciar,
        pa_2horas_iniciar, fc_2horas_iniciar, ta_2horas_iniciar, fr_2horas_iniciar, spo2_2horas_iniciar,
        pa_3horas_iniciar, fc_3horas_iniciar, ta_3horas_iniciar, fr_3horas_iniciar, spo2_3horas_iniciar
    ) VALUES (
        :idpa, :tipo_rh, :diagnostico_hemoderivados, :medico_tratante_hemoderivados, 
        :enfermero_responsable_hemoderivados, :sangre_completa_hemoderivados, 
        :globulos_rojos_hemoderivados, :plasma_normal_hemoderivados, :plasma_fresco_congelado_hemoderivados, 
        :plaquetas_hemoderivados, :plaquetas_aferesis_hemoderivados, :crio_precipitado_hemoderivados, 
        :otros_hemoderivados, :cantidad_unidades_hemoderivados, :hora_inicio_hemoderivados, 
        :hora_finalizacion_hemoderivados, :pa_antes_transfundir, :fc_antes_transfundir, 
        :ta_antes_transfundir, :fr_antes_transfundir, :spo2_antes_transfundir, 
        :transfusion_reacciones, :fecha_registro,
        :pa_30minutos_iniciar, :fc_30minutos_iniciar, :ta_30minutos_iniciar, :fr_30minutos_iniciar, :spo2_30minutos_iniciar,
        :pa_1hora_iniciar, :fc_1hora_iniciar, :ta_1hora_iniciar, :fr_1hora_iniciar, :spo2_1hora_iniciar,
        :pa_2horas_iniciar, :fc_2horas_iniciar, :ta_2horas_iniciar, :fr_2horas_iniciar, :spo2_2horas_iniciar,
        :pa_3horas_iniciar, :fc_3horas_iniciar, :ta_3horas_iniciar, :fr_3horas_iniciar, :spo2_3horas_iniciar
    )";

    $stmt = $connect->prepare($sql);
    $stmt->execute(array_merge([
        ':idpa' => $idpa,
        ':tipo_rh' => $tipo_rh,
        ':diagnostico_hemoderivados' => $diagnostico_hemoderivados,
        ':medico_tratante_hemoderivados' => $medico_tratante_hemoderivados,
        ':enfermero_responsable_hemoderivados' => $enfermero_responsable,
        ':cantidad_unidades_hemoderivados' => $cantidad_unidades,
        ':hora_inicio_hemoderivados' => $hora_inicio,
        ':hora_finalizacion_hemoderivados' => $hora_finalizacion,
        ':pa_antes_transfundir' => $pa_antes,
        ':fc_antes_transfundir' => $fc_antes,
        ':ta_antes_transfundir' => $ta_antes,
        ':fr_antes_transfundir' => $fr_antes,
        ':spo2_antes_transfundir' => $spo2_antes,
        ':fecha_registro' => $fecha_registro
    ], $valoresOpcionales));

    echo json_encode(["success" => true, "message" => "Registro agregado correctamente."]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
