<?php
require '../../backend/bd/Conexion.php';
session_start();
date_default_timezone_set('America/Tegucigalpa');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Estado Honorario</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
</head>
<body>
<?php
$id_factura = isset($_POST['id_factura']) ? intval($_POST['id_factura']) : 0;
$id_doctor = isset($_POST['id_doctor']) ? intval($_POST['id_doctor']) : 0;
$monto_honorario = isset($_POST['monto_honorario']) ? floatval($_POST['monto_honorario']) : 0;
$status = (isset($_POST['status']) && $_POST['status'] === 'pagado') ? 'pagado' : 'pendiente';
$usuario = $_SESSION['name'] ?? 'Desconocido';
$fecha_hora_hn = date('Y-m-d H:i:s');

if ($id_factura > 0 && $id_doctor > 0) {
    // Validar el estado actual en la base de datos
    $stmtCheck = $connect->prepare("SELECT estado_pago, monto_honorario FROM honorarios_medicos WHERE id_factura = ? AND id_doctor = ?");
    $stmtCheck->execute([$id_factura, $id_doctor]);
    $honorario_existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($honorario_existente && $honorario_existente['estado_pago'] === 'pagado') {
        echo '<script>swal("No permitido", "El estado de pago ya fue marcado como pagado y no puede ser modificado ni revertido.", "warning").then(function(){ window.location.href = "honorarios.php"; });</script>';
        exit;
    }

    if ($honorario_existente) {
        // Actualizar existente
        $sql = "UPDATE honorarios_medicos SET estado_pago = ?, updated_by = ?, updated_at = ?, monto_honorario = ?";
        $params = [$status, $usuario, $fecha_hora_hn, $monto_honorario];

        if ($status === 'pagado') {
            $sql .= ", fecha_pago = ?";
            $params[] = $fecha_hora_hn;
        }

        $sql .= " WHERE id_factura = ? AND id_doctor = ?";
        $params[] = $id_factura;
        $params[] = $id_doctor;

    } else {
        // Insertar nuevo
        $sql = "INSERT INTO honorarios_medicos (id_factura, id_doctor, monto_honorario, estado_pago, fecha_pago, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$id_factura, $id_doctor, $monto_honorario, $status, ($status === 'pagado' ? $fecha_hora_hn : null), $usuario, $fecha_hora_hn];
    }

    $stmt = $connect->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo '<script>swal("¡Actualizado!", "El estado del honorario se actualizó correctamente", "success").then(function(){ window.location.href = "honorarios.php"; });</script>';
    } else {
        echo '<script>swal("Error", "No se pudo actualizar el estado.", "error").then(function(){ window.location.href = "honorarios.php"; });</script>';
    }
} else {
    echo '<script>swal("Error", "Datos incompletos.", "error").then(function(){ window.location.href = "honorarios.php"; });</script>';
}
?>
</body>
</html> 