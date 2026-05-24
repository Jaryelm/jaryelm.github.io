<?php
include_once '../../registros/session_check.php';

if (isset($_POST['add_puesto'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $created_by = $_POST['created_by'];

    try {
        $sql = "INSERT INTO puestos_trabajo (name, description, requirements, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([$name, $description, $requirements, $created_by]);

        if ($result) {
            echo "<script>alert('Puesto registrado con éxito'); window.location.href='../../../frontend/recursos_humanos/puestos_trabajo.php';</script>";
        } else {
            echo "<script>alert('Error al registrar el puesto'); window.history.back();</script>";
        }
    } catch (Exception $e) {
        error_log("Error add_puesto_trabajo: " . $e->getMessage());
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}
?>
