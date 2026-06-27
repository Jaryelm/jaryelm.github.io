<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
require_once '../../backend/registros/rrhh_guard.php';
medidata_staff_ensure_tables($connect);

$id = (int) ($_GET['id'] ?? 0);
$stmt_a = $connect->prepare("
        SELECT idadm, id_user, numide, nomadm, apeadm, nacadm, sexadm, state,
               num_empleado, tipo_empleado, duracion_contrato, fecha_ingreso,
               id_departamento, id_cargo, id_horario, id_salary_level, salario,
               cuenta_bac, telefono, correo_personal, correo_institucional,
               num_locker, id_biometrico, id_candidate_rrhh,
               (url_contrato IS NOT NULL AND url_contrato != '') AS has_contrato,
               (url_solicitud IS NOT NULL AND url_solicitud != '') AS has_solicitud,
               (url_psicometricas IS NOT NULL AND url_psicometricas != '') AS has_psicometricas
        FROM staff_administrative 
        WHERE idadm = ? LIMIT 1
    ");
$stmt_a->execute([$id]);
$data = $stmt_a->fetchAll(PDO::FETCH_OBJ);
$staffUsers = medidata_staff_fetch_users_for_select($connect);

// Consultar lista de cargos
$cargos = [];
try {
    $stmt_p = $connect->prepare("SELECT id, name FROM positions ORDER BY name ASC");
    $stmt_p->execute();
    $cargos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$rrhh_docs = null;
if (count($data) > 0 && !empty($data[0]->id_candidate_rrhh)) {
    $pdoRrhh = medidata_rrhh_pdo();
    if ($pdoRrhh) {
        $stmtHR = $pdoRrhh->prepare("SELECT * FROM hiring_requirements WHERE id_candidate = ? LIMIT 1");
        $stmtHR->execute([$data[0]->id_candidate_rrhh]);
        $rrhh_docs = $stmtHR->fetch(PDO::FETCH_ASSOC);
    }
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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
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
        <?php
        $hora = (int) date('H');
        $saludo = ($hora >= 6 && $hora < 12) ? 'Buenos DÃ­as' : (($hora >= 12 && $hora < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="rrhh-tab-nav" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px;">
    <a href="administrativo.php" class="button tab-button <?php echo ($current_page == 'administrativo.php' || $current_page == 'administrativo_usr.php') ? 'active' : ''; ?>">Personal Activo</a>
    <a href="administrativo_ex.php" class="button tab-button <?php echo ($current_page == 'administrativo_ex.php' || $current_page == 'administrativo_ex_usr.php') ? 'active' : ''; ?>">Ex Colaboradores</a>
    <a href="agregar_colaborador.php" class="button tab-button" style="background-color: #28a745; color: white;">Agregar Colaborador</a>
</div>

        <?php if (count($data) > 0): foreach ($data as $d): ?>
        <form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="return_page" value="administrativo.php">
            <div class="containerss">
                <h1>Actualizar colaborador administrativo</h1>
                <input type="hidden" name="admidp" value="<?php echo (int) $d->idadm; ?>">
                <hr>
                
                <label><b>NÂ° de Empleado (Institucional)</b></label>
                <input type="text" name="num_empleado" value="<?php echo htmlspecialchars($d->num_empleado ?? ''); ?>" placeholder="ejm: EMP-001">

                <label><b>NÂ° de identificaciÃ³n (DNI)</b></label><span class="badge-warning">*</span>
                <input type="text" name="admiden" maxlength="14" value="<?php echo htmlspecialchars($d->numide); ?>" required>
                
                <label><b>Nombres</b></label><span class="badge-warning">*</span>
                <input type="text" name="admnam" value="<?php echo htmlspecialchars($d->nomadm); ?>" required>
                
                <label><b>Apellidos</b></label><span class="badge-warning">*</span>
                <input type="text" name="admape" value="<?php echo htmlspecialchars($d->apeadm); ?>" required>
                
                <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
                <input type="date" name="admdat" value="<?php echo htmlspecialchars($d->nacadm); ?>" required>
                
                <label><b>GÃ©nero</b></label><span class="badge-warning">*</span>
                <select class="select2" name="admge" required>
                    <option value="Masculino" <?php echo $d->sexadm === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $d->sexadm === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                </select>

                <hr>
                <h3>InformaciÃ³n Laboral</h3>
                
                <label><b>Tipo de Empleado</b></label><span class="badge-warning">*</span>
                <select class="select2" name="tipo_empleado" id="tipo_empleado" required onchange="document.getElementById('duracion_contrato_div').style.display = (this.value === 'Temporal' || this.value === 'Tiempo parcial') ? 'block' : 'none';">
                    <option value="Permanente" <?php echo ($d->tipo_empleado ?? '') === 'Permanente' ? 'selected' : ''; ?>>Permanente</option>
                    <option value="Temporal" <?php echo ($d->tipo_empleado ?? '') === 'Temporal' ? 'selected' : ''; ?>>Temporal</option>
                    <option value="Tiempo parcial" <?php echo ($d->tipo_empleado ?? '') === 'Tiempo parcial' ? 'selected' : ''; ?>>Tiempo parcial</option>
                </select>

                <div id="duracion_contrato_div" style="display:<?php echo in_array($d->tipo_empleado ?? '', ['Temporal', 'Tiempo parcial']) ? 'block' : 'none'; ?>; margin-top:10px;">
                    <label><b>DuraciÃ³n de Contrato</b></label>
                    <input type="text" name="duracion_contrato" value="<?php echo htmlspecialchars($d->duracion_contrato ?? ''); ?>" placeholder="Ej: 6 meses">
                </div>

                <label><b>Fecha de Ingreso</b></label>
                <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($d->fecha_ingreso ?? ''); ?>">

                <label><b>Departamento</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_departamento" id="id_departament" required>
                    <option value="<?php echo (int)($d->id_departamento ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Cargo / PosiciÃ³n</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_cargo" required>
                    <option value="" disabled>Seleccione...</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id']; ?>" <?php echo ($d->id_cargo ?? 0) == $cargo['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cargo['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label><b>Horario</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_horario" id="id_schedule" required>
                    <option value="<?php echo (int)($d->id_horario ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Nivel Salarial</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_salary_level" id="id_salary_level" required>
                    <option value="<?php echo (int)($d->id_salary_level ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Salario Base</b></label>
                <input type="number" step="0.01" name="salario" value="<?php echo htmlspecialchars($d->salario ?? ''); ?>" placeholder="Ej: 15000.00">

                <label><b>NÂ° Cuenta de BAC</b></label>
                <input type="text" name="cuenta_bac" value="<?php echo htmlspecialchars($d->cuenta_bac ?? ''); ?>" placeholder="NÃºmero de cuenta de banco BAC">

                <hr>
                <h3>InformaciÃ³n de Contacto y Accesos</h3>

                <label><b>TelÃ©fono Celular</b></label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($d->telefono ?? ''); ?>" placeholder="Ej: 99887766">

                <label><b>Correo Personal</b></label>
                <input type="email" name="correo_personal" value="<?php echo htmlspecialchars($d->correo_personal ?? ''); ?>" placeholder="Correo electrÃ³nico personal">

                <label><b>Correo Institucional</b></label>
                <input type="email" name="correo_institucional" value="<?php echo htmlspecialchars($d->correo_institucional ?? ''); ?>" placeholder="Correo electrÃ³nico de Medicasa">

                <label><b>NÂ° de Locker Asignado</b></label>
                <input type="text" name="num_locker" value="<?php echo htmlspecialchars($d->num_locker ?? ''); ?>" placeholder="Ej: L-10">

                <label><b>ID Empleado (Reloj BiomÃ©trico)</b></label>
                <input type="number" name="id_biometrico" value="<?php echo htmlspecialchars($d->id_biometrico ?? ''); ?>" placeholder="Ej: 123">

                <label><b>Usuario del Sistema (Opcional)</b></label>
                <?php
                $staffUserFieldName = 'admid_user';
                $staffSelectedUserId = isset($d->id_user) ? (int) $d->id_user : 0;
                include '_staff_user_select.php';
                ?>
                
                <hr>
                <h3>Documentos (Opcionales)</h3>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">Subir un documento nuevo reemplazarÃ¡ al anterior.</p>
                
                <?php
                function _showDocLink($label, $url) {
                    if (!empty($url)) {
                        echo '<p style="margin-top:0; margin-bottom:10px; font-size:0.85rem;"><a href="'.htmlspecialchars($url).'" target="_blank"><i class="bx bx-link-external"></i> Ver '.$label.' actual</a></p>';
                    }
                }
                ?>

                <label>Solicitud de empleo (Ya guardada)</label>
                <input type="file" name="doc_solicitud" accept=".pdf,.doc,.docx,.jpg,.png" style="padding:10px;">
                <?php if ($d->has_solicitud): ?>
                    <br><a href="../../backend/php/view_staff_doc.php?id=<?php echo $d->idadm; ?>&doc=solicitud" target="_blank" class="badge-success" style="padding:5px; text-decoration:none;"><i class="bx bx-link-external"></i> Ver solicitud actual</a>
                <?php endif; ?>
                <br><br>
                
                <label>Pruebas PsicomÃ©tricas (Ya guardadas)</label>
                <input type="file" name="doc_psicometricas" accept=".pdf,.doc,.docx,.jpg,.png" style="padding:10px;">
                <?php if ($d->has_psicometricas): ?>
                    <br><a href="../../backend/php/view_staff_doc.php?id=<?php echo $d->idadm; ?>&doc=psicometricas" target="_blank" class="badge-success" style="padding:5px; text-decoration:none;"><i class="bx bx-link-external"></i> Ver pruebas actuales</a>
                <?php endif; ?>
                <br><br>

                <label>Copia de partida de nacimiento de hijos</label>
                <input type="file" name="doc_birth_cert_children" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('partida', $rrhh_docs['birth_cert_children'] ?? null); ?>
                
                <label>Foto (Para su Carnet)</label>
                <input type="file" name="doc_photo_id_card" accept=".jpg,.png" style="padding:10px;">
                <?php _showDocLink('foto', $rrhh_docs['photo_id_card'] ?? null); ?>
                
                <label>Documento de identidad (revÃ©s y derecho)</label>
                <input type="file" name="doc_id_document" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('documento de identidad', $rrhh_docs['id_document'] ?? null); ?>
                
                <label>Copia de recibo (agua, luz, telÃ©fono)</label>
                <input type="file" name="doc_utility_bill" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('recibo', $rrhh_docs['utility_bill'] ?? null); ?>
                
                <label>Antecedentes Penales</label>
                <input type="file" name="doc_criminal_record" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('antecedentes penales', $rrhh_docs['criminal_record'] ?? null); ?>
                
                <label>Antecedentes Policiales</label>
                <input type="file" name="doc_police_record" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('antecedentes policiales', $rrhh_docs['police_record'] ?? null); ?>
                
                <label>2 Referencias personales</label>
                <input type="file" name="doc_personal_references" accept=".pdf,.zip,.rar" style="padding:10px;">
                <?php _showDocLink('referencias personales', $rrhh_docs['personal_references'] ?? null); ?>
                
                <label>2 Referencias profesionales</label>
                <input type="file" name="doc_professional_references" accept=".pdf,.zip,.rar" style="padding:10px;">
                <?php _showDocLink('referencias profesionales', $rrhh_docs['professional_references'] ?? null); ?>
                
                <label>Diplomas o tÃ­tulos recibidos</label>
                <input type="file" name="doc_diplomas" accept=".pdf,.zip,.rar" style="padding:10px;">
                <?php _showDocLink('diplomas', $rrhh_docs['diplomas'] ?? null); ?>
                
                <label>Croquis de vivienda</label>
                <input type="file" name="doc_home_sketch" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('croquis', $rrhh_docs['home_sketch'] ?? null); ?>
                
                <label><b>Contrato Firmado (Ya guardado)</b></label>
                <input type="file" name="doc_contrato" accept=".pdf,.jpg,.png" style="padding:10px; border:1px solid #2980b9;">
                <?php if ($d->has_contrato): ?>
                    <br><a href="../../backend/php/view_staff_doc.php?id=<?php echo $d->idadm; ?>&doc=contrato" target="_blank" class="badge-success" style="padding:5px; text-decoration:none;"><i class="bx bx-link-external"></i> Ver contrato actual</a>
                <?php endif; ?>
                <br><br>

                <hr>
                <button type="submit" name="upd_administrative" class="registerbtn">Guardar Cambios</button>
                <button type="button" class="registerbtn btn-delete-staff" style="background:#c0392b;margin-top:10px;"
                    data-id="<?php echo (int) $d->idadm; ?>">Eliminar colaborador</button>
            </div>
        </form>
        <?php endforeach; else: ?>
        <p class="alert alert-warning">No hay datos</p>
        <?php endif; ?>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

<script src="../../backend/js/script.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php include_once '../../backend/php/upd_administrative.php'; ?>
<script>
window.MEDIDATA_STAFF_ADMIN = {
    toggleSelector: '.staff-state-toggle',
    deleteSelector: '.btn-delete-staff',
    toggleUrl: '../../backend/php/toggle_administrative_state.php',
    deleteUrl: '../../backend/php/delete_administrative.php',
    idParam: 'idadm',
    deleteTitle: 'Â¿Eliminar colaborador administrativo?',
    deleteFn: 'deleteAdministrative'
};
</script>
<script src="../../backend/registros/script/tabla_personal_staff.js"></script>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/js/cat_departaments.js"></script>
<script src="../../backend/js/cat_salary_levels.js"></script>
<script src="../../backend/js/cat_schedules.js"></script>
</body>
</html>

