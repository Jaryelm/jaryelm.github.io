<?php
require_once('../../backend/bd/Conexion.php');
if(isset($_POST['delete_departament'])){
    $id = trim($_POST['id']);
    $consulta = "DELETE FROM `departaments` WHERE `id`=:id";
    $sql = $connect_rrhh->prepare($consulta);
    $sql->bindParam(':id', $id, PDO::PARAM_INT);
    $sql->execute();

    if($sql->rowCount() > 0)
    {
        echo '<script type="text/javascript">
Swal.fire("Eliminado!", "Departamento eliminado correctamente", "success").then(function() {
            window.location = "departamentos.php";
        });
        </script>';
    }
    else{
        echo '<script type="text/javascript">
Swal.fire("Error!", "Error al eliminar", "error").then(function() {
            window.location = "departamentos.php";
        });
        </script>';
    }
}
?>