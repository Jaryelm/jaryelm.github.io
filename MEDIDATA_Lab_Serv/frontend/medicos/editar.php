<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">



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

<?php $medicos_nav_rrhh = false; include __DIR__ . '/_botones_medicos.php'; ?>
           
           <!-- multistep form -->
<?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT * FROM doctor  WHERE idodc= '$id';");
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

<form action="" enctype="multipart/form-data" method="POST" autocomplete="off">
    <div class="containerss">
        <h1>Actualizar Médico</h1>
        <br>
        <hr>

        <label for="especialidad"><b>Especialidad del médico</b></label><span class="badge-warning">*</span>
        <input type="text" id="especialidad" name="doces" required placeholder="Ingrese la especialidad" value="<?php echo $d->nomesp; ?>">
        
        <input type="hidden" name="midp" value="<?php echo $d->idodc; ?>">

        <label for="dni"><b>DNI del médico</b></label><span class="badge-warning">*</span>
        <input type="text" maxlength="15" name="docce" value="<?php echo $d->ceddoc; ?>" required>

        <label for="nombre"><b>Nombre del médico</b></label><span class="badge-warning">*</span>
        <input type="text" name="docna" value="<?php echo $d->nodoc; ?>" required>

        <label for="apellido"><b>Apellido del médico</b></label><span class="badge-warning">*</span>
        <input type="text" name="docap" value="<?php echo $d->apdoc; ?>" required>

        <label for="direccion"><b>Dirección del médico</b></label><span class="badge-warning">*</span>
        <input type="text" name="docdi" value="<?php echo $d->direcd; ?>" required>

        <label for="correo"><b>Correo electrónico del médico</b></label><span class="badge-warning">*</span>
        <input type="email" name="doccorr" value="<?php echo $d->corr; ?>" required>

        <label for="genero"><b>Género del médico</b></label><span class="badge-warning">*</span>
<select required name="docge" id="gep">
    <option value="Masculino" <?php if ($d->sexd == 'Masculino') echo 'selected'; ?>>Masculino</option>
    <option value="Femenino" <?php if ($d->sexd == 'Femenino') echo 'selected'; ?>>Femenino</option>
</select>

        <label for="telefono"><b>Teléfono del médico</b></label><span class="badge-warning">*</span>
        <input type="text" maxlength="13" name="docte" value="<?php echo $d->phd; ?>" required>

        <label for="nacimiento"><b>Nacimiento del médico</b></label><span class="badge-warning">*</span>
        <input type="date" name="docda" value="<?php echo $d->nacd; ?>" required>

        <hr>
        <button type="submit" name="upd_doctors" class="registerbtn">Guardar</button>
        <button type="button" class="registerbtn btn-delete-doctor" style="background:#c0392b;margin-top:10px;"
            data-id="<?php echo (int) $d->idodc; ?>" data-redirect="mostrar.php">Eliminar médico</button>
    </div>
</form>

<?php endforeach; ?>
  
    <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>

        </main>
        <!-- MAIN -->
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <?php include_once '../../backend/php/upd_doctor.php' ?>
    <script src="../../backend/registros/script/tabla_medicos.js"></script>
    <script src='../../backend/js/submenu.js'></script>
    <script src="../../backend/registros/script/botones_color.js"></script>

   
</body>
</html>


