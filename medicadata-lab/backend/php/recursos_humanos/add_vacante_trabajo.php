<?php
include_once '../../registros/session_check.php';

if (isset($_POST['add_vacante'])) {
    $id_position = $_POST['id_position'];
    $benefits = $_POST['benefits'];
    $init_date = $_POST['init_date'];
    $end_date = $_POST['end_date'];
    $created_by = $_POST['created_by'];

    try {
        $sql = "INSERT INTO vacantes_trabajo (id_position, benefits, init_date, end_date, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([$id_position, $benefits, $init_date, $end_date, $created_by]);

        if ($result) {
            echo "<script>alert('Vacante registrada con éxito'); window.location.href='../../../frontend/recursos_humanos/vacantes_trabajo.php';</script>";
        } else {
            echo "<script>alert('Error al registrar la vacante'); window.history.back();</script>";
        }
    } catch (Exception $e) {
        error_log("Error add_vacante_trabajo: " . $e->getMessage());
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}
?>
