<?php
include_once '../registros/session_check.php';
$conexion_rrhh = $connect_rrhh; // Alias for compatibility

if (isset($_POST['upd_vacante'])) {
    $id = $_POST['id'];
    $id_position = $_POST['id_position'];
    $benefits = $_POST['benefits'];
    $init_date = $_POST['init_date'];
    $end_date = $_POST['end_date'];
    $updated_by = $name;

    try {
        $sql = "UPDATE vacant_positions SET id_position = ?, benefits = ?, init_date = ?, end_date = ?, updated_by = ? WHERE id = ?";
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([$id_position, $benefits, $init_date, $end_date, $updated_by, $id]);

        if ($result) {
            echo '<script type="text/javascript">
                swal("¡Actualizado!", "Vacante actualizada con éxito", "success").then(function() {
                    window.location.reload();
                });
            </script>';
        } else {
            echo '<script type="text/javascript">
                swal("Error", "No se pudo actualizar la vacante", "error").then(function() {
                    window.history.back();
                });
            </script>';
        }
    } catch (Exception $e) {
        error_log("Error upd_vacante_trabajo: " . $e->getMessage());
        echo '<script type="text/javascript">
            swal("Error", "' . addslashes($e->getMessage()) . '", "error").then(function() {
                window.history.back();
            });
        </script>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
</head>
<body>
</body>
</html>
