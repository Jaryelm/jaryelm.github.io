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
            <button class="button" onclick="location.href='mostrar.php'">Lista de Inventario</button>
            <button class="button" onclick="location.href='compra_unificada.php'">Registro de Nuevo Producto al Inventario</button>
            <button class="button" onclick="location.href='ingreso.php'">Ingreso de Producto al Inventario</button>
            <button class="button" onclick="location.href='categoria_nuevo.php'">Registrar Categoria</button>
            <button class="button" onclick="location.href='categoria.php'">Categorias</button>
            <button class="button" onclick="location.href='new_sale.php'">Nueva Venta</button>
    
    <?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT * FROM category  WHERE idcat= '$id';");
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
    <h1>Actualizar categorias</h1>

    <hr>
    <br>
    <label for="email"><b>Nombre de la categoria</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Analgésicos" value="<?php echo $d->nomcat; ?>" name="catename" required>
    <input type="hidden" name="cateid" value="<?php echo $d->idcat; ?>">
   

    <hr>
    <button type="submit" name="upd_category" class="registerbtn">Guardar</button>
 
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
 <?php include_once '../../backend/php/upd_category.php' ?>
</body>
</html>


