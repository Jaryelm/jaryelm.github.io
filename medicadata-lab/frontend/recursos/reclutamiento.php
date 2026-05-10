<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>

<?php
// Conexión a la base de datos
$servername = "162.241.123.41";
$username = "medic9ue_moisesc";
$password = "Mrecords%7";
$dbname = "medic9ue_postulaciones";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">




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

<button class="button" onclick="cambiarColor(this, '../recursos/enfermera_nuevo.php')">Registrar Enfermeria</button>
<button class="button" onclick="cambiarColor(this, '../recursos/enfermera.php')">Enfermeria</button>
<button class="button" onclick="cambiarColor(this, '#')">Registrar Administrativo</button>
<button class="button" onclick="cambiarColor(this, '#')">Administrativo</button>
<button class="button" onclick="cambiarColor(this, '#')">Registrar Mantenimiento</button>
<button class="button" onclick="cambiarColor(this, '#')">Mantenimiento</button>
<button class="button" onclick="cambiarColor(this, '../medicos/nuevo.php')">Registrar Médicos</button>
<button class="button" onclick="cambiarColor(this, '../medicos/mostrar.php')">Médicos</button>
<button class="button" onclick="cambiarColor(this, '../recursos/reclutamiento.php')">Reclutamiento</button>
<button class="button" onclick="cambiarColor(this, '#')">Proceso de Entrevista</button>
<button class="button" onclick="cambiarColor(this, '../recursos/laboratorios_nuevo.php')">Registrar Área de Servicio</button>
<button class="button" onclick="cambiarColor(this, '../recursos/laboratiorios.php')">Äreas de Servicios</button>

<div class="data">
    <div class="content-data">
        <div class="head">
            <h3>Reclutamiento</h3>
        </div>
        <div class="table-responsive" style="overflow-x:auto;">
            <?php
            // Consulta para obtener los datos de la tabla "aplica"
            $sentencia = $conn->prepare("SELECT * FROM aplica ORDER BY fecha_registro DESC");
            $sentencia->execute();
            $result = $sentencia->get_result();
            $data = array();

            if ($result) {
                while ($r = $result->fetch_object()) {
                    $data[] = $r;
                }
            }
            ?>
            <?php if (count($data) > 0): ?>
                <table id="example" class="responsive-table">
                    <thead>
                        <tr>
                            <th scope="col">DNI</th>
                            <th scope="col">Nombre Completo</th>
                            <th scope="col">Puesto Aspirado</th>
                            <th scope="col">Num. Celular</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Fecha de Registro</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $d): ?>
                            <tr>
                                <th scope="row"><?php echo $d->numero_id; ?></th>
                                <td data-title="Nombre Completo"><?php echo $d->nombre_completo; ?></td>
                                <td data-title="Puesto Aspirado"><?php echo $d->puesto_aspirado; ?></td>
                                <td data-title="Num. Celular"><?php echo $d->whatsapp; ?></td>
                                <td data-title="Correo"><?php echo $d->correo; ?></td>
                                <td data-title="Fecha de Registro"><?php echo $d->fecha_registro; ?></td>
                                <td>
                                    <?php if (!empty($d->cv)): ?>
                                    <a title="Descargar CV" href="../../backend/php/download_cv.php?id=<?php echo htmlspecialchars($d->id); ?>">
                                        <i class="fa fa-download tooltip" style="font-size: 15px;"></i>
                                    </a>
                                    <?php else: ?>
                                        No se adjuntó CV
                                    <?php endif; ?>
                                </td>



                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                    <strong>Alerta!</strong> No hay datos disponibles.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Cerrar la conexión
$conn->close();
?>
  

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
        order: [[4, 'desc']], // Ordena por la quinta columna (fecha_registro) en orden descendente
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
//When user clicks the accept button
document.getElementById("acceptCookie").addEventListener("click", () => {
  //Create date object
  let d = new Date();
  //Increment the current time by 1 minute (cookie will expire after 1 minute)
  d.setMinutes(2 + d.getMinutes());
  //Create Cookie withname = myCookieName, value = thisIsMyCookie and expiry time=1 minute
  document.cookie = "myCookieName=thisIsMyCookie; expires = " + d + ";";
  //Hide the popup
  popUp.classList.add("hide");
  popUp.classList.remove("shows");
});
//Check if cookie is already present
const checkCookie = () => {
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
 <?php include_once '../../backend/php/delete_nurse.php' ?>
</body>
</html>