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
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">

    <title>MEDIDATA - Departamentos</title>
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

          <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Departamentos</h3>
                    </div>
                   <div class="table-responsive" style="overflow-x:auto;">
                       <?php 
$sentencia = $connect_rrhh->prepare("SELECT * FROM departaments ORDER BY id DESC;");
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
                    <th scope="col">Código</th>
                    <th scope="col">Departamento</th>
                    <th scope="col">Jefe</th>
                    <th scope="col">Correo</th>
                    <th scope="col">Teléfono</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $d):?>
                    <tr>
                        <th scope="row"><?php echo $d->departament_code ?></th>
                        <td><?php echo $d->name ?></td>
                        <td><?php echo $d->head_departament ?></td>
                        <td><?php echo $d->email ?></td>
                        <td><?php echo $d->phone ?></td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" class="state-toggle" data-id="<?php echo $d->id; ?>" <?php echo ($d->status == 'Activo') ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>
                            <a title="Editar" href="departamentos_editar.php?id=<?php echo $d->id ?>" class="fa fa-edit" style="color:#06adbf; background:none; border:none; cursor:pointer; font-size: 1.2rem; margin-right: 10px; text-decoration:none;"></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
            </tbody>
         </table> 
         <?php else:?>
  
    <div class="alert">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <strong>¡Aviso!</strong> No hay datos registrados.
    </div>
    <?php endif; ?>
                    </div>
                </div>
            </div>  

        </main>
    </section>
    
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src='../../backend/js/submenu.js'></script>
    <script src="../../backend/registros/script/botones_color.js"></script>
    
    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script type="text/javascript">
$(document).ready(function() {
    $('#example').DataTable({
        pageLength: 10,
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

    $('.state-toggle').on('change', function() {
        const id = $(this).data('id');
        const state = this.checked ? 1 : 0;
        
        $.ajax({
            type: 'POST',
            url: '../../backend/php/toggle_departament_state.php',
            data: { id: id, state: state },
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    Swal.fire('Error', response.message, 'error');
                    window.location.reload();
                }
            },
            error: function() {
                Swal.fire('Error', 'Ocurrió un error al cambiar el estado', 'error');
                window.location.reload();
            }
        });
    });
});
</script>
</body>
</html>