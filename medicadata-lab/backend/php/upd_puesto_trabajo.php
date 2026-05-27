<?php
include_once '../registros/session_check.php';
$conexion_rrhh = $connect_rrhh; // Alias for compatibility

if (isset($_POST['upd_puesto'])) {
    $id = $_POST['id'];
    $id_positions = $_POST['id_position'];
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $updated_by = $name;

    try {
        $sql = "UPDATE positions_details SET id_positions = ?, description = ?, requirements = ?, updated_by = ? WHERE id = ?";
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([$id_positions, $description, $requirements, $updated_by, $id]);

        if ($result) {
            echo '<script type="text/javascript">
                swal("¡Actualizado!", "Puesto actualizado con éxito", "success").then(function() {
                    window.location.reload();
                });
            </script>';
        } else {
            echo '<script type="text/javascript">
                swal("Error", "No se pudo actualizar el puesto", "error").then(function() {
                    window.history.back();
                });
            </script>';
        }
    } catch (Exception $e) {
        error_log("Error upd_puesto_trabajo: " . $e->getMessage());
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
