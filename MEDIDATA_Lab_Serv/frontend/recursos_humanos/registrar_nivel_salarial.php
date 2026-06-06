<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$id_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id_edit > 0;
$edit_data = null;
$pdoRrhh = medidata_rrhh_pdo();

if ($is_edit && $pdoRrhh) {
    try {
        $stmt = $pdoRrhh->prepare("SELECT * FROM salary_levels WHERE id = :id AND deleted = 0");
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
    <title>MEDIDATA - <?php echo $is_edit ? 'Editar' : 'Registrar'; ?> Nivel Salarial</title>
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
        <h1 class="title"><?php echo $is_edit ? 'Editar' : 'Registrar Nuevo'; ?> Nivel Salarial</h1>

        <?php if (!medidata_rrhh_disponible()): ?>
        <div class="alert-danger" style="margin-bottom: 20px;">
            <strong>Base de datos RRHH no disponible.</strong>
            No se puede guardar hasta que esté activa <code>medic9ue_medi_rrhh_interviews</code>.
        </div>
        <?php endif; ?>

        <button class="button" onclick="window.location.href='niveles_salariales.php'">Listar Niveles</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3><?php echo $is_edit ? 'Modificar' : 'Crear'; ?> Estructura Salarial</h3>
                </div>
                
                <form id="salaryForm" action="../../backend/php/<?php echo $is_edit ? 'upd' : 'add'; ?>_salary_level.php" method="POST" autocomplete="off">
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $id_edit; ?>">
                        <input type="hidden" name="upd_salary_level" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_salary_level" value="1">
                    <?php endif; ?>
                    
                    <div class="containerss">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Nombre del Nivel <span style="color:red;">*</span></label>
                            <input type="text" name="level_name" value="<?php echo $is_edit ? htmlspecialchars($edit_data['level_name']) : ''; ?>" required placeholder="Ej: Nivel 1, Senior, Junior A" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Categoría del Cargo <span style="color:red;">*</span></label>
                            <input type="text" name="position_category" value="<?php echo $is_edit ? htmlspecialchars($edit_data['position_category']) : ''; ?>" required placeholder="Ej: Administrativo, Operativo, Gerencial" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group" style="flex: 1;">
                                <label>Salario Mínimo (L.) <span style="color:red;">*</span></label>
                                <input type="number" step="0.01" name="min_salary" value="<?php echo $is_edit ? $edit_data['min_salary'] : ''; ?>" required style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Salario Máximo (L.) <span style="color:red;">*</span></label>
                                <input type="number" step="0.01" name="max_salary" value="<?php echo $is_edit ? $edit_data['max_salary'] : ''; ?>" required style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <input type="hidden" name="<?php echo $is_edit ? 'updated_by' : 'created_by'; ?>" value="<?php echo htmlspecialchars($name); ?>">

                        <div style="display: flex; gap: 15px; margin-top: 30px; align-items: center;">
                            <button type="submit" class="registerbtn" id="btnGuardarSalario" style="flex: 1; margin: 0; padding: 15px;" <?php echo medidata_rrhh_disponible() ? '' : 'disabled'; ?>><?php echo $is_edit ? 'Actualizar Nivel' : 'Guardar Nivel'; ?></button>
                            <a href="niveles_salariales.php" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; padding: 15px;">Cancelar</a>
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
    $('#salaryForm').on('submit', function(e) {
        e.preventDefault();

        var min = parseFloat($('input[name="min_salary"]').val());
        var max = parseFloat($('input[name="max_salary"]').val());

        if (max < min) {
            Swal.fire('Error', 'El salario máximo no puede ser menor al mínimo.', 'error');
            return;
        }

        var $btn = $('#btnGuardarSalario');
        $btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('¡Éxito!', response.message, 'success').then(function() {
                        window.location = 'niveles_salariales.php';
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                    $btn.prop('disabled', false).text(<?php echo json_encode($is_edit ? 'Actualizar Nivel' : 'Guardar Nivel', JSON_UNESCAPED_UNICODE); ?>);
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
                $btn.prop('disabled', false).text(<?php echo json_encode($is_edit ? 'Actualizar Nivel' : 'Guardar Nivel', JSON_UNESCAPED_UNICODE); ?>);
            }
        });
    });
});
</script>

</body>
</html>