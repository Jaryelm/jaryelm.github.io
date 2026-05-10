<?php
require_once('../../backend/bd/Conexion.php');

date_default_timezone_set('America/Tegucigalpa');
header('Content-Type: application/json');

try {
    // Guardar datos en log para depuración
    file_put_contents('debug_anestesia.log', print_r($_POST, true));

    // Validar que los campos esenciales no estén vacíos
    if (empty($_POST['idpa']) || empty($_POST['tiempo_anestesia']) || empty($_POST['temp']) ||
        empty($_POST['tension_arterial']) || empty($_POST['pulso']) || empty($_POST['frecuencia_respiratoria']) ||
        empty($_POST['frecuencia_cardiaca']) || empty($_POST['diagnostico']) || empty($_POST['operacion']) ||
        empty($_POST['metodo_anestesia']) || empty($_POST['anestesiologo']) || empty($_POST['clave']) || 
        empty($_POST['cirujano']) || empty($_POST['ayudante']) || empty($_POST['instrumentista']) || 
        empty($_POST['circulante']) || empty($_POST['procesado_por'])) {
        throw new Exception("Todos los campos obligatorios deben estar completos.");
    }

    // Sanitizar entrada
    $idpa = intval($_POST['idpa']);
    $tiempo_anestesia = intval($_POST['tiempo_anestesia']);
    $temp = floatval($_POST['temp']);
    $tension_arterial = htmlspecialchars($_POST['tension_arterial'], ENT_QUOTES, 'UTF-8');
    $pulso = intval($_POST['pulso']);
    $frecuencia_respiratoria = intval($_POST['frecuencia_respiratoria']);
    $frecuencia_cardiaca = intval($_POST['frecuencia_cardiaca']);
    $diagnostico = htmlspecialchars($_POST['diagnostico'], ENT_QUOTES, 'UTF-8');
    $operacion = htmlspecialchars($_POST['operacion'], ENT_QUOTES, 'UTF-8');
    $metodo_anestesia = htmlspecialchars($_POST['metodo_anestesia'], ENT_QUOTES, 'UTF-8');

    // Capturar valores correctamente de radio buttons y inputs de texto
    $mascarilla = isset($_POST['mascarilla']) ? $_POST['mascarilla'] : "No";
    $canula = isset($_POST['canula']) ? $_POST['canula'] : "Nasal";
    $tubo_endotraqueal = !empty($_POST['tubo_endotraqueal']) ? htmlspecialchars($_POST['tubo_endotraqueal'], ENT_QUOTES, 'UTF-8') : NULL;
    $globo_inflable = !empty($_POST['globo_inflable']) ? htmlspecialchars($_POST['globo_inflable'], ENT_QUOTES, 'UTF-8') : NULL;
    $complicaciones = isset($_POST['complicaciones']) ? $_POST['complicaciones'] : "No";
    $sangre_soluciones = !empty($_POST['sangre_soluciones']) ? htmlspecialchars($_POST['sangre_soluciones'], ENT_QUOTES, 'UTF-8') : NULL;

    $medicamentos = htmlspecialchars($_POST['medicamentos'], ENT_QUOTES, 'UTF-8');
    $caso_obstetrico = htmlspecialchars($_POST['caso_obstetrico'], ENT_QUOTES, 'UTF-8');
    $nombre_recien_nacido = !empty($_POST['nombre_recien_nacido']) ? htmlspecialchars($_POST['nombre_recien_nacido'], ENT_QUOTES, 'UTF-8') : NULL;
    $hora_nacimiento = !empty($_POST['hora_nacimiento']) ? $_POST['hora_nacimiento'] : NULL;
    $sexo = htmlspecialchars($_POST['sexo'], ENT_QUOTES, 'UTF-8');
    $peso = !empty($_POST['peso']) ? floatval($_POST['peso']) : NULL;
    $talla = !empty($_POST['talla']) ? floatval($_POST['talla']) : NULL;

    $anestesiologo = htmlspecialchars($_POST['anestesiologo'], ENT_QUOTES, 'UTF-8');
    $clave = htmlspecialchars($_POST['clave'], ENT_QUOTES, 'UTF-8');
    $cirujano = htmlspecialchars($_POST['cirujano'], ENT_QUOTES, 'UTF-8');
    $ayudante = htmlspecialchars($_POST['ayudante'], ENT_QUOTES, 'UTF-8');
    $instrumentista = htmlspecialchars($_POST['instrumentista'], ENT_QUOTES, 'UTF-8');
    $circulante = htmlspecialchars($_POST['circulante'], ENT_QUOTES, 'UTF-8');
    $observaciones = !empty($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones'], ENT_QUOTES, 'UTF-8') : NULL;
    $procesado_por = htmlspecialchars($_POST['procesado_por'], ENT_QUOTES, 'UTF-8');
    $created_at = date('Y-m-d H:i:s');

    // Insertar en la base de datos
    $sql = "INSERT INTO anestesia 
            (idpa, tiempo_anestesia, temp, tension_arterial, pulso, frecuencia_respiratoria, 
            frecuencia_cardiaca, diagnostico, operacion, metodo_anestesia, 
            medicamentos, caso_obstetrico, nombre_recien_nacido, hora_nacimiento, sexo, 
            peso, talla, anestesiologo, clave, cirujano, ayudante, instrumentista, 
            circulante, observaciones, procesado_por, created_at, mascarilla, canula, tubo_endotraqueal, globo_inflable, complicaciones, sangre_soluciones) 
            VALUES 
            (:idpa, :tiempo_anestesia, :temp, :tension_arterial, :pulso, :frecuencia_respiratoria, 
            :frecuencia_cardiaca, :diagnostico, :operacion, :metodo_anestesia, '', 
            :medicamentos, :caso_obstetrico, :nombre_recien_nacido, :hora_nacimiento, :sexo, 
            :peso, :talla, :anestesiologo, :clave, :cirujano, :ayudante, :instrumentista, 
            :circulante, :observaciones, :procesado_por, :created_at, :mascarilla, :canula, :tubo_endotraqueal, :globo_inflable, :complicaciones, :sangre_soluciones)";

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':idpa' => $idpa,
        ':tiempo_anestesia' => $tiempo_anestesia,
        ':temp' => $temp,
        ':tension_arterial' => $tension_arterial,
        ':pulso' => $pulso,
        ':frecuencia_respiratoria' => $frecuencia_respiratoria,
        ':frecuencia_cardiaca' => $frecuencia_cardiaca,
        ':diagnostico' => $diagnostico,
        ':operacion' => $operacion,
        ':metodo_anestesia' => $metodo_anestesia,
        ':medicamentos' => $medicamentos,
        ':caso_obstetrico' => $caso_obstetrico,
        ':nombre_recien_nacido' => $nombre_recien_nacido,
        ':hora_nacimiento' => $hora_nacimiento,
        ':sexo' => $sexo,
        ':peso' => $peso,
        ':talla' => $talla,
        ':anestesiologo' => $anestesiologo,
        ':clave' => $clave,
        ':cirujano' => $cirujano,
        ':ayudante' => $ayudante,
        ':instrumentista' => $instrumentista,
        ':circulante' => $circulante,
        ':observaciones' => $observaciones,
        ':procesado_por' => $procesado_por,
        ':created_at' => $created_at,
        ':mascarilla' => $mascarilla,
        ':canula' => $canula,
        ':tubo_endotraqueal' => $tubo_endotraqueal,
        ':globo_inflable' => $globo_inflable,
        ':complicaciones' => $complicaciones,
        ':sangre_soluciones' => $sangre_soluciones
    ]);

    echo json_encode(["success" => "Registro guardado correctamente."]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
