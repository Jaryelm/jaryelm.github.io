<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';
// incuir el archivo de sesion login
$depto_map = [];
$pdoRrhh = medidata_rrhh_pdo();
if ($pdoRrhh) {
    try {
        $stmt_dept = $pdoRrhh->query("SELECT id, name FROM departaments");
        while ($row = $stmt_dept->fetch(PDO::FETCH_ASSOC)) {
            $depto_map[$row['id']] = $row['name'];
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
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

        <button class="button" onclick="cambiarColor(this, 'enfermera.php')">Personal Activo</button>
        <button class="button" onclick="cambiarColor(this, 'enfermera_ex.php')">Ex Enfermería</button>
        <button class="button" onclick="cambiarColor(this, 'enfermera_nuevo.php')">Registrar Enfermería</button>
<div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Enfermería Activa</h3>
                      

                    </div>
                   <div class="table-responsive" style="overflow-x:auto;">
                       <?php 
$sentencia = $connect->prepare("
                        SELECT sa.*, p.name AS position_name 
                        FROM nurse sa 
                        LEFT JOIN positions p ON sa.id_cargo = p.id 
                        WHERE sa.state = '1' 
                        ORDER BY sa.idnur DESC
                    ");
 $sentencia->execute();
$data =  array();
if($sentencia){
  while($r = $sentencia->fetchObject()){
    $data[] = $r;
  }
}
     ?>
     <?php if(count($data)>0):?>
         <table id="example" class="responsive-table">
                        <thead>
                            <tr>
                                <th>TIPO DE EMPLEADO</th>
                                <th>N°</th>
                                <th>DNI</th>
                                <th>APELLIDOS</th>
                                <th>NOMBRES</th>
                                <th>SEXO</th>
                                <th>AREA</th>
                                <th>SALARIO</th>
                                <th>N°CUENTA</th>
                                <th>FECHA DE INGRESO</th>
                                <th>CONTACTO</th>
                                <th>CORREO PERSONAL / INSTITUCIONAL</th>
                                <th>FECHA DE NACIMIENTO</th>
                                <th>MARCAJE</th>
                                <th>LOKER</th>
                                <th>CONTRATO</th>
                                <th>ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $d): ?>
                            <tr>
                                <td>
                                    <select class="inline-select" data-id="<?php echo (int) $d->idnur; ?>" data-field="tipo_empleado" data-table="nurse" data-idcol="idnur" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer;">
                                        <option value="Permanente" <?php echo (($d->tipo_empleado ?? '') == 'Permanente') ? 'selected' : ''; ?>>Permanente</option>
                                        <option value="Temporal" <?php echo (($d->tipo_empleado ?? '') == 'Temporal') ? 'selected' : ''; ?>>Temporal</option>
                                        <option value="Tiempo parcial" <?php echo (($d->tipo_empleado ?? '') == 'Tiempo parcial') ? 'selected' : ''; ?>>Tiempo parcial</option>
                                    </select>
                                </td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="num_empleado" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->num_empleado ?? '—'); ?></td>
                                <th scope="row" class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="numide" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->numide); ?></th>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="apenur" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->apenur); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="nomnur" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->nomnur); ?></td>
                                <td>
                                    <select class="inline-select" data-id="<?php echo (int) $d->idnur; ?>" data-field="sexnur" data-table="nurse" data-idcol="idnur" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer;">
                                        <option value="Masculino" <?php echo ($d->sexnur == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="Femenino" <?php echo ($d->sexnur == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="inline-select" data-id="<?php echo (int) $d->idnur; ?>" data-field="id_departamento" data-table="nurse" data-idcol="idnur" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer; min-width: 120px;">
                                        <option value="">—</option>
                                        <?php foreach ($depto_map as $id_dept => $name_dept): ?>
                                            <option value="<?php echo $id_dept; ?>" <?php echo ($d->id_departamento == $id_dept) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name_dept); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="salario" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->salario ?? ''); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="cuenta_bac" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->cuenta_bac ?? '—'); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="fecha_ingreso" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Ej: 2024-01-30"><?php echo htmlspecialchars($d->fecha_ingreso ?? '—'); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="telefono" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->telefono ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars(($d->correo_personal ?? '—') . ' / ' . ($d->correo_institucional ?? '—')); ?></td>
                                <td><?php echo htmlspecialchars($d->nacinur); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="id_biometrico" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->id_biometrico ?? '—'); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idnur; ?>" data-field="num_locker" data-table="nurse" data-idcol="idnur" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->num_locker ?? '—'); ?></td>
                                <td>
                                    <?php if (!empty($d->url_contrato)): ?>
                                        <a href="../../backend/php/view_staff_doc.php?id=<?php echo (int) $d->idnur; ?>&doc=contrato" target="_blank" class="badge-success" style="padding:4px; text-decoration:none;"><i class="bx bx-file"></i> Ver</a>
                                    <?php else: ?>
                                        <span class="badge-warning" style="padding:4px;">N/D</span>
                                    <?php endif; ?>
                                    <br>
                                    <label class="badge-primary" style="padding:4px; cursor:pointer; display:inline-block; margin-top:4px;" onclick="document.getElementById('upload_contrato_<?php echo $d->idnur; ?>').click();">
                                        <i class="bx bx-upload"></i> Subir
                                    </label>
                                    <input type="file" id="upload_contrato_<?php echo $d->idnur; ?>" style="display:none;" accept=".pdf,.jpg,.png" onchange="uploadContract(this, <?php echo $d->idnur; ?>, 'nurse', 'idnur')">
                                </td>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" class="staff-state-toggle" data-id="<?php echo (int) $d->idnur; ?>" <?php echo $d->state == '1' ? 'checked' : ''; ?>/>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <a title="Actualizar" href="enfermera_editar.php?id=<?php echo (int) $d->idnur; ?>" class="fa fa-pencil tooltip"></a>
                                    <a title="Eliminar" href="#" class="fa fa-trash tooltip btn-delete-staff" data-id="<?php echo (int) $d->idnur; ?>"></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table> 
         <?php else:?>
  
    <div class="alert">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <strong>Danger!</strong> No hay datos.
    </div>
    <?php endif; ?>
                    </div>
                </div>
            </div>  

        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
    <script src="../../backend/registros/script/tabla_personal_staff.js"></script>
    <script>
    window.MEDIDATA_STAFF_NURSE = {
        toggleSelector: '.nurse-state-toggle',
        deleteSelector: '.btn-delete-nurse',
        toggleUrl: '../../backend/php/toggle_nurse_state.php',
        deleteUrl: '../../backend/php/delete_nurse.php',
        idParam: 'idnur',
        deleteTitle: '¿Eliminar enfermero(a)?',
        deleteFn: 'deleteNurse'
    };
    </script>
    
    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script type="text/javascript">
$(document).ready(function() {
    $('#example').DataTable({
        pageLength: 10, // Establece explícitamente 10 registros por página
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
            "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
            "sSearch": "Buscar:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        }
    });
});
</script>

 <script type="text/javascript">
    let popUp = document.getElementById("cookiePopup");
    let acceptCookieBtn = document.getElementById("acceptCookie");
//When user clicks the accept button
if (acceptCookieBtn) {
  acceptCookieBtn.addEventListener("click", () => {
    //Create date object
    let d = new Date();
    //Increment the current time by 1 minute (cookie will expire after 1 minute)
    d.setMinutes(2 + d.getMinutes());
    //Create Cookie withname = myCookieName, value = thisIsMyCookie and expiry time=1 minute
    document.cookie = "myCookieName=thisIsMyCookie; expires = " + d + ";";
    //Hide the popup
    if (popUp) {
      popUp.classList.add("hide");
      popUp.classList.remove("shows");
    }
  });
}
//Check if cookie is already present
const checkCookie = () => {
  if (!popUp) return;
  //Read the cookie and split on "="
  let input = document.cookie.split("=");
  //Check for our cookie
  if (input[0] == "myCookieName") {
    //Hide the popup
    popUp.classList.add("hide");
    popUp.classList.remove("shows");
  } else {
    //Show the popup
    popUp.classList.add("shows");
    popUp.classList.remove("hide");
  }
};
//Check if cookie exists when page loads
window.onload = () => {
  setTimeout(() => {
    checkCookie();
  }, 2000);
};
    </script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
 <?php include_once '../../backend/php/delete_nurse.php' ?>
</body>
</html>


