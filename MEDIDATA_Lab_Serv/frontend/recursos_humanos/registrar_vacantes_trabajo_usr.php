<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$id_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id_edit > 0;
$edit_data = null;
$pdoRrhh = medidata_rrhh_pdo();

if ($is_edit && $pdoRrhh) {
    try {
        $stmt = $pdoRrhh->prepare("SELECT * FROM vacant_positions WHERE id = :id AND deleted = 0");
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
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
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
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <?php if (!medidata_rrhh_disponible()): ?>
        <div class="alert-danger" style="margin-bottom: 20px;">
            <strong>Base de datos RRHH no disponible.</strong>
            No se puede guardar hasta que esté activa <code>medic9ue_medi_rrhh_interviews</code>.
            <?php if ($err = medidata_rrhh_last_error()): ?>
            <br><small><?php echo htmlspecialchars($err); ?></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <button class="button" onclick="cambiarColor(this, 'vacantes_trabajo_usr.php')">Listar Vacantes de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_vacantes_trabajo_usr.php')">Registrar Vacante de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3><?php echo $is_edit ? 'Actualizar' : 'Registrar Nueva'; ?> Vacante de Trabajo</h3>
                </div>
                
                <form id="vacanteForm" action="../../backend/php/<?php echo $is_edit ? 'upd' : 'add'; ?>_vacante_trabajo.php" method="POST" autocomplete="off">
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $id_edit; ?>">
                        <input type="hidden" name="upd_vacante" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_vacante" value="1">
                    <?php endif; ?>
                    
                    <div class="containerss">
                        <div class="alert-danger" style="margin-bottom: 20px;">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                            <strong>Importante:</strong> Los campos marcados con <span style="color:red;">*</span> son obligatorios.
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="id_position">Puesto de Trabajo (Detallado) <span style="color:red;">*</span></label>
                            <select class="select2" name="id_position" id="id_position" required>
                                <option value="" disabled <?php echo !$is_edit ? 'selected' : ''; ?>>Seleccione un puesto detallado...</option>
                                <?php 
                                $puestosDetalleCount = 0;
                                try {
                                    $pdoPd = medidata_rrhh_pdo();
                                    if ($pdoPd) {
                                        $stmt_pd = $pdoPd->prepare("SELECT pd.id, p.name FROM positions_details pd JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id WHERE pd.deleted = 0 ORDER BY p.name ASC");
                                        $stmt_pd->execute();
                                        while ($row = $stmt_pd->fetch(PDO::FETCH_ASSOC)) {
                                            $puestosDetalleCount++;
                                            $selected = ($is_edit && $edit_data['id_position'] == $row['id']) ? 'selected' : '';
                                            echo '<option value="' . (int) $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
                                        }
                                    }
                                } catch (Throwable $e) {}
                                ?>
                            </select>
                            <?php if ($puestosDetalleCount === 0): ?>
                            <p style="color:#c0392b;margin-top:8px;font-size:0.9rem;">
                                No hay puestos detallados registrados. Cree uno en
                                <a href="registrar_puesto_trabajo_usr.php">Registrar Puesto de Trabajo</a> antes de continuar.
                            </p>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 2;">
                                <label for="vacant_name">Nombre de la Vacante <span style="color:red;">*</span></label>
                                <input type="text" name="vacant_name" id="vacant_name" value="<?php echo $is_edit ? htmlspecialchars($edit_data['vacant_name']) : ''; ?>" required placeholder="Ej: Enfermera de Noche" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="available_slots">Plazas Disponibles <span style="color:red;">*</span></label>
                                <input type="number" name="available_slots" id="available_slots" value="<?php echo $is_edit ? htmlspecialchars($edit_data['available_slots']) : '1'; ?>" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="requesting_department">Departamento Solicitante <span style="color:red;">*</span></label>
                                <input type="text" name="requesting_department" id="requesting_department" value="<?php echo $is_edit ? htmlspecialchars($edit_data['requesting_department']) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="requesting_boss">Jefe Solicitante</label>
                                <input type="text" name="requesting_boss" id="requesting_boss" value="<?php echo $is_edit ? htmlspecialchars($edit_data['requesting_boss'] ?? '') : ''; ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="reason">Motivo de la Vacante <span style="color:red;">*</span></label>
                            <textarea name="reason" id="reason" rows="2" placeholder="Ej: Renuncia, Nuevo Puesto, Expansión..." required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['reason']) : ''; ?></textarea>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="init_date">Fecha de Apertura <span style="color:red;">*</span></label>
                                <input type="date" name="init_date" id="init_date" value="<?php echo $is_edit ? htmlspecialchars($edit_data['init_date']) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="end_date">Fecha Tentativa de Cierre <span style="color:red;">*</span></label>
                                <input type="date" name="end_date" id="end_date" value="<?php echo $is_edit ? htmlspecialchars($edit_data['end_date']) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="priority">Prioridad <span style="color:red;">*</span></label>
                                <select class="select2" name="priority" id="priority" required>
                                    <option value="Baja" <?php echo ($is_edit && $edit_data['priority'] == 'Baja') ? 'selected' : ''; ?>>Baja</option>
                                    <option value="Media" <?php echo ($is_edit && $edit_data['priority'] == 'Media') ? 'selected' : (!$is_edit ? 'selected' : ''); ?>>Media</option>
                                    <option value="Alta" <?php echo ($is_edit && $edit_data['priority'] == 'Alta') ? 'selected' : ''; ?>>Alta</option>
                                    <option value="Urgente" <?php echo ($is_edit && $edit_data['priority'] == 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="rrhh_responsible">Responsable en RRHH</label>
                                <input type="text" name="rrhh_responsible" id="rrhh_responsible" value="<?php echo $is_edit ? htmlspecialchars($edit_data['rrhh_responsible'] ?? '') : ''; ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="publication_channel">Canal de Publicación</label>
                                <input type="text" name="publication_channel" id="publication_channel" value="<?php echo $is_edit ? htmlspecialchars($edit_data['publication_channel'] ?? '') : ''; ?>" placeholder="Ej: LinkedIn, Computrabajo" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="benefits">Beneficios (Generales / Adicionales al Puesto) <span style="color:red;">*</span></label>
                            <textarea name="benefits" id="benefits" rows="3" placeholder="Lista de beneficios ofrecidos para esta vacante..." required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['benefits']) : ''; ?></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="internal_observations">Observaciones Internas</label>
                            <textarea name="internal_observations" id="internal_observations" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['internal_observations'] ?? '') : ''; ?></textarea>
                        </div>

                        <input type="hidden" name="<?php echo $is_edit ? 'updated_by' : 'created_by'; ?>" value="<?php echo htmlspecialchars($name); ?>">

                        <div style="display: flex; gap: 10px; margin-top: 20px; align-items: center;">
                            <button type="submit" class="registerbtn" id="btnGuardarVacante" style="flex: 1; margin: 0;" <?php echo (medidata_rrhh_disponible() && $puestosDetalleCount > 0) ? '' : 'disabled'; ?>><?php echo $is_edit ? 'Actualizar Vacante' : 'Guardar Vacante'; ?></button>
                            <a href="vacantes_trabajo_usr.php" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>

<script type="text/javascript">
function medidataParseAjaxJson(xhr) {
    var text = xhr.responseText || '';
    if (text.indexOf('login.php') !== -1) {
        return { success: false, message: 'Su sesión expiró. Vuelva a iniciar sesión.' };
    }
    try {
        return JSON.parse(text);
    } catch (e) {
        return { success: false, message: 'Respuesta inválida del servidor.' };
    }
}

$(document).ready(function() {
    $('#vacanteForm').on('submit', function(e) {
        e.preventDefault();

        if (!$('#id_position').val()) {
            Swal.fire('Campo requerido', 'Seleccione un puesto de trabajo detallado.', 'warning');
            return;
        }

        var $btn = $('#btnGuardarVacante');
        if ($btn.prop('disabled')) {
            return;
        }

        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'text',
            complete: function(xhr) {
                $btn.prop('disabled', false).text(<?php echo json_encode($is_edit ? 'Actualizar Vacante' : 'Guardar Vacante', JSON_UNESCAPED_UNICODE); ?>);
                var response = medidataParseAjaxJson(xhr);
                if (response.success) {
                    Swal.fire(<?php echo json_encode($is_edit ? '¡Actualizado!' : '¡Agregado!', JSON_UNESCAPED_UNICODE); ?>, response.message || 'Operación completada', 'success').then(function() {
                        window.location = 'vacantes_trabajo_usr.php';
                    });
                } else {
                    Swal.fire('Error', response.message || 'No se pudo guardar la vacante', 'error');
                }
            }
        });
    });
});
</script>

</body>
</html>
