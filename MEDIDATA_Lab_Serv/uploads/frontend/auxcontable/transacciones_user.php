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
include_once '../auxcontable/menu.php';
// incuir el archivo menu principal
?>

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
include_once '../auxcontable/perfil.php';
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

<button class="button" onclick="cambiarColor(this, 'catalogo_user.php')">Catálogo de Cuentas</button>
<button class="button" onclick="cambiarColor(this, 'diariogeneral_user.php')">Diario General</button>
<button class="button" onclick="cambiarColor(this, 'partida_manual_user.php')">Partida Manual</button>
<button class="button" onclick="cambiarColor(this, 'transacciones_user.php')">Transacciones Capturadas</button>

<!-- Módulo "Transacciones Diarias" -->
<div class="container">
    <h2>Transacciones Diarias</h2>

        <!-- Botones de Categorías -->
    <div class="text-center mt-4">
        <button class="btn btn-option" onclick="showTable('general')">General</button>
        <button class="btn btn-option" onclick="showTable('desembolsos')">Desembolsos</button>
        <button class="btn btn-option" onclick="showTable('recibidos')">Recibidos</button>
        <button class="btn btn-option" onclick="showTable('ventas')">Ventas</button>
        <button class="btn btn-option" onclick="showTable('compras')">Compras</button>
        <button class="btn btn-option" onclick="showTable('inventario')">Inventario</button>
        <button class="btn btn-option" onclick="showTable('todos')">Todos</button>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="search-container">
        <div>
            <label for="fechaDesde">Desde:</label>
            <input type="date" id="fechaDesde" class="form-control">
        </div>
        <div>
            <label for="fechaHasta">Hasta:</label>
            <input type="date" id="fechaHasta" class="form-control">
        </div>
        <button class="btn btn-option" onclick="searchByDate()">Buscar</button>
    </div>



    <!-- Contenedor de la Tabla -->
    <div id="tableContainer" class="mt-4">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>DNI</th>
                    <th>Cuenta</th>
                    <th>Débito</th>
                    <th>Crédito</th>
                    <th>Proyecto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="text-center">Realice una busqueda avanzada desde, hasta o filtre por transacción.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    let currentOption = 'todos';

    function showTable(option) {
        currentOption = option;
        const fechaDesde = document.getElementById('fechaDesde').value;
        const fechaHasta = document.getElementById('fechaHasta').value;

        fetch(`get_transactions.php?option=${option}&fechaDesde=${fechaDesde}&fechaHasta=${fechaHasta}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error(data.message);
                    alert("Error al obtener datos: " + data.message);
                    return;
                }

                let tableHtml = `
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>DNI</th>
                                <th>Cuenta</th>
                                <th>Débito</th>
                                <th>Crédito</th>
                                <th>Proyecto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                if (data.data.length > 0) {
                    data.data.forEach(item => {
                        tableHtml += `
                            <tr>
                                <td>${item.fecha || '-'}</td>
                                <td>${item.dni || '-'}</td>
                                <td>${item.cuenta || '-'}</td>
                                <td>${item.debito || '-'}</td>
                                <td>${item.credito || '-'}</td>
                                <td>${item.proyecto || '-'}</td>
                                <td>
                                    <button class="btn btn-info" onclick="generateReport('${item.id || 'N/A'}')">Generar Reporte</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tableHtml += `
                        <tr>
                            <td colspan="7" class="text-center">Realice una busqueda avanzada desde, hasta o filtre por transacción.</td>
                        </tr>
                    `;
                }

                tableHtml += `
                        </tbody>
                    </table>
                `;

                document.getElementById('tableContainer').innerHTML = tableHtml;
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Error al comunicarse con el servidor.");
            });
    }

    function searchByDate() {
        showTable(currentOption);
    }

    function generateReport(id) {
        alert(`Generando reporte para ID: ${id}`);
    }
</script>

<style>

.container {
    margin: 20px auto;
    max-width: 1500px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

h2 {
    text-align: center;
    color: #06adbf;
    margin-bottom: 20px;
}

.search-container {
    display: flex;
    justify-content: flex-start; /* Alinea el contenido hacia la izquierda */
    align-items: center;
    gap: 10px; /* Espaciado entre los elementos */
    margin-left: 25px; /* Mueve el contenedor un poco a la izquierda */
}

.search-container label {
    font-size: 14px;
    margin-right: 5px;
    font-weight: bold;
    color: #035c67;
}

.search-container .form-control {
    width: 150px;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
}

.search-container button.btn-option {
    padding: 8px 15px;
    border-radius: 5px;
    background-color: #035c67;
    color: white;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s;
}

.search-container button.btn-option:hover {
    background-color: #06adbf;
}

.search-container button {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    background-color: #035c67;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s;
}

.search-container button:hover {
    background-color: #06adbf;
}

.btn-option {
    margin: 5px;
    padding: 10px 20px;
    border-radius: 5px;
    border: none;
    background-color: #035c67;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-option:hover {
    background-color: #06adbf;
}

#tableContainer {
    margin-top: 20px;
    display: flex;
    justify-content: center;
}

.table {
    width: 100%;
    max-width: 1500px;
    border-collapse: collapse;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.table th {
    background-color: #035c67;
    color: white;
    padding: 12px;
    text-align: left;
}

.table td {
    padding: 12px;
    border: 1px solid #ddd;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: #f9f9f9;
}

.table-striped tbody tr:hover {
    background-color: #eaf7fa;
}
</style>



        </main>
        <!-- MAIN -->
    </section>
    
    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

</body>
</html>