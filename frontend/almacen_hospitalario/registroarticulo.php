<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

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

<h1 class="title"><?php echo $saludo . ', <strong>' . $_SESSION['name'] . '</strong>'; ?></h1>

            <button class="button" onclick="location.href='venta.php'">Historial de Ventas</button>
            <button class="button" onclick="location.href='mostrar.php'">Lista de Medicamentos</button>
            <button class="button" onclick="location.href='../almacen/compra_unificada.php'">Registrar Nuevo Medicamento</button>
            <button class="button" onclick="location.href='categoria_nuevo.php'">Registrar Nueva Categoria</button>
            <button class="button" onclick="location.href='categoria.php'">Categoria</button>
            <button class="button" onclick="location.href='registroarticulo.php'">Registro de Articulos</button>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include Notificaciones -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <!-- Include CSS de Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .form-container h2 {
            grid-column: span 4;
            margin-bottom: 20px;
            text-align: center;
            font-size: 24px;
            background: linear-gradient(to right, #06adbf, #06adbf, #06adbf);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #fff;
            box-sizing: border-box;
            margin-bottom: 0;
        }
        .form-container input[type="text"],
        .form-container input[type="number"],
        .form-container input[type="date"],
        .form-container select {
            height: 40px;
        }
        .form-container button {
            grid-column: span 4;
            padding: 14px;
            background-color: #035c67;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }
        .form-container button:hover {
            background-color: #06adbf;
        }
        .form-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        @media (max-width: 768px) {
            .form-container {
                grid-template-columns: 1fr;
            }
            .form-container h2, .form-container button {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
<form id="registroarticuloform">
<div class="form-container">
    <h2>Nuevo Artículo</h2>
    <div class="form-item">
    <label for="linea">Línea:</label>
    <select id="linea" name="linea" class="select2" onchange="updateArticulo()">
        <option value="">Seleccionar...</option>
        <option value="medicamentos" data-codigo="MEDI_711107">MEDICAMENTOS</option>
        <option value="material_descartable" data-codigo="MATE_018531">MATERIAL DESCARTABLE</option>
        <option value="servicios_hospitalizacion" data-codigo="SERV_008503">SERVICIOS DE HOSPITALIZACIÓN</option>
        <option value="diagnostico_radiologia" data-codigo="IMGS_062305">DIAGNOSTICO RADIOLOGÍA E IMAGEN</option>
        <option value="laboratorio" data-codigo="LABS_574021">LABORATORIO</option>
        <option value="insumos_descartables" data-codigo="INSV_749857">INSUMOS DESCARTABLES</option>
        <option value="emergencia" data-codigo="EMER_046268">EMERGENCIA</option>
        <option value="procedimiento_cirugia_menor" data-codigo="PROC_206365">PROCEDIMIENTO CIRUGÍA MENOR</option>
        <option value="observacion" data-codigo="OBSE_974271">OBSERVACIÓN</option>
        <option value="cirugia_quirofano" data-codigo="CIRU_935226">CIRUGÍA / QUIROFANO</option>
        <option value="uci" data-codigo="UCI_689527">UCI</option>
        <option value="unidad_digestiva" data-codigo="DIGE_326260">UNIDAD DIGESTIVA / GASTROENTEROLOGÍA</option>
        <option value="servicio_ambulancia" data-codigo="SERV_960458">SERVICIO DE AMBULANCIA</option>
        <option value="medico_por_llamado" data-codigo="MÉD_239647">MEDICO POR LLAMADO</option>
        <option value="atencion_medica_domicilio" data-codigo="DOMI_152187">ATENCIÓN MÉDICA A DOMICILIO</option>
        <option value="cardiologia_unidad" data-codigo="CARD_283276">CARDIOLOGÍA UNIDAD</option>
        <option value="neurologia_unidad" data-codigo="NEUR_857219">NEUROLOGÍA UNIDAD</option>
        <option value="servicio_labor_parto" data-codigo="LABO_284010">SERVICIO DE LABOR Y PARTO</option>
        <option value="suministro_oficina" data-codigo="SUMI_307323">SUMINISTRO DE OFICINA</option>
        <option value="cuidados_maternidad" data-codigo="MUJE_486470">CUIDADOS/MATERNIDAD DE MUJER (AMBULATORIO)</option>
        <option value="otorrinolaringologia" data-codigo="OTOR_467248">OTORRINOLARINGOLOGÍA</option>
        <option value="urologia" data-codigo="UROL_648819">UROLOGÍA</option>
        <option value="hematologia" data-codigo="HEMA_788205">HEMATOLOGÍA</option>
        <option value="neumologia" data-codigo="NEUM_368679">NEUMOLOGÍA</option>
        <option value="equipo_quirurgico" data-codigo="INST_917759">EQUIPO QUIRÚRGICO / INSTRUMENTAL</option>
        <option value="promociones" data-codigo="PROM_277228">PROMOCIONES</option>
        <option value="podologia" data-codigo="PODO_465537">PODOLOGÍA</option>
        <option value="arrendamiento" data-codigo="ARRE_187836">ARRENDAMIENTO</option>
        <option value="procedimiento" data-codigo="PROC_710650">PROCEDIMIENTO</option>
        <option value="alquiler" data-codigo="ALQU_773016">ALQUILER</option>
    </select>
</div>

    <div class="form-item">
        <label for="sub_linea">Sub línea:</label>
        <select id="sub_linea" name="sub_linea" class="select2">
            <option value="">Seleccionar...</option>
            <option value="hospitalarios">HOSPITALARIOS</option>
            <option value="hematologia">HEMATOLOGÍA</option>
            <option value="quimica_clinica">QUÍMICA CLÍNICA</option>
            <option value="inmunologia">INMUNOLOGÍA</option>
            <option value="endocrinologia">ENDOCRINOLOGÍA</option>
            <option value="drogas_abuso">DROGAS DE ABUSO</option>
            <option value="bacteriologia">BACTERIOLOGÍA</option>
            <option value="coagulacion">COAGULACIÓN</option>
            <option value="coproanalisis">COPROANÁLISIS</option>
            <option value="uroanalisis">UROANÁLISIS</option>
            <option value="banco_sangre">BANCO DE SANGRE</option>
            <option value="gestiones_laboratorio">GESTIONES DE LABORATORIO DIVERSAS</option>
            <option value="suturas_medicas">SUTURAS MÉDICAS</option>
            <option value="tubos_endotraqueales">TUBOS ENDOTRAQUEALES</option>
            <option value="sondas">SONDAS</option>
            <option value="vendas_medicas">VENDAS MÉDICAS</option>
            <option value="suministros_varios">SUMINISTROS VARIOS</option>
            <option value="materiales_medicos_quirurgicos">MATERIALES MÉDICOS QUIRÚRGICOS</option>
            <option value="materiales_radiologia">MATERIALES DE RADIOLOGÍA E IMÁGENES</option>
            <option value="materiales_laboratorio">MATERIALES DE LABORATORIO CLÍNICO</option>
            <option value="otros_inventarios">OTROS INVENTARIOS</option>
            <option value="servicios_laboratorio">SERVICIOS DE LABORATORIO CLÍNICO</option>
            <option value="servicios_radiologia">SERVICIOS DE RADILOGÍA E IMAGEN</option>
            <option value="alimentacion">ALIMENTACIÓN (DIETAS)</option>
            <option value="servicios_terapia">SERVICIOS DE TERAPIA RESPIRATORIA</option>
            <option value="servicios_enfermeria">SERVICIOS DE ENFERMERÍA</option>
            <option value="servicios_consulta_emergencia">SERVICIOS DE CONSULTA DE EMERGENCIA</option>
            <option value="paquetes_quirurgicos">PAQUETES QUIRÚRGICOS</option>
            <option value="habitaciones">HABITACIONES</option>
            <option value="insumos_indirectos">INSUMOS INDIRECTOS (MATERIALES)</option>
            <option value="anestesicos_gases">ANESTÉSICOS Y GASES</option>
            <option value="roperia">ROPERÍA</option>
            <option value="salas_intervencion">SALAS DE INTERVENCIÓN</option>
            <option value="instrumental">INSTRUMENTAL</option>
            <option value="equipo_medico">EQUIPO MÉDICO</option>
            <option value="servicios_varios">SERVICIOS VARIOS</option>
            <option value="alquiler_locales">ALQUILER DE LOCALES</option>
            <option value="abdomen">ABDOMEN</option>
            <option value="cabeza">CABEZA</option>
            <option value="columna_pelvis">COLUMNA Y PELVIS</option>
            <option value="estudios_especiales">ESTUDIOS ESPECIALES</option>
            <option value="extremidad_inferior">EXTREMIDAD INFERIOR</option>
            <option value="extremidad_superior">EXTREMIDAD SUPERIOR</option>
            <option value="rayos_x">RAYOS X</option>
            <option value="torax">TÓRAX</option>
            <option value="ultrasonido">ULTRASONIDO</option>
            <option value="descuentos_especiales">DESCUENTOS ESPECIALES</option>
            <option value="suministros_limpieza">SUMINISTROS DE LIMPIEZA</option>
            <option value="emergencia">EMERGENCIA</option>
            <option value="materiales_oficina">MATERIALES DE OFICINA</option>
            <option value="cabeza_tomografia">CABEZA / TOMOGRAFÍA</option>
            <option value="cuello_tomografia">CUELLO / TOMOGRAFÍA</option>
            <option value="torax_tomografia">TÓRAX / TOMOGRAFÍA</option>
            <option value="abdomen_tomografia">ABDOMEN / TOMOGRAFÍA</option>
            <option value="miembros_superiores_tomografia">MIEMBROS SUPERIORES / TOMOGRAFÍA</option>
            <option value="especiales_tomografia">ESPECIALES / TOMOGRAFÍA</option>
            <option value="mamografia">MAMOGRAFÍA</option>
            <option value="odontologia_general">ODONTOLOGÍA GENERAL</option>
            <option value="endodoncia">ENDODONCIA</option>
            <option value="periodoncia_implantologia">PERIODONCIA E IMPLANTOLOGÍA</option>
            <option value="armonizacion_orofacial">ARMONIZACIÓN OROFACIAL</option>
            <option value="prostodoncia_implantologia">PROSTODONCIA E IMPLANTOLOGÍA</option>
            <option value="ortodoncia_ortopedia">ORTODONCIA Y ORTOPEDIA MAXILOFACIAL</option>
            <option value="odontologia_pediatrica">ODONTOLOGÍA PEDIÁTRICA</option>
            <option value="ambulancia">AMBULANCIA</option>
            <option value="radiodiagnostico_dental">RADIODIAGNÓSTICO DENTAL</option>
            <option value="rayos_x">RAYOS X</option>
            <option value="tomografia">TOMOGRAFÍA</option>
            <option value="angio_tomografia">ANGIO TOMOGRAFÍA</option>
            <option value="sala_cuna">SALA CUNA</option>
            <option value="maternidad_sala_hospital">MATERNIDAD SALA HOSPITAL</option>
            <option value="dia_padre">DIA DEL PADRE</option>
            <option value="cardiologia_pediatrico">CARDIOLOGÍA PEDIÁTRICO</option>
            <option value="cardiologia_adulto">CARDIOLOGÍA ADULTO</option>
            <option value="clinicas_medicasa">CLÍNICAS MEDICASA</option>
            <option value="baaf">BAAF</option>
            <option value="procedimientos">PROCEDIMIENTOS</option>
            <option value="honorarios_medicos">HONORARIOS MÉDICOS</option>
        </select>
    </div>

    <div class="form-item">
        <label for="envase">Presentación:</label>
        <select id="envase" name="envase" class="select2">
            <option value="">Seleccionar...</option>
            <option value="tableta">TABLETA</option>
            <option value="comprimido">COMPRIMIDO</option>
            <option value="ampolla">AMPOLLA</option>
            <option value="caja">CAJA</option>
            <option value="sobre">SOBRE</option>
            <option value="frasco">FRASCO</option>
            <option value="tubo">TUBO</option>
            <option value="bolsa">BOLSA</option>
            <option value="lata">LATA</option>
            <option value="supositorio">SUPOSITORIO</option>
            <option value="capsula">CAPSULA</option>
            <option value="jeringa">JERINGA</option>
            <option value="jeringa_prellenada">JERINGA PRELLENADA</option>
            <option value="inhalacion">SOLUCIÓN PARA INHALACIÓN</option>
            <option value="empotrado">EMPOTRADO</option>
            <option value="galon">GALÓN</option>
            <option value="lapiz">LAPIZ</option>
            <option value="medias">MEDIAS</option>
            <option value="aguja">AGUJA</option>
        </select>
    </div>
    <div class="form-item">
    <label for="farmaceutica">Forma Farmaceutica:</label>
    <select id="farmaceutica" name="farmaceutica" class="select2">
        <option value="">Seleccionar...</option>
        <option value="caja_x_10_6s">CAJA X 10 6S</option>
        <option value="caja_x_10_comprimidos">CAJA X 10 COMPRIMIDOS</option>
        <option value="caja_x_10_capsulas">CAJA X 10 CAPSULAS</option>
        <option value="caja_x_10_sobres">CAJA X 10 SOBRES</option>
        <option value="caja_x_12_capsulas">CAJA X 12 CAPSULAS</option>
        <option value="caja_x_12_bolsas">CAJA X 12 BOLSAS</option>
        <option value="caja_x_12_tabletas">CAJA X 12 TABLETAS</option>
        <option value="caja_x_14_6s">CAJA X 14 6S</option>
        <option value="caja_x_14_comprimidos">CAJA X 14 COMPRIMIDOS</option>
        <option value="caja_x_14_capsulas">CAJA X 14 CAPSULAS</option>
        <option value="caja_x_14_sobres">CAJA X 14 SOBRES</option>
        <option value="caja_x_14_tabletas">CAJA X 14 TABLETAS</option>
        <option value="caja_x_15_tabletas">CAJA X 15 TABLETAS</option>
        <option value="caja_x_16_capsulas">CAJA X 16 CAPSULAS</option>
        <option value="caja_x_16_tabletas">CAJA X 16 TABLETAS</option>
        <option value="caja_x_18_6s">CAJA X 18 6S</option>
        <option value="caja_x_18_tabletas">CAJA X 18 TABLETAS</option>
        <option value="caja_x_20_capsulas">CAJA X 20 CAPSULAS</option>
        <option value="caja_x_20_tabletas">CAJA X 20 TABLETAS</option>
        <option value="caja_x_28_6s">CAJA X 28 6S</option>
        <option value="caja_x_28_comprimidos">CAJA X 28 COMPRIMIDOS</option>
        <option value="caja_x_28_tabletas">CAJA X 28 TABLETAS</option>
        <option value="caja_x_30_6s">CAJA X 30 6S</option>
        <option value="caja_x_30_capsulas">CAJA X 30 CAPSULAS</option>
        <option value="caja_x_30_sobres">CAJA X 30 SOBRES</option>
        <option value="caja_x_30_tabletas">CAJA X 30 TABLETAS</option>
        <option value="caja_x_40_bolsas">CAJA X 40 BOLSAS</option>
        <option value="caja_x_40_tabletas">CAJA X 40 TABLETAS</option>
        <option value="caja_x_50_6s">CAJA X 50 6S</option>
        <option value="caja_x_50_capsulas">CAJA X 50 CAPSULAS</option>
        <option value="caja_x_50_sobres">CAJA X 50 SOBRES</option>
        <option value="caja_x_50_tabletas">CAJA X 50 TABLETAS</option>
        <option value="caja_x_100_capsulas">CAJA X 100 CAPSULAS</option>
        <option value="caja_x_100_tabletas">CAJA X 100 TABLETAS</option>
        <option value="caja_x_100_ampollas">CAJA X 100 AMPOLLAS</option>
        <option value="caja_x_2_ampollas">CAJA X 2 AMPOLLAS</option>
        <option value="caja_x_2_tabletas">CAJA X 2 TABLETAS</option>
        <option value="caja_x_3_tabletas">CAJA X 3 TABLETAS</option>
        <option value="caja_x_5_ampollas_bebibles">CAJA X 5 AMPOLLAS BEBIBLES</option>
        <option value="caja_x_6_tabletas">CAJA X 6 TABLETAS</option>
        <option value="caja_x_9_tabletas_masticables">CAJA X 9 TABLETAS MASTICABLES</option>
        <option value="caja_x_9_capsulas">CAJA X 9 CAPSULAS</option>
        <option value="caja_x_12_tabletas">CAJA X 12 TABLETAS</option>
        <option value="caja_x_18_sobres">CAJA X 18 SOBRES</option>
        <option value="caja_x_24_bolsas">CAJA X 24 BOLSAS</option>
        <option value="caja_x_30_sobres">CAJA X 30 SOBRES</option>
        <option value="capsula_cx_10">CAPSULA CX 10</option>
        <option value="crema">CREMA</option>
        <option value="crema_topica">CREMA TOPICA</option>
        <option value="crema_uso_externo">CREMA USO EXTERNO</option>
        <option value="crema_uso_externo_al_1%">CREMA USO EXTERNO AL 1%</option>
        <option value="crema_x_9_tabletas_masticables">CREMA X 9 TABLETAS MASTICABLES</option>
        <option value="crema_topica_x_15_g">CREMA TOPICA X 15 G</option>
        <option value="crema_topica_x_20_g">CREMA TOPICA X 20 G</option>
        <option value="crema_topica_x_57_g">CREMA TOPICA X 57 G</option>
        <option value="crema_topica_x_60_g">CREMA TOPICA X 60 G</option>
        <option value="comprimido">COMPRIMIDO</option>
        <option value="ampolla">AMPOLLA</option>
        <option value="vial_inyectable">VIAL INYECTABLE</option>
        <option value="vial_2_ml_inyectable">VIAL 2 ML INYECTABLE</option>
        <option value="solucion_germicida">SOLUCIÓN GERMICIDA</option>
        <option value="solucion_inyectable">SOLUCIÓN INYECTABLE</option>
        <option value="solucion_p_nebulizar">SOLUCIÓN P/NEBULIZAR</option>
        <option value="solucion_p_nebulizar_x_20_ml">SOLUCIÓN P/NEBULIZAR X 20 ML</option>
        <option value="solucion_p_nebulizar_x_2_ml">SOLUCIÓN P/NEBULIZAR X 2 ML</option>
        <option value="solucion_p_inyectable_x_1_ml">SOLUCIÓN P/INYECTABLE X 1 ML</option>
        <option value="solucion_inyectable_x_2_ml">SOLUCIÓN INYECTABLE X 2 ML</option>
        <option value="solucion_inyectable_x_3_ml">SOLUCIÓN INYECTABLE X 3 ML</option>
        <option value="solucion_inyectable_x_4_ml">SOLUCIÓN INYECTABLE X 4 ML</option>
        <option value="solucion_inyectable_x_5_ml">SOLUCIÓN INYECTABLE X 5 ML</option>
        <option value="solucion_inyectable_x_10_ml">SOLUCIÓN INYECTABLE X 10 ML</option>
        <option value="solucion_rectal">SOLUCIÓN RECTAL</option>
        <option value="solucion_p_inyectable_x_30_ml">SOLUCIÓN P/INYECTABLE X 30 ML</option>
        <option value="solucion_gotas_x_20_ml">SOLUCIÓN GOTAS X 20 ML</option>
        <option value="solucion_gotas_x_40_ml">SOLUCIÓN GOTAS X 40 ML</option>
        <option value="solucion_salina_laxante">SOLUCIÓN SALINA LAXANTE</option>
        <option value="solucion_anestesica">SOLUCIÓN ANESTESICA</option>
        <option value="solucion_oral">SOLUCIÓN ORAL</option>
        <option value="solucion_oftalmica_x_5_ml">SOLUCIÓN OFTALMICA X 5 ML</option>
        <option value="solucion_inhalante">SOLUCIÓN INHALANTE</option>
        <option value="solucion_gelatina">SOLUCIÓN GELATINA</option>
        <option value="solucion_100_ml">SOLUCIÓN 100 ML</option>
        <option value="spray_aerosol_x_20_ml">SPRAY AEROSOL X 20 ML</option>
        <option value="spray_nasal">SPRAY NASAL</option>
        <option value="spray_bucal_x_10_ml">SPRAY BUCAL X 10 ML</option>
        <option value="suspension_p_inhalacion">SUSPENSIÓN P/INHALACIÓN</option>
        <option value="suspension_x_10_ml">SUSPENSIÓN X 10 ML</option>
        <option value="suspension_x_15_ml">SUSPENSIÓN X 15 ML</option>
        <option value="suspension_x_22_5_ml">SUSPENSIÓN X 22.5 ML</option>
        <option value="suspension_x_50_ml">SUSPENSIÓN X 50 ML</option>
        <option value="suspension_x_60_ml">SUSPENSIÓN X 60 ML</option>
        <option value="suspension_x_70_ml">SUSPENSIÓN X 70 ML</option>
        <option value="suspension_x_80_ml">SUSPENSIÓN X 80 ML</option>
        <option value="suspension_x_100_ml">SUSPENSIÓN X 100 ML</option>
        <option value="suspension_x_120_ml">SUSPENSIÓN X 120 ML</option>
        <option value="suspension_x_150_ml">SUSPENSIÓN X 150 ML</option>
        <option value="suspension_x_220_ml">SUSPENSIÓN X 220 ML</option>
        <option value="suspension_x_237_ml">SUSPENSIÓN X 237 ML</option>
        <option value="suspension_x_360_ml">SUSPENSIÓN X 360 ML</option>
        <option value="suspension_oral_x_60_ml">SUSPENSIÓN ORAL X 60 ML</option>
        <option value="buster_x_4_capsulas">BUSTER X 4 CAPSULAS</option>
        <option value="buster_x_10_capsulas">BUSTER X 10 CAPSULAS</option>
        <option value="bolsa">BOLSA</option>
        <option value="jarabe_x_20_ml">JARABE X 20 ML</option>
        <option value="jarabe_x_30_ml">JARABE X 30 ML</option>
        <option value="jarabe_x_60_ml">JARABE X 60 ML</option>
        <option value="jarabe_x_100_ml">JARABE X 100 ML</option>
        <option value="jarabe_x_120_ml">JARABE X 120 ML</option>
        <option value="jarabe_x_125_ml">JARABE X 125 ML</option>
        <option value="jarabe_x_150_ml">JARABE X 150 ML</option>
        <option value="jarabe_x_200_ml">JARABE X 200 ML</option>
        <option value="jarabe_x_240_ml">JARABE X 240 ML</option>
        <option value="jabón_liquido_antiseptico">JABÓN LIQUIDO ANTISEPTICO</option>
        <option value="1_tableta">1 TABLETA</option>
        <option value="polvo">POLVO</option>
        <option value="polvo_para_inhalacion">POLVO PARA INHALACIÓN</option>
        <option value="polvo_para_suspension_oral_200_g">POLVO PARA SUSPENSIÓN ORAL 200 G</option>
        <option value="locion_x_100_ml">LOCION X 100 ML</option>
        <option value="frasco_200_mg_100ml">FRASCO 200 MG/100ML</option>
        <option value="frasco_250_mg_5ml">FRASCO 250 MG/5ML</option>
        <option value="frasco_500mg_100ml">FRASCO 500MG/100ML</option>
        <option value="frasco_10_ml">FRASCO 10 ML</option>
        <option value="frasco_200_ml">FRASCO 200 ML</option>
        <option value="frasco_x_10_tabletas">FRASCO X 10 TABLETAS</option>
        <option value="frasco_x_30_tabletas">FRASCO X 30 TABLETAS</option>
        <option value="faja">FAJA</option>
        <option value="fibra_en_polvo_suspension_oral">FIBRA EN POLVO SUSPENSIÓN ORAL</option>
        <option value="sobre_x_12_tabletas">SOBRE X 12 TABLETAS</option>
        <option value="infusion_inyectable">INFUSIÓN INYECTABLE</option>
        <option value="gragea">GRAGEA</option>
        <option value="gotas">GOTAS</option>
        <option value="gotas_x_5_ml">GOTAS X 5 ML</option>
        <option value="gotas_x_15_ml">GOTAS X 15 ML</option>
        <option value="gotas_x_20_ml">GOTAS X 20 ML</option>
        <option value="gotas_x_30_ml">GOTAS X 30 ML</option>
        <option value="gotas_oticas_x_5_ml">GOTAS OTICAS X 5 ML</option>
        <option value="gel_uso_oral_5_mg_ml">GEL/USO ORAL 5 MG/ML</option>
        <option value="gel_oral_x_60_g">GEL ORAL X 60 G</option>
        <option value="gel_oral_x_78_g">GEL ORAL X 78 G</option>
        <option value="gel_oral_x_120_ml">GEL ORAL X 120 ML</option>
        <option value="gel_topico_x_30_g">GEL TOPICO X 30 G</option>
        <option value="gel_topico_x_60_g">GEL TOPICO X 60 G</option>
        <option value="gel_x_114_gr">GEL X 114 GR</option>
        <option value="sobre_soluble_x_10">SOBRE SOLUBLE X 10</option>
        <option value="sobre_liquido_x_10_ml">SOBRE LIQUIDO X 10 ML</option>
        <option value="unguento">UNGÚENTO</option>
        <option value="unguento_topica_x_15_g">UNGÚENTO TOPICA X 15 G</option>
        <option value="leche">LECHE</option>
        <option value="lata_x_400_g">LATA X 400 G</option>
        <option value="lata_x_375_g">LATA X 375 G</option>
        <option value="inhalador">INHALADOR</option>
        <option value="jeringa_prellenada">JERINGA PRELLENADA</option>
        <option value="emugel">EMUGEL</option>
        <option value="tabletas_6_x_12">TABLETAS 6 X 12</option>
        <option value="6_soluble_x_10">6 SOLUBLE X 10</option>
        <option value="6_liquido_x_10_ml">6 LIQUIDO X 10 ML</option>
        <option value="enjuague_bucal">ENJUAGUE BUCAL</option>
        <option value="litros">LITROS</option>
        <option value="suministros">SUMINISTROS</option>
        <option value="28_comprimidos_bicapa">28 COMPRIMIDOS BICAPA</option>
        <option value="medias">MEDIAS</option>
        <option value="aguja_subcutanea">AGUJA SUBCUTANEA</option>
        <option value="vendas">VENDAS</option>
    </select>
</div>
<div class="form-item">
    <label for="concentracion">Concentración:</label>
    <select id="concentracion" name="concentracion" class="select2">
        <option value="">Seleccionar...</option>
        <option value="baja_concentracion">Baja Concentración (0.7 GR/2.5 ML)</option>
        <option value="media_concentracion">Media Concentración (5 MG)</option>
        <option value="concentracion_media">Concentración Media (2.5 MG/5 ML)</option>
        <option value="alta_concentracion">Alta Concentración (40 MG)</option>
        <option value="muy_alta_concentracion">Muy Alta Concentración (100 MG/ML)</option>
        <option value="extrema_concentracion">Extrema Concentración (125 MG/ML)</option>
    </select>
</div>
<div class="form-item">
    <label for="via_administracion">Via Administración:</label>
    <select id="via_administracion" name="via_administracion" class="select2">
        <option value="">Seleccionar...</option>
        <option value="oral">ORAL</option>
        <option value="intramuscular">INTRAMUSCULAR</option>
        <option value="nasal">NASAL</option>
        <option value="inhalado">INHALADO</option>
        <option value="ocular">OCULAR</option>
        <option value="topica">TOPICA</option>
        <option value="topica_oral">TOPICA ORAL</option>
        <option value="inhalado_con_espaciador">INHALADO CON ESPACIADOR DE VOLUMEN</option>
        <option value="aerosol_inhalado_polvo_seco">AEROSOL INHALADO DE POLVO SECO</option>
        <option value="suspension_nebulizar">SUSPENSIÓN PARA NEBULIZAR</option>
        <option value="solucion_acuosa">SOLUCIÓN ACUOSA</option>
        <option value="otica">OTICA</option>
        <option value="liofilizado_disolver">LIOFILIZADO PARA DISOLVER</option>
        <option value="irrigacion_nasal">IRRIGACIÓN NASAL</option>
        <option value="vaginal">VAGINAL</option>
        <option value="subcutanea">SUBCUTANEA</option>
        <option value="intravenosa">INTRAVENOSA</option>
    </select>
</div>

<div class="form-item">
    <label for="codigo_articulo">Código Artículo:</label>
    <select id="codigo_articulo" name="codigo_articulo" class="select2">
        <option value="">Seleccionar...</option>
        <option value="MEDI_711107">MEDI_711107</option>
        <option value="MATE_018531">MATE_018531</option>
        <option value="SERV_008503">SERV_008503</option>
        <option value="IMGS_062305">IMGS_062305</option>
        <option value="LABS_574021">LABS_574021</option>
        <option value="INSV_749857">INSV_749857</option>
        <option value="EMER_046268">EMER_046268</option>
        <option value="PROC_206365">PROC_206365</option>
        <option value="OBSE_974271">OBSE_974271</option>
        <option value="CIRU_935226">CIRU_935226</option>
        <option value="UCI_689527">UCI_689527</option>
        <option value="DIGE_326260">DIGE_326260</option>
        <option value="SERV_960458">SERV_960458</option>
        <option value="MÉD_239647">MÉD_239647</option>
        <option value="DOMI_152187">DOMI_152187</option>
        <option value="CARD_283276">CARD_283276</option>
        <option value="NEUR_857219">NEUR_857219</option>
        <option value="LABO_284010">LABO_284010</option>
        <option value="SUMI_307323">SUMI_307323</option>
        <option value="MUJE_486470">MUJE_486470</option>
        <option value="OTOR_467248">OTOR_467248</option>
        <option value="UROL_648819">UROL_648819</option>
        <option value="HEMA_788205">HEMA_788205</option>
        <option value="NEUM_368679">NEUM_368679</option>
        <option value="INST_917759">INST_917759</option>
        <option value="PROM_277228">PROM_277228</option>
        <option value="PODO_465537">PODO_465537</option>
        <option value="ARRE_187836">ARRE_187836</option>
        <option value="PROC_710650">PROC_710650</option>
        <option value="ALQU_773016">ALQU_773016</option>
    </select>
</div>

<script>
function updateArticulo() {
    const lineaSelect = document.getElementById("linea");
    const codigoSelect = $("#codigo_articulo");

    // Limpiar el select de código artículo
    codigoSelect.val("").trigger('change'); // Limpiar el valor y actualizar Select2

    // Obtener el índice de la opción seleccionada en la lista de línea
    const selectedIndex = lineaSelect.selectedIndex;

    // Si se ha seleccionado una opción válida
    if (selectedIndex > 0) {
        // Obtener el valor del atributo data-codigo
        const codigoArticulo = lineaSelect.options[selectedIndex].getAttribute("data-codigo");
        
        // Seleccionar la opción correspondiente en el select de código artículo
        codigoSelect.val(codigoArticulo).trigger('change'); // Actualizar el valor y refrescar Select2
    }
}
</script>

    <div class="form-item">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre">
    </div>
    <div class="form-item">
        <label for="descripcion">Descripción:</label>
        <input type="text" id="descripcion" name="descripcion">
    </div>
    <div class="form-item">
        <label for="precio_maximo_venta">Precio Máximo de Venta:</label>
        <input type="text" id="precio_maximo_venta" name="precio_maximo_venta" step="0.01">
    </div>
    <div class="form-item">
        <label for="existencia_minima">Existencia Mínima:</label>
        <input type="text" id="existencia_minima" name="existencia_minima">
    </div>
    <div class="form-item">
        <label for="existencia_maxima">Existencia Máxima:</label>
        <input type="text" id="existencia_maxima" name="existencia_maxima">
    </div>
    <div class="form-item">
        <label for="comision">Comisión:</label>
        <input type="text" id="comision" name="comision" step="0.01">
    </div>
<div class="form-item">
    <label for="fecha_vence">Fecha Vencimiento:</label>
    <input type="date" id="fecha_vence" name="fecha_vence">
</div>

<div class="form-item">
    <label for="costo">Precio Costo:</label>
    <input type="text" id="costo" name="costo" oninput="calcularPrecioVenta()">
</div>
<div class="form-item">
    <label for="margen_ganancia">Margen Ganancia (%):</label>
    <input type="text" id="margen_ganancia" name="margen_ganancia" oninput="calcularPrecioVenta()">
</div>
<div class="form-item">
    <label for="precio_venta">Precio Venta:</label>
    <input type="text" id="precio_venta" name="precio_venta" step="0.01" readonly>
</div>

<script>
    function calcularPrecioVenta() {
        // Obtener los valores de los campos
        let costo = parseFloat(document.getElementById('costo').value) || 0;
        let margenGanancia = parseFloat(document.getElementById('margen_ganancia').value) || 0;

        // Calcular el precio de venta sumando el margen de ganancia al costo
        let precioVenta = costo + (costo * (margenGanancia / 100));

        // Mostrar el resultado en el campo de precio de venta
        document.getElementById('precio_venta').value = precioVenta.toFixed(2);
    }
</script>

    <div class="form-item">
        <label for="impuestos">Impuesto:</label> 
        <input type="text" id="impuestos" name="impuestos">
    </div>
    <div class="form-item">
        <label for="lote">Número de Lote: <span class="required">*</span></label>
        <input type="text" id="lote" name="lote" required>
    </div>
    <button type="submit">Registrar</button>
</div>
</form>

<!-- Include jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- Include Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<!-- Inicializar Select2 JS -->
<script>
    $(document).ready(function() {
        $('.select2').select2(); // Inicializa Select2 para todos los select con clase select2
    });
</script>

</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="ruta/a/tu/estilo.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
</head>
<body>
    <!-- Título centrado -->
    <div class="table-title">
        <h1>Registros de Articulos</h1>
    </div>
    <!-- Contenedor para buscar y paginar -->
    <div class="controls">
        <input type="text" id="search-input" placeholder="Buscar...">
        <button id="search-button">Buscar</button>
        <div id="pagination"></div>
    </div>
    <!-- Tabla para mostrar los datos -->
    <div class="table-container">
        <table id="directorio-table" border="1">
            <thead>
                <tr>
                    <th>Linea</th>
                    <th>Sub Linea</th>
                    <th>Sucursal/Bodega</th>
                    <th>Presentación</th>
                    <th>Forma Farmaceutica</th>
                    <th>Concentración</th>
                    <th>Via Administración</th>
                    <th>Código Artículo</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio Máximo de Venta</th>
                    <th>Existencia Mínima</th>
                    <th>Existencia Máxima</th>
                    <th>Comisión</th>
                    <th>Fecha del Registro</th>
                    <th>Fecha Vencimiento</th>
                    <th>Existencias</th>
                    <th>Precio Costo</th>
                    <th>Margen Ganancia (%)</th>
                    <th>Precio Venta</th>
                    <th>Impuestos</th>
                    <th>Número de Lote</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Las filas se llenarán dinámicamente aquí -->
            </tbody>
        </table>
    </div>
    <!-- Script para visualizar datos del formulario en la tabla -->
    <script src="../../backend/registros/script/tabla_articulo.js"></script>
    <!-- Script para el registro del formulario -->
    <script src="../../backend/registros/script/reg_articulo.js"></script>
    <!-- Script para generar PDF 
    <script src="../../backend/registros/script/directorio_pdf.js"></script>-->
</body>
</html>

        </main>
        <!-- MAIN -->
    </section>
    
    <script src="../../backend/js/script.js"></script>
    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

</body>
</html>