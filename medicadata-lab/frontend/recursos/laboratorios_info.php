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

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">




    <title>MEDIDATA</title>
</head>
<body>
    
<!-- SIDEBAR --> 
<section id="sidebar">
    <a href="../../admin/escritorio.php" class="brand"><i class='bx bxs-home home'></i> MEDIDATA</a>
        <ul class="side-menu">
            <li><a href="../../admin/escritorio.php" class="active"><i class='bx bxs-dashboard icon' ></i> Panel</a></li>
            <li class="divider" data-text="panel">Panel</li>


            <li>
    <a href="#" class="new-menu-link"><i class='bx bxs-group icon'></i> Contabilidad y Finanzas<i class='bx bx-chevron-right icon-right'></i></a>
    <ul class="side-dropdown">
        <li>
            <a href="#" class="new-submenu-link">Diario Mayor</a>
            <ul class="new-side-dropdown">
                <li><a href="../../../frontend/contabilidad/diariomayor/catalogo.php">Catalogo de Cuentas</a></li>
                <li><a href="#">Diario General</a></li>
                <li><a href="../../../frontend/contabilidad/diariomayor/transacciones.php">Transacciones Capturadas</a></li>
            </ul>
        </li>
        <li>
            <a href="#" class="new-submenu-link">Gestión de Pagos</a>
            <ul class="new-side-dropdown">
                <li><a href="../../../frontend/contabilidad/chequera/emitir_cheque.php">Emitir Cheques</a></li>
                <li><a href="../../../frontend/contabilidad/chequera/conciliacion_bancaria.php">Conciliación Bancaria</a></li>
                <li><a href="../../../frontend/contabilidad/chequera/recibir_pagos.php">Recibir Pagos</a></li>
                <li><a href="#">Preparar Deposito Bancario</a></li>
                <li><a href="#">Transacciones Capturadas</a></li>
                <li><a href="../../../frontend/contabilidad/chequera/tabla_cheque.php">Cheques Registrados</a></li>
            </ul>
        </li>
        <li>
            <a href="#" class="new-submenu-link">Ventas</a>
            <ul class="new-side-dropdown">
                <li><a href="#">Información de Ventas</a></li>
                <li><a href="#">Cotizaciones Ordenes Facturas</a></li>
                <li><a href="#">Registros de Ventas</a></li>
                <li><a href="#">Devoluciones</a></li>
                <li><a href="#">Transacciones Capturadas</a></li>
                <li><a href="#">Pagos del Cliente</a></li>
                <li><a href="#">Imprimir Estados de Cuentas</a></li>
                <li><a href="#">Imprimir Ventas</a></li>
            </ul>
        </li>
        <li>
            <a href="#" class="new-submenu-link">Factura de Tiempo</a>
            <ul class="new-side-dropdown">
                <li><a href="#">#</a></li>
            </ul>
        </li>
        <li>
            <a href="#" class="new-submenu-link">Compras</a>
            <ul class="new-side-dropdown">
                <li><a href="../../../frontend/contabilidad/compras/nuevacompra.php">Nueva Compra</a></li>
                <li><a href="#">Información de Compras</a></li>
                <li><a href="#">Cotizaciones Ordenes Compras</a></li>
                <li><a href="#">Registro de Compras</a></li>
                <li><a href="#">Devoluciones</a></li>
                <li><a href="#">Transacciones Capturadas</a></li>
                <li><a href="#">Pago al Proveedor</a></li>
                <li><a href="#">Imprimir Formulario de Compras</a></li>
            </ul>
        </li>
        <li>
            <a href="#" class="new-submenu-link">Inventario</a>
            <ul class="new-side-dropdown">
                <li><a href="#">#</a></li>
            </ul>
        </li>
        <li>
            <a href="#" class="new-submenu-link">Directorio de Proveedores</a>
            <ul class="new-side-dropdown">
                <li><a href="../../../frontend/contabilidad/directorio/tabla_directorio.php">Tabla Directorio Médico</a></li>
                <li><a href="../../../frontend/contabilidad/directorio/tabla_directorio_comercial.php">Tabla Directorio Comercial</a></li>
            </ul>
        </li>
    </ul>
</li>

<li>
                <a href="#"><i class='bx bxs-user-pin icon' ></i> Recursos Humanos<i class='bx bx-chevron-right icon-right' ></i></a>
                <ul class="side-dropdown">
                    <li><a href="../../recursos/enfermera.php">Personal de Enfermeria</a></li>
                    <li><a href="#">Personal Administrativo</a></li>
                    <li><a href="../../recursos/laboratiorios.php">Laboratorios</a></li>
                    
                </ul>
        </li>

        <li>
                <a href="#"><i class='bx bxs-spray-can icon' ></i>Almacen Compras<i class='bx bx-chevron-right icon-right' ></i></a>
                <ul class="side-dropdown">
                    <li><a href="../../almacen/venta.php">Historial de Ventas</a></li>
                    <li><a href="../../almacen/mostrar.php">Listado de Medicinas</a></li>
                    <li><a href="../../almacen/compra_unificada.php">Compra e inventario</a></li>
                    <li><a href="../../almacen/categoria.php">Categoria</a></li>
                    <li><a href="../../almacen/registroarticulo.php">Registro de Articulos</a></li>

                </ul>
            </li>

            <li>
                <a href="#"><i class='bx bxs-spray-can icon' ></i>CRM Marketing<i class='bx bx-chevron-right icon-right' ></i></a>
                <ul class="side-dropdown">
                    <li><a href="../../#">Cumpleaños</a></li>

                </ul>
            </li>

            <li>
                <a href="#"><i class='bx bxs-user icon' ></i>Gestión de Pacientes <i class='bx bx-chevron-right icon-right' ></i></a>
                <ul class="side-dropdown">
                    <li><a href="../../pacientes/mostrar.php" >Registro de Pacientes</a></li>
                    <li><a href="../../pacientes/pagos.php">#</a></li>
                    <li><a href="../../pacientes/historial.php">Historial de los pacientes</a></li>
                    <li><a href="../../pacientes/documentos.php">Documentos</a></li>
                    <li><a href="../../citas/nuevo.php">Nueva Cita</a></li>
                    <li><a href="../../citas/mostrar.php">Todas las citas</a></li>
                    <li><a href="../../citas/calendario.php">Calendario de Citas</a></li>
                    <li><a href="../../citas/nuevo.php">Nueva Cita</a></li>
                    <li><a href="../../citas/mostrar.php">Todas las citas</a></li>
                    <li><a href="../../citas/calendario.php">Calendario de Citas</a></li>
                </ul>
        </li>

        <li>
                <a href="#"><i class='bx bxs-user icon' ></i>Gestión de Médica<i class='bx bx-chevron-right icon-right' ></i></a>
                <ul class="side-dropdown">
                    <li><a href="../../../pacientes/#" >#</a></li>
                    <li><a href="../../../pacientes/#">#</a></li>
                    <li><a href="../../../pacientes/#">#</a></li>
                    <li><a href="../../../pacientes/#">#</a></li>
                    <li><a href="../../../citas/#">#</a></li>
                    <li><a href="../../../citas/#">#</a></li>
                    <li><a href="../../../citas/#">#</a></li>
                   
                </ul>
        </li>

        <li>
            <a href="#"><i class='bx bxs-diamond icon' ></i> Ventas<i class='bx bx-chevron-right icon-right' ></i></a>
            <ul class="side-dropdown">
                <li><a href="../../actividades/new_sale.php">Facturación</a></li>
                <li><a href="../../actividades/#">Cotizaciones</a></li>
                <li><a href="../../actividades/#">Estados de cuenta</a></li>
                <li><a href="../../actividades/venta.php">Resumen de Ventas</a></li>
            </ul>
        </li>

        <li>
                <a href="#"><i class='bx bxs-briefcase icon' ></i> Usuarios <i class='bx bx-chevron-right icon-right' ></i></a>
                <ul class="side-dropdown">
                    <li><a href="../../medicos/nuevo.php">Nuevo Médico</a></li>
                    <li><a href="../../medicos/mostrar.php">Lista de Medicos</a></li>
                    <li><a href="../../frontend/contabilidad/directorio/formulario_directorio.php">Formulario Proveedores</a></li>
                </ul>
        </li>

            <li><a href="../../acerca/mostrar.php"><i class='bx bxs-info-circle icon' ></i>Acerca de MEDIDATA</a></li>
           
        </ul>
       

    </section>
<!-- SIDEBAR -->

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

<button class="button" onclick="location.href='enfermera.php'">Personal de Enfermeria</button>
<button class="button" onclick="location.href='enfermera_nuevo.php'">Registrar Personal de Enfermeria</button>
<button class="button" onclick="location.href='#'">Personal Administrativo</button>
<button class="button" onclick="location.href='#'">Registrar Personal Administrativo</button>
<button class="button" onclick="location.href='laboratiorios.php'">Laboratorios</button>
<button class="button" onclick="location.href='laboratorios_nuevo.php'">Registrar Laboratorio</button>
 
 <?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT * FROM laboratory  WHERE idlab= '$id';");
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

<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off" onsubmit="return validacion()">
  <div class="containerss">
    <h1>Información del laboratorio</h1>
   
   
    <hr>
<br>
    <label for="email"><b>Nombre del laboratorio</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Radiografia" name="labname" value="<?php echo $d->nomlab; ?>" required>
    <hr>
   

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

</body>
</html>


