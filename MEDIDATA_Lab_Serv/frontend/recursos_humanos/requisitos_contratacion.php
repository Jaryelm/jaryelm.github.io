<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$vacantes = medidata_rrhh_vacantes_filtro();
$id_vacante = isset($_GET['id_vacante']) ? (int) $_GET['id_vacante'] : 0;
$rrhh_disponible = medidata_rrhh_pdo() !== null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">

    <title>MEDIDATA</title>
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
        <?php include __DIR__ . '/_rrhh_aviso.php'; ?>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Filtrar por Plaza Vacante</h3>
                </div>
                <form method="GET" action="" class="rrhh-filter-row">
                        <div class="form-group">
                            <label for="id_vacante">Seleccionar Vacante</label>
                            <select class="select2" name="id_vacante" id="id_vacante">
                                <option value="0">Todas las vacantes</option>
                                <?php foreach ($vacantes as $v): ?>
                                <option value="<?php echo $v['id']; ?>" <?php echo $id_vacante == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="rrhh-filter-actions">
                            <button type="submit" class="button rrhh-btn-inline">Filtrar</button>
                            <a href="requisitos_contratacion.php" class="pabtn rrhh-btn-inline">Limpiar</a>
                        </div>
                </form>
            </div>
        </div>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Listado de Requisitos de Contratación</h3>
                </div>
                <div class="table-responsive" style="overflow-x:auto;">
                    <?php
                    $data = medidata_rrhh_fetch_postulantes("p.status = 'Llenando Expediente'", $id_vacante);
                    ?>
                    <?php if(count($data) > 0): ?>
                        <table id="example" class="responsive-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Vacante</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">DNI</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Solicitud Llenada</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data as $d): ?>
                                    <tr>
                                        <th scope="row"><?php echo $d->id ?></th>
                                        <td data-title="Vacante"><?php echo htmlspecialchars($d->vacancy_name ?? 'N/A') ?></td>
                                        <td data-title="Nombre"><?php echo htmlspecialchars($d->fullname) ?></td>
                                        <td data-title="DNI"><?php echo htmlspecialchars($d->dni) ?></td>
                                        <td data-title="Email"><?php echo htmlspecialchars($d->email) ?></td>
                                        <td data-title="Solicitud Llenada">
                                            <?php 
                                            $filled = isset($d->has_filled_application) ? $d->has_filled_application : false;
                                            echo $filled ? '<span class="badge-rrhh badge-rrhh-yes">Sí</span>' : '<span class="badge-rrhh badge-rrhh-pending">No</span>'; 
                                            ?>
                                        </td>
                                        <td>
                                            <a title="Ver detalle" href="detalle_postulante.php?id=<?php echo $d->id ?>" class="fa fa-eye"></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table> 
                    <?php else: ?>
                        <div class="alert">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                            <strong>Aviso:</strong> No hay candidatos en etapa de llenado de expediente registrados para esta selección.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>  

        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

    <script src="../../backend/js/script.js"></script>
    <script src='../../backend/js/submenu.js'></script>
    
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
    });
    </script>
</body>
</html>
