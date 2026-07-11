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
            <div class="containerss staff-edit-form">
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

                <label><b>Horario</b></label>
                <select class="select2" name="id_horario" id="id_schedule">
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

                <?php
                $staffUserFieldName = 'nurid_user';
                $staffSelectedUserId = isset($d->id_user) ? (int) $d->id_user : 0;
                include '../recursos_humanos/_staff_user_select.php';
                ?>
                
                <?php
                $staffDocId = (int) $d->idnur;
                $staffDocTable = 'nurse';
                $staffDocIdcol = 'idnur';
                $staffDocRow = $d;
                $staffDocRrhh = $rrhh_docs ?? null;
                include __DIR__ . '/../recursos_humanos/_staff_edit_documentos_section.php';
                ?>

                <div class="staff-form-actions">
                <button type="submit" name="upd_nurse" class="registerbtn">Guardar Cambios</button>
                <button type="button" class="registerbtn btn-delete-staff" style="background:#c0392b;margin-top:10px;"
                    data-id="<?php echo (int) $d->idnur; ?>">Eliminar colaborador</button>
                </div>
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


