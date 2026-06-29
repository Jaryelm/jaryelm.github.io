<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
require_once '../../backend/registros/rrhh_guard.php';
medidata_staff_ensure_tables($connect);

$depto_map = [];
$salary_level_map = [];
$pdoRrhh = medidata_rrhh_pdo();
if ($pdoRrhh) {
    try {
        $stmt_dept = $pdoRrhh->query("SELECT id, name FROM departaments");
        while ($row = $stmt_dept->fetch(PDO::FETCH_ASSOC)) {
            $depto_map[$row['id']] = $row['name'];
        }
        $stmt_sl = $pdoRrhh->query("SELECT id, level_name, position_category FROM salary_levels WHERE deleted = 0");
        while ($row = $stmt_sl->fetch(PDO::FETCH_ASSOC)) {
            $salary_level_map[$row['id']] = $row['level_name'] . ' - ' . $row['position_category'];
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
    <link rel="stylesheet" href="../../backend/css/cards.css">
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>
    <?php include_once './menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once './perfil.php'; ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

            <div class="rrhh-tab-nav">
                <a href="lista_colaboradores_usr.php" class="button tab-button">Lista de Colaboradores</a>
                <a href="lista_colaboradores_medicos_usr.php" class="button tab-button active">Lista de Médicos</a>
                <a href="lista_excolaboradores_usr.php" class="button tab-button">Lista de Excolaboradores</a>
            </div>

            <div class="data">
                <div class="content-data">
                    <div class="table-title">
                        <h1>Lista Médicos</h1>
                    </div>

                    <div class="table-responsive">
                        <table id="example" class="responsive-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>CATEGORÍA</th>
                                    <th>TIPO DE EMPLEADO</th>
                                    <th>N° EMPLEADO</th>
                                    <th>DNI</th>
                                    <th>NOMBRES</th>
                                    <th>APELLIDOS</th>
                                    <th>SEXO</th>
                                    <th>ÁREA/DEPTO</th>
                                    <th>NIVEL SALARIAL</th>
                                    <th>SALARIO</th>
                                    <th>N° CUENTA</th>
                                    <th>FECHA DE INGRESO</th>
                                    <th>TELÉFONO</th>
                                    <th>CORREO</th>
                                    <th>MARCAJE</th>
                                    <th>LOKER</th>
                                    <th>CONTRATO</th>
                                    <th>ESTADO</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/registros/script/inline_editing.js"></script>

    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>

    <script>
        window.MEDIDATA_COLAB_CONFIG = {
            ajaxUrl: '../../backend/php/get_colaboradores.php',
            estado: '1',
            variant: 'usr',
            tipo: 'doctor',
            deptoMap: <?php echo json_encode($depto_map, JSON_UNESCAPED_UNICODE); ?>,
            salaryMap: <?php echo json_encode($salary_level_map, JSON_UNESCAPED_UNICODE); ?>
        };
    </script>
    <script src="../../backend/registros/script/tabla_colaboradores.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
</body>
</html>
