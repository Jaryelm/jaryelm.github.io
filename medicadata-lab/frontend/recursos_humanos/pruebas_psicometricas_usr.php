<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/backend/registros/session_check.php';

// Fetch vacancies for the filter dropdown
try {
    $stmt_vacantes = $connect_rrhh->prepare("SELECT v.id, p.name FROM vacantes_trabajo v JOIN puestos_trabajo p ON v.id_position = p.id WHERE v.deleted = 0 ORDER BY p.name ASC");
    $stmt_vacantes->execute();
    $vacantes = $stmt_vacantes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $vacantes = [];
}

// Filter logic
$id_vacante = isset($_GET['id_vacante']) ? (int)$_GET['id_vacante'] : 0;
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

    <title>MEDIDATA - Pruebas Psicométricas</title>
</head>
<body>
    
<?php include_once './menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once './perfil.php'; ?>
        </nav>

        <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Filtrar por Plaza Vacante</h3>
                </div>
                <form method="GET" action="" style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <div class="form-group" style="flex: 1;">
                            <label for="id_vacante">Seleccionar Vacante</label>
                            <select name="id_vacante" id="id_vacante" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                                <option value="0">Todas las vacantes</option>
                                <?php foreach ($vacantes as $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php echo $id_vacante == $v['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="button" style="margin-bottom: 0;">Filtrar</button>
                        <a href="pruebas_psicometricas_usr.php" class="button" style="background-color: #666; margin-bottom: 0;">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Listado de Pruebas Psicométricas</h3>
                </div>
                <div class="table-responsive" style="overflow-x:auto;">
                    <?php 
                    try {
                        $query = "SELECT p.*, pt.name as vacancy_name 
                                  FROM postulantes p 
                                  LEFT JOIN vacantes_trabajo v ON p.id_vacant_position = v.id 
                                  LEFT JOIN puestos_trabajo pt ON v.id_position = pt.id 
                                  WHERE p.deleted = 0 AND p.status = 'Pruebas Psicometricas'";
                        
                        if ($id_vacante > 0) {
                            $query .= " AND p.id_vacant_position = :id_vacante";
                        }
                        
                        $query .= " ORDER BY p.id DESC";
                        
                        $sentencia = $connect_rrhh->prepare($query);
                        if ($id_vacante > 0) {
                            $sentencia->bindParam(':id_vacante', $id_vacante, PDO::PARAM_INT);
                        }
                        $sentencia->execute();
                        $data = $sentencia->fetchAll(PDO::FETCH_OBJ);
                    } catch (Exception $e) {
                        $data = [];
                    }
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
                                            echo $filled ? '<span class="badge" style="background-color: green; color: white; padding: 2px 5px; border-radius: 3px;">Sí</span>' : '<span class="badge" style="background-color: orange; color: white; padding: 2px 5px; border-radius: 3px;">No</span>'; 
                                            ?>
                                        </td>
                                        <td>
                                            <a title="Ver detalle" href="detalle_pruebas_psicometricas_usr.php?id=<?php echo $d->id ?>" class="fa fa-eye"></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table> 
                    <?php else: ?>
                        <div class="alert">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                            <strong>Aviso:</strong> No hay candidatos en etapa de pruebas psicométricas registrados para esta selección.
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
