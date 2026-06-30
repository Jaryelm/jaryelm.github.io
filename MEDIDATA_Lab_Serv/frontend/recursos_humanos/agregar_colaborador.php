<?php
include_once '../../backend/registros/session_check.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header('Location: lista_colaboradores.php');
    exit;
}
require_once '../../backend/php/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$staffUsers = medidata_staff_fetch_users_for_select($connect);

$cargos = [];
try {
    $stmt_p = $connect->prepare("SELECT id, name FROM positions ORDER BY name ASC");
    $stmt_p->execute();
    $cargos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
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
    <title>MEDIDATA - Agregar Colaborador</title>
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
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>
    <main>
        <?php
        $hora = (int) date('H');
        $saludo = ($hora >= 6 && $hora < 12) ? 'Buenos Días' : (($hora >= 12 && $hora < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
        
        <div class="rrhh-tab-nav" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px;">
            <a href="lista_colaboradores.php?area=todos" class="button tab-button">Todos</a>
            <a href="lista_colaboradores.php?area=medico" class="button tab-button">Médicos</a>
            <a href="lista_colaboradores.php?area=enfermeria" class="button tab-button">Enfermería</a>
            <a href="lista_colaboradores.php?area=administrativo" class="button tab-button">Administrativos</a>
            <a href="lista_colaboradores.php?area=servicios_generales" class="button tab-button">Servicios Generales</a>
            <a href="lista_excolaboradores.php" class="button tab-button">Excolaboradores</a>
            <a href="agregar_colaborador.php" class="button tab-button active" style="background-color: #28a745; color: white;">Agregar Colaborador</a>
        </div>

        <form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
            <input type="hidden" name="return_page" value="lista_colaboradores.php">
            <div class="containerss">
                <h1>Nuevo Colaborador</h1>
                <div class="alert-danger">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                    <strong>Importante:</strong> Complete los campos marcados con <span class="badge-warning">*</span>
                </div>
                <hr>
                
                <label><b>Área o Tipo de Colaborador</b></label><span class="badge-warning">*</span>
                <select class="select2" name="area_colaborador" required>
                    <option value="">Seleccione un área...</option>
                    <option value="doctor">Médico</option>
                    <option value="nurse">Enfermería</option>
                    <option value="staff_administrative">Administrativo</option>
                    <option value="staff_general_services">Servicios Generales</option>
                </select>

                <hr>

                <label><b>N° de Empleado (Institucional)</b></label>
                <input type="text" name="num_empleado" placeholder="ejm: EMP-001 (o dejar en blanco para automático)">

                <label><b>N° de identificación (DNI)</b></label><span class="badge-warning">*</span>
                <input type="text" name="identificacion" maxlength="14" placeholder="ejm: 0801199012345" required>
                
                <label><b>Nombres</b></label><span class="badge-warning">*</span>
                <input type="text" name="nombres" placeholder="ejm: Juan Raúl" required>
                
                <label><b>Apellidos</b></label><span class="badge-warning">*</span>
                <input type="text" name="apellidos" placeholder="ejm: Ramírez Requena" required>
                
                <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
                <input type="date" name="fecha_nacimiento" required>
                
                <label><b>Género</b></label><span class="badge-warning">*</span>
                <select class="select2" name="genero" required>
                    <option value="">Seleccione</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                </select>

                <hr>
                <h3>Información Laboral</h3>
                
                <label><b>Tipo de Empleado</b></label><span class="badge-warning">*</span>
                <select class="select2" name="tipo_empleado" id="tipo_empleado" required onchange="document.getElementById('duracion_contrato_div').style.display = (this.value === 'Temporal' || this.value === 'Tiempo parcial') ? 'block' : 'none';">
                    <option value="Permanente">Permanente</option>
                    <option value="Temporal">Temporal</option>
                    <option value="Tiempo parcial">Tiempo parcial</option>
                </select>

                <div id="duracion_contrato_div" style="display:none; margin-top:10px;">
                    <label><b>Duración de Contrato</b></label>
                    <input type="text" name="duracion_contrato" placeholder="Ej: 6 meses">
                </div>

                <label><b>Fecha de Ingreso</b></label><span class="badge-warning">*</span>
                <input type="date" name="fecha_ingreso" required>

                <label><b>Departamento</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_departamento" id="id_departament" required>
                    <option value="" disabled selected>Seleccione...</option>
                </select>

                <label><b>Cargo / Posición</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_cargo" required>
                    <option value="" disabled selected>Seleccione...</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id']; ?>"><?php echo htmlspecialchars($cargo['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label><b>Horario</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_horario" id="id_schedule" required>
                    <option value="" disabled selected>Seleccione...</option>
                </select>

                <label><b>Nivel Salarial</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_salary_level" id="id_salary_level" required>
                    <option value="" disabled selected>Seleccione...</option>
                </select>

                <label><b>Salario Base</b></label>
                <input type="number" step="0.01" name="salario" placeholder="Ej: 15000.00">

                <label><b>N° Cuenta de BAC</b></label>
                <input type="text" name="cuenta_bac" placeholder="Número de cuenta de banco BAC">

                <hr>
                <h3>Información de Contacto y Accesos</h3>

                <label><b>Teléfono Celular</b></label>
                <input type="text" name="telefono" placeholder="Ej: 99887766">

                <label><b>Correo Personal</b></label>
                <input type="email" name="correo_personal" placeholder="Correo electrónico personal">

                <label><b>Correo Institucional</b></label>
                <input type="email" name="correo_institucional" placeholder="Correo electrónico de Medicasa">

                <label><b>N° de Locker Asignado</b></label>
                <input type="text" name="num_locker" placeholder="Ej: L-10">

                <label><b>ID Empleado (Reloj Biométrico)</b></label>
                <input type="number" name="id_biometrico" placeholder="Ej: 123">

                <label><b>Usuario del Sistema (Opcional)</b></label>
                <?php
                $staffUserFieldName = 'id_user';
                $staffSelectedUserId = 0;
                include '_staff_user_select.php';
                ?>
                
                <hr>
                <h3>Documentos (Opcionales)</h3>
                
                <label>Solicitud de empleo</label>
                <input type="file" name="doc_solicitud" accept=".pdf,.doc,.docx,.jpg,.png" style="padding:10px;">
                
                <label>Pruebas Psicométricas</label>
                <input type="file" name="doc_psicometricas" accept=".pdf,.doc,.docx,.jpg,.png" style="padding:10px;">
                
                <label>Copia de partida de nacimiento de hijos</label>
                <input type="file" name="doc_birth_cert_children" accept=".pdf,.jpg,.png" style="padding:10px;">
                
                <label>Foto (Para su Carnet)</label>
                <input type="file" name="doc_photo_id_card" accept=".jpg,.png" style="padding:10px;">
                
                <label>Documento de identidad (revés y derecho)</label>
                <input type="file" name="doc_id_document" accept=".pdf,.jpg,.png" style="padding:10px;">
                
                <label>Copia de recibo (agua, luz, teléfono)</label>
                <input type="file" name="doc_utility_bill" accept=".pdf,.jpg,.png" style="padding:10px;">
                
                <label>Antecedentes Penales</label>
                <input type="file" name="doc_criminal_record" accept=".pdf,.jpg,.png" style="padding:10px;">
                
                <label>Antecedentes Policiales</label>
                <input type="file" name="doc_police_record" accept=".pdf,.jpg,.png" style="padding:10px;">
                
                <label>2 Referencias personales</label>
                <input type="file" name="doc_personal_references" accept=".pdf,.zip,.rar" style="padding:10px;">
                
                <label>2 Referencias profesionales</label>
                <input type="file" name="doc_professional_references" accept=".pdf,.zip,.rar" style="padding:10px;">
                
                <label>Diplomas o títulos recibidos</label>
                <input type="file" name="doc_diplomas" accept=".pdf,.zip,.rar" style="padding:10px;">
                
                <label>Croquis de vivienda</label>
                <input type="file" name="doc_home_sketch" accept=".pdf,.jpg,.png" style="padding:10px;">
                
                <label><b>Contrato Firmado</b></label>
                <input type="file" name="doc_contrato" accept=".pdf,.jpg,.png" style="padding:10px; border:1px solid #2980b9;">
                <hr>
                <button type="submit" name="add_colaborador" class="registerbtn">Guardar</button>
            </div>
        </form>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>
<script src="../../backend/js/script.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php include_once '../../backend/php/add_colaborador.php'; ?>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/js/cat_departaments.js"></script>
<script src="../../backend/js/cat_salary_levels.js"></script>
<script src="../../backend/js/cat_schedules.js"></script>
</body>
</html>

