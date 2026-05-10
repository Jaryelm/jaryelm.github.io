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
    <form id="radiologia-imagen-form" action="procesar_radiologia.php" method="POST" class="radiologia-form">
        <h2>Estudios de Radiología e Imagen</h2>

        <!-- Nombre y Cuenta Hosp -->
        <div class="form-group">
            <label for="dni_estudios">DNI:</label>
            <input id="dni_estudios" name="dni_estudios" type="text" placeholder="Ingrese el DNI">
        </div>
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input id="nombre" name="nombre" type="text" placeholder="Ingrese el nombre completo">
        </div>
        <div class="form-group">
            <label for="cuenta-hosp">Médico Tratante:</label>
            <input id="cuenta-hosp" name="cuenta_hosp" type="text" placeholder="Ingrese la cuenta hospitalaria">
        </div>
        <!-- Observaciones -->
        <div class="form-group">
            <label for="observaciones">Orden Médica:</label>
            <textarea id="observaciones" name="observaciones" rows="4" placeholder="Ingrese observaciones"></textarea>
        </div>
        <!-- Servicio -->
        <div class="form-group">
            <label for="medico_requerido">Medico Requerido:</label>
            <input id="medico_requerido" name="medico_requerido" type="text" placeholder="Ingrese el servicio">
        </div>

        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="button" class="btn-action" onclick="agregarCodigo()">Agregar</button>
            <button type="submit" class="btn-action">Finalizar</button>
            <button type="reset" class="btn-action cancel">Cancelar</button>
        </div>

        <!-- Tabla de códigos -->
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Detalle</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody id="codigo-table-body">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>
    </form>
</div>

<style>
    .radiologia-form {
        max-width: 1500px;
        margin: 20px auto;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    }

    .radiologia-form h2 {
    color: #035c67;
    text-align: center;
    font-size: 1.5rem;
    margin-bottom: 20px;
    font-weight: bold;
}

.form-group {
    margin-bottom: 20px; /* Espaciado entre grupos */
}

.form-group label {
    display: block; /* Mantiene el título arriba del input */
    margin-bottom: 8px; /* Espacio reducido entre título e input */
    font-size: 1rem; /* Tamaño uniforme del título */
    color: #035c67; /* Color destacado del título */
    font-weight: bold; /* Resaltar el título */
    border-bottom: 2px solid #ddd; /* Línea subrayada */
    padding-bottom: 5px; /* Espacio con el subrayado */
}

.form-group input,
.form-group textarea {
    width: 100%; /* Asegura que ocupe todo el ancho disponible */
    max-width: none; /* Elimina la restricción de ancho máximo */
    padding: 10px; /* Espaciado interno para comodidad */
    font-size: 1rem; /* Tamaño de fuente uniforme */
    border: 1px solid #ddd; /* Bordes suaves */
    border-radius: 8px; /* Bordes redondeados */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Sombra interna ligera */
    transition: all 0.3s ease-in-out; /* Transición suave en foco */
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #06adbf; /* Cambiar el borde al hacer foco */
    box-shadow: 0 0 8px rgba(6, 173, 191, 0.4); /* Sombra azul suave */
    outline: none; /* Eliminar el borde predeterminado */
}

.form-group textarea {
    resize: vertical; /* Permitir redimensionar solo verticalmente */
    height: 80px; /* Tamaño inicial consistente */
}

    .form-actions {
        display: flex;
        justify-content: space-evenly;
        margin-top: 20px;
    }

    .btn-action {
        background-color: #035c67;
        color: #fff;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-transform: uppercase;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn-action:hover {
        background-color: #06adbf;
        transform: scale(1.05);
    }

    .btn-action.cancel {
        background-color: #f44336;
    }

    .btn-action.cancel:hover {
        background-color: #d32f2f;
    }

    .table-container {
        margin-top: 20px;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }

    .custom-table thead {
        background-color: #035c67;
        color: #fff;
    }

    .custom-table th,
    .custom-table td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
    }

    .custom-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .custom-table tbody tr:hover {
        background-color: #f1f1f1;
    }
</style>

<script>
    function agregarCodigo() {
        const tableBody = document.getElementById('codigo-table-body');
        const newRow = `
            <tr>
                <td><input type="text" name="codigo[]" placeholder="Ingrese el código"></td>
                <td><input type="text" name="detalle[]" placeholder="Ingrese el detalle"></td>
                <td><input type="text" name="observacion[]" placeholder="Ingrese la observación"></td>
            </tr>
        `;
        tableBody.insertAdjacentHTML('beforeend', newRow);
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


