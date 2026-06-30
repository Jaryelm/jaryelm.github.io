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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
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

    <!-- Include CSS de Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

</head>
<body>

<div class="form-container">
    <h2>Nueva Compra</h2>
    <div class="form-item">
        <label for="fecha-compra">Fecha de compra:</label>
        <input type="date" id="fecha-compra" value="2024-10-07">
    </div>
    <div class="form-item">
        <label for="proveedor">Proveedor:</label>
        <select id="proveedor" class="select2">
            <option value="">Seleccionar proveedor...</option>
            <option value="LABORATORIO CLINICO LABITECH">LABORATORIO CLINICO LABITECH</option>
            <option value="MAGA">MAGA</option>
            <option value="HONDUMEDIC">HONDUMEDIC</option>
            <option value="INVERSIONES MEDICAS LOS ALMENDROS">INVERSIONES MEDICAS LOS ALMENDROS</option>
            <option value="SYCOM">SYCOM</option>
            <option value="PAPELERIA HONDURAS">PAPELERIA HONDURAS</option>
            <option value="INNOVATECK">INNOVATECK</option>
            <option value="SKYTEK SECURITY">SKYTEK SECURITY</option>
            <option value="TECNOCOMP">TECNOCOMP</option>
            <option value="TECSO">TECSO</option>
            <option value="SUPLIDORA MEDICA S. DE R.L. DE C.V.">SUPLIDORA MEDICA S. DE R.L. DE C.V.</option>
            <option value="MATAMOROS">MATAMOROS</option>
            <option value="DIMEX MEDICA S. DE R.L.">DIMEX MEDICA S. DE R.L.</option>
            <option value="DICOSA">DICOSA</option>
            <option value="GAMEDICAL">GAMEDICAL</option>
            <option value="DROGUERIA LE CLINIQUE">DROGUERIA LE CLINIQUE</option>
            <option value="FINLAY">FINLAY</option>
            <option value="NIPRO MEDICAL CORPORATION">NIPRO MEDICAL CORPORATION</option>
            <option value="MEDIFARMA">MEDIFARMA</option>
            <option value="NAZARETH">NAZARETH</option>
            <option value="AMERICANA">AMERICANA</option>
            <option value="DROMEINTER">DROMEINTER</option>
            <option value="SOLFAHSA">SOLFAHSA</option>
            <option value="PHARMAETICA S.A.">PHARMAETICA S.A.</option>
            <option value="PROCONSUMO (MANDOFER)">PROCONSUMO (MANDOFER)</option>
            <option value="DRORISA">DRORISA</option>
            <option value="CORINFAR S.A. DE C.V.">CORINFAR S.A. DE C.V.</option>
            <option value="SUPER FARMACIA SIMAN S.A.">SUPER FARMACIA SIMAN S.A.</option>
            <option value="SERVIMEDICA S. DE R.L.">SERVIMEDICA S. DE R.L.</option>
            <option value="EYL COMERCIAL S.A. (ECSA)">EYL COMERCIAL S.A. (ECSA)</option>
            <option value="MENFAR">MENFAR</option>
            <option value="LETERAGO">LETERAGO</option>
            <option value="MEDILAB">MEDILAB</option>
            <option value="FARINTER">FARINTER</option>
            <option value="MV PHARMA">MV PHARMA</option>
            <option value="FRANCESCA">FRANCESCA</option>
            <option value="HENIE FARMA">HENIE FARMA</option>
            <option value="CORFARMA S.A. DEC.V">CORFARMA S.A. DEC.V</option>
            <option value="REJUWE S. DE R.L.">REJUWE S. DE R.L.</option>
            <option value="HASTHER S. DE R.L.">HASTHER S. DE R.L.</option>
            <option value="PRIME IMPORTS S.A.">PRIME IMPORTS S.A.</option>
            <option value="IPRA">IPRA</option>
            <option value="HILLROY">HILLROY</option>
            <option value="HIGEA PHARMA">HIGEA PHARMA</option>
            <option value="KRISAN">KRISAN</option>
            <option value="VERSALLES">VERSALLES</option>
            <option value="PHAR MED SALES HONDURAS S.A.">PHAR MED SALES HONDURAS S.A.</option>
            <option value="NUTRITECH">NUTRITECH</option>
            <option value="PROMESA">PROMESA</option>
            <option value="ANDIFAR S. DE R.L.">ANDIFAR S. DE R.L.</option>
            <option value="PHARMA INTERNACIONAL">PHARMA INTERNACIONAL</option>
            <option value="PAYSEN">PAYSEN</option>
            <option value="NACIONAL S.A.">NACIONAL S.A.</option>
            <option value="MC">MC</option>
            <option value="LANCASCO">LANCASCO</option>
            <option value="CORPOLABF">CORPOLABF</option>
            <option value="GENERIC PHARMA">GENERIC PHARMA</option>
            <option value="ULTIMATE">ULTIMATE</option>
            <option value="BENPHARMA">BENPHARMA</option>
            <option value="KARNEL">KARNEL</option>
            <option value="GUARDADO">GUARDADO</option>
            <option value="LAGOMAC">LAGOMAC</option>
            <option value="UNIVERSAL">UNIVERSAL</option>
            <option value="PIERSAN">PIERSAN</option>
            <option value="DISTRIBUIDORA ALFAMEDIC">DISTRIBUIDORA ALFAMEDIC</option>
            <option value="FARMACIA KIELSA">FARMACIA KIELSA</option>
            <option value="MACC MEDICAL">MACC MEDICAL</option>
            <option value="SEVEN PHARMA HONDURAS S.A.">SEVEN PHARMA HONDURAS S.A.</option>
            <option value="ORTHOMED">ORTHOMED</option>
            <option value="TECNOLOGÍA MEDICA DE HONDURAS">TECNOLOGÍA MEDICA DE HONDURAS</option>
            <option value="DISTRIBUCIONES MEDICAS EDUMAY S. DE R.L.">DISTRIBUCIONES MEDICAS EDUMAY S. DE R.L.</option>
            <option value="MEDI SYSTEM S DE R.L.">MEDI SYSTEM S DE R.L.</option>
            <option value="GRUPO MEY-KO S.A.">GRUPO MEY-KO S.A.</option>
            <option value="DIMEHOS">DIMEHOS</option>
            <option value="DIDELSA">DIDELSA</option>
            <option value="EMBOTELLADORA DE SULA (EMSULA)">EMBOTELLADORA DE SULA (EMSULA)</option>
            <option value="SUPER MERCADO LA COLONIA S.A. DE C.V.">SUPER MERCADO LA COLONIA S.A. DE C.V.</option>
            <option value="VERMACO">VERMACO</option>
            <option value="MEDICINAS PARA PHARMACIAS">MEDICINAS PARA PHARMACIAS</option>
            <option value="DRODIME">DRODIME</option>
            <option value="PACASA PAPELERIA CALPULES S.A. DE C.V.">PACASA PAPELERIA CALPULES S.A. DE C.V.</option>
            <option value="IMPRESOS GRAFICOS">IMPRESOS GRAFICOS</option>
            <option value="CERVECERIA HONDUREÑA S.A. DE C.V.">CERVECERIA HONDUREÑA S.A. DE C.V.</option>
            <option value="LA BOTICA">LA BOTICA</option>
            <option value="DROGUERIA MARIE">DROGUERIA MARIE</option>
            <option value="COMERCIAL ARCES S. DE R.L.">COMERCIAL ARCES S. DE R.L.</option>
            <option value="DINSULA S. DE R.L. DE C.V.">DINSULA S. DE R.L. DE C.V.</option>
            <option value="VARIEDADES CLAUDINA">VARIEDADES CLAUDINA</option>
            <option value="PHARMED SALES HONDURAS S.A.">PHARMED SALES HONDURAS S.A.</option>
            <option value="VILLAVICENCIO COMERCIAL">VILLAVICENCIO COMERCIAL</option>
            <option value="LEOPLAST">LEOPLAST</option>
            <option value="DENTAL HOME S DE R.L.">DENTAL HOME S DE R.L.</option>
            <option value="LEMPIRA MASTER IMPRESIONES">LEMPIRA MASTER IMPRESIONES</option>
            <option value="CORPORACION E INVERSIONES LEXDAN">CORPORACION E INVERSIONES LEXDAN</option>
            <option value="JORGE ARTURO CRUZ RAMOS">JORGE ARTURO CRUZ RAMOS</option>
            <option value="VIDRIOS Y CELOCIAS CENTENARIO">VIDRIOS Y CELOCIAS CENTENARIO</option>
            <option value="EMPRESA DE INGENIERIA SALINAS">EMPRESA DE INGENIERIA SALINAS</option>
            <option value="SUMINISTROS OPTICOS L Y G">SUMINISTROS OPTICOS L Y G</option>
            <option value="CAFETERIA SOANY">CAFETERIA SOANY</option>
            <option value="REBABI-MED S. DE R.L.">REBABI-MED S. DE R.L.</option>
            <option value="KUN Y BOLT S. DE R.L.">KUN Y BOLT S. DE R.L.</option>
            <option value="EDUAR ALBERTO ZELAYA SALGADO">EDUAR ALBERTO ZELAYA SALGADO</option>
            <option value="SUYAPA MEDIOS">SUYAPA MEDIOS</option>
            <option value="EMOSORAS UNIDAS">EMOSORAS UNIDAS</option>
            <option value="INDUSTRIA MA K NUDO S.A DE C.V">INDUSTRIA MA K NUDO S.A DE C.V</option>
            <option value="SERVICIOS INTEGRADOS DE MERCADEO DE R.L.">SERVICIOS INTEGRADOS DE MERCADEO DE R.L.</option>
            <option value="SINERKA SOLUTIONS S.A.">SINERKA SOLUTIONS S.A.</option>
            <option value="GRABANDO PRODUCCIONES RADIOFONICAS S DE R.L.">GRABANDO PRODUCCIONES RADIOFONICAS S DE R.L.</option>
            <option value="ANTHONY MARTINEZ">ANTHONY MARTINEZ</option>
            <option value="HONDURED">HONDURED</option>
            <option value="SPEED DIGITAL">SPEED DIGITAL</option>
            <option value="LA TRIBUNA">LA TRIBUNA</option>
            <option value="PRINT COLOR">PRINT COLOR</option>
            <option value="QHUBO TV">QHUBO TV</option>
            <option value="KAIROS COMUNICACIONES S. DE R.L. DE C.V">KAIROS COMUNICACIONES S. DE R.L. DE C.V</option>
            <option value="PRODUCTOS CRUZ ABADI S.A.">PRODUCTOS CRUZ ABADI S.A.</option>
            <option value="GRPO DISTRIBUIDOR">GRPO DISTRIBUIDOR</option>
            <option value="PUSH DIGITAL S. DE R.L.">PUSH DIGITAL S. DE R.L.</option>
            <option value="ANCHECTA ROMERO & ASOCIADOS S. DE R.L.">ANCHECTA ROMERO & ASOCIADOS S. DE R.L.</option>
            <option value="PANGEA LOGISTCS">PANGEA LOGISTCS</option>
            <option value="TECHNICAL SECURITY">TECHNICAL SECURITY</option>
            <option value="SUPERFICIES NOVA">SUPERFICIES NOVA</option>
            <option value="ESCRITORIOS Y MAS S. DE R.L.">ESCRITORIOS Y MAS S. DE R.L.</option>
            <option value="PINTURAS COMEX">PINTURAS COMEX</option>
            <option value="SERVICIOS ELECTRICOS Y HOSPITALARIOS S. DE R.L.">SERVICIOS ELECTRICOS Y HOSPITALARIOS S. DE R.L.</option>
            <option value="ESTUDIOS DOS">ESTUDIOS DOS</option>
            <option value="VAST NUTRITION S. DE R.L.">VAST NUTRITION S. DE R.L.</option>
            <option value="INFRA DE HONDURAS">INFRA DE HONDURAS</option>
            <option value="IMPRESOS S. DE R.L. DE C.V.">IMPRESOS S. DE R.L. DE C.V.</option>
            <option value="MEDICASA">MEDICASA</option>
            <option value="MC DENTAL">MC DENTAL</option>
            <option value="INEQ MEDICA S. DE R.L. DE C.V.">INEQ MEDICA S. DE R.L. DE C.V.</option>
            <option value="MEDIDENT S. DE R.L.">MEDIDENT S. DE R.L.</option>
            <option value="BODEGA JERUZALEN">BODEGA JERUZALEN</option>
            <option value="ACOSA">ACOSA</option>
            <option value="BRICMED">BRICMED</option>
        </select>
    </div>
    <div class="form-item">
        <label for="documento">Documento:</label>
        <input type="text" id="documento" placeholder="# Documento">
    </div>
    <div class="form-item">
        <label for="mes-aplicar">Mes a aplicar:</label>
        <input type="text" id="mes-aplicar" value="OCTUBRE de 2024">
    </div>
    <div class="form-item">
        <label for="monto">Monto:</label>
        <input type="number" id="monto" value="0">
    </div>
    <div class="form-item">
        <label for="fovial">Fovial:</label>
        <input type="number" id="fovial" value="0">
    </div>
    <div class="form-item">
        <label for="coatrans">COATRANS:</label>
        <input type="number" id="coatrans" value="0">
    </div>
    <div class="form-item">
        <label for="credito-fiscal">Crédito Fiscal:</label>
        <input type="number" id="credito-fiscal" value="0">
    </div>
    <div class="form-item">
        <label for="percepcion">Percepción:</label>
        <input type="number" id="percepcion" value="0">
    </div>
    <div class="form-item">
        <label for="cesc">CESC:</label>
        <input type="number" id="cesc" value="0">
    </div>
    <div class="form-item">
        <label for="tipo-compras">Tipo de Compras:</label>
        <select id="tipo-compras">
            <option value="">Seleccionar...</option>
            <option value="interna">Compra Local</option>
            <option value="externa">Compra Internacional</option>
        </select>
    </div>
    <div class="form-item">
        <label for="tipo">Tipo:</label>
        <select id="tipo">
            <option value="">Seleccionar...</option>
            <option value="grabado">Gravado</option>
            <option value="exento">Exento</option>
        </select>
    </div>
    <div class="form-item">
        <label for="tipo-pago">Tipo de pago:</label>
        <select id="tipo-pago">
            <option value="">Seleccionar...</option>
            <option value="contado">Contado</option>
            <option value="credito">Crédito</option>
        </select>
    </div>
    <div class="form-item">
        <label for="dias-credito">Días crédito:</label>
        <input type="number" id="dias-credito" value="0">
    </div>
    <!-- Campo Total -->
    <div class="form-item">
        <label for="total">Total:</label>
        <input type="number" id="total" step="0.01" placeholder="Ingrese el total">
    </div>

    <div class="form-item">
        <label for="divisa">Divisa:</label>
        <select id="divisa">
            <option value="">Seleccionar...</option>
            <option value="LPS">LPS</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="R$">R$</option>
        </select>
    </div>

    <button type="submit">Registrar Compra</button>
</div>

<!-- Include jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- Include Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
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
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
</head>
<body>
    <!-- Título centrado -->
    <div class="table-title">
        <h1>Registros de Compras</h1>
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
                    <th>Fecha de Compra</th>
                    <th>Proveedor</th>
                    <th>Documento</th>
                    <th>Mes Aplicar</th>
                    <th>Monto</th>
                    <th>Fovial</th>
                    <th>COATRANS</th>
                    <th>Crédito Fiscal</th>
                    <th>Percepción</th>
                    <th>CESC</th>
                    <th>Tipo de Compras</th>
                    <th>Tipo</th>
                    <th>Tipo de Pago</th>
                    <th>Días Crédico</th>
                    <th>Total</th>
                    <th>Archivo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Las filas se llenarán dinámicamente aquí -->
            </tbody>
        </table>
    </div>
    <!-- Script para visualizar datos del formulario en la tabla -->
    <script src="../../backend/registros/script/tabla_directorio_comercial.js"></script>
    <!-- Script para generar PDF 
    <script src="../../backend/registros/script/directorio_pdf_comercial.js"></script>-->
    <!-- Script para el registro del formulario 
    <script src="../../backend/registros/script/tabla_directorio.js"></script>-->
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