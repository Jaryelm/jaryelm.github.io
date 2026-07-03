<?php
require_once('../../backend/bd/Conexion.php');
date_default_timezone_set('America/Tegucigalpa');

try {
    // Validar campos obligatorios
    if (
        empty($_POST['pt']) || 
        empty($_POST['mb']) || 
        empty($_POST['cb']) || 
        empty($_POST['ar']) || 
        empty($_POST['peso_habitual']) || 
        empty($_POST['talla']) || 
        empty($_POST['imc']) || 
        empty($_POST['imc_20_5']) || 
        empty($_POST['perdida_peso']) || 
        empty($_POST['ingesta_reducida']) || 
        empty($_POST['paciente_grave']) ||
        empty($_POST['peso_actual']) || 
        empty($_POST['csidpa']) || 
        empty($_POST['csnopa']) ||
        empty($_POST['grado1']) || 
        empty($_POST['grado2']) || 
        empty($_POST['grado3']) || 
        empty($_POST['severidad']) || 
        empty($_POST['edad']) || 
        empty($_POST['marcador_total']) || 
        empty($_POST['interpretacion']) || 
        empty($_POST['acciones']) || 
        empty($_POST['diagnostico']) 
    ) {
        throw new Exception("Todos los campos obligatorios deben ser completados.");
    }

    // Recibir datos
    $pt = $_POST['pt'];
    $mb = $_POST['mb'];
    $cb = $_POST['cb'];
    $ar = $_POST['ar'];
    $peso_habitual = $_POST['peso_habitual'];
    $talla = $_POST['talla'];
    $imc = $_POST['imc'];
    $imc_20_5 = $_POST['imc_20_5'];
    $perdida_peso = $_POST['perdida_peso'];
    $ingesta_reducida = $_POST['ingesta_reducida'];
    $paciente_grave = $_POST['paciente_grave'];
    $peso_actual = $_POST['peso_actual'];
    $csidpa = $_POST['csidpa'];
    $csnopa = $_POST['csnopa'];
    $grado1 = $_POST['grado1'];
    $grado2 = $_POST['grado2'];
    $grado3 = $_POST['grado3'];
    $severidad = $_POST['severidad'];
    $edad = $_POST['edad'];
    $marcador_total = $_POST['marcador_total'];
    $interpretacion = $_POST['interpretacion'];
    $acciones = $_POST['acciones'];
    $diagnostico = $_POST['diagnostico'];
    $fecha_actual = date('Y-m-d H:i:s');

    // Preparar la consulta SQL
    $sql = "INSERT INTO apoyo_nutricion 
    (pt, mb, cb, ar, peso_habitual, talla, imc, peso_actual, idpa, nompa, fecha_evaluacion, 
     imc_20_5, perdida_peso, ingesta_reducida, paciente_grave, grado1, grado2, grado3, severidad, edad, 
     marcador_total, interpretacion, acciones, diagnostico) 
    VALUES 
    (:pt, :mb, :cb, :ar, :peso_habitual, :talla, :imc, :peso_actual, :idpa, :nompa, :fecha_evaluacion, 
     :imc_20_5, :perdida_peso, :ingesta_reducida, :paciente_grave, :grado1, :grado2, :grado3, :severidad, :edad, 
     :marcador_total, :interpretacion, :acciones, :diagnostico)";

    $stmt = $connect->prepare($sql);

    // Asignar parámetros
    $stmt->bindParam(':pt', $pt);
    $stmt->bindParam(':mb', $mb);
    $stmt->bindParam(':cb', $cb);
    $stmt->bindParam(':ar', $ar);
    $stmt->bindParam(':peso_habitual', $peso_habitual);
    $stmt->bindParam(':talla', $talla);
    $stmt->bindParam(':imc', $imc);
    $stmt->bindParam(':imc_20_5', $imc_20_5);
    $stmt->bindParam(':perdida_peso', $perdida_peso);
    $stmt->bindParam(':ingesta_reducida', $ingesta_reducida);
    $stmt->bindParam(':paciente_grave', $paciente_grave);
    $stmt->bindParam(':peso_actual', $peso_actual);
    $stmt->bindParam(':idpa', $csidpa);
    $stmt->bindParam(':nompa', $csnopa);
    $stmt->bindParam(':fecha_evaluacion', $fecha_actual);
    $stmt->bindParam(':grado1', $grado1);
    $stmt->bindParam(':grado2', $grado2);
    $stmt->bindParam(':grado3', $grado3);
    $stmt->bindParam(':severidad', $severidad);
    $stmt->bindParam(':edad', $edad);
    $stmt->bindParam(':marcador_total', $marcador_total);
    $stmt->bindParam(':interpretacion', $interpretacion);
    $stmt->bindParam(':acciones', $acciones);
    $stmt->bindParam(':diagnostico', $diagnostico);

    // Ejecutar la consulta
    $stmt->execute();

    echo "Agregado correctamente";
} catch (Exception $e) {
    // Capturar y mostrar errores
    echo "Error: " . $e->getMessage();
}
?>
