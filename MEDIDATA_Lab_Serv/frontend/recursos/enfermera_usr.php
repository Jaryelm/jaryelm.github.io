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

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">




    <title>MEDIDATA</title>
</head>
<body>
    
<?php include_once '../recursos_humanos/menu.php'; ?>

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
<?php include_once '../recursos_humanos/perfil.php'; ?>
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
        <button class="button" onclick="cambiarColor(this, 'enfermera_usr.php')">Personal Activo</button>
        <button class="button" onclick="cambiarColor(this, 'enfermera_ex_usr.php')">Ex Enfermería</button>
        <button class="button" onclick="cambiarColor(this, 'enfermera_nuevo_usr.php')">Registrar Enfermería</button>
<div class="data">
                <div class="content-data">
                        <h3>Enfermería Activa</h3>

                    </div>
                   <div class="table-responsive" style="overflow-x:auto;">
                       <?php 
                       
$sentencia = $connect->prepare("SELECT * FROM nurse WHERE state = '1' ORDER BY idnur DESC;");
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
                    <th scope="col">DNI</th>
                    <th scope="col">Enfermera(o)</th>
                    <th scope="col">Sexo</th>
                    <th scope="col">Fecha de Nacimiento</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $d):?>
                    <tr>
                        <th scope="row"><?php echo $d->numide ?></th>
                        <td data-title="Paciente"><?php echo $d->nomnur ?>&nbsp;<?php echo $d->apenur ?></td>
                        <td data-title="Sexo"><?php echo $d->sexnur ?></td>
                        <td data-title="Grupo"><?php echo $d->nacinur ?></td>
                        <td data-title="Estado">
                            <label class="switch">
                                <input type="checkbox" class="nurse-state-toggle" data-id="<?php echo (int) $d->idnur; ?>" <?php echo (isset($d->state) ? $d->state : '1') == '1' ? 'checked' : ''; ?>/>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>
                            <a title="Actualizar" href="../recursos/enfermera_editar.php?id=<?php echo $d->idnur ?>" class="fa fa-pencil tooltip"></a>                           
                            <a title="Historial médico" href="../pacientes/otros_anexos.php?id=<?php echo $d->idpa ?>" class="fa fa-stethoscope"></a>        
                            <a title="Eliminar" href="#" class="fa fa-trash tooltip btn-delete-nurse" data-id="<?php echo (int) $d->idnur; ?>"></a>
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


