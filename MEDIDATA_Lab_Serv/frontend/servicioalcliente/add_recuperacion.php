<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    // Depuración: Guardar datos recibidos en log
    file_put_contents('debug_recuperacion.log', print_r($_POST, true));

    // Validar datos obligatorios
    if (empty($_POST['idpa']) || empty($_POST['diagnostico']) || empty($_POST['cirujano_realizada']) ||
        empty($_POST['cirujano_principal']) || empty($_POST['anestesista']) || empty($_POST['tipo_anestesia']) ||
        empty($_POST['fecha']) || empty($_POST['hora_inicio_cirugia']) || empty($_POST['hora_fin_cirugia'])) {
        throw new Exception("Todos los campos obligatorios deben estar completos.");
    }

    // Datos generales
    $idpa = intval($_POST['idpa']);
    $diagnostico = htmlspecialchars($_POST['diagnostico'], ENT_QUOTES, 'UTF-8');
    $cirujano_realizada = htmlspecialchars($_POST['cirujano_realizada'], ENT_QUOTES, 'UTF-8');
    $cirujano_principal = htmlspecialchars($_POST['cirujano_principal'], ENT_QUOTES, 'UTF-8');
    $anestesista = htmlspecialchars($_POST['anestesista'], ENT_QUOTES, 'UTF-8');
    $tipo_anestesia = htmlspecialchars($_POST['tipo_anestesia'], ENT_QUOTES, 'UTF-8');
    $fecha = htmlspecialchars($_POST['fecha'], ENT_QUOTES, 'UTF-8');
    $hora_inicio_cirugia = htmlspecialchars($_POST['hora_inicio_cirugia'], ENT_QUOTES, 'UTF-8');
    $hora_fin_cirugia = htmlspecialchars($_POST['hora_fin_cirugia'], ENT_QUOTES, 'UTF-8');

    // Cuidados Post Operatorios Inmediatos
    $reflejos = $_POST['reflejos'] ?? 'No';
    $canula_endotraqueal = $_POST['canula_endotraqueal'] ?? 'No';
    $oxigeno = $_POST['oxigeno'] ?? 'No';
    $sonda_foley = $_POST['sonda_foley'] ?? 'No';
    $sonda_nsg = $_POST['sonda_nsg'] ?? 'No';
    $cvp = $_POST['cvp'] ?? 'No';
    $cvc = $_POST['cvc'] ?? 'No';
    $drenos = $_POST['drenos'] ?? 'No';
    $tipo_cuidado = htmlspecialchars($_POST['tipo_cuidado'] ?? '', ENT_QUOTES, 'UTF-8');

    // Líquidos en Infusión
    $liquidos_infusion = htmlspecialchars($_POST['liquidos_infusion'] ?? '', ENT_QUOTES, 'UTF-8');
    $cantidad_liquidos = htmlspecialchars($_POST['cantidad_liquidos'] ?? '', ENT_QUOTES, 'UTF-8');
    $mezcla_liquidos = htmlspecialchars($_POST['mezcla_liquidos'] ?? '', ENT_QUOTES, 'UTF-8');

    // Signos Vitales
    $hora_signos = htmlspecialchars($_POST['hora_signos'] ?? '', ENT_QUOTES, 'UTF-8');
    $pa_signos = htmlspecialchars($_POST['pa_signos'] ?? '', ENT_QUOTES, 'UTF-8');
    $fc_signos = htmlspecialchars($_POST['fc_signos'] ?? '', ENT_QUOTES, 'UTF-8');
    $fr_signos = htmlspecialchars($_POST['fr_signos'] ?? '', ENT_QUOTES, 'UTF-8');
    $ta_signos = htmlspecialchars($_POST['ta_signos'] ?? '', ENT_QUOTES, 'UTF-8');
    $spo2_signos = htmlspecialchars($_POST['spo2_signos'] ?? '', ENT_QUOTES, 'UTF-8');

    // Medicamentos
    $medicamento = htmlspecialchars($_POST['medicamento'] ?? '', ENT_QUOTES, 'UTF-8');
    $dosis = htmlspecialchars($_POST['dosis'] ?? '', ENT_QUOTES, 'UTF-8');
    $via = htmlspecialchars($_POST['via'] ?? '', ENT_QUOTES, 'UTF-8');
    $hora_medicamento = htmlspecialchars($_POST['hora_medicamento'] ?? '', ENT_QUOTES, 'UTF-8');

    // Control de Líquidos
    $ingestas_orales = htmlspecialchars($_POST['ingestas_orales'] ?? '', ENT_QUOTES, 'UTF-8');
    $ingestas_iv = htmlspecialchars($_POST['ingestas_iv'] ?? '', ENT_QUOTES, 'UTF-8');
    $excretas_orina = htmlspecialchars($_POST['excretas_orina'] ?? '', ENT_QUOTES, 'UTF-8');
    $excretas_vomitos = htmlspecialchars($_POST['excretas_vomitos'] ?? '', ENT_QUOTES, 'UTF-8');
    $excretas_succion = htmlspecialchars($_POST['excretas_succion'] ?? '', ENT_QUOTES, 'UTF-8');

    // Observaciones
    $observaciones = htmlspecialchars($_POST['observaciones'] ?? '', ENT_QUOTES, 'UTF-8');

    // Fecha de creación
    $created_at = date('Y-m-d H:i:s');

    // Insertar en la base de datos
    $sql = "INSERT INTO recuperacion (
        idpa, diagnostico, cirujano_realizada, cirujano_principal, anestesista, tipo_anestesia, 
        fecha, hora_inicio_cirugia, hora_fin_cirugia, reflejos, canula_endotraqueal, oxigeno, 
        sonda_foley, sonda_nsg, cvp, cvc, drenos, tipo_cuidado, 
        hora_signos, pa_signos, fc_signos, fr_signos, ta_signos, spo2_signos, 
        medicamento, dosis, via, hora_medicamento, ingestas_orales, ingestas_iv, 
        excretas_orina, excretas_vomitos, excretas_succion, observaciones, created_at
    ) VALUES (
        :idpa, :diagnostico, :cirujano_realizada, :cirujano_principal, :anestesista, :tipo_anestesia, 
        :fecha, :hora_inicio_cirugia, :hora_fin_cirugia, :reflejos, :canula_endotraqueal, :oxigeno, 
        :sonda_foley, :sonda_nsg, :cvp, :cvc, :drenos, :tipo_cuidado, 
        :hora_signos, :pa_signos, :fc_signos, :fr_signos, :ta_signos, :spo2_signos, 
        :medicamento, :dosis, :via, :hora_medicamento, :ingestas_orales, :ingestas_iv, 
        :excretas_orina, :excretas_vomitos, :excretas_succion, :observaciones, :created_at
    )";

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':idpa' => $idpa,
        ':diagnostico' => $diagnostico,
        ':cirujano_realizada' => $cirujano_realizada,
        ':cirujano_principal' => $cirujano_principal,
        ':anestesista' => $anestesista,
        ':tipo_anestesia' => $tipo_anestesia,
        ':fecha' => $fecha,
        ':hora_inicio_cirugia' => $hora_inicio_cirugia,
        ':hora_fin_cirugia' => $hora_fin_cirugia,
        ':reflejos' => $reflejos,
        ':canula_endotraqueal' => $canula_endotraqueal,
        ':oxigeno' => $oxigeno,
        ':sonda_foley' => $sonda_foley,
        ':sonda_nsg' => $sonda_nsg,
        ':cvp' => $cvp,
        ':cvc' => $cvc,
        ':drenos' => $drenos,
        ':tipo_cuidado' => $tipo_cuidado,
        ':hora_signos' => $hora_signos,
        ':pa_signos' => $pa_signos,
        ':fc_signos' => $fc_signos,
        ':fr_signos' => $fr_signos,
        ':ta_signos' => $ta_signos,
        ':spo2_signos' => $spo2_signos,
        ':medicamento' => $medicamento,
        ':dosis' => $dosis,
        ':via' => $via,
        ':hora_medicamento' => $hora_medicamento,
        ':ingestas_orales' => $ingestas_orales,
        ':ingestas_iv' => $ingestas_iv,
        ':excretas_orina' => $excretas_orina,
        ':excretas_vomitos' => $excretas_vomitos,
        ':excretas_succion' => $excretas_succion,
        ':observaciones' => $observaciones,
        ':created_at' => $created_at
    ]);

    echo json_encode(["success" => "Registro guardado correctamente."]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>
