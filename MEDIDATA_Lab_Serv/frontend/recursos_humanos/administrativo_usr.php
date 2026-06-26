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
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once './perfil.php'; ?>
    </nav>
    <main>
        <?php
        $hora = (int) date('H');
        $saludo = ($hora >= 6 && $hora < 12) ? 'Buenos Días' : (($hora >= 12 && $hora < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
        <button class="button" onclick="cambiarColor(this, 'administrativo_usr.php')">Personal Activo</button>
        <button class="button" onclick="cambiarColor(this, 'administrativo_ex_usr.php')">Ex Administrativos</button>
        <button class="button" onclick="cambiarColor(this, 'administrativo_nuevo_usr.php')">Registrar Administrativo</button>

        <div class="data">
            <div class="content-data">
                <div class="head"><h3>Personal Administrativo Activo</h3></div>
                <div class="table-responsive" style="overflow-x:auto;">
                    <?php
                    $sentencia = $connect->prepare("
                        SELECT sa.*, p.name AS position_name
                        FROM staff_administrative sa 
                        LEFT JOIN positions p ON sa.id_cargo = p.id 
                        WHERE sa.state = '1' 
                        ORDER BY sa.idadm DESC
                    ");
                    $sentencia->execute();
                    $data = $sentencia->fetchAll(PDO::FETCH_OBJ);
                    ?>
                    <?php if (count($data) > 0): ?>
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
                                <th>NIVEL SALARIAL</th>
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
                                    <select class="inline-select" data-id="<?php echo (int) $d->idadm; ?>" data-field="tipo_empleado" data-table="staff_administrative" data-idcol="idadm" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer;">
                                        <option value="Permanente" <?php echo (($d->tipo_empleado ?? '') == 'Permanente') ? 'selected' : ''; ?>>Permanente</option>
                                        <option value="Temporal" <?php echo (($d->tipo_empleado ?? '') == 'Temporal') ? 'selected' : ''; ?>>Temporal</option>
                                        <option value="Tiempo parcial" <?php echo (($d->tipo_empleado ?? '') == 'Tiempo parcial') ? 'selected' : ''; ?>>Tiempo parcial</option>
                                    </select>
                                </td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="num_empleado" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->num_empleado ?? '—'); ?></td>
                                <th scope="row" class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="numide" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->numide); ?></th>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="apeadm" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->apeadm); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="nomadm" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->nomadm); ?></td>
                                <td>
                                    <select class="inline-select" data-id="<?php echo (int) $d->idadm; ?>" data-field="sexadm" data-table="staff_administrative" data-idcol="idadm" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer;">
                                        <option value="Masculino" <?php echo ($d->sexadm == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="Femenino" <?php echo ($d->sexadm == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="inline-select" data-id="<?php echo (int) $d->idadm; ?>" data-field="id_departamento" data-table="staff_administrative" data-idcol="idadm" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer; min-width: 120px;">
                                        <option value="">—</option>
                                        <?php foreach ($depto_map as $id_dept => $name_dept): ?>
                                            <option value="<?php echo $id_dept; ?>" <?php echo ($d->id_departamento == $id_dept) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name_dept); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="inline-select" data-id="<?php echo (int) $d->idadm; ?>" data-field="id_salary_level" data-table="staff_administrative" data-idcol="idadm" style="border:1px dashed #ccc; background:#f9f9f9; cursor:pointer; min-width: 120px;">
                                        <option value="">—</option>
                                        <?php foreach ($salary_level_map as $id_sl => $name_sl): ?>
                                            <option value="<?php echo $id_sl; ?>" <?php echo (isset($d->id_salary_level) && $d->id_salary_level == $id_sl) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name_sl); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="salario" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->salario ?? ''); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="cuenta_bac" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->cuenta_bac ?? '—'); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="fecha_ingreso" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Ej: 2024-01-30"><?php echo htmlspecialchars($d->fecha_ingreso ?? '—'); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="telefono" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->telefono ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars(($d->correo_personal ?? '—') . ' / ' . ($d->correo_institucional ?? '—')); ?></td>
                                <td><?php echo htmlspecialchars($d->nacadm); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="id_biometrico" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->id_biometrico ?? '—'); ?></td>
                                <td class="editable-cell" data-id="<?php echo (int) $d->idadm; ?>" data-field="num_locker" data-table="staff_administrative" data-idcol="idadm" contenteditable="true" style="background-color: #f9f9f9; border: 1px dashed #ccc; cursor: pointer;" title="Haz clic para editar"><?php echo htmlspecialchars($d->num_locker ?? '—'); ?></td>
                                <td>
                                    <?php if (!empty($d->url_contrato)): ?>
                                        <a href="../../backend/php/view_staff_doc.php?id=<?php echo (int) $d->idadm; ?>&doc=contrato" target="_blank" class="badge-success" style="padding:4px; text-decoration:none;"><i class="bx bx-file"></i> Ver</a>
                                            <a href="#" onclick="deleteContract(<?php echo $d->id; ?>, '<?php echo htmlspecialchars($d->source_table); ?>', '<?php echo htmlspecialchars($d->source_idcol); ?>'); return false;" class="badge-danger" style="padding:4px; text-decoration:none; margin-left:4px;" title="Eliminar contrato"><i class="bx bx-trash"></i></a>
                                    <?php else: ?>
                                        <span class="badge-warning" style="padding:4px;">N/D</span>
                                    <?php endif; ?>
                                    <br>
                                    <label class="badge-primary" style="padding:4px; cursor:pointer; display:inline-block; margin-top:4px;" onclick="document.getElementById('upload_contrato_<?php echo $d->idadm; ?>').click();">
                                        <i class="bx bx-upload"></i> Subir
                                    </label>
                                    <input type="file" id="upload_contrato_<?php echo $d->idadm; ?>" style="display:none;" accept=".pdf,.jpg,.png" onchange="uploadContract(this, <?php echo $d->idadm; ?>, 'staff_administrative', 'idadm')">
                                </td>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" class="staff-state-toggle" data-id="<?php echo (int) $d->idadm; ?>" <?php echo $d->state == '1' ? 'checked' : ''; ?>/>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <a title="Actualizar" href="administrativo_editar_usr.php?id=<?php echo (int) $d->idadm; ?>" class="fa fa-pencil tooltip"></a>
                                    <a title="Eliminar" href="#" class="fa fa-trash tooltip btn-delete-staff" data-id="<?php echo (int) $d->idadm; ?>"></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="alert">
                        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                        <strong>Sin datos</strong> No hay colaboradores administrativos registrados.
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
<script src="../../backend/registros/script/tabla_personal_staff.js"></script>
<script src="../../backend/registros/script/inline_editing.js"></script>
<script src="../../backend/js/datatable.js"></script>
<script src="../../backend/js/datatablebuttons.js"></script>
<script src="../../backend/js/jszip.js"></script>
<script src="../../backend/js/pdfmake.js"></script>
<script src="../../backend/js/vfs_fonts.js"></script>
<script src="../../backend/js/buttonshtml5.js"></script>
<script src="../../backend/js/buttonsprint.js"></script>
<script>
window.MEDIDATA_STAFF_ADMIN = {
    toggleSelector: '.staff-state-toggle',
    deleteSelector: '.btn-delete-staff',
    toggleUrl: '../../backend/php/toggle_administrative_state.php',
    deleteUrl: '../../backend/php/delete_administrative.php',
    idParam: 'idadm',
    deleteTitle: '¿Eliminar colaborador administrativo?',
    deleteFn: 'deleteAdministrative'
};
$(document).ready(function () {
    if ($('#example').length) {
        $('#example').DataTable({
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                sProcessing: 'Procesando...',
                sLengthMenu: 'Mostrar _MENU_ registros',
                sZeroRecords: 'No se encontraron resultados',
                sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                sInfoEmpty: 'Mostrando 0 a 0 de 0 registros',
                sInfoFiltered: '(filtrado de _MAX_ registros totales)',
                sSearch: 'Buscar:',
                oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' }
            }
        });
    }
});
</script>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
</body>
</html>
