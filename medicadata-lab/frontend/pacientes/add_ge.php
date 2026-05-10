<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

try {
    // Validar los datos recibidos
    if (empty($_POST['pa1']) || empty($_POST['nomp1'])) {
        throw new Exception("Todos los campos son obligatorios");
    }

    // Recibir los datos del formulario
    $pa2 = $_POST['pa1'];
    $nomp2 = $_POST['nomp1'];

    // Capturar los nuevos campos
    $antecedentesFamiliares = $_POST['antecedentes_familiares'];
    $alergias = $_POST['alergias'];
    $medicamentosActuales = $_POST['medicamentos_actuales'];
    $tipeoSanguineo = $_POST['tipeo_sanguineo'];
    $antecedentesMedicos = $_POST['antecedentes_medicos']; 
    $notasMedicas = $_POST['notas_medicas'];
    $complicacionesDiabetes = $_POST['complicaciones_diabetes'];
    $notasDiabetes = $_POST['notas_diabetes'];
    $enfermedadesCronicas = $_POST['enfermedades_cronicas']; 
    $notasCronicas = $_POST['notas_cronicas'];
    $antecedentesQuirurgicos = $_POST['antecedentes_quirurgicos']; 
    $notasQuirurgicas = $_POST['notas_quirurgicas'];
    $procesadoPor = $_POST['procesado_por'];

    // Generar la fecha y hora actual desde PHP
    $fecha_actual = date('Y-m-d H:i:s');

    // Inserción en la tabla con la fecha generada desde PHP
    $sql = "INSERT INTO genogram (
        idpa, nompa, state, fere, antecedentes_familiares, alergias, 
        medicamentos_actuales, tipeo_sanguineo, antecedentes_medicos, notas_medicas, 
        complicaciones_diabetes, notas_diabetes, enfermedades_cronicas, notas_cronicas, 
        antecedentes_quirurgicos, notas_quirurgicas, procesado_por
    ) VALUES (
        :idpa, :nompa, '1', :fere, :antecedentes_familiares, :alergias, 
        :medicamentos_actuales, :tipeo_sanguineo, :antecedentes_medicos, :notas_medicas, 
        :complicaciones_diabetes, :notas_diabetes, :enfermedades_cronicas, :notas_cronicas, 
        :antecedentes_quirurgicos, :notas_quirurgicas, :procesado_por
    )";

    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $pa2);
    $stmt->bindParam(':nompa', $nomp2);
    $stmt->bindParam(':fere', $fecha_actual);
    $stmt->bindParam(':antecedentes_familiares', $antecedentesFamiliares);
    $stmt->bindParam(':alergias', $alergias);
    $stmt->bindParam(':medicamentos_actuales', $medicamentosActuales);
    $stmt->bindParam(':tipeo_sanguineo', $tipeoSanguineo);
    $stmt->bindParam(':antecedentes_medicos', $antecedentesMedicos);
    $stmt->bindParam(':notas_medicas', $notasMedicas);
    $stmt->bindParam(':complicaciones_diabetes', $complicacionesDiabetes);
    $stmt->bindParam(':notas_diabetes', $notasDiabetes);
    $stmt->bindParam(':enfermedades_cronicas', $enfermedadesCronicas);
    $stmt->bindParam(':notas_cronicas', $notasCronicas);
    $stmt->bindParam(':antecedentes_quirurgicos', $antecedentesQuirurgicos);
    $stmt->bindParam(':notas_quirurgicas', $notasQuirurgicas);
    $stmt->bindParam(':procesado_por', $procesadoPor);
    $stmt->execute();

    // Obtener el último registro insertado
    $last_id = $connect->lastInsertId();
    $stmt = $connect->prepare("SELECT * FROM genogram WHERE idge = :last_id");
    $stmt->bindParam(':last_id', $last_id);
    $stmt->execute();
    $new_record = $stmt->fetch(PDO::FETCH_ASSOC);

    // Enviar el registro en formato JSON
    echo json_encode($new_record);
} catch (Exception $e) {
    http_response_code(500); // Devuelve error HTTP 500
    echo json_encode(["error" => $e->getMessage()]);
}
?>