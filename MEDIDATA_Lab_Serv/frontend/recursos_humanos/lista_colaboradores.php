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
    <?php 
    if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') {
        include_once '../admin/menu.php'; 
    } else {
        include_once '../recursos_humanos/menu.php'; 
    }
    ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

            <?php
            $filtro_area = isset($_GET['area']) ? $_GET['area'] : 'todos';
            ?>
            <div class="rrhh-tab-nav" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px;">
                <a href="lista_colaboradores.php?area=todos" class="button tab-button <?php echo $filtro_area == 'todos' ? 'active' : ''; ?>">Todos</a>
                <a href="lista_colaboradores.php?area=medico" class="button tab-button <?php echo $filtro_area == 'medico' ? 'active' : ''; ?>">Médicos</a>
                <a href="lista_colaboradores.php?area=enfermeria" class="button tab-button <?php echo $filtro_area == 'enfermeria' ? 'active' : ''; ?>">Enfermería</a>
                <a href="lista_colaboradores.php?area=administrativo" class="button tab-button <?php echo $filtro_area == 'administrativo' ? 'active' : ''; ?>">Administrativos</a>
                <a href="lista_colaboradores.php?area=servicios_generales" class="button tab-button <?php echo $filtro_area == 'servicios_generales' ? 'active' : ''; ?>">Servicios Generales</a>
                <a href="lista_excolaboradores.php" class="button tab-button">Excolaboradores</a>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                <a href="agregar_colaborador.php" class="button tab-button" style="background-color: #28a745; color: white;">Agregar Colaborador</a>
                <?php endif; ?>
            </div>

            <div class="data">
                <div class="content-data">
                    <div class="table-title">
                        <h1>Lista de Colaboradores Activos <?php echo $filtro_area != 'todos' ? '- ' . ucfirst(str_replace('_', ' ', $filtro_area)) : ''; ?></h1>
                    </div>
                    
                    <div class="table-responsive" style="overflow-x:auto;">
                        <?php
                        $sql_administrativo = "SELECT 
                                'staff_administrative' AS source_table, 'idadm' AS source_idcol, idadm AS id,
                                num_empleado, numide AS identificacion, nomadm AS nombres, apeadm AS apellidos, sexadm AS sexo,
                                'nomadm' AS field_nombres, 'apeadm' AS field_apellidos, 'sexadm' AS field_sexo, 'numide' AS field_identificacion,
                                tipo_empleado, id_departamento, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
                                correo_personal, correo_institucional, nacadm AS fecha_nacimiento, id_biometrico, num_locker,
                                url_contrato, state, 'administrativo_editar.php' AS edit_file
                            FROM staff_administrative WHERE state = '1'";

                        $sql_medico = "SELECT 
                                'doctor' AS source_table, 'idodc' AS source_idcol, idodc AS id,
                                num_empleado, ceddoc AS identificacion, nodoc AS nombres, apdoc AS apellidos, sexd AS sexo,
                                'nodoc' AS field_nombres, 'apdoc' AS field_apellidos, 'sexd' AS field_sexo, 'ceddoc' AS field_identificacion,
                                tipo_empleado, id_departamento, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
                                correo_personal, correo_institucional, nacd AS fecha_nacimiento, id_biometrico, num_locker,
                                url_contrato, state, '../medicos/editar.php' AS edit_file
                            FROM doctor WHERE state = '1'";

                        $sql_enfermeria = "SELECT 
                                'nurse' AS source_table, 'idnur' AS source_idcol, idnur AS id,
                                num_empleado, numide AS identificacion, nomnur AS nombres, apenur AS apellidos, sexnur AS sexo,
                                'nomnur' AS field_nombres, 'apenur' AS field_apellidos, 'sexnur' AS field_sexo, 'numide' AS field_identificacion,
                                tipo_empleado, id_departamento, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
                                correo_personal, correo_institucional, nacinur AS fecha_nacimiento, id_biometrico, num_locker,
                                url_contrato, state, '../enfermeria/editar.php' AS edit_file
                            FROM nurse WHERE state = '1'";

                        $sql_servicios_generales = "SELECT 
                                'staff_general_services' AS source_table, 'idsg' AS source_idcol, idsg AS id,
                                num_empleado, numide AS identificacion, nomsg AS nombres, apesg AS apellidos, sexsg AS sexo,
                                'nomsg' AS field_nombres, 'apesg' AS field_apellidos, 'sexsg' AS field_sexo, 'numide' AS field_identificacion,
                                tipo_empleado, id_departamento, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
                                correo_personal, correo_institucional, nacsg AS fecha_nacimiento, id_biometrico, num_locker,
                                url_contrato, state, 'servicios_generales_editar.php' AS edit_file
                            FROM staff_general_services WHERE state = '1'";

                        $queries = [];
                        if ($filtro_area == 'todos' || $filtro_area == 'administrativo') $queries[] = $sql_administrativo;
                        if ($filtro_area == 'todos' || $filtro_area == 'medico') $queries[] = $sql_medico;
                        if ($filtro_area == 'todos' || $filtro_area == 'enfermeria') $queries[] = $sql_enfermeria;
                        if ($filtro_area == 'todos' || $filtro_area == 'servicios_generales') $queries[] = $sql_servicios_generales;

                        $final_query = implode(" UNION ALL ", $queries) . " ORDER BY nombres ASC";
                        
                        $sentencia = $connect->prepare($final_query);
                        $sentencia->execute();
                        $data = $sentencia->fetchAll(PDO::FETCH_OBJ);
                        ?>
                        <?php if (count($data) > 0): ?>
                        <table id="example" class="responsive-table">
                            <thead>
                                <tr>
                                    <th>CATEGORÃA</th>
                                    <th>TIPO DE EMPLEADO</th>
                                    <th>N° EMPLEADO</th>
                                    <th>DNI</th>
                                    <th>APELLIDOS</th>
                                    <th>NOMBRES</th>
                                    <th>SEXO</th>
                                    <th>ÃREA/DEPTO</th>
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
                            <tbody>
                                <?php foreach ($data as $d): ?>
                                <?php
                                    $categoria_label = '';
                                    if ($d->source_table == 'staff_administrative') $categoria_label = 'Administrativo';
                                    elseif ($d->source_table == 'doctor') $categoria_label = 'Médico';
                                    elseif ($d->source_table == 'nurse') $categoria_label = 'Enfermería';
                                    elseif ($d->source_table == 'staff_general_services') $categoria_label = 'Servicios Generales';
                                ?>
                                <tr>
                                    <td><span class="badge-primary" style="padding:4px; border-radius:4px; font-size:0.8rem;"><?php echo $categoria_label; ?></span></td>
                                    <td>
                                        <select class="inline-select" data-id="<?php echo (int) $d->id; ?>" data-field="tipo_empleado" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer;" <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] != 'admin') ? 'disabled' : ''; ?>>
                                            <option value="Permanente" <?php echo (($d->tipo_empleado ?? '') == 'Permanente') ? 'selected' : ''; ?>>Permanente</option>
                                            <option value="Temporal" <?php echo (($d->tipo_empleado ?? '') == 'Temporal') ? 'selected' : ''; ?>>Temporal</option>
                                            <option value="Tiempo parcial" <?php echo (($d->tipo_empleado ?? '') == 'Tiempo parcial') ? 'selected' : ''; ?>>Tiempo parcial</option>
                                        </select>
                                    </td>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="num_empleado" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->num_empleado ?? '—'); ?></td>
                                    <th scope="row" <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="<?php echo htmlspecialchars($d->field_identificacion); ?>" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->identificacion); ?></th>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="<?php echo htmlspecialchars($d->field_apellidos); ?>" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->apellidos); ?></td>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="<?php echo htmlspecialchars($d->field_nombres); ?>" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->nombres); ?></td>
                                    <td>
                                        <select class="inline-select" data-id="<?php echo (int) $d->id; ?>" data-field="<?php echo htmlspecialchars($d->field_sexo); ?>" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer;" <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] != 'admin') ? 'disabled' : ''; ?>>
                                            <option value="Masculino" <?php echo ($d->sexo == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="Femenino" <?php echo ($d->sexo == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="inline-select" data-id="<?php echo (int) $d->id; ?>" data-field="id_departamento" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer; min-width: 120px;" <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] != 'admin') ? 'disabled' : ''; ?>>
                                            <option value="">—</option>
                                            <?php foreach ($depto_map as $id_dept => $name_dept): ?>
                                                <option value="<?php echo $id_dept; ?>" <?php echo ($d->id_departamento == $id_dept) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name_dept); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="inline-select" data-id="<?php echo (int) $d->id; ?>" data-field="id_salary_level" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer; min-width: 120px;" <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] != 'admin') ? 'disabled' : ''; ?>>
                                            <option value="">—</option>
                                            <?php foreach ($salary_level_map as $id_sl => $name_sl): ?>
                                                <option value="<?php echo $id_sl; ?>" <?php echo (isset($d->id_salary_level) && $d->id_salary_level == $id_sl) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name_sl); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="salario" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->salario ?? ''); ?></td>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="cuenta_bac" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->cuenta_bac ?? '—'); ?></td>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="fecha_ingreso" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Ej: 2024-01-30"><?php echo htmlspecialchars($d->fecha_ingreso ?? '—'); ?></td>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="telefono" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->telefono ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars(($d->correo_personal ?? '—') . ' / ' . ($d->correo_institucional ?? '—')); ?></td>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="id_biometrico" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->id_biometrico ?? '—'); ?></td>
                                    <td <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? 'class="editable-cell" contenteditable="true"' : ''; ?> data-id="<?php echo (int) $d->id; ?>" data-field="num_locker" data-table="<?php echo htmlspecialchars($d->source_table); ?>" data-idcol="<?php echo htmlspecialchars($d->source_idcol); ?>" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;"><?php echo htmlspecialchars($d->num_locker ?? '—'); ?></td>
                                    <td>
                                        <?php if (!empty($d->url_contrato)): ?>
                                            <a href="../../backend/php/view_staff_doc.php?id=<?php echo (int) $d->id; ?>&doc=contrato&table=<?php echo htmlspecialchars($d->source_table); ?>&idcol=<?php echo htmlspecialchars($d->source_idcol); ?>" target="_blank" class="badge-success" style="padding:4px; text-decoration:none;"><i class="bx bx-file"></i> Ver</a>
                                            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                                            <a href="#" onclick="deleteContract(<?php echo $d->id; ?>, '<?php echo htmlspecialchars($d->source_table); ?>', '<?php echo htmlspecialchars($d->source_idcol); ?>'); return false;" class="badge-danger" style="padding:4px; text-decoration:none; margin-left:4px;" title="Eliminar contrato"><i class="bx bx-trash"></i></a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge-warning" style="padding:4px;">N/D</span>
                                        <?php endif; ?>
                                        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                                        <br>
                                        <label class="badge-primary" style="padding:4px; cursor:pointer; display:inline-block; margin-top:4px;" onclick="document.getElementById('upload_contrato_<?php echo htmlspecialchars($d->source_table . '_' . $d->id); ?>').click();">
                                            <i class="bx bx-upload"></i> Subir
                                        </label>
                                        <input type="file" id="upload_contrato_<?php echo htmlspecialchars($d->source_table . '_' . $d->id); ?>" style="display:none;" accept=".pdf,.jpg,.png" onchange="uploadContract(this, <?php echo $d->id; ?>, '<?php echo htmlspecialchars($d->source_table); ?>', '<?php echo htmlspecialchars($d->source_idcol); ?>')">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" class="unified-state-toggle" data-id="<?php echo (int) $d->id; ?>" data-table="<?php echo htmlspecialchars($d->source_table); ?>" <?php echo $d->state == '1' ? 'checked' : ''; ?> <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] != 'admin') ? 'disabled' : ''; ?>/>
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                                        <a title="Actualizar" href="<?php echo htmlspecialchars($d->edit_file); ?>?id=<?php echo (int) $d->id; ?>" class="fa fa-pencil tooltip"></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="alert">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                            <strong>Sin datos</strong> No hay colaboradores registrados.
                        </div>
                        <?php endif; ?>
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
        $(document).ready(function() {
            if ($('#example').length) {
                $('#example').DataTable({
                    pageLength: 10,
                    dom: 'Bfrtip',
                    buttons: [
                        { extend: 'copy', className: 'button' },
                        { extend: 'csv', className: 'button' },
                        { extend: 'excel', className: 'button' },
                        { extend: 'pdf', className: 'button' },
                        { extend: 'print', className: 'button' }
                    ],
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
            }

            $('.unified-state-toggle').on('change', function() {
                const checkbox = $(this);
                const row = checkbox.closest('tr');
                const id = checkbox.data('id');
                const table = checkbox.data('table');
                const newState = checkbox.is(':checked') ? 1 : 0;

                const endpoints = {
                    'doctor': '../../backend/php/toggle_doctor_state.php',
                    'nurse': '../../backend/php/toggle_nurse_state.php',
                    'staff_administrative': '../../backend/php/toggle_administrative_state.php',
                    'staff_general_services': '../../backend/php/toggle_general_services_state.php'
                };

                const url = endpoints[table];
                if (!url) {
                    Swal.fire('Error', 'Tipo de empleado no reconocido.', 'error');
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    return;
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { id: id, state: newState },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success || response.status === 'success') {
                            Swal.fire({
                                title: 'Éxito',
                                text: response.message || 'Estado actualizado',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            row.fadeOut(400, function() {
                                $(this).remove();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo actualizar el estado.', 'error');
                            checkbox.prop('checked', !checkbox.is(':checked'));
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                        checkbox.prop('checked', !checkbox.is(':checked'));
                    }
                });
            });
        });
    </script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
</body>
</html>

