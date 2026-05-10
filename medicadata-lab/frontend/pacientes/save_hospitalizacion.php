<?php
require_once('../../backend/bd/Conexion.php');

try {
    // Validar que se haya enviado el ID del paciente
    if (!isset($_POST['idpa']) || empty($_POST['idpa'])) {
        http_response_code(400); // Código HTTP 400 (Bad Request)
        echo json_encode(['error' => 'El ID del paciente (idpa) es obligatorio para guardar datos.']);
        exit;
    }     

    $idpa = intval($_POST['idpa']); // Sanitizar el ID del paciente

    // Recibir datos del formulario
    $data = ['idpa' => $idpa]; // Siempre incluir `idpa`
    foreach ($_POST as $key => $value) {
        if (!empty($value) && $key !== 'idpa') {
            $data[$key] = $value; // Agregar otros campos válidos
        }
    }

    // Verificar si se recibieron datos adicionales
    if (count($data) <= 1) { // Solo contiene `idpa`
        throw new Exception("No se enviaron datos válidos.");
    }

    // Generar la fecha y hora actual
    $fecha_actual = date('Y-m-d H:i:s');

    // Crear consulta dinámica para insertar los datos
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));

    $sql = "INSERT INTO hospitalizacion ($columns, fere) VALUES ($placeholders, :fere)";
    $stmt = $connect->prepare($sql);
    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':fere', $fecha_actual);
    $stmt->execute();

    echo json_encode(["message" => "Guardado correctamente."]);
} catch (Exception $e) {
    http_response_code(400); // Código HTTP 400: Bad Request
    echo json_encode(["error" => $e->getMessage()]);
}
?>
