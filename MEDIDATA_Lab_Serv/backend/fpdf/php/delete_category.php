<?php
    require_once('../../backend/bd/Conexion.php');
if(isset($_POST['delete_category'])){
////////////// Actualizar la tabla /////////
$consulta = "DELETE FROM `category` WHERE `idcat`=:idcat";
$sql = $connect-> prepare($consulta);
$sql -> bindParam(':idcat', $idcat, PDO::PARAM_INT);
$idcat=trim($_POST['idcat']);
$sql->execute();

if($sql->rowCount() > 0)
{
$count = $sql -> rowCount();
echo '<script type="text/javascript">
Swal.fire("Eliminado!", "Eliminado correctamente", "success").then(function() {
            window.location = "categoria.php";
        });
        </script>';
}
else{
    echo '<script type="text/javascript">
Swal.fire("Error!", "Error", "error").then(function() {
            window.location = "categoria.php";
        });
        </script>';

print_r($sql->errorInfo()); 
}
}// Cierra envio de guardado
?>


 

