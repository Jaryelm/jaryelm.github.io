<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/users_rrhh_extra_lib.php';
require_once '../../backend/registros/rrhh_guard.php';
medidata_users_rrhh_extra_ensure($connect);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <?php include __DIR__ . '/_rrhh_select2_head.php'; ?>
    <title>MEDIDATA - Editar Colaborador</title>
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

        <div class="rrhh-tab-nav">
            <a href="lista_colaboradores_usr.php" class="button tab-button active">Lista de Colaboradores</a>
            <a href="lista_colaboradores_medicos_usr.php" class="button tab-button">Lista de Médicos</a>
            <a href="lista_excolaboradores_usr.php" class="button tab-button">Lista de Excolaboradores</a>
        </div>

        <?php
        $id = (int) ($_GET['id'] ?? 0);
        $d = null;
        if ($id > 0) {
            $stmt = $connect->prepare("SELECT u.id, u.name, u.username, u.email, u.rol, u.cedula, u.sexo, u.uid_biometrico, u.state,
                                              ue.num_empleado, ue.tipo_empleado, ue.duracion_contrato, ue.id_departamento,
                                              ue.id_cargo, ue.id_horario, ue.id_salary_level, ue.salario, ue.cuenta_bac,
                                              ue.fecha_ingreso, ue.telefono, ue.correo_personal, ue.num_locker,
                                              (ue.url_contrato IS NOT NULL) AS has_contrato
                                       FROM users u
                                       LEFT JOIN users_rrhh_extra ue ON ue.id_user = u.id
                                       WHERE u.id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $d = $stmt->fetch(PDO::FETCH_OBJ);
        }

        // Cargos / posiciones (BD principal).
        $cargos = [];
        try {
            $stmt_p = $connect->query("SELECT id, name FROM positions ORDER BY name ASC");
            $cargos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        ?>

        <?php if ($d): ?>
        <form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
            <input type="hidden" name="return_page" value="lista_colaboradores_usr.php">
            <input type="hidden" name="midp" value="<?php echo (int) $d->id; ?>">
            <div class="containerss">
                <h1>Actualizar colaborador (Usuario)</h1>
                <p style="font-size:0.9rem; color:#666;">Cuenta del sistema: <strong><?php echo htmlspecialchars($d->username ?? ''); ?></strong> (usuario y contraseña se gestionan en Usuarios Registrados).</p>
                <hr>

                <label><b>N° de Empleado (Institucional)</b></label>
                <input type="text" name="num_empleado" value="<?php echo htmlspecialchars($d->num_empleado ?? ''); ?>" placeholder="ejm: EMP-001">

                <label><b>N° de identificación (DNI)</b></label>
                <input type="text" name="cedula" maxlength="20" value="<?php echo htmlspecialchars($d->cedula ?? ''); ?>">

                <label><b>Nombre Completo</b></label><span class="badge-warning">*</span>
                <input type="text" name="name" value="<?php echo htmlspecialchars($d->name ?? ''); ?>" required>

                <label><b>Sexo</b></label>
                <select class="select2" name="sexo">
                    <option value="">Seleccione...</option>
                    <option value="Masculino" <?php echo ($d->sexo ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo ($d->sexo ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                </select>

                <label><b>Rol del Usuario</b></label>
                <input type="text" name="rol" value="<?php echo htmlspecialchars($d->rol ?? ''); ?>" placeholder="Ej: Caja, Aseo, Técnico">

                <hr>
                <h3>Información Laboral</h3>

                <label><b>Tipo de Empleado</b></label>
                <select class="select2" name="tipo_empleado" id="tipo_empleado" onchange="document.getElementById('duracion_contrato_div').style.display = (this.value === 'Temporal' || this.value === 'Tiempo parcial') ? 'block' : 'none';">
                    <option value="Permanente" <?php echo ($d->tipo_empleado ?? '') === 'Permanente' ? 'selected' : ''; ?>>Permanente</option>
                    <option value="Temporal" <?php echo ($d->tipo_empleado ?? '') === 'Temporal' ? 'selected' : ''; ?>>Temporal</option>
                    <option value="Tiempo parcial" <?php echo ($d->tipo_empleado ?? '') === 'Tiempo parcial' ? 'selected' : ''; ?>>Tiempo parcial</option>
                </select>

                <div id="duracion_contrato_div" style="display:<?php echo in_array($d->tipo_empleado ?? '', ['Temporal', 'Tiempo parcial']) ? 'block' : 'none'; ?>; margin-top:10px;">
                    <label><b>Duración de Contrato</b></label>
                    <input type="text" name="duracion_contrato" value="<?php echo htmlspecialchars($d->duracion_contrato ?? ''); ?>" placeholder="Ej: 6 meses">
                </div>

                <label><b>Fecha de Ingreso</b></label>
                <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($d->fecha_ingreso ?? ''); ?>">

                <label><b>Departamento</b></label>
                <select class="select2" name="id_departamento" id="id_departament">
                    <option value="<?php echo (int) ($d->id_departamento ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Cargo / Posición</b></label>
                <select class="select2" name="id_cargo">
                    <option value="">Seleccione...</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id']; ?>" <?php echo ((int) ($d->id_cargo ?? 0)) === (int) $cargo['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cargo['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label><b>Horario</b></label>
                <select class="select2" name="id_horario" id="id_schedule">
                    <option value="<?php echo (int) ($d->id_horario ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Nivel Salarial</b></label>
                <select class="select2" name="id_salary_level" id="id_salary_level">
                    <option value="<?php echo (int) ($d->id_salary_level ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Salario Base</b></label>
                <input type="number" step="0.01" name="salario" value="<?php echo htmlspecialchars($d->salario ?? ''); ?>" placeholder="Ej: 15000.00">

                <label><b>N° Cuenta de BAC</b></label>
                <input type="text" name="cuenta_bac" value="<?php echo htmlspecialchars($d->cuenta_bac ?? ''); ?>" placeholder="Número de cuenta de banco BAC">

                <hr>
                <h3>Información de Contacto y Accesos</h3>

                <label><b>Teléfono Celular</b></label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($d->telefono ?? ''); ?>" placeholder="Ej: 99887766">

                <label><b>Correo Personal</b></label>
                <input type="email" name="correo_personal" value="<?php echo htmlspecialchars($d->correo_personal ?? ''); ?>" placeholder="Correo electrónico personal">

                <label><b>Correo Institucional (login)</b></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($d->email ?? ''); ?>" placeholder="Correo de la cuenta">

                <label><b>N° de Locker Asignado</b></label>
                <input type="text" name="num_locker" value="<?php echo htmlspecialchars($d->num_locker ?? ''); ?>" placeholder="Ej: L-10">

                <label><b>ID Empleado (Reloj Biométrico)</b></label>
                <input type="text" name="uid_biometrico" value="<?php echo htmlspecialchars($d->uid_biometrico ?? ''); ?>" placeholder="Ej: 123">

                <hr>
                <h3>Documentos</h3>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">Subir un documento nuevo reemplazará al anterior.</p>

                <label><b>Contrato Firmado</b></label>
                <input type="file" name="doc_contrato" accept=".pdf,.jpg,.png" style="padding:10px; border:1px solid #2980b9;">
                <?php if (!empty($d->has_contrato)): ?>
                    <br><a href="../../backend/php/view_staff_doc.php?id=<?php echo (int) $d->id; ?>&doc=contrato&table=users" target="_blank" class="badge-success" style="padding:5px; text-decoration:none;"><i class="bx bx-link-external"></i> Ver contrato actual</a>
                <?php endif; ?>
                <br><br>

                <hr>
                <button type="submit" name="upd_user_colab" class="registerbtn">Guardar Cambios</button>
                <button type="button" class="registerbtn" id="btn-desactivar-usuario" style="background:#c0392b;margin-top:10px;"
                    data-id="<?php echo (int) $d->id; ?>">Eliminar colaborador (desactivar)</button>
            </div>
        </form>
        <?php else: ?>
            <p class="alert alert-warning">No hay datos del colaborador solicitado.</p>
        <?php endif; ?>

    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>
<script src="../../backend/js/script.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php include_once '../../backend/php/upd_user_colaborador.php'; ?>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/js/cat_departaments.js"></script>
<script src="../../backend/js/cat_salary_levels.js"></script>
<script src="../../backend/js/cat_schedules.js"></script>
<script>
$(function () {
    $('#btn-desactivar-usuario').on('click', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: '¿Desactivar colaborador?',
            text: 'La cuenta pasará a Excolaboradores y no podrá iniciar sesión. No se elimina la información.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c0392b',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            $.ajax({
                url: '../../backend/php/toggle_user_state.php',
                type: 'POST',
                data: { id: id, state: 0 },
                dataType: 'json',
                success: function (resp) {
                    if (resp.success) {
                        Swal.fire('Desactivado', 'El colaborador pasó a Excolaboradores.', 'success')
                            .then(function () { window.location = 'lista_excolaboradores_usr.php'; });
                    } else {
                        Swal.fire('Error', resp.message || 'No se pudo desactivar.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                }
            });
        });
    });
});
</script>
</body>
</html>
