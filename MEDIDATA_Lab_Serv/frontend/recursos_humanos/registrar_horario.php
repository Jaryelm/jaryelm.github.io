<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$id_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id_edit > 0;
$edit_data = null;
$pdoRrhh = medidata_rrhh_pdo();

if ($is_edit && $pdoRrhh) {
    try {
        $stmt = $pdoRrhh->prepare("SELECT * FROM schedules WHERE id = :id AND deleted = 0");
        $stmt->bindParam(':id', $id_edit, PDO::PARAM_INT);
        $stmt->execute();
        $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_data) {
            $is_edit = false;
        }
    } catch (Throwable $e) {
        $is_edit = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA - <?php echo $is_edit ? 'Editar' : 'Registrar'; ?> Horario</title>
</head>
<body>

<?php include_once '../admin/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>

    <main>
        <h1 class="title"><?php echo $is_edit ? 'Editar' : 'Registrar Nuevo'; ?> Horario Laboral</h1>

        <?php if (!medidata_rrhh_disponible()): ?>
        <div class="alert-danger" style="margin-bottom: 20px;">
            <strong>Base de datos RRHH no disponible.</strong>
            No se puede guardar hasta que esté activa <code>medic9ue_medi_rrhh_interviews</code>.
        </div>
        <?php endif; ?>

        <button class="button" onclick="window.location.href='horarios.php'">Listar Horarios</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3><?php echo $is_edit ? 'Modificar' : 'Crear'; ?> Estructura de Horario</h3>
                </div>
                
                <form id="scheduleForm" action="../../backend/php/<?php echo $is_edit ? 'upd' : 'add'; ?>_schedule.php" method="POST" autocomplete="off">
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $id_edit; ?>">
                        <input type="hidden" name="upd_schedule" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_schedule" value="1">
                    <?php endif; ?>
                    
                    <div class="containerss">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Nombre descriptivo del Horario <span style="color:red;">*</span></label>
                            <input type="text" name="name" value="<?php echo $is_edit ? htmlspecialchars($edit_data['name']) : ''; ?>" required placeholder="Ej: Administrativo Tegucigalpa, Turno Nocturno B" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;">
                        </div>

                        <h4 style="margin: 25px 0 15px; color: #035c67; border-bottom: 2px solid #eee; padding-bottom: 5px;">Detalle de Días Laborales</h4>
                        <p style="color: #666; margin-bottom: 20px; font-size: 0.9rem;">Marque los días que componen este horario e indique las horas de entrada y salida.</p>

                        <div class="responsive-table">
                            <table style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                                <thead>
                                    <tr style="background-color: #035c67; color: #fff;">
                                        <th style="padding: 15px; border: 1px solid #035c67;">Día</th>
                                        <th style="padding: 15px; border: 1px solid #035c67; width: 100px;">¿Activo?</th>
                                        <th style="padding: 15px; border: 1px solid #035c67;">Entrada</th>
                                        <th style="padding: 15px; border: 1px solid #035c67;">Salida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $days = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'];
                                    $existing_details = [];
                                    if ($is_edit && $pdoRrhh) {
                                        $stmtD = $pdoRrhh->prepare("SELECT day, entry_time, exit_time FROM schedule_details WHERE id_schedule = ?");
                                        $stmtD->execute([$id_edit]);
                                        while ($rowD = $stmtD->fetch(PDO::FETCH_ASSOC)) {
                                            $existing_details[$rowD['day']] = $rowD;
                                        }
                                    }
                                    
                                    foreach ($days as $index => $dayName): 
                                        $checked = isset($existing_details[$dayName]) ? 'checked' : '';
                                        $entry = isset($existing_details[$dayName]) ? $existing_details[$dayName]['entry_time'] : '08:00';
                                        $exit = isset($existing_details[$dayName]) ? $existing_details[$dayName]['exit_time'] : '17:00';
                                    ?>
                                    <tr style="<?php echo $index % 2 == 0 ? 'background-color: #f9f9f9;' : ''; ?>">
                                        <td style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; color: #333;"><?php echo $dayName; ?></td>
                                        <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                                            <input type="checkbox" class="day-check" name="details[<?php echo $index; ?>][active]" value="1" <?php echo $checked; ?> style="transform: scale(1.5); cursor: pointer;">
                                            <input type="hidden" name="details[<?php echo $index; ?>][day]" value="<?php echo $dayName; ?>">
                                        </td>
                                        <td style="padding: 12px; border: 1px solid #ddd;">
                                            <input type="time" name="details[<?php echo $index; ?>][entry_time]" value="<?php echo $entry; ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" <?php echo $checked ? '' : 'disabled'; ?>>
                                        </td>
                                        <td style="padding: 12px; border: 1px solid #ddd;">
                                            <input type="time" name="details[<?php echo $index; ?>][exit_time]" value="<?php echo $exit; ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" <?php echo $checked ? '' : 'disabled'; ?>>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <input type="hidden" name="<?php echo $is_edit ? 'updated_by' : 'created_by'; ?>" value="<?php echo htmlspecialchars($name); ?>">

                        <div style="display: flex; gap: 15px; margin-top: 30px; align-items: center;">
                            <button type="submit" class="registerbtn" id="btnGuardarHorario" style="flex: 1; margin: 0; padding: 15px;" <?php echo medidata_rrhh_disponible() ? '' : 'disabled'; ?>><?php echo $is_edit ? 'Actualizar Horario' : 'Guardar Horario'; ?></button>
                            <a href="horarios.php" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; padding: 15px;">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    // Habilitar/deshabilitar inputs de tiempo según el checkbox
    $('.day-check').on('change', function() {
        var $row = $(this).closest('tr');
        var isChecked = $(this).is(':checked');
        $row.find('input[type="time"]').prop('disabled', !isChecked);
    });

    $('#scheduleForm').on('submit', function(e) {
        e.preventDefault();

        if ($('.day-check:checked').length === 0) {
            Swal.fire('Aviso', 'Debe activar al menos un día para este horario.', 'warning');
            return;
        }

        var $btn = $('#btnGuardarHorario');
        $btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('¡Logrado!', response.message, 'success').then(function() {
                        window.location = 'horarios.php';
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                    $btn.prop('disabled', false).text(<?php echo json_encode($is_edit ? 'Actualizar Horario' : 'Guardar Horario', JSON_UNESCAPED_UNICODE); ?>);
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
                $btn.prop('disabled', false).text(<?php echo json_encode($is_edit ? 'Actualizar Horario' : 'Guardar Horario', JSON_UNESCAPED_UNICODE); ?>);
            }
        });
    });
});
</script>

</body>
</html>