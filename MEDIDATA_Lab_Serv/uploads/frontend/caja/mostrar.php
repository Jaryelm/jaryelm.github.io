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
    
<?php
include_once '../caja/menu.php';
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
include_once '../caja/perfil.php';
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

            <button class="button" onclick="cambiarColor(this, '../caja/new_sale.php')">Nueva Venta</button>
            <button class="button" onclick="cambiarColor(this, '../caja/cart.php')">Procesar Venta</button>
            <button class="button" onclick="cambiarColor(this, '#')">Cotizaciones</button>
            <button class="button" onclick="cambiarColor(this, '#')">Estados de Cuenta</button>
            <button class="button" onclick="cambiarColor(this, '../caja/venta.php')">Resumen de Ventas</button>
            <button class="button" onclick="cambiarColor(this, '../caja/mostrar.php')">Resumen de Citas</button>
            
          <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Citas</h3>
                      

                    </div>
                   <div class="table-responsive" style="overflow-x:auto;">
                       <?php 
$sentencia = $connect->prepare("SELECT events.id, events.title, patients.idpa, patients.numhs,patients.nompa, patients.apepa, doctor.idodc, doctor.ceddoc, doctor.nodoc, doctor.apdoc, laboratory.idlab, laboratory.nomlab, events.start, events.end, events.color, events.state,events.chec,events.monto FROM events INNER JOIN patients ON events.idpa = patients.idpa INNER JOIN doctor ON events.idodc = doctor.idodc INNER JOIN laboratory ON events.idlab = laboratory.idlab ORDER BY id DESC;");
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
                    <th scope="col">Pacientes</th>
                    <th scope="col">Motivo</th>
                    <th scope="col">Médico</th>
                    <th scope="col">Especialidad</th>
                    <th scope="col">Fecha inicio</th>
                    <th scope="col">Fecha fin</th>
                    <th scope="col">Total</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $d):?>
                    <tr>
                        <th scope="row"><?php echo $d->nompa ?>&nbsp;<?php echo $d->apepa ?></th>
                        <td data-title="Cita"><?php echo $d->title ?></td>
                        <td data-title="Médico"><?php echo $d->nodoc ?>&nbsp;<?php echo $d->apdoc ?></td>
                        <td data-title="Laboratorio"><?php echo $d->nomlab ?></td>
                        <td data-title="Fecha inicio"><?php echo $d->start ?></td>
                        <td data-title="Fecha fin"><?php echo $d->end ?></td>
                        <td data-title="Total"><?php echo $d->monto ?> LPS.</td>
                        <td data-title="Estado">
                        <label class="switch">
                          <input type="checkbox" id="<?=$d->id?>" value="<?=$d->chec ?>" <?=$d->chec == '1' ? 'checked' : '' ;?>/> 

                          <span class="slider"></span>
                        </label>
                        </td>
                        <td>

                            

                <!-- MAIN <a title="Información" href="../citas/info.php?id=<?php echo $d->id ?>" class="fa fa-info"></a>-->
               
                
                <?php 
                                if ($d->chec == '0') {
                                    // code...
                echo '<a title="Pago"  href="../citas/money.php?id='.$d->idpa.'" class="fa fa-money"></a>';

                                }else {
                echo '<a title="Boleta"  href="../citas/documento.php?id='.$d->id.'" class="fa fa-file-text-o"></a>';
                echo '<a title="Ticket"  href="../citas/ticket.php?id='.$d->id.'" class="fa fa-ticket"></a>';                  
                                }

                             ?>

                             
                          
                        </td>
                    </tr>
                    <?php endforeach; ?>
            </tbody>
         </table> 
         <?php else:?>
  
    <div class="alert">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <strong>Error!</strong> No hay datos.
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

    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- Función Cierre de Caja -->
    <script src='../../backend/registros/script/cierre_caja.js'></script>
 
</body>
</html>


