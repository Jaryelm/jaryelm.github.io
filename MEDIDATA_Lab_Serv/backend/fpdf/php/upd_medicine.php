<?php  
if(isset($_POST['upd_medicine']))
{
    $idprcd = $_POST['meid'];
    $codpro = $_POST['medicode'];
    $nompro = $_POST['mediname'];
    $idcat = $_POST['medicate'];
    $preprd = $_POST['mediprec'];
    $stock = $_POST['medistoc'];    
    $stock_minimo = $_POST['stock_minimo']; // Nuevo campo stock mínimo
    $fecha_vencimiento = trim($_POST['fecha_vencimiento']); // Nuevo campo
    $via_administracion = trim($_POST['via_administracion']); // Nuevo campo
    $concentracion = trim($_POST['concentracion']); // Nuevo campo
    $forma_farmaceutica = trim($_POST['forma_farmaceutica']); // Nuevo campo
    $presentacion = trim($_POST['presentacion']); // Nuevo campo
    $sub_linea = trim($_POST['sub_linea']); // Nuevo campo
    $linea = trim($_POST['linea']); // Nuevo campo
    $comision = trim($_POST['comision']); // Nuevo campo
    $margen_ganancia = trim($_POST['margen_ganancia']); // Nuevo campo
    $impuesto = trim($_POST['impuesto']); // Nuevo campo
    $precio_venta = trim($_POST['precio_venta']); // Nuevo campo

    try {
        // Modificar la consulta para incluir todos los nuevos campos
        $query = "UPDATE product SET 
                    codpro=:codpro,
                    nompro=:nompro,
                    idcat=:idcat,
                    preprd=:preprd,
                    stock=:stock,
                    stock_minimo=:stock_minimo,
                    fecha_vencimiento=:fecha_vencimiento,
                    via_administracion=:via_administracion,
                    concentracion=:concentracion,
                    forma_farmaceutica=:forma_farmaceutica,
                    presentacion=:presentacion,
                    sub_linea=:sub_linea,
                    linea=:linea,
                    comision=:comision,
                    margen_ganancia=:margen_ganancia,
                    impuesto=:impuesto,
                    precio_venta=:precio_venta
                  WHERE idprcd=:idprcd LIMIT 1";

        $statement = $connect->prepare($query);
        $data = [
            ':codpro' => $codpro,
            ':nompro' => $nompro,
            ':idcat' => $idcat,
            ':preprd' => $preprd,
            ':stock' => $stock,
            ':stock_minimo' => $stock_minimo,
            ':fecha_vencimiento' => $fecha_vencimiento,
            ':via_administracion' => $via_administracion,
            ':concentracion' => $concentracion,
            ':forma_farmaceutica' => $forma_farmaceutica,
            ':presentacion' => $presentacion,
            ':sub_linea' => $sub_linea,
            ':linea' => $linea,
            ':comision' => $comision,
            ':margen_ganancia' => $margen_ganancia,
            ':impuesto' => $impuesto,
            ':precio_venta' => $precio_venta,
            ':idprcd' => $idprcd
        ];

        $query_execute = $statement->execute($data);
        if($query_execute)
        {
            echo '<script type="text/javascript">
            Swal.fire("Actualizado!", "Actualizado correctamente", "success").then(function() {
                window.location = "mostrar.php";
            });
            </script>';
            exit(0);
        }
        else
        {
            echo '<script type="text/javascript">
            Swal.fire("Error!", "Error al actualizar", "error").then(function() {
                window.location = "mostrar.php";
            });
            </script>';
            exit(0);
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}