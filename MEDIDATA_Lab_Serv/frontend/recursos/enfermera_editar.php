<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);
$staffUsers = medidata_staff_fetch_users_for_select($connect);
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
<?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.6.9/sweetalert2.min.css">



    <title>MEDIDATA</title>
</head>
<body>

<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

    <!-- NAVBAR -->
    <section id="content">

        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">
                </div>
            </form>
            
           
            <span class="divider"></span>
            <?php
include_once '../admin/perfil.php';
// incuir el archivo menu principal
?>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->

        <main>
        <?php
// Obtener la hora actual
$hora_actual = date('H'); // Obtiene la hora en formato de 24 horas (0-23)

if ($hora_actual >= 6 && $hora_actual < 12) {
    $saludo = "Buenos Días";
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = "Buenas Tardes";
} else {
    $saludo = "Buenas Noches";
}
?>

<h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, '../recursos_humanos/lista_colaboradores.php')">Personal Activo</button>
        <button class="button" onclick="cambiarColor(this, '../recursos_humanos/lista_excolaboradores.php')">Ex Enfermería</button>
        <button class="button" onclick="cambiarColor(this, 'enfermera_nuevo.php')">Registrar Enfermería</button>
<!-- multistep form -->

<?php 
 $id = (int) ($_GET['id'] ?? 0);
 $sentencia = $connect->prepare("SELECT *,
        (url_contrato IS NOT NULL AND url_contrato != '') AS has_contrato,
        (url_solicitud IS NOT NULL AND url_solicitud != '') AS has_solicitud,
        (url_psicometricas IS NOT NULL AND url_psicometricas != '') AS has_psicometricas
    FROM nurse WHERE idnur = :id LIMIT 1");
 $sentencia->execute([':id' => $id]);

$data =  array();
if($sentencia){
  while($r = $sentencia->fetchObject()){
    $data[] = $r;
  }
}
   ?>
   <?php
// Consultar lista de cargos (positions) de la base de datos principal
$cargos = [];
try {
    $stmt_p = $connect->prepare("SELECT id, name FROM positions ORDER BY name ASC");
    $stmt_p->execute();
    $cargos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<?php if(count($data)>0):?>
        <?php foreach($data as $d):?>

<form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="return_page" value="enfermera.php">
            <div class="containerss">
                <h1>Actualizar enfermero(a)</h1>
                <input type="hidden" name="nuridp" value="<?php echo (int) $d->idnur; ?>">
                <hr>
                
                <label><b>N° de Empleado (Institucional)</b></label>
                <input type="text" name="num_empleado" value="<?php echo htmlspecialchars($d->num_empleado ?? ''); ?>" placeholder="ejm: EMP-001">

                <label><b>N° de identificación (DNI)</b></label><span class="badge-warning">*</span>
                <input type="text" name="nuriden" maxlength="14" value="<?php echo htmlspecialchars($d->numide); ?>" required>
                
                <label><b>Nombres</b></label><span class="badge-warning">*</span>
                <input type="text" name="nurnam" value="<?php echo htmlspecialchars($d->nomnur); ?>" required>
                
                <label><b>Apellidos</b></label><span class="badge-warning">*</span>
                <input type="text" name="nurape" value="<?php echo htmlspecialchars($d->apenur); ?>" required>
                
                <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
                <input type="date" name="nurdat" value="<?php echo htmlspecialchars($d->nacinur); ?>" required>
                
                <label><b>Género</b></label><span class="badge-warning">*</span>
                <select class="select2" name="nurge" required>
                    <option value="Masculino" <?php echo $d->sexnur === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $d->sexnur === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                </select>

                <hr>
                <h3>Información Laboral</h3>
                
                <label><b>Tipo de Empleado</b></label><span class="badge-warning">*</span>
                <select class="select2" name="tipo_empleado" id="tipo_empleado" required onchange="document.getElementById('duracion_contrato_div').style.display = (this.value === 'Temporal' || this.value === 'Tiempo parcial') ? 'block' : 'none';">
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

                <label><b>Departamento</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_departamento" id="id_departament" required>
                    <option value="<?php echo (int)($d->id_departamento ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Cargo / Posición</b></label><span class="badge-warning">*</span>
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

                <label><b>N° Cuenta de BAC</b></label>
                <input type="text" name="cuenta_bac" value="<?php echo htmlspecialchars($d->cuenta_bac ?? ''); ?>" placeholder="Número de cuenta de banco BAC">

                <hr>
                <h3>Información de Contacto y Accesos</h3>

                <label><b>Teléfono Celular</b></label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($d->telefono ?? ''); ?>" placeholder="Ej: 99887766">

                <label><b>Correo Personal</b></label>
                <input type="email" name="correo_personal" value="<?php echo htmlspecialchars($d->correo_personal ?? ''); ?>" placeholder="Correo electrónico personal">

                <label><b>Correo Institucional</b></label>
                <input type="email" name="correo_institucional" value="<?php echo htmlspecialchars($d->correo_institucional ?? ''); ?>" placeholder="Correo electrónico de Medicasa">

                <label><b>N° de Locker Asignado</b></label>
                <input type="text" name="num_locker" value="<?php echo htmlspecialchars($d->num_locker ?? ''); ?>" placeholder="Ej: L-10">

                <label><b>ID Empleado (Reloj Biométrico)</b></label>
                <input type="number" name="id_biometrico" value="<?php echo htmlspecialchars($d->id_biometrico ?? ''); ?>" placeholder="Ej: 123">

                <label><b>Usuario del Sistema (Opcional)</b></label>
                <?php
                $staffUserFieldName = 'nurid_user';
                $staffSelectedUserId = isset($d->id_user) ? (int) $d->id_user : 0;
                include '../recursos_humanos/_staff_user_select.php';
                ?>
                
                <hr>
                <h3>Documentos (Opcionales)</h3>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">Subir un documento nuevo reemplazará al anterior.</p>
                
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
                    <br><a href="../../backend/php/view_staff_doc.php?id=<?php echo $d->idnur; ?>&doc=solicitud" target="_blank" class="badge-success" style="padding:5px; text-decoration:none;"><i class="bx bx-link-external"></i> Ver solicitud actual</a>
                <?php endif; ?>
                <br><br>
                
                <label>Pruebas Psicométricas (Ya guardadas)</label>
                <input type="file" name="doc_psicometricas" accept=".pdf,.doc,.docx,.jpg,.png" style="padding:10px;">
                <?php if ($d->has_psicometricas): ?>
                    <br><a href="../../backend/php/view_staff_doc.php?id=<?php echo $d->idnur; ?>&doc=psicometricas" target="_blank" class="badge-success" style="padding:5px; text-decoration:none;"><i class="bx bx-link-external"></i> Ver pruebas actuales</a>
                <?php endif; ?>
                <br><br>

                <label>Copia de partida de nacimiento de hijos</label>
                <input type="file" name="doc_birth_cert_children" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('partida', $rrhh_docs['birth_cert_children'] ?? null); ?>
                
                <label>Foto (Para su Carnet)</label>
                <input type="file" name="doc_photo_id_card" accept=".jpg,.png" style="padding:10px;">
                <?php _showDocLink('foto', $rrhh_docs['photo_id_card'] ?? null); ?>
                
                <label>Documento de identidad (revés y derecho)</label>
                <input type="file" name="doc_id_document" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('documento de identidad', $rrhh_docs['id_document'] ?? null); ?>
                
                <label>Copia de recibo (agua, luz, teléfono)</label>
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
                
                <label>Diplomas o títulos recibidos</label>
                <input type="file" name="doc_diplomas" accept=".pdf,.zip,.rar" style="padding:10px;">
                <?php _showDocLink('diplomas', $rrhh_docs['diplomas'] ?? null); ?>
                
                <label>Croquis de vivienda</label>
                <input type="file" name="doc_home_sketch" accept=".pdf,.jpg,.png" style="padding:10px;">
                <?php _showDocLink('croquis', $rrhh_docs['home_sketch'] ?? null); ?>
                
                <label><b>Contrato Firmado (Ya guardado)</b></label>
                <input type="file" name="doc_contrato" accept=".pdf,.jpg,.png" style="padding:10px; border:1px solid #2980b9;">
                <?php if ($d->has_contrato): ?>
                    <br><a href="../../backend/php/view_staff_doc.php?id=<?php echo $d->idnur; ?>&doc=contrato" target="_blank" class="badge-success" style="padding:5px; text-decoration:none;"><i class="bx bx-link-external"></i> Ver contrato actual</a>
                <?php endif; ?>
                <br><br>

                <hr>
                <button type="submit" name="upd_nurse" class="registerbtn">Guardar Cambios</button>
                <button type="button" class="registerbtn btn-delete-staff" style="background:#c0392b;margin-top:10px;"
                    data-id="<?php echo (int) $d->idnur; ?>">Eliminar colaborador</button>
            </div>
        </form>

<?php endforeach; ?>
  
    <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>

        </main>
        <!-- MAIN -->
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_foot.php'; ?>

    <!-- NAVBAR -->
    
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/multistep.js"></script>
    <script src="../../backend/js/vpat.js"></script>
   
   <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <?php include_once '../../backend/php/upd_nurse.php' ?>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
   
<script src="../../backend/js/cat_departaments.js"></script>
<script src="../../backend/js/cat_salary_levels.js"></script>
<script src="../../backend/js/cat_schedules.js"></script>
</body>
</html>


