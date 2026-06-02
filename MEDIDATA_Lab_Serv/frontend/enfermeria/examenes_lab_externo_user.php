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
include_once '../enfermeria/menu.php';
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
include_once '../enfermeria/perfil.php';
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
            
<div class="form-container">
    <form id="laboratorio-form" action="procesar_laboratorio.php" method="POST" class="laboratorio-form">
        <h2>Orden Radiología e Imagen Externo</h2>

        <div class="form-group">
            <label for="dni_estudios">DNI:</label>
            <input id="dni_estudios" name="dni_estudios" type="text" placeholder="Ingrese el DNI">
        </div>

        <!-- Nombre y Cuenta Hosp -->
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input id="nombre" name="nombre" type="text" placeholder="Ingrese el nombre completo">
        </div>

        <!-- Servicio y Observaciones -->
        <div class="form-group">
            <label for="servicio">Estudio Solicitado:</label>
            <input id="servicio" name="servicio" type="text" placeholder="Ingrese el servicio">
        </div>
        <!-- Servicio y Observaciones -->
        <div class="form-group">
            <label for="servicio">Proveedor:</label>
            <input id="servicio" name="servicio" type="text" placeholder="Ingrese el servicio">
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones:</label>
            <textarea id="observaciones" name="observaciones" rows="4" placeholder="Ingrese observaciones"></textarea>
        </div>

        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="button" class="btn-action" onclick="agregarFila()">Agregar</button>
            <button type="submit" class="btn-action">Finalizar</button>
            <button type="reset" class="btn-action cancel">Cancelar</button>
        </div>

        <!-- Tabla de exámenes -->
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Cantidad</th>
                        <th>Código</th>
                        <th>Detalle</th>
                        <th>Observación</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="examenes-table-body">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>
    </form>
</div>

<style>
.laboratorio-form {
    max-width: 1500px; /* Máximo ancho del formulario */
    margin: 20px auto; /* Centrar el formulario */
    background-color: #f9f9f9; /* Fondo del formulario */
    padding: 20px;
    border-radius: 8px; /* Bordes redondeados */
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Sombra para destacar */
}

.laboratorio-form h2 {
    color: #035c67; /* Color del título */
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.5rem; /* Tamaño del título */
    font-weight: bold;
}

.form-group {
    margin-bottom: 20px; /* Espaciado entre cada pregunta */
}

.form-group label {
    display: block; /* Mantiene el título arriba del input */
    font-size: 1rem; /* Tamaño uniforme del título */
    color: #035c67; /* Color del título */
    font-weight: bold; /* Resaltar el título */
    margin-bottom: 8px; /* Espacio reducido entre etiqueta e input */
    border-bottom: 2px solid #ddd; /* Línea subrayada */
    padding-bottom: 5px; /* Espaciado entre texto y subrayado */
}

.form-group input,
.form-group textarea {
    width: 100%; /* Extender el campo al ancho total del contenedor */
    max-width: none; /* Quitar límite de ancho */
    padding: 10px; /* Espaciado interno */
    font-size: 1rem; /* Tamaño de fuente uniforme */
    border: 1px solid #ddd; /* Bordes suaves */
    border-radius: 8px; /* Bordes redondeados */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Sombra interna */
    transition: all 0.3s ease-in-out; /* Suavizar transiciones */
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #06adbf; /* Resaltar el borde en azul claro */
    box-shadow: 0 0 8px rgba(6, 173, 191, 0.4); /* Sombra externa al enfocar */
    outline: none; /* Quitar el borde predeterminado */
}

.form-group textarea {
    resize: vertical; /* Permitir redimensionar solo verticalmente */
    height: 80px; /* Altura inicial */
}

.form-actions {
    display: flex;
    justify-content: space-evenly; /* Espaciado uniforme entre botones */
    margin-top: 20px;
}

.btn-action {
    background-color: #035c67; /* Fondo del botón */
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-transform: uppercase; /* Convertir texto a mayúsculas */
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.btn-action:hover {
    background-color: #06adbf; /* Fondo más claro al pasar el cursor */
    transform: scale(1.05); /* Escala ligera */
}

.btn-action.cancel {
    background-color: #f44336; /* Fondo rojo para cancelar */
}

.btn-action.cancel:hover {
    background-color: #d32f2f;
}

.table-container {
    margin-top: 20px;
}

.custom-table {
    width: 100%; /* Asegura que la tabla ocupe todo el ancho disponible */
    border-collapse: collapse; /* Elimina espacios entre celdas */
}

.custom-table thead {
    background-color: #035c67; /* Fondo del encabezado */
    color: #fff; /* Texto blanco para contraste */
}

.custom-table th,
.custom-table td {
    padding: 10px;
    text-align: left; /* Alinear texto a la izquierda */
    border: 1px solid #ddd; /* Bordes claros */
}

.custom-table tbody tr:nth-child(even) {
    background-color: #f9f9f9; /* Fondo para filas pares */
}

.custom-table tbody tr:hover {
    background-color: #f1f1f1; /* Fondo al pasar el cursor */
}
</style>

<script>
    function agregarFila() {
        const tableBody = document.getElementById('examenes-table-body');
        const newRow = `
            <tr>
                <td><input type="number" name="cantidad[]" placeholder="Cantidad" min="1"></td>
                <td><input type="text" name="codigo[]" placeholder="Código"></td>
                <td><input type="text" name="detalle[]" placeholder="Detalle"></td>
                <td><input type="text" name="observacion[]" placeholder="Observación"></td>
                <td><input type="number" name="total[]" placeholder="Total" min="0" step="0.01"></td>
                <td>
                    <button type="button" class="btn-action cancel" onclick="eliminarFila(this)">Eliminar</button>
                </td>
            </tr>
        `;
        tableBody.insertAdjacentHTML('beforeend', newRow);
    }

    function eliminarFila(button) {
        const row = button.closest('tr');
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


