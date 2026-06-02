<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Include CSS de Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">



    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../almacen/menu.php';
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
include_once '../almacen/perfil.php';
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

        <!-- Opciones de Navegación -->
        <button class="button" onclick="cambiarColor(this, 'compra_unificada.php')">Compra e inventario</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_compras_user.php')">Compras Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_user.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'reorden_user.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_solicitud_reorden.php')">Autorización Compras Almacen</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones_user.php')">Requisiciones</button>

        <form action="" enctype="multipart/form-data" method="POST"  autocomplete="off" onsubmit="return validacion()">
  <div class="containerss">
    <h1>Registrar Inventario</h1>
    
    <br>
   
    <div class="alert-danger">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
</div>
    <hr>
    <br>

<!-- Campo Nombre del Producto Dinámico -->
<label for="mediname"><b>Nombre del Producto</b></label><span class="badge-warning">*</span>
<select id="mediname" name="mediname" class="select2" style="width: 100%;">
    <option value="">Seleccione o busque un producto</option>
    <!-- Opciones dinámicas de productos se cargarán aquí -->
</select>

<br><br>

<label for="principio_activo"><b>Principio Activo</b></label>
<input type="text" id="principio_activo" name="principio_activo">

<label for="linea"><b>Linea</b></label>
<select name="linea" id="linea" class="select2" onchange="actualizarCodigo()">
    <option value="">Seleccione</option>
    <option value="MEDICAMENTOS" data-codigo="110400102">MEDICAMENTOS</option>
    <option value="MATERIAL DESCARTABLE" data-codigo="110400103">MATERIAL DESCARTABLE</option>
    <option value="SERVICIOS DE HOSPITALIZACIÓN" data-codigo="110400102">SERVICIOS DE HOSPITALIZACIÓN</option>
    <option value="DIAGNOSTICO RADIOLOGÍA E IMAGEN" data-codigo="110400102">DIAGNOSTICO RADIOLOGÍA E IMAGEN</option>
    <option value="LABORATORIO" data-codigo="110400102">LABORATORIO</option>
    <option value="INSUMOS DESCARTABLES" data-codigo="110400102">INSUMOS DESCARTABLES</option>
    <option value="EMERGENCIA" data-codigo="110400102">EMERGENCIA</option>
    <option value="PROCEDIMIENTO CIRUGÍA MENOR" data-codigo="110400102">PROCEDIMIENTO CIRUGÍA MENOR</option>
    <option value="OBSERVACIÓN" data-codigo="110400102">OBSERVACIÓN</option>
    <option value="CIRUGÍA / QUIROFANO" data-codigo="110400102">CIRUGÍA / QUIROFANO</option>
    <option value="UCI" data-codigo="110400102">UCI</option>
    <option value="UNIDAD DIGESTIVA / GASTROENTEROLOGÍA" data-codigo="110400102">UNIDAD DIGESTIVA / GASTROENTEROLOGÍA</option>
    <option value="SERVICIO DE AMBULANCIA" data-codigo="110400102">SERVICIO DE AMBULANCIA</option>
    <option value="MEDICO POR LLAMADO" data-codigo="110400102">MEDICO POR LLAMADO</option>
    <option value=">ATENCIÓN MÉDICA A DOMICILIO" data-codigo="110400102">ATENCIÓN MÉDICA A DOMICILIO</option>
    <option value="CARDIOLOGÍA UNIDAD" data-codigo="110400102">CARDIOLOGÍA UNIDAD</option>
    <option value="NEUROLOGÍA UNIDAD" data-codigo="110400102">NEUROLOGÍA UNIDAD</option>
    <option value="SERVICIO DE LABOR Y PARTO" data-codigo="110400102">SERVICIO DE LABOR Y PARTO</option>
    <option value="SUMINISTRO DE OFICINA" data-codigo="110400103">SUMINISTRO DE OFICINA</option>
    <option value="CUIDADOS/MATERNIDAD DE MUJER (AMBULATORIO)" data-codigo="110400102">CUIDADOS/MATERNIDAD DE MUJER (AMBULATORIO)</option>
    <option value="OTORRINOLARINGOLOGÍA" data-codigo="110400102">OTORRINOLARINGOLOGÍA</option>
    <option value="UROLOGÍA" data-codigo="110400102">UROLOGÍA</option>
    <option value="HEMATOLOGÍA" data-codigo="110400102">HEMATOLOGÍA</option>
    <option value="NEUMOLOGÍA" data-codigo="110400102">NEUMOLOGÍA</option>
    <option value="EQUIPO QUIRÚRGICO / INSTRUMENTAL" data-codigo="110400102">EQUIPO QUIRÚRGICO / INSTRUMENTAL</option>
    <option value="PROMOCIONES" data-codigo="110400103">PROMOCIONES</option>
    <option value="PODOLOGÍA" data-codigo="110400102">PODOLOGÍA</option>
    <option value="ARRENDAMIENTO" data-codigo="110400103">ARRENDAMIENTO</option>
    <option value="PROCEDIMIENTO" data-codigo="110400102">PROCEDIMIENTO</option>
    <option value="ALQUILER" data-codigo="110400103">ALQUILER</option>
</select>

<label for="sub_linea"><b>Área de Servicio</b></label>
<select name="sub_linea" id="sub_linea" class="select2">
    <option value="">Seleccionar...</option>
    <option value="GENERAL">GENERAL</option>
    <option value="HOSPITALARIOS">HOSPITALARIOS</option>
    <option value="HEMATOLOGÍA">HEMATOLOGÍA</option>
    <option value="QUÍMICA CLÍNICA">QUÍMICA CLÍNICA</option>
    <option value="INMUNOLOGÍA">INMUNOLOGÍA</option>
    <option value="ENDOCRINOLOGÍA">ENDOCRINOLOGÍA</option>
    <option value="DROGAS DE ABUSO">DROGAS DE ABUSO</option>
    <option value="BACTERIOLOGÍA">BACTERIOLOGÍA</option>
    <option value="COAGULACIÓN">COAGULACIÓN</option>
    <option value="COPROANÁLISIS">COPROANÁLISIS</option>
    <option value="UROANÁLISIS">UROANÁLISIS</option>
    <option value="BANCO DE SANGRE">BANCO DE SANGRE</option>
    <option value="GESTIONES DE LABORATORIO DIVERSAS">GESTIONES DE LABORATORIO DIVERSAS</option>
    <option value="SUTURAS MÉDICAS">SUTURAS MÉDICAS</option>
    <option value="TUBOS ENDOTRAQUEALES">TUBOS ENDOTRAQUEALES</option>
    <option value="SONDAS">SONDAS</option>
    <option value="VENDAS MÉDICAS">VENDAS MÉDICAS</option>
    <option value="SUMINISTROS VARIOS">SUMINISTROS VARIOS</option>
    <option value="MATERIALES MÉDICOS QUIRÚRGICOS">MATERIALES MÉDICOS QUIRÚRGICOS</option>
    <option value="MATERIALES DE RADIOLOGÍA E IMÁGENES">MATERIALES DE RADIOLOGÍA E IMÁGENES</option>
    <option value="MATERIALES DE LABORATORIO CLÍNICO">MATERIALES DE LABORATORIO CLÍNICO</option>
    <option value="OTROS INVENTARIOS">OTROS INVENTARIOS</option>
    <option value="SERVICIOS DE LABORATORIO CLÍNICO">SERVICIOS DE LABORATORIO CLÍNICO</option>
    <option value="SERVICIOS DE RADILOGÍA E IMAGEN">SERVICIOS DE RADILOGÍA E IMAGEN</option>
    <option value="ALIMENTACIÓN (DIETAS)">ALIMENTACIÓN (DIETAS)</option>
    <option value="SERVICIOS DE TERAPIA RESPIRATORIA">SERVICIOS DE TERAPIA RESPIRATORIA</option>
    <option value="SERVICIOS DE ENFERMERÍA">SERVICIOS DE ENFERMERÍA</option>
    <option value="SERVICIOS DE CONSULTA DE EMERGENCIA">SERVICIOS DE CONSULTA DE EMERGENCIA</option>
    <option value="PAQUETES QUIRÚRGICOS">PAQUETES QUIRÚRGICOS</option>
    <option value="HABITACIONES">HABITACIONES</option>
    <option value="INSUMOS INDIRECTOS (MATERIALES)">INSUMOS INDIRECTOS (MATERIALES)</option>
    <option value="ANESTÉSICOS Y GASES">ANESTÉSICOS Y GASES</option>
    <option value="ROPERÍA">ROPERÍA</option>
    <option value="SALAS DE INTERVENCIÓN">SALAS DE INTERVENCIÓN</option>
    <option value="INSTRUMENTAL">INSTRUMENTAL</option>
    <option value="EQUIPO MÉDICO">EQUIPO MÉDICO</option>
    <option value="SERVICIOS VARIOS">SERVICIOS VARIOS</option>
    <option value="ALQUILER DE LOCALES">ALQUILER DE LOCALES</option>
    <option value="ABDOMEN">ABDOMEN</option>
    <option value="CABEZA">CABEZA</option>
    <option value="COLUMNA Y PELVIS">COLUMNA Y PELVIS</option>
    <option value="ESTUDIOS ESPECIALES">ESTUDIOS ESPECIALES</option>
    <option value="EXTREMIDAD INFERIOR">EXTREMIDAD INFERIOR</option>
    <option value="EXTREMIDAD SUPERIOR">EXTREMIDAD SUPERIOR</option>
    <option value="rayos_x">RAYOS X</option>
    <option value="TÓRAX">TÓRAX</option>
    <option value="ULTRASONIDO">ULTRASONIDO</option>
    <option value="DESCUENTOS ESPECIALES">DESCUENTOS ESPECIALES</option>
    <option value="SUMINISTROS DE LIMPIEZA">SUMINISTROS DE LIMPIEZA</option>
    <option value="EMERGENCIA">EMERGENCIA</option>
    <option value="MATERIALES DE OFICINA">MATERIALES DE OFICINA</option>
    <option value="CABEZA / TOMOGRAFÍA">CABEZA / TOMOGRAFÍA</option>
    <option value="CUELLO / TOMOGRAFÍA">CUELLO / TOMOGRAFÍA</option>
    <option value="TÓRAX / TOMOGRAFÍA">TÓRAX / TOMOGRAFÍA</option>
    <option value="ABDOMEN / TOMOGRAFÍA">ABDOMEN / TOMOGRAFÍA</option>
    <option value="MIEMBROS SUPERIORES / TOMOGRAFÍA">MIEMBROS SUPERIORES / TOMOGRAFÍA</option>
    <option value="ESPECIALES / TOMOGRAFÍA">ESPECIALES / TOMOGRAFÍA</option>
    <option value="MAMOGRAFÍA">MAMOGRAFÍA</option>
    <option value="ODONTOLOGÍA GENERAL">ODONTOLOGÍA GENERAL</option>
    <option value="ENDODONCIA">ENDODONCIA</option>
    <option value="PERIODONCIA E IMPLANTOLOGÍA">PERIODONCIA E IMPLANTOLOGÍA</option>
    <option value="ARMONIZACIÓN OROFACIAL">ARMONIZACIÓN OROFACIAL</option>
    <option value="PROSTODONCIA E IMPLANTOLOGÍA">PROSTODONCIA E IMPLANTOLOGÍA</option>
    <option value="ORTODONCIA Y ORTOPEDIA MAXILOFACIAL">ORTODONCIA Y ORTOPEDIA MAXILOFACIAL</option>
    <option value="ODONTOLOGÍA PEDIÁTRICA">ODONTOLOGÍA PEDIÁTRICA</option>
    <option value="ambulancia">AMBULANCIA</option>
    <option value="RADIODIAGNÓSTICO DENTAL">RADIODIAGNÓSTICO DENTAL</option>
    <option value="RAYOS X">RAYOS X</option>
    <option value="TOMOGRAFÍA">TOMOGRAFÍA</option>
    <option value="ANGIO TOMOGRAFÍA">ANGIO TOMOGRAFÍA</option>
    <option value="SALA CUNA">SALA CUNA</option>
    <option value="ATERNIDAD SALA HOSPITAL">MATERNIDAD SALA HOSPITAL</option>
    <option value="DIA DEL PADRE">DIA DEL PADRE</option>
    <option value="CARDIOLOGÍA PEDIÁTRICO">CARDIOLOGÍA PEDIÁTRICO</option>
    <option value="CARDIOLOGÍA ADULTO">CARDIOLOGÍA ADULTO</option>
    <option value="CLÍNICAS MEDICASA">CLÍNICAS MEDICASA</option>
    <option value="BAAF">BAAF</option>
    <option value="PROCEDIMIENTOS">PROCEDIMIENTOS</option>
    <option value="HONORARIOS MÉDICOS">HONORARIOS MÉDICOS</option>
</select>

<script>
    $(document).ready(function() {
        // Inicializa Select2
        $('.select2').select2();

        // Elementos del DOM
        const lineaSelect = $("#linea");
        
        // Seleccionar los contenedores de los campos con `.form-group`
        const catContainer = $("#cat").closest(".form-group");
        const presentacionContainer = $("#presentacion").closest(".form-group");
        const formaFarmaceuticaContainer = $("#forma_farmaceutica").closest(".form-group");
        const concentracionContainer = $("#concentracion").closest(".form-group");
        const viaAdministracionContainer = $("#via_administracion").closest(".form-group");

        // Campos a mostrar/ocultar
        const fieldsToToggle = [
            catContainer,
            presentacionContainer,
            formaFarmaceuticaContainer,
            concentracionContainer,
            viaAdministracionContainer
        ];

        // Función de alternancia de visibilidad
        function toggleFields() {
            const selectedOption = lineaSelect.val();
            console.log("Opción seleccionada:", selectedOption); // Depuración

            if (selectedOption === "MEDICAMENTOS") {
                console.log("Mostrando campos para 'MEDICAMENTOS'"); // Depuración
                fieldsToToggle.forEach(container => container.show());
            } else {
                console.log("Ocultando campos para opción:", selectedOption); // Depuración
                fieldsToToggle.forEach(container => container.hide());
            }
        }

        // Evento en el cambio de selección y carga inicial
        lineaSelect.change(toggleFields);
        toggleFields(); // Ejecutar en carga inicial
    });
</script>

<!-- "cat" no va si la opcion es material descartable -->
<div class="form-group form-section">
<label for="psw"><b>Categoria de Uso</b></label>
    <select name="medicate" id="cat" class="select2">
        <option>Seleccionar...</option>
        
    </select>
</div>

<!-- "presentacion" no va si es material descartable -->
<div class="form-group form-section">
<label for="presentacion"><b>Presentación</b></label>
<select name="presentacion" id="presentacion" class="select2">
    <option value="">Seleccionar...</option>
    <option value="TABLETA">TABLETA</option>
    <option value="COMPRIMIDO">COMPRIMIDO</option>
    <option value="AMPOLLA">AMPOLLA</option>
    <option value="CAJA">CAJA</option>
    <option value="SOBRE">SOBRE</option>
    <option value="FRASCO">FRASCO</option>
    <option value="TUBO">TUBO</option>
    <option value="BOLSA">BOLSA</option>
    <option value="LATA">LATA</option>
    <option value="SUPOSITORIO">SUPOSITORIO</option>
    <option value="CÁPSULA">CÁPSULA</option>
    <option value="JERINGA">JERINGA</option>
    <option value="JERINGA PRELLENADA">JERINGA PRELLENADA</option>
    <option value="SOLUCIÓN PARA INHALACIÓN">SOLUCIÓN PARA INHALACIÓN</option>
    <option value="EMPOTRADO">EMPOTRADO</option>
    <option value="GALÓN">GALÓN</option>
    <option value="LÁPIZ">LÁPIZ</option>
    <option value="MEDIAS">MEDIAS</option>
    <option value="AGUJA">AGUJA</option>
    <option value="SEDA">SEDA</option>
</select>
</div>

<!-- "forma_farmaceutica" no va si es material descartable -->
<div class="form-group form-section">
<label for="forma_farmaceutica"><b>Forma Farmacéutica</b></label>
<select name="forma_farmaceutica" id="forma_farmaceutica" class="select2">
    <option value="">Seleccionar...</option>
    <option value="CAJA X 10 6S">CAJA X 10 6S</option>
    <option value="CAJA X 10 COMPRIMIDOS">CAJA X 10 COMPRIMIDOS</option>
    <option value="CAJA X 10 CAPSULAS">CAJA X 10 CAPSULAS</option>
    <option value="CAJA X 10 SOBRES">CAJA X 10 SOBRES</option>
    <option value="CAJA X 12 CAPSULAS">CAJA X 12 CAPSULAS</option>
    <option value="CAJA X 12 BOLSAS">CAJA X 12 BOLSAS</option>
    <option value="CAJA X 12 TABLETAS">CAJA X 12 TABLETAS</option>
    <option value="CAJA X 14 6S">CAJA X 14 6S</option>
    <option value="CAJA X 14 COMPRIMIDOS">CAJA X 14 COMPRIMIDOS</option>
    <option value="CAJA X 14 CAPSULAS">CAJA X 14 CAPSULAS</option>
    <option value="CAJA X 14 SOBRES">CAJA X 14 SOBRES</option>
    <option value="CAJA X 14 TABLETAS">CAJA X 14 TABLETAS</option>
    <option value="CAJA X 15 TABLETAS">CAJA X 15 TABLETAS</option>
    <option value="CAJA X 16 CAPSULAS">CAJA X 16 CAPSULAS</option>
    <option value="CAJA X 16 TABLETAS">CAJA X 16 TABLETAS</option>
    <option value="CAJA X 18 6S">CAJA X 18 6S</option>
    <option value="CAJA X 18 TABLETAS">CAJA X 18 TABLETAS</option>
    <option value="CAJA X 20 CAPSULAS">CAJA X 20 CAPSULAS</option>
    <option value="CAJA X 20 TABLETAS">CAJA X 20 TABLETAS</option>
    <option value="CAJA X 28 6S">CAJA X 28 6S</option>
    <option value="CAJA X 28 COMPRIMIDOS">CAJA X 28 COMPRIMIDOS</option>
    <option value="CAJA X 28 TABLETAS">CAJA X 28 TABLETAS</option>
    <option value="CAJA X 30 6S">CAJA X 30 6S</option>
    <option value="CAJA X 30 CAPSULAS">CAJA X 30 CAPSULAS</option>
    <option value="CAJA X 30 SOBRES">CAJA X 30 SOBRES</option>
    <option value="CAJA X 30 TABLETAS">CAJA X 30 TABLETAS</option>
    <option value="CAJA X 40 BOLSAS">CAJA X 40 BOLSAS</option>
    <option value="CAJA X 40 TABLETAS">CAJA X 40 TABLETAS</option>
    <option value="CAJA X 50 6S">CAJA X 50 6S</option>
    <option value="CAJA X 50 CAPSULAS">CAJA X 50 CAPSULAS</option>
    <option value="CAJA X 50 SOBRES">CAJA X 50 SOBRES</option>
    <option value="CAJA X 50 TABLETAS">CAJA X 50 TABLETAS</option>
    <option value="CAJA X 100 CAPSULAS">CAJA X 100 CAPSULAS</option>
    <option value="CAJA X 100 TABLETAS">CAJA X 100 TABLETAS</option>
    <option value="CAJA X 100 AMPOLLAS">CAJA X 100 AMPOLLAS</option>
    <option value="CAJA X 2 AMPOLLAS">CAJA X 2 AMPOLLAS</option>
    <option value="CAJA X 2 TABLETAS">CAJA X 2 TABLETAS</option>
    <option value="CAJA X 3 TABLETAS">CAJA X 3 TABLETAS</option>
    <option value="CAJA X 5 AMPOLLAS BEBIBLES">CAJA X 5 AMPOLLAS BEBIBLES</option>
    <option value="CAJA X 6 TABLETAS">CAJA X 6 TABLETAS</option>
    <option value="CAJA X 9 TABLETAS MASTICABLES">CAJA X 9 TABLETAS MASTICABLES</option>
    <option value="CAJA X 9 CAPSULAS">CAJA X 9 CAPSULAS</option>
    <option value="CAJA X 12 TABLETAS">CAJA X 12 TABLETAS</option>
    <option value="CAJA X 18 SOBRES">CAJA X 18 SOBRES</option>
    <option value="CAJA X 24 BOLSAS">CAJA X 24 BOLSAS</option>
    <option value="CAJA X 30 SOBRES">CAJA X 30 SOBRES</option>
    <option value="CAPSULA CX 10">CAPSULA CX 10</option>
    <option value="CREMA">CREMA</option>
    <option value="CREMA TÓPICA">CREMA TÓPICA</option>
    <option value="CREMA USO EXTERNO">CREMA USO EXTERNO</option>
    <option value="CREMA USO EXTERNO AL 1%%">CREMA USO EXTERNO AL 1%</option>
    <option value="CREMA X 9 TABLETAS MASTICABLES">CREMA X 9 TABLETAS MASTICABLES</option>
    <option value="CREMA TÓPICA X 15 G">CREMA TÓPICA X 15 G</option>
    <option value="CREMA TÓPICA X 20 G">CREMA TÓPICA X 20 G</option>
    <option value="CREMA TÓPICA X 57 G">CREMA TÓPICA X 57 G</option>
    <option value="CREMA TÓPICA X 60 G">CREMA TÓPICA X 60 G</option>
    <option value="COMPRIMIDO">COMPRIMIDO</option>
    <option value="AMPOLLA">AMPOLLA</option>
    <option value="VIAL INYECTABLE">VIAL INYECTABLE</option>
    <option value="VIAL 2 ML INYECTABLE">VIAL 2 ML INYECTABLE</option>
    <option value="SOLUCIÓN GERMICIDA">SOLUCIÓN GERMICIDA</option>
    <option value="SOLUCIÓN INYECTABLE">SOLUCIÓN INYECTABLE</option>
    <option value="SOLUCIÓN P/NEBULIZAR">SOLUCIÓN P/NEBULIZAR</option>
    <option value="SOLUCIÓN P/NEBULIZAR X 20 ML">SOLUCIÓN P/NEBULIZAR X 20 ML</option>
    <option value="SOLUCIÓN P/NEBULIZAR X 2 ML">SOLUCIÓN P/NEBULIZAR X 2 ML</option>
    <option value="SOLUCIÓN P/INYECTABLE X 1 ML">SOLUCIÓN P/INYECTABLE X 1 ML</option>
    <option value="SOLUCIÓN INYECTABLE X 2 ML">SOLUCIÓN INYECTABLE X 2 ML</option>
    <option value="SOLUCIÓN INYECTABLE X 3 ML">SOLUCIÓN INYECTABLE X 3 ML</option>
    <option value="SOLUCIÓN INYECTABLE X 4 ML">SOLUCIÓN INYECTABLE X 4 ML</option>
    <option value="SOLUCIÓN INYECTABLE X 5 ML">SOLUCIÓN INYECTABLE X 5 ML</option>
    <option value="SOLUCIÓN INYECTABLE X 10 ML">SOLUCIÓN INYECTABLE X 10 ML</option>
    <option value="SOLUCIÓN RECTAL">SOLUCIÓN RECTAL</option>
    <option value="SOLUCIÓN P/INYECTABLE X 30 ML">SOLUCIÓN P/INYECTABLE X 30 ML</option>
    <option value="SOLUCIÓN GOTAS X 20 ML">SOLUCIÓN GOTAS X 20 ML</option>
    <option value="SOLUCIÓN GOTAS X 40 ML">SOLUCIÓN GOTAS X 40 ML</option>
    <option value="SOLUCIÓN SALINA LAXANTE">SOLUCIÓN SALINA LAXANTE</option>
    <option value="SOLUCIÓN ANESTÉSICA">SOLUCIÓN ANESTÉSICA</option>
    <option value="SOLUCIÓN ORAL">SOLUCIÓN ORAL</option>
    <option value="SOLUCIÓN OFTÁLMICA X 5 ML">SOLUCIÓN OFTÁLMICA X 5 ML</option>
    <option value="SOLUCIÓN INHALANTE">SOLUCIÓN INHALANTE</option>
    <option value="SOLUCIÓN GELATINA">SOLUCIÓN GELATINA</option>
    <option value="SOLUCIÓN 100 ML">SOLUCIÓN 100 ML</option>
    <option value="SPRAY AEROSOL X 20 ML">SPRAY AEROSOL X 20 ML</option>
    <option value="SPRAY NASAL">SPRAY NASAL</option>
    <option value="SPRAY BUCAL X 10 ML">SPRAY BUCAL X 10 ML</option>
    <option value="SUSPENSIÓN P/INHALACIÓN">SUSPENSIÓN P/INHALACIÓN</option>
    <option value="SUSPENSIÓN X 10 ML">SUSPENSIÓN X 10 ML</option>
    <option value="SUSPENSIÓN X 15 ML">SUSPENSIÓN X 15 ML</option>
    <option value="SUSPENSIÓN X 22.5 ML">SUSPENSIÓN X 22.5 ML</option>
    <option value="SUSPENSIÓN X 50 ML">SUSPENSIÓN X 50 ML</option>
    <option value="SUSPENSIÓN X 60 ML">SUSPENSIÓN X 60 ML</option>
    <option value="SUSPENSIÓN X 70 ML">SUSPENSIÓN X 70 ML</option>
    <option value="SUSPENSIÓN X 80 ML">SUSPENSIÓN X 80 ML</option>
    <option value="SUSPENSIÓN X 100 ML">SUSPENSIÓN X 100 ML</option>
    <option value="SUSPENSIÓN X 120 ML">SUSPENSIÓN X 120 ML</option>
    <option value="SUSPENSIÓN X 150 ML">SUSPENSIÓN X 150 ML</option>
    <option value="SUSPENSIÓN X 220 ML">SUSPENSIÓN X 220 ML</option>
    <option value="SUSPENSIÓN X 237 ML">SUSPENSIÓN X 237 ML</option>
    <option value="SUSPENSIÓN X 360 ML">SUSPENSIÓN X 360 ML</option>
    <option value="SUSPENSIÓN ORAL X 60 ML">SUSPENSIÓN ORAL X 60 ML</option>
    <option value="BLiSTER X 4 CAPSULAS">BLiSTER X 4 CAPSULAS</option>
    <option value="BLiSTER X 10 CAPSULAS">BLiSTER X 10 CAPSULAS</option>
    <option value="BOLSA">BOLSA</option>
    <option value="JARABE X 20 ML">JARABE X 20 ML</option>
    <option value="JARABE X 30 ML">JARABE X 30 ML</option>
    <option value="JARABE X 60 ML">JARABE X 60 ML</option>
    <option value="JARABE X 100 ML">JARABE X 100 ML</option>
    <option value="JARABE X 120 ML">JARABE X 120 ML</option>
    <option value="JARABE X 125 ML">JARABE X 125 ML</option>
    <option value="JARABE X 150 ML">JARABE X 150 ML</option>
    <option value="JARABE X 200 ML">JARABE X 200 ML</option>
    <option value="JARABE X 240 ML">JARABE X 240 ML</option>
    <option value="JABÓN LÍQUIDO ANTISEPTICO">JABÓN LÍQUIDO ANTISEPTICO</option>
    <option value="1 TABLETA">1 TABLETA</option>
    <option value="POLVO">POLVO</option>
    <option value="POLVO PARA INHALACIÓN">POLVO PARA INHALACIÓN</option>
    <option value="POLVO PARA SUSPENSIÓN ORAL 200 G">POLVO PARA SUSPENSIÓN ORAL 200 G</option>
    <option value="LOCION X 100 ML">LOCION X 100 ML</option>
    <option value="FRASCO 200 MG/100ML">FRASCO 200 MG/100ML</option>
    <option value="FRASCO 250 MG/5ML">FRASCO 250 MG/5ML</option>
    <option value="FRASCO 500MG/100ML">FRASCO 500MG/100ML</option>
    <option value="FRASCO 10 ML">FRASCO 10 ML</option>
    <option value="FRASCO 200 ML">FRASCO 200 ML</option>
    <option value="FRASCO X 10 TABLETAS">FRASCO X 10 TABLETAS</option>
    <option value="FRASCO X 30 TABLETAS">FRASCO X 30 TABLETAS</option>
    <option value="FAJA">FAJA</option>
    <option value="FIBRA EN POLVO SUSPENSIÓN ORAL">FIBRA EN POLVO SUSPENSIÓN ORAL</option>
    <option value="SOBRE X 12 TABLETAS">SOBRE X 12 TABLETAS</option>
    <option value="INFUSIÓN INYECTABLE">INFUSIÓN INYECTABLE</option>
    <option value="GRAGEA">GRAGEA</option>
    <option value="GOTAS">GOTAS</option>
    <option value="GOTAS X 5 ML">GOTAS X 5 ML</option>
    <option value="GOTAS X 15 ML">GOTAS X 15 ML</option>
    <option value="GOTAS X 20 ML">GOTAS X 20 ML</option>
    <option value="GOTAS X 30 ML">GOTAS X 30 ML</option>
    <option value="GOTAS OTICAS X 5 ML">GOTAS OTICAS X 5 ML</option>
    <option value="GEL/USO ORAL 5 MG/ML">GEL/USO ORAL 5 MG/ML</option>
    <option value="GEL ORAL X 60 G">GEL ORAL X 60 G</option>
    <option value="GEL ORAL X 78 G">GEL ORAL X 78 G</option>
    <option value="GEL ORAL X 120 ML">GEL ORAL X 120 ML</option>
    <option value="GEL TÓPICO X 30 G">GEL TÓPICO X 30 G</option>
    <option value="GEL TÓPICO X 60 G">GEL TÓPICO X 60 G</option>
    <option value="GEL X 114 GR">GEL X 114 GR</option>
    <option value="SOBRE SOLUBLE X 10">SOBRE SOLUBLE X 10</option>
    <option value="SOBRE LIQUIDO X 10 ML">SOBRE LIQUIDO X 10 ML</option>
    <option value="UNGÜENTO">UNGÜENTO</option>
    <option value="UNGÜENTO TÓPICA X 15 G">UNGÜENTO TÓPICA X 15 G</option>
    <option value="LECHE">LECHE</option>
    <option value="LATA X 400 G">LATA X 400 G</option>
    <option value="LATA X 375 G">LATA X 375 G</option>
    <option value="INHALADOR">INHALADOR</option>
    <option value="JERINGA PRELLENADA">JERINGA PRELLENADA</option>
    <option value="EMUGEL">EMUGEL</option>
    <option value="TABLETAS 6 X 12">TABLETAS 6 X 12</option>
    <option value="6 SOLUBLE X 10">6 SOLUBLE X 10</option>
    <option value="6 LIQUIDO X 10 ML">6 LIQUIDO X 10 ML</option>
    <option value="ENJUAGUE BUCAL">ENJUAGUE BUCAL</option>
    <option value="LITROS">LITROS</option>
    <option value="SUMINISTROS">SUMINISTROS</option>
    <option value="28 COMPRIMIDOS BICAPA">28 COMPRIMIDOS BICAPA</option>
    <option value="MEDIAS">MEDIAS</option>
    <option value="AGUJA SUBCUTÁNEA">AGUJA SUBCUTÁNEA</option>
    <option value="VENDAS">VENDAS</option>
</select>
</div>

<!-- "concentracion" no va si es material descartable -->
<div class="form-group form-section">
<label for="concentracion"><b>Concentración</b></label>
<select name="concentracion" id="concentracion" class="select2">
    <option value="">Seleccionar...</option>
    <option value="Baja Concentración (0.7 GR/2.5 ML)">Baja Concentración (0.7 GR/2.5 ML)</option>
    <option value="Media Concentración (5 MG)">Media Concentración (5 MG)</option>
    <option value="Concentración Media (2.5 MG/5 ML)">Concentración Media (2.5 MG/5 ML)</option>
    <option value="Alta Concentración (40 MG)">Alta Concentración (40 MG)</option>
    <option value="Muy Alta Concentración (100 MG/ML)">Muy Alta Concentración (100 MG/ML)</option>
    <option value="Extrema Concentración (125 MG/ML)">Extrema Concentración (125 MG/ML)</option>
</select>
</div>

<!-- "via_administracion" no va si es material descartable -->
<div class="form-group form-section">
<label for="via_administracion"><b>Vía Administración</b></label>
<select name="via_administracion" id="via_administracion" class="select2">
    <option value="">Seleccionar...</option>
    <option value="ORAL">ORAL</option>
    <option value="INTRAMUSCULAR">INTRAMUSCULAR</option>
    <option value="NASAL">NASAL</option>
    <option value="INHALADO">INHALADO</option>
    <option value="OCULAR">OCULAR</option>
    <option value="TÓPICA">TÓPICA</option>
    <option value="TÓPICA ORAL">TÓPICA ORAL</option>
    <option value="INHALADO CON ESPACIADOR DE VOLUMEN">INHALADO CON ESPACIADOR DE VOLUMEN</option>
    <option value="AEROSOL INHALADO DE POLVO SECO">AEROSOL INHALADO DE POLVO SECO</option>
    <option value="SUSPENSIÓN PARA NEBULIZAR">SUSPENSIÓN PARA NEBULIZAR</option>
    <option value="SOLUCIÓN ACUOSA">SOLUCIÓN ACUOSA</option>
    <option value="ÓTICA">ÓTICA</option>
    <option value="LIOFILIZADO PARA DISOLVER">LIOFILIZADO PARA DISOLVER</option>
    <option value="IRRIGACIÓN NASAL">IRRIGACIÓN NASAL</option>
    <option value="VAGINAL">VAGINAL</option>
    <option value="SUBCUTÁNEA">SUBCUTÁNEA</option>
    <option value="INTRAVENOSA">INTRAVENOSA</option>
</select>
</div>

<br><br>

<label for="codpro"><b>Código del Producto</b></label>
<input type="text" id="codpro" name="codpro" readonly>

<script>
    function actualizarCodigo() {
        const lineaSelect = document.getElementById("linea");
        const selectedOption = lineaSelect.options[lineaSelect.selectedIndex];
        const lineaValue = selectedOption.value;
        
        // Mapeo de líneas a códigos de inventario según el catálogo de cuentas
        // 110400102 - Inventario Insumos Medicasa
        // 110400103 - Inventario Consumibles Medicasa
        // 110400104 - Inventario Insumos Oftalmed
        // 110400105 - Inventario Consumibles Oftalmed
        
        let codigo = '';
        
        // Lógica de asignación según la línea
        if (!lineaValue) {
            codigo = '';
        } else if (lineaValue.includes('OFTALMED') || lineaValue.includes('OFTALM')) {
            // Líneas relacionadas con Oftalmed
            // Determinar si es insumo o consumible según el tipo
            if (lineaValue.includes('INSUMO') || lineaValue.includes('MATERIAL') || lineaValue.includes('EQUIPO')) {
                codigo = '110400104'; // Inventario Insumos Oftalmed
            } else {
                codigo = '110400105'; // Inventario Consumibles Oftalmed
            }
        } else if (lineaValue.includes('CONSUMIBLE') || lineaValue.includes('SUMINISTRO') || 
                   lineaValue.includes('MATERIAL DESCARTABLE') || lineaValue.includes('PROMOCIONES') ||
                   lineaValue.includes('ARRENDAMIENTO') || lineaValue.includes('ALQUILER')) {
            // Líneas de consumibles de Medicasa
            codigo = '110400103'; // Inventario Consumibles Medicasa
        } else {
            // Por defecto: Inventario Insumos Medicasa (incluye DIAGNOSTICO RADIOLOGÍA E IMAGEN, etc.)
            codigo = '110400102'; // Inventario Insumos Medicasa
        }
        
        document.getElementById("codpro").value = codigo;
    }
</script>

<label for="codbars"><b>Código de Barras del Producto</b></label>
<input type="text" id="codbars" name="codbars">
<!--<p id="mensaje"></p>  Para mostrar un mensaje opcional -->

<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("codbars");

    input.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault(); // Evita que se envíe el formulario si lo hay
            document.getElementById("mensaje").innerText = "Código escaneado: " + input.value;
            console.log("Código de barras ingresado:", input.value);
        }
    });

    // Opcional: Si el escáner no requiere pulsar "Enter", podrías validar cuando deje de escribir
    input.addEventListener("input", function () {
        if (input.value.length >= 6) { // Ajusta este número al tamaño del código de barras
            console.log("Código de barras detectado:", input.value);
        }
    });
});
</script>

<!-- Campo Precio Costo (ya existente) -->
<label for="mediprec"><b>Precio Costo</b></label><span class="badge-warning">*</span>
<input type="text" id="costo" placeholder="ejm: 25.90" name="mediprec" readonly>

<!-- Campo Fecha Vencimiento -->
<label for="fecha_vencimiento"><b>Fecha de Vencimiento</b></label><span class="badge-warning">*</span>
<input type="date" id="fecha_vencimiento" name="fecha_vencimiento" required>


<!-- Campo Margen Ganancia en % -->
<label for="margen_ganancia"><b>Margen de Ganancia en %</b></label><span class="badge-warning">*</span>
<input type="text" id="margen_ganancia" placeholder="ejm: 20" name="margen_ganancia" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required oninput="calcularPrecioVenta()">

<!-- Campo Impuesto -->
<label><b>Impuesto</b></label><span class="badge-warning">*</span><br>
<div>
    <input type="radio" id="gravado" name="impuesto" value="G" required onchange="calcularPrecioVenta()">
    <label for="gravado">Gravado 15%</label>
</div>
<div>
    <input type="radio" id="exento" name="impuesto" value="E" required onchange="calcularPrecioVenta()">
    <label for="exento">Exento 0%</label>
</div>

<br>

<label for="email"><b>Stock Disponibles</b></label><span class="badge-warning">*</span>
<input type="text" id="stock_disponible" placeholder="ejm: 90" name="medistoc" maxlength="9" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required readonly>

<label for="stock_minimo"><b>Stock Mínimo de Reorden</b></label><span class="badge-warning">*</span>
<input type="text" id="stock_minimo" placeholder="ejm: 10" name="stock_minimo" maxlength="9" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required value="5">

<label for="adj_foto"><b>Adjuntar Fotografía</b></label><span class="badge-warning">*</span>
<input type="file" id="adj_foto" name="adj_foto" accept="image/*">

<!-- Campo Precio Venta -->
<label for="precio_venta"><b>Precio de Venta</b></label><span class="badge-warning">*</span>
<input type="text" id="precio_venta" placeholder="ejm: 30.90" name="precio_venta" readonly>

<!-- Script para calcular el Precio Venta -->
<script>
function calcularPrecioVenta() {
    const costo = parseFloat(document.getElementById('costo').value) || 0;
    const margenGanancia = parseFloat(document.getElementById('margen_ganancia').value) || 0;
    const impuestoSeleccionado = document.querySelector('input[name="impuesto"]:checked')?.value;

    // Calcular precio de venta sin impuesto
    const precioVenta = costo + (costo * (margenGanancia / 100));

    // Calcular impuesto basado en la selección
    let totalVenta = precioVenta;
    if (impuestoSeleccionado === 'G') {
        totalVenta += precioVenta * 0.15; // 15% de impuesto
    }

    document.getElementById('precio_venta').value = totalVenta.toFixed(2);
}
</script>

    <hr>
   
    <button type="submit" name="add_medicine" class="registerbtn">Guardar</button>
  </div>
  
</form>

        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/cat.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/cat_cuentas.js"></script>
    <script src="../../backend/js/cat_cuentas_reg.js"></script>
    <script src="../../backend/js/cat_proveedores.js"></script>
    <script src="../../backend/js/cat_descripcion.js"></script>
    <script src="../../backend/js/linea_mostrar_campos.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- activa y desactiva campos 
    <script src='../../backend/js/active_desactive_campos.js'></script>-->

    <!-- Include jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2(); // Inicializa Select2 para todos los select con clase select2
    });
    </script>

    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
 <?php include_once '../../backend/php/add_medicine.php' ?>
</body>
</html>


