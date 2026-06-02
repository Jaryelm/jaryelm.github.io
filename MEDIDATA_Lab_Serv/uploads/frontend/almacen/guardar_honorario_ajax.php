<?php
require '../../backend/bd/Conexion.php';
session_start();
date_default_timezone_set('America/Tegucigalpa');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <meta charset="UTF-8">
    <title>MEDIDATA</title>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
</head>
<body>
<?php
$id_factura = isset($_POST['id_factura']) ? intval($_POST['id_factura']) : 0;
$id_doctor = isset($_POST['id_doctor']) ? intval($_POST['id_doctor']) : 0;
$porcentaje = isset($_POST['porcentaje_honorario']) ? floatval($_POST['porcentaje_honorario']) : 0;
$usuario = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Desconocido';
$fecha_hora_hn = date('Y-m-d H:i:s');

// Validar que el doctor existe
$stmt = $connect->prepare("SELECT COUNT(*) FROM doctor WHERE idodc = ?");
$stmt->execute([$id_doctor]);
if ($stmt->fetchColumn() == 0) {
    echo '<script>Swal.fire("Error", "El médico seleccionado no existe en la base de datos.", "error").then(function(){ window.location.href = "honorarios.php"; });</script>';
    exit;
}
// Obtener total de la factura
$stmt = $connect->prepare("SELECT total_price FROM orders WHERE idord = ?");
$stmt->execute([$id_factura]);
$total = $stmt->fetchColumn();
$monto = $total * $porcentaje / 100;
$sql = "INSERT INTO honorarios_medicos (id_factura, id_doctor, porcentaje_honorario, monto_honorario, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE porcentaje_honorario = VALUES(porcentaje_honorario), monto_honorario = VALUES(monto_honorario), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)";
$stmt = $connect->prepare($sql);
$ok = $stmt->execute([$id_factura, $id_doctor, $porcentaje, $monto, $usuario, $fecha_hora_hn]);
if ($ok) {
    echo '<script>Swal.fire("¡Guardado!", "Honorario actualizado correctamente", "success").then(function(){ window.location.href = "honorarios.php"; });</script>';
} else {
    echo '<script>Swal.fire("Error", "Error al guardar el honorario.", "error").then(function(){ window.location.href = "honorarios.php"; });</script>';
}
?>
</body>
</html> 