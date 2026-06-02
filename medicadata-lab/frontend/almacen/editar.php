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
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

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

            <button class="button" onclick="location.href='venta.php'">Historial de Ventas</button>
            <button class="button" onclick="location.href='mostrar.php'">Lista de Inventario</button>
            <button class="button" onclick="location.href='compra_unificada.php'">Registro de Nuevo Producto al Inventario</button>
            <button class="button" onclick="location.href='ingreso.php'">Ingreso de Producto al Inventario</button>
            <button class="button" onclick="location.href='categoria_nuevo.php'">Registrar Categoria</button>
            <button class="button" onclick="location.href='categoria.php'">Categorias</button>
            <button class="button" onclick="location.href='new_sale.php'">Nueva Venta</button>

         <?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT product.idprcd, product.codpro, product.nompro, category.idcat, category.nomcat, product.preprd, product.stock, product.stock_minimo, product.state, product.fere, product.fecha_vencimiento, product.via_administracion, product.concentracion, product.forma_farmaceutica, product.forma_farmaceutica, product.presentacion, product.sub_linea, product.linea, product.comision, product.margen_ganancia, product.impuesto FROM product INNER JOIN category ON product.idcat = category.idcat WHERE idprcd= '$id';");
 $sentencia->execute();

$data =  array();
if($sentencia){
  while($r = $sentencia->fetchObject()){
    $data[] = $r;
  }
}
   ?>
   <?php if(count($data)>0):?>
        <?php foreach($data as $d):?>    
            <form action="" enctype="multipart/form-data" method="POST" autocomplete="off" onsubmit="return validacion()">
  <div class="containerss">
    <h1>Actualizar Medicamentos</h1>
    <div class="alert-danger">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
    </div>
    <hr>
    <br>

        <!-- Campo Categoría -->
        <label for="medicate"><b>Categoria de Medicamentos</b></label>
    <select name="medicate" class="select2">
        <option value="<?php echo $d->idcat; ?>"><?php echo $d->nomcat; ?></option>
        <option>---------------------------------</option>
        <?php
            $stmt = $connect->prepare('SELECT * FROM category'); 
            $stmt->execute();
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
        ?>
            <option value="<?php echo $idcat; ?>"><?php echo $nomcat; ?></option>
        <?php
            }
        ?>
    </select>

    <label for="linea"><b>Linea</b></label>
<select name="linea" id="linea" class="select2">
    <option value="">Seleccione</option>
    <option value="MEDICAMENTOS" data-codigo="MEDI_711107">MEDICAMENTOS</option>
    <option value="MATERIAL DESCARTABLE" data-codigo="MATE_018531">MATERIAL DESCARTABLE</option>
    <option value="SERVICIOS DE HOSPITALIZACIÓN" data-codigo="SERV_008503">SERVICIOS DE HOSPITALIZACIÓN</option>
    <option value="DIAGNOSTICO RADIOLOGÍA E IMAGEN" data-codigo="IMGS_062305">DIAGNOSTICO RADIOLOGÍA E IMAGEN</option>
    <option value="LABORATORIO" data-codigo="LABS_574021">LABORATORIO</option>
    <option value="INSUMOS DESCARTABLES" data-codigo="INSV_749857">INSUMOS DESCARTABLES</option>
    <option value="EMERGENCIA" data-codigo="EMER_046268">EMERGENCIA</option>
    <option value="PROCEDIMIENTO CIRUGÍA MENOR" data-codigo="PROC_206365">PROCEDIMIENTO CIRUGÍA MENOR</option>
    <option value="OBSERVACIÓN" data-codigo="OBSE_974271">OBSERVACIÓN</option>
    <option value="CIRUGÍA / QUIROFANO" data-codigo="CIRU_935226">CIRUGÍA / QUIROFANO</option>
    <option value="UCI" data-codigo="UCI_689527">UCI</option>
    <option value="UNIDAD DIGESTIVA / GASTROENTEROLOGÍA" data-codigo="DIGE_326260">UNIDAD DIGESTIVA / GASTROENTEROLOGÍA</option>
    <option value="SERVICIO DE AMBULANCIA" data-codigo="SERV_960458">SERVICIO DE AMBULANCIA</option>
    <option value="MEDICO POR LLAMADO" data-codigo="MÉD_239647">MEDICO POR LLAMADO</option>
    <option value=">ATENCIÓN MÉDICA A DOMICILIO" data-codigo="DOMI_152187">ATENCIÓN MÉDICA A DOMICILIO</option>
    <option value="CARDIOLOGÍA UNIDAD" data-codigo="CARD_283276">CARDIOLOGÍA UNIDAD</option>
    <option value="NEUROLOGÍA UNIDAD" data-codigo="NEUR_857219">NEUROLOGÍA UNIDAD</option>
    <option value="SERVICIO DE LABOR Y PARTO" data-codigo="LABO_284010">SERVICIO DE LABOR Y PARTO</option>
    <option value="SUMINISTRO DE OFICINA" data-codigo="SUMI_307323">SUMINISTRO DE OFICINA</option>
    <option value="CUIDADOS/MATERNIDAD DE MUJER (AMBULATORIO)" data-codigo="MUJE_486470">CUIDADOS/MATERNIDAD DE MUJER (AMBULATORIO)</option>
    <option value="OTORRINOLARINGOLOGÍA" data-codigo="OTOR_467248">OTORRINOLARINGOLOGÍA</option>
    <option value="UROLOGÍA" data-codigo="UROL_648819">UROLOGÍA</option>
    <option value="HEMATOLOGÍA" data-codigo="HEMA_788205">HEMATOLOGÍA</option>
    <option value="NEUMOLOGÍA" data-codigo="NEUM_368679">NEUMOLOGÍA</option>
    <option value="EQUIPO QUIRÚRGICO / INSTRUMENTAL" data-codigo="INST_917759">EQUIPO QUIRÚRGICO / INSTRUMENTAL</option>
    <option value="PROMOCIONES" data-codigo="PROM_277228">PROMOCIONES</option>
    <option value="PODOLOGÍA" data-codigo="PODO_465537">PODOLOGÍA</option>
    <option value="ARRENDAMIENTO" data-codigo="ARRE_187836">ARRENDAMIENTO</option>
    <option value="PROCEDIMIENTO" data-codigo="PROC_710650">PROCEDIMIENTO</option>
    <option value="ALQUILER" data-codigo="ALQU_773016">ALQUILER</option>
</select>

<label for="sub_linea"><b>Sub Linea</b></label>
<select name="sub_linea" id="sub_linea" class="select2">
    <option value="">Seleccionar...</option>
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
</select>

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
    <option value="BUSTER X 4 CAPSULAS">BUSTER X 4 CAPSULAS</option>
    <option value="BUSTER X 10 CAPSULAS">BUSTER X 10 CAPSULAS</option>
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

    <label for="via_administracion"><b>Vía Administración</b></label>
    <input type="hidden" name="meid" value="<?php echo $d->via_administracion; ?>">
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
    
    <!-- Campo Lote/Código de Medicina -->
    <label for="medicode"><b>Lote/Código</b></label><span class="badge-warning">*</span>
    <input type="text" value="<?php echo $d->codpro; ?>" placeholder="ejm:  877578VNRB4" maxlength="14" name="medicode" required>
    <input type="hidden" name="meid" value="<?php echo $d->idprcd; ?>">

    <!-- Campo Nombre de la Medicina -->
    <label for="mediname"><b>Nombre de la Medicina</b></label><span class="badge-warning">*</span>
    <input value="<?php echo $d->nompro; ?>" type="text" placeholder="ejm:  PRADAXA 75 MG X 30 CÁPSULAS" name="mediname" required>

    <!-- Campo Fecha Vencimiento -->
    <label for="fecha_vencimiento"><b>Fecha de Vencimiento</b></label><span class="badge-warning">*</span>
    <input type="date" id="fecha_vencimiento" value="<?php echo $d->fecha_vencimiento; ?>" name="fecha_vencimiento" required>

    <!-- Campo Precio Costo -->
    <label for="mediprec"><b>Precio Costo</b></label><span class="badge-warning">*</span>
    <input type="text" id="costo" value="<?php echo $d->preprd; ?>" placeholder="ejm: 25.90" name="mediprec" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required oninput="calcularPrecioVenta()">

    <!-- Campo Comisión -->
    <label for="comision"><b>Comisión</b></label><span class="badge-warning">*</span>
    <input type="text" id="comision" value="<?php echo $d->comision; ?>" placeholder="ejm: 10" name="comision" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required>

    <!-- Campo Margen Ganancia en % -->
    <label for="margen_ganancia"><b>Margen de Ganancia en %</b></label><span class="badge-warning">*</span>
    <input type="text" id="margen_ganancia" value="<?php echo $d->margen_ganancia; ?>" placeholder="ejm: 20" name="margen_ganancia" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required oninput="calcularPrecioVenta()">

    <!-- Campo Impuesto -->
    <label for="impuesto"><b>Impuesto</b></label><span class="badge-warning">*</span>
    <input type="text" id="impuesto" value="<?php echo $d->impuesto; ?>" placeholder="ejm: 10" name="impuesto" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required oninput="calcularPrecioVenta()">

    <!-- Campo Stock Disponibles -->
    <label for="stock"><b>Stock Disponibles</b></label><span class="badge-warning">*</span>
    <input type="text" value="<?php echo $d->stock; ?>" placeholder="ejm: 90" name="medistoc" maxlength="9" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required>

    <!-- Campo Stock Mínimo de Reorden -->
    <label for="stock_minimo"><b>Stock Mínimo de Reorden</b></label><span class="badge-warning">*</span>
    <input type="text" value="<?php echo $d->stock_minimo ?? 5; ?>" placeholder="ejm: 10" name="stock_minimo" maxlength="9" onKeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;" required>

    <!-- Campo Precio Venta -->
    <label for="precio_venta"><b>Precio de Venta</b></label><span class="badge-warning">*</span>
    <input type="text" id="precio_venta" placeholder="ejm: 30.90" name="precio_venta" readonly>

    <!-- Script para calcular el Precio Venta -->
    <script>
        function calcularPrecioVenta() {
            // Obtener los valores de los campos
            let costo = parseFloat(document.getElementById('costo').value) || 0;
            let margenGanancia = parseFloat(document.getElementById('margen_ganancia').value) || 0;
            let impuesto = parseFloat(document.getElementById('impuesto').value) || 0;
            // Calcular el precio de venta sumando el margen de ganancia al costo
            let precioVenta = costo + (costo * (margenGanancia / 100));
            // Restar el impuesto del precio de venta
            precioVenta -= impuesto;
            // Mostrar el resultado en el campo de precio de venta
            document.getElementById('precio_venta').value = precioVenta.toFixed(2);
        }
    </script>

    <hr>
    <button type="submit" name="upd_medicine" class="registerbtn">Guardar</button>
  </div>
</form>

<?php endforeach; ?>
  
    <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>

        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
 
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

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
 <?php include_once '../../backend/php/upd_medicine.php' ?>
</body>
</html>


