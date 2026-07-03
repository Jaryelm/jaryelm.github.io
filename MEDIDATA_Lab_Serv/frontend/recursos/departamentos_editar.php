<?php
include_once '../../backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">

    <title>MEDIDATA - Editar Departamento</title>
</head>
<body>

<?php
include_once '../admin/menu.php';
?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">
                </div>
            </form>
            <span class="divider"></span>
            <?php
include_once '../admin/perfil.php';
?>
        </nav>

        <main>
        <?php
        $hora_actual = date('H');
        if ($hora_actual >= 6 && $hora_actual < 12) {
            $saludo = "Buenos Días";
        } elseif ($hora_actual >= 12 && $hora_actual < 18) {
            $saludo = "Buenas Tardes";
        } else {
            $saludo = "Buenas Noches";
        }
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'departamentos_nuevo.php')">Registrar Departamento</button>
        <button class="button" onclick="cambiarColor(this, 'departamentos.php')">Departamentos</button>
           
<?php 
 $id = $_GET['id'];
 $sentencia = $connect_rrhh->prepare("SELECT * FROM departaments WHERE id= :id;");
 $sentencia->bindParam(':id', $id, PDO::PARAM_INT);
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

<form action="" method="POST" autocomplete="off">
  <div class="containerss">
    <h1>Actualizar Departamento</h1>
    <hr>
    <input type="hidden" name="dep_id" value="<?php echo $d->id; ?>">

    <label for="dep_code"><b>Código Departamento</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: RRHH-001" value="<?php echo $d->departament_code; ?>" name="dep_code" required>

    <label for="dep_name"><b>Nombre Departamento</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Recursos Humanos" value="<?php echo $d->name; ?>" name="dep_name" required>

    <label for="dep_head"><b>Jefe Departamento</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="Nombre completo" value="<?php echo $d->head_departament; ?>" name="dep_head" required>

    <label for="dep_description"><b>Descripción</b></label><span class="badge-warning">*</span>
    <textarea name="dep_description" style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;" rows="3" required><?php echo $d->description; ?></textarea>

    <label for="dep_email"><b>Correo Departamento</b></label>
    <input type="email" placeholder="rrhh@medicasa.hn" value="<?php echo $d->email; ?>" name="dep_email">

    <label for="dep_phone"><b>Teléfono Departamento</b></label>
    <input type="text" placeholder="2234-1001" value="<?php echo $d->phone; ?>" name="dep_phone" maxlength="10">

    <label for="dep_status"><b>Estado</b></label><span class="badge-warning">*</span>
    <select class="select2" required name="dep_status">
        <option value="<?php echo $d->status; ?>"><?php echo $d->status; ?></option>
        <option value="">-------------------------</option>
        <option value="Activo">Activo</option>
        <option value="Inactivo">Inactivo</option>
    </select>

    <label for="dep_observations"><b>Observaciones</b></label>
    <textarea name="dep_observations" style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;" rows="2"><?php echo $d->observations; ?></textarea>

    <hr>
    <button type="submit" name="upd_departament" class="registerbtn">Guardar</button>
  </div>
</form>

<?php endforeach; ?>
    <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>

        </main>
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
    <?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_foot.php'; ?>
    
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/multistep.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <?php include_once '../../backend/php/upd_departament.php' ?>
    <script src='../../backend/js/submenu.js'></script>
    <script src="../../backend/registros/script/botones_color.js"></script>
</body>
</html>