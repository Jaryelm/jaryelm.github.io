<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/backend/registros/session_check.php';
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

    <title>MEDIDATA - Puestos de Trabajo</title>
</head>
<body>
    
<?php include_once '../admin/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>

        <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'puestos_trabajo_usr.php')">Listar Puestos de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_puesto_trabajo_usr.php')">Registrar Puesto de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Puestos de Trabajo</h3>
                </div>
                <div class="table-responsive" style="overflow-x:auto;">
                    <?php 
                    try {
                        // Fetch positions for the modal select
                        $stmt_p = $connect->prepare("SELECT id, name FROM positions ORDER BY name ASC");
                        $stmt_p->execute();
                        $puestos_list = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

                        $sentencia = $connect_rrhh->prepare("SELECT pd.*, p.name FROM positions_details pd JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id ORDER BY pd.id DESC;");
                        $sentencia->execute();
                        $data = $sentencia->fetchAll(PDO::FETCH_OBJ);
                    } catch (Exception $e) {
                        $data = [];
                        $puestos_list = [];
                    }
                    ?>
                    <?php if(count($data) > 0): ?>
                        <table id="example" class="responsive-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data as $d): ?>
                                    <tr>
                                        <th scope="row"><?php echo $d->id ?></th>
                                        <td data-title="Nombre"><?php echo htmlspecialchars($d->name) ?></td>
                                        <td data-title="Descripción"><?php echo htmlspecialchars(mb_strimwidth($d->description, 0, 50, "...")) ?></td>
                                        <td data-title="Estado">
                                            <label class="switch">
                                                <input type="checkbox" class="status-toggle" data-id="<?=$d->id?>" <?=$d->deleted == '0' ? 'checked' : '' ;?>/> 
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <label title="Ver detalles y Editar" for="btns-modal-puesto-<?php echo $d->id; ?>" style="cursor:pointer;">
                                                <i class="fa fa-eye" style="color: #06adbf;"></i>
                                            </label>
                                            <?php include '../../backend/modal/md_puesto_trabajo.php'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table> 
                    <?php else: ?>
                        <div class="alert">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                            <strong>Aviso:</strong> No hay puestos de trabajo registrados.
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

    <script type="text/javascript">
    $(document).ready(function() {
        $('#example').DataTable({
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            }
        });

        $('.status-toggle').on('change', function() {
            var id = $(this).data('id');
            var status = $(this).is(':checked') ? 0 : 1;
            console.log("Cambiando estado de " + id + " a " + status);
        });
    });
    </script>
</body>
</html>
