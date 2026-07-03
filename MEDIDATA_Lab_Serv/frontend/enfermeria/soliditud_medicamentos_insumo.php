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

<br>
            
<form id="solicitud-medicamentos-form" action="procesar_medicamentos.php" method="POST" class="medicamentos-form">
    <h2>Solicitud de Medicamentos e Insumos</h2>

    <!-- Nombre y Cuenta Hospitalaria -->
    <div class="form-row">
        <div class="form-group">
            <label for="dni_mi">DNI:</label>
            <input id="dni_mi" name="dni_mi" type="text" placeholder="Ingrese el nombre del paciente" required>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input id="nombre" name="nombre" type="text" placeholder="Ingrese el nombre del paciente" required>
        </div>
        <div class="form-group">
            <label for="medico_cabecera">Médico de Cabecera:</label>
            <input id="medico_cabecera" name="medico_cabecera" type="text" placeholder="Ingrese Médico de Cabecera" required>
        </div>
    </div>

    <!-- Producto, Existencias y Cantidad -->
    <div class="form-row">
        <div class="form-group">
            <label for="producto">Producto:</label>
            <input id="producto" name="producto" type="text" placeholder="Ingrese el producto" required>
        </div>
        <div class="form-group">
            <label for="existencias">Existencias:</label>
            <input id="existencias" name="existencias" type="number" min="0" placeholder="Existencias disponibles" readonly>
        </div>
        <div class="form-group">
            <label for="cantidad">Cantidad:</label>
            <input id="cantidad" name="cantidad" type="number" min="1" placeholder="Ingrese la cantidad" required>
        </div>
    </div>

    <!-- Bodega, Motivo y Fecha -->
    <div class="form-row">
        <div class="form-group">
            <label for="bodega">Bodega:</label>
            <select id="bodega" name="bodega" required>
    <option value="">Seleccione una bodega</option>
    <option value="bodega_general">BODEGA GENERAL</option>
    <option value="almacen_hospitalario">ALMACEN HOSPITALARIO</option>
    <option value="ambulancia_1">AMBULANCIA 1</option>
    <option value="ambulancia_2">AMBULANCIA 2</option>
    <option value="quirofano">QUIROFANO</option>
    <option value="sala_de_cuna">SALA DE CUNA</option>
    <option value="sala_de_partos">SALA DE PARTOS</option>
    <option value="carro_rojo_emergencia">CARRO ROJO EMERGENCIA</option>
    <option value="sala_de_procedimientos">SALA DE PROCEDIMIENTOS</option>
    <option value="almacen_enfermeria_hospital">ALMECEN ENFERMERIA HOSPITAL</option>
    <option value="tecnologia_it">TECNOLOGIA TI</option>
    <option value="clinica_ginecologia">CLINICA GINECOLOGÍA</option>
    <option value="usg_medicacasa">USG MEDICASA</option>
    <option value="cardiologia_medicacasa">CARDIOLOGIA MEDICASA</option>
    <option value="unidad_digestiva">UNIDAD DIGESTIVA</option>
    <option value="eeg">EEG</option>
    <option value="cubiculo_ginecologia">CUBÍCULO GINECOLOGÍA</option>
    <option value="almacen_enfermeria_emergencia">ALMECEN ENFERMERIA EMERGENCIA</option>
    <option value="podiatria">PODIATRIA</option>
    <option value="radiologia">RADIOLOGÍA</option>
    <option value="mercadeo">MERCADEO</option>
    <option value="recepcion">RECEPCIÓN</option>
    <option value="facturacion">FACTURACION</option>
    <option value="tomografia">TOMOGRAFIA</option>
    <option value="seguro">SEGURSO</option>
    <option value="caja">CAJA</option>
    <option value="mamografia">MAMOGRAFIA</option>
    <option value="odontologia">ODONTOLOGIA</option>
</select>
    </div>
    <div class="form-group">
            <label for="motivo">Motivo:</label>
            <input id="motivo" name="motivo" type="text" placeholder="Ingrese el motivo" required>
        </div>
        <div class="form-group">
            <label for="fecha">Fecha:</label>
            <input id="fecha" name="fecha" type="date" required>
        </div>
    </div>

    <!-- Observaciones -->
    <div class="form-group">
        <label for="observaciones">Observaciones:</label>
        <textarea id="observaciones" name="observaciones" rows="4" placeholder="Ingrese observaciones adicionales"></textarea>
    </div>

    <!-- Botones de Acción -->
    <div class="form-actions">
        <button type="button" class="btn-add" onclick="agregarProducto()">Agregar</button>
        <button type="submit" class="btn-submit">Finalizar</button>
        <button type="reset" class="btn-cancel">Cancelar</button>
    </div>

    <!-- Tabla de Productos Agregados -->
    <table class="custom-table" id="productos-agregados">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Filas dinámicas de productos -->
        </tbody>
    </table>
</form>

<style>
.medicamentos-form {
    max-width: 1500px; /* Ancho máximo del formulario */
    margin: 20px auto; /* Centrar el formulario */
    background-color: #f9f9f9; /* Fondo */
    padding: 20px;
    border-radius: 8px; /* Bordes redondeados */
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Sombra suave */
}

.medicamentos-form h2 {
    color: #035c67; /* Color del título */
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.5rem; /* Tamaño del título */
    font-weight: bold;
}

.form-row {
    display: flex; /* Mostrar elementos en línea */
    gap: 20px; /* Espaciado entre columnas */
    margin-bottom: 8px; /* Espaciado inferior */
}

.form-group {
    flex: 1; /* Asegurar que las columnas se ajusten */
}

.form-group label {
    display: block; /* Etiqueta arriba del input */
    font-size: 1rem; /* Tamaño de texto */
    color: #035c67; /* Color del texto */
    font-weight: bold; /* Resaltar título */
    margin-bottom: 8px; /* Espaciado entre etiqueta e input */
    border-bottom: 2px solid #ddd; /* Línea subrayada */
    padding-bottom: 5px; /* Espacio con el subrayado */
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%; /* Asegura que ocupe todo el ancho disponible */
    max-width: none; /* Sin límite de ancho */
    padding: 10px; /* Espaciado interno */
    border: 1px solid #ddd; /* Bordes claros */
    border-radius: 8px; /* Bordes redondeados */
    font-size: 1rem; /* Tamaño de texto uniforme */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Sombra interna */
    transition: all 0.3s ease-in-out; /* Animación suave */
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #06adbf; /* Cambiar el borde al enfocar */
    box-shadow: 0 0 8px rgba(6, 173, 191, 0.4); /* Sombra externa */
    outline: none; /* Quitar borde predeterminado */
}

textarea {
    resize: vertical; /* Permitir solo redimensionamiento vertical */
    height: 80px; /* Altura inicial consistente */
}

.form-actions {
    display: flex; /* Botones en línea */
    justify-content: space-evenly; /* Espaciado uniforme */
    margin-top: 20px; /* Espaciado superior */
}

.btn-add,
.btn-submit,
.btn-cancel {
    background-color: #035c67; /* Fondo del botón */
    color: #fff; /* Texto blanco */
    padding: 10px 20px; /* Espaciado interno */
    border: none;
    border-radius: 5px; /* Bordes redondeados */
    cursor: pointer;
    text-transform: uppercase; /* Texto en mayúsculas */
    transition: background-color 0.3s ease, transform 0.2s ease; /* Animaciones */
}

.btn-add:hover,
.btn-submit:hover,
.btn-cancel:hover {
    background-color: #06adbf; /* Cambiar fondo al pasar el cursor */
    transform: scale(1.05); /* Escala ligera */
}

.btn-cancel {
    background-color: #f44336; /* Botón de cancelar en rojo */
}

.btn-cancel:hover {
    background-color: #d32f2f; /* Más oscuro al pasar el cursor */
}

.custom-table {
    width: 100%; /* Asegura que la tabla ocupe todo el ancho */
    border-collapse: collapse; /* Elimina espacio entre celdas */
    margin-top: 20px;
    background-color: #fff; /* Fondo blanco */
    border-radius: 5px; /* Bordes redondeados */
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Sombra suave */
}

.custom-table thead {
    background-color: #035c67; /* Fondo del encabezado */
    color: #fff; /* Texto blanco */
}

.custom-table th,
.custom-table td {
    padding: 12px 15px; /* Espaciado interno */
    border: 1px solid #ddd; /* Bordes claros */
    text-align: center; /* Centrar texto */
}

.custom-table tbody tr:nth-child(even) {
    background-color: #f9f9f9; /* Fondo alternado */
}

.custom-table tbody tr:hover {
    background-color: #f1f1f1; /* Fondo al pasar el cursor */
}

.custom-table .action-btn {
    background-color: #f44336; /* Botón rojo */
    color: #fff; /* Texto blanco */
    padding: 8px 12px;
    border: none;
    border-radius: 5px; /* Bordes redondeados */
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease; /* Animación suave */
}

.custom-table .action-btn:hover {
    background-color: #ff7961; /* Más claro al pasar el cursor */
    transform: scale(1.05); /* Escala ligera */
}
</style>

<script>
function agregarProducto() {
    const producto = document.getElementById("producto").value;
    const cantidad = document.getElementById("cantidad").value;
    const fecha = document.getElementById("fecha").value;

    if (!producto || !cantidad || !fecha) {
        alert("Por favor, complete todos los campos antes de agregar.");
        return;
    }

    const tableBody = document.querySelector("#productos-agregados tbody");
    const newRow = document.createElement("tr");

    newRow.innerHTML = `
        <td>${fecha}</td>
        <td>${producto}</td>
        <td>${cantidad}</td>
        <td><button class="action-btn" onclick="eliminarFila(this)">Eliminar</button></td>
    `;

    tableBody.appendChild(newRow);

    // Limpiar campos
    document.getElementById("producto").value = "";
    document.getElementById("cantidad").value = "";
}

function eliminarFila(button) {
    const row = button.parentElement.parentElement;
    row.remove();
}
</script>

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


