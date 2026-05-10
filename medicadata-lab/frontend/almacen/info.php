<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">





    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">
                    
                </div>
            </form>
            
           
            <span class="divider"></span>
            <?php
include_once '../admin/perfil.php';
// incuir el archivo menu principal
?>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
        <?php
// Obtener la hora actual
$hora_actual = date('H'); // Obtiene la hora en formato de 24 horas (0-23)

if ($hora_actual >= 6 && $hora_actual < 12) {
    $saludo = "Buenos Días";
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = "Buenas Tardes";
} else {
    $saludo = "Buenas Noches";
}
?>

<h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

            <button class="button" onclick="location.href='venta.php'">Historial de Ventas</button>
            <button class="button" onclick="location.href='mostrar.php'">Lista de Medicamentos</button>
            <button class="button" onclick="location.href='compra_unificada.php'">Registrar Nuevo Medicamento</button>
            <button class="button" onclick="location.href='categoria_nuevo.php'">Registrar Nueva Categoria</button>
            <button class="button" onclick="location.href='categoria.php'">Categoria</button>
            <button class="button" onclick="location.href='registroarticulo.php'">Registro de Articulos</button>

              <?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT product.idprcd, product.codpro, product.nompro, category.idcat, category.nomcat, product.preprd, product.stock, product.state, product.fere FROM product INNER JOIN category ON product.idcat = category.idcat WHERE idprcd= '$id';");
 $sentencia->execute();

$data =  array();
if($sentencia){
  while($r = $sentencia->fetchObject()){
    $data[] = $r;
  }
}
   ?>
   <?php if(count($data)>0):?>
        <?php foreach($data as $d):?> 
           
<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off" onsubmit="return validacion()">
  <div class="containerss">
    <h1>Información de las medicinas</h1>

    <hr>
    <br>
    <label for="email"><b>Lote/Código de la medicina</b></label><span class="badge-warning">*</span>
    <input type="text" value="<?php echo $d->codpro; ?>" placeholder="ejm:  877578VNRB4" maxlength="14" name="medicode" required>

    <label for="email"><b>Nombre de la medicina</b></label><span class="badge-warning">*</span>
    <input type="text" value="<?php echo $d->nompro; ?>" placeholder="ejm:  PRADAXA 75 MG X 30 CÁPSULAS" name="mediname" required>

    <label for="psw"><b>Categoria de la medicina</b></label><span class="badge-warning">*</span>
    <select required name="medicate">
        <option><?php echo $d->nomcat; ?></option>
        
    </select>

    <label for="email"><b>Precio de la medicina</b></label><span class="badge-warning">*</span>
    <input type="text" value="<?php echo $d->preprd; ?>" placeholder="ejm: 25.90" name="mediprec" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required>

    <label for="email"><b>Stock de la medicina</b></label><span class="badge-warning">*</span>
    <input type="text" value="<?php echo $d->stock; ?>" placeholder="ejm: 90" name="medistoc" maxlength="9" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required>

    <hr>
   
   
  </div>
  
</form>
<?php endforeach; ?>
  
    <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>

        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
   
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

</body>
</html>


