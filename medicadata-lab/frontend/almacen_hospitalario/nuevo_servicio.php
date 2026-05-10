<?php
include_once '../../backend/registros/session_check.php'; // Archivo de sesión
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Include CSS de Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <title>Registro de Servicios Hospitalarios</title>
</head>
<body>

<?php
include_once '../admin/menu.php'; // Menú principal
?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#">
            <div class="form-group"></div>
        </form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; // Perfil del usuario ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <!-- Botones de navegación -->
        <button class="button" onclick="cambiarColor(this, '../almacen/compra_unificada.php')">Compra e inventario</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_compras.php')">Compras Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'categoria_new.php')">Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'categoria.php')">Lista de Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'nuevo_servicio.php')">Registrar Servicio</button>
        <button class="button" onclick="cambiarColor(this, 'lista_servicios.php')">Lista de Servicios</button>
        <button class="button" onclick="cambiarColor(this, 'reorden.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_solicitud_reorden_admin.php')">Autorización Compras Almacen</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones.php')">Requisiciones</button>

<!-- Formulario de Registro de Servicios Hospitalarios -->
<form action="" method="POST" autocomplete="off">
    <div class="containerss">
        <h1>Registrar Servicio Hospitalario</h1>

        <br>

        <div class="alert-danger">
            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            <strong>Importante!</strong> Es importante rellenar los campos con <span class="badge-warning">*</span>
        </div>

        <br>

        <!-- Campo Nombre del Servicio Dinámico -->
        <label for="service_name"><b>Nombre del Servicio</b></label><span class="badge-warning">*</span>
        <select id="service_name" name="service_name" class="select2" style="width: 100%;" required>
            <option value="">Seleccione o busque un servicio</option>
        </select>

        <br><br>

        <!-- Campo Uso del Servicio -->
        <label for="uso_servicio"><b>Uso del Servicio</b></label><span class="badge-warning">*</span>
        <select id="uso_servicio" name="uso_servicio" class="select2" style="width: 100%;" required>
            <option value="">Seleccione una opción</option>
            <option value="Atención">Atención</option>
            <option value="Día">Día</option>
            <option value="Hora">Hora</option>
            <option value="Por uso">Por uso</option>
            <option value="Fracción de Hora">Fracción de Hora</option>
            <option value="Visita">Visita</option>
            <option value="Procedimiento">Procedimiento</option>
            <option value="Equipo">Equipo</option>
            <option value="Estudio">Estudio</option>
        </select>

        <br><br>

        <!-- Campo Código de Servicio -->
        <label for="service_code"><b>Código de Servicio</b></label><span class="badge-warning">*</span>
        <input type="text" id="service_code" name="service_code" placeholder="Ejemplo: SRV_001" readonly required>

<!-- Campo Categoría de Servicio -->
<label for="categoria_servicio"><b>Unidad de Servicio</b></label><span class="badge-warning">*</span>
<select id="categoria_servicio" name="categoria_servicio" class="select2" required>
    <option value="" disabled selected>Seleccione una categoría</option>
    <option value="Cardiología">Cardiología</option>
    <option value="Gineco Obstetricia">Gineco Obstetricia</option>
    <option value="Unidad Digestiva">Unidad Digestiva</option>
    <option value="Neurología">Neurología</option>
    <option value="Salud Dental">Salud Dental</option>
    <option value="Radilogía e Imagen">Radilogía e Imagen</option>
    <option value="Arrendamientos">Arrendamientos</option>
    <option value="Servicios de Enfermeria">Servicios de Enfermeria</option>
    <option value="Servicios de Hospitalización">Servicios de Hospitalización</option>
    <option value="Material Quirurgico">Material Quirurgico</option>
    <option value="Emergencias">Emergencias</option>
    <option value="Medicina General">Medicina General</option>
    <option value="Laboratorio Clinico">Laboratorio Clinico</option>
    <option value="Servicios por Uso de Quirofano">Servicios por Uso de Quirofano</option>
    <option value="Uso de Equipo Médico">Uso de Equipo Médico</option>
    <option value="Medicamentos">Medicamentos</option>
    <option value="Servicios por Procedimientos">Servicios por Procedimientos</option>
    <option value="Gases Medicinales">Gases Medicinales</option>
    <option value="Ambulancia">Ambulancia</option>
    <option value="Estacionamiento">Estacionamiento</option>
    <option value="Otras Especialidades Medicas">Especialidades Medicas (%)</option>
    <option value="Patología Externa">Patología Externa</option>
    <option value="Otros">Otros</option>
</select>

<br><br>

        <!-- Campo Precio de Costo -->
        <label for="cost_price"><b>Precio Costo</b></label><span class="badge-warning">*</span>
        <input type="number" id="cost_price" name="cost_price" step="0.01" placeholder="Ejemplo: 100.00" required>

        <!-- Campo Margen de Ganancia -->
        <label for="profit_margin"><b>Margen de Ganancia (%)</b></label><span class="badge-warning">*</span>
        <input type="number" id="profit_margin" name="profit_margin" step="0.01" placeholder="Ejemplo: 20%" required>

        <!-- Campo Impuesto -->
        <label><b>Impuesto</b></label><span class="badge-warning">*</span><br>
        <div>
            <input type="radio" id="gravado" name="tax" value="G" required>
            <label for="gravado">Gravado 15%</label>
        </div>
        <div>
            <input type="radio" id="exento" name="tax" value="E" required>
            <label for="exento">Exento 0%</label>
        </div>

        <br>

        <!-- Campo Precio de Venta -->
        <label for="sale_price"><b>Precio de Venta</b></label><span class="badge-warning">*</span>
        <input type="number" id="sale_price" name="sale_price" step="0.01" placeholder="Ejemplo: 150.00" readonly required>

        <!-- Campo Total -->
        <label for="price_total"><b>Total</b></label><span class="badge-warning">*</span>
        <input type="number" id="price_total" name="price_total" step="0.01" placeholder="Ejemplo: 150.00" readonly required>

        <!-- Script para cálculo dinámico -->
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Escuchar eventos en cada campo relevante
                document.getElementById("cost_price").addEventListener("input", calcularTotales);
                document.getElementById("profit_margin").addEventListener("input", calcularTotales);
                document.getElementsByName("tax").forEach((radio) => {
                    radio.addEventListener("change", calcularTotales);
                });

                function calcularTotales() {
                    const costPrice = parseFloat(document.getElementById("cost_price").value) || 0;
                    const profitMargin = parseFloat(document.getElementById("profit_margin").value) || 0;

                    // Determinar el impuesto basado en el checkbox seleccionado
                    let taxPercentage = 0;
                    const selectedTax = document.querySelector('input[name="tax"]:checked');
                    if (selectedTax && selectedTax.value === "G") {
                        taxPercentage = 15; // Gravado 15%
                    }

                    // Calcular margen de ganancia en valor monetario
                    const profitValue = costPrice * (profitMargin / 100);

                    // Precio de venta sin impuesto
                    const salePrice = costPrice + profitValue;
                    document.getElementById("sale_price").value = salePrice.toFixed(2);

                    // Calcular impuesto en valor monetario
                    const taxValue = salePrice * (taxPercentage / 100);

                    // Calcular total
                    const totalPrice = salePrice + taxValue;
                    document.getElementById("price_total").value = totalPrice.toFixed(2);
                }
            });
        </script>

        <button type="submit" name="add_service" class="registerbtn">Guardar Servicio</button>
    </div>
</form>


    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/cat_cuentas_ingreso.js"></script>

    <!-- Include jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2(); // Inicializa Select2 para todos los select con clase select2
    });
    </script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <?php include_once '../../backend/registros/reg_servicios.php' ?>
</body>
</html>
