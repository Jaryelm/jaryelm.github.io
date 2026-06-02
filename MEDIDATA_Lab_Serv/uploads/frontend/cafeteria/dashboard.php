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

<style>
.custom-table-container {
    width: 100%;
    overflow-x: auto;
    margin-top: 5px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
}

.custom-table thead {
    background-color: #06adbf;
    color: #fff;
    text-align: left;
}

.custom-table th,
.custom-table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
}

.custom-table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.custom-table tbody tr:hover {
    background-color: #f1f1f1;
}

.custom-table td {
    text-align: center;
}

.custom-table th {
    font-weight: bold;
    text-transform: uppercase;
}

.custom-table .action-btn {
    padding: 8px 12px;
    background-color: #035c67;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.custom-table .action-btn:hover {
    background-color: #06adbf;
}
</style>

<style>
       .table-title {
       margin-top: 5px;
   }
.dashboard-summary {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.summary-card {
    flex: 1;
    background-color: #06adbf;
    color: #fff;
    text-align: center;
    padding: 15px;
    margin: 0 10px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.summary-card h3 {
    font-size: 1.2rem;
    margin-bottom: 10px;
    text-transform: uppercase;
}

.summary-card p {
    font-size: 1.5rem;
    font-weight: bold;
}

.summary-card:nth-child(1) {
    background-color: #035c67;
}

.summary-card:nth-child(2) {
    background-color: #EFC25B;
    color: #222;
}

.summary-card:nth-child(3) {
    background-color: #06adbf; /* Entregadas ahora es azul */
}

.summary-card:nth-child(4) {
    background-color: #f44336; /* Pendientes ahora es rojo */
}

.table-filters {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
}

.table-filters button {
    padding: 8px 15px;
    background-color: #035c67;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    text-transform: uppercase;
}

.table-filters button:hover {
    background-color: #06adbf;
}
</style>
            
<h2 class="table-title">Solicitud de Dietas Hospitalarias</h2>

<div class="dashboard-summary">
    <div class="summary-card" id="total-solicitudes">
        <h3>Total de Solicitudes (24h)</h3>
        <p>0</p>
    </div>
    <div class="summary-card" id="solicitudes-globales">
        <h3>Solicitudes Globales</h3>
        <p>0</p>
    </div>
    <div class="summary-card" id="solicitudes-entregadas">
        <h3>Entregadas</h3>
        <p>0</p>
    </div>
    <div class="summary-card" id="solicitudes-pendientes">
        <h3>Pendientes</h3>
        <p>0</p>
    </div>
</div>

<div class="table-filters" style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
    <div>
        <button onclick="filtrarSolicitudes('entregadas')">Entregadas</button>
        <button onclick="filtrarSolicitudes('pendientes')">Pendientes</button>
        <button onclick="cargarDatosPacientes()">Ver Todo</button>
    </div>
    <input type="text" id="busqueda" placeholder="Buscar..." 
        style="padding: 8px; width: 250px; border: 1px solid #ccc; border-radius: 4px;">
</div>

<script>
    document.getElementById("busqueda").addEventListener("input", function() {
    let filtro = this.value.toLowerCase();
    let filas = document.querySelectorAll(".custom-table tbody tr");

    filas.forEach(fila => {
        let textoFila = fila.textContent.toLowerCase();
        fila.style.display = textoFila.includes(filtro) ? "" : "none";
    });
});
</script>

<div class="custom-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>PACIENTE</th>
                <th>DNI</th>
                <th>NO. HABITACIÓN</th>
                <th>FECHA Y HORA</th>
                <th>TURNO</th>
                <th>TIPO DE DIETA</th>
                <th>PROCESADO POR</th>
                <th>ESTADO</th>
                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody id="tablaPacientesBody">
            <!-- Los datos serán cargados dinámicamente -->
        </tbody>
    </table>
</div>

<style>
    /* Estilo de los botones de paginación */
#pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

#pagination button {
    padding: 8px 15px;
    background-color: #035c67;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin: 0 5px;
    font-size: 1rem;
    text-transform: uppercase;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    transition: background-color 0.3s ease, transform 0.2s ease;
}

#pagination button:hover {
    background-color: #06adbf;
    transform: scale(1.05);
}

#pagination button:disabled {
    background-color: #ccc;
    color: #666;
    cursor: not-allowed;
}

#pagination span {
    font-size: 1rem;
    font-weight: bold;
    margin: 0 10px;
    align-self: center;
    color: #035c67;
}
</style>

<div id="pagination">
    <button id="prevPage" onclick="changePage(-1)" disabled>Anterior</button>
    <span id="currentPage">1</span>
    <button id="nextPage" onclick="changePage(1)">Siguiente</button>
</div>

<script>
let currentPage = 1;
const recordsPerPage = 5;
let solicitudes = [];
let todasLasSolicitudes = [];
let solicitudes24h = [];

function actualizarDashboard() {
    const ahora = new Date();
    const hace24h = new Date(ahora.getTime() - 24 * 60 * 60 * 1000);
    solicitudes24h = todasLasSolicitudes.filter(s => {
        // Asegura formato compatible con Date
        let fechaStr = s.fecha_hora.replace(' ', 'T');
        let fecha = new Date(fechaStr);
        return !isNaN(fecha) && fecha >= hace24h;
    });
    const total24h = solicitudes24h.length;
    const entregadas24h = solicitudes24h.filter(s => (s.estado || '').trim().toLowerCase() === "entregado").length;
    const pendientes24h = solicitudes24h.filter(s => (s.estado || '').trim().toLowerCase() === "pendiente").length;
    const totalGlobal = todasLasSolicitudes.length;

    document.getElementById("total-solicitudes").querySelector("p").textContent = total24h;
    document.getElementById("solicitudes-globales").querySelector("p").textContent = totalGlobal;
    document.getElementById("solicitudes-entregadas").querySelector("p").textContent = entregadas24h;
    document.getElementById("solicitudes-pendientes").querySelector("p").textContent = pendientes24h;
}

function cargarDatosPacientes() {
    $.ajax({
        type: "GET",
        url: "fetch_pacientes_dietas.php",
        dataType: "json",
        success: function (response) {
            if (response.error) {
                Swal.fire('Error', response.error, 'error');
                return;
            }
            todasLasSolicitudes = response.data;
            solicitudes = [...todasLasSolicitudes];
            mostrarPagina();
            actualizarDashboard();
        },
        error: function (xhr) {
            console.error("Error al cargar los datos:", xhr.responseText);
        }
    });
}

function mostrarPagina() {
    const start = (currentPage - 1) * recordsPerPage;
    const end = start + recordsPerPage;
    const registrosPaginados = solicitudes.slice(start, end);

    let content = ''; // Limpia el contenido previo
    registrosPaginados.forEach(item => {
        content += `
            <tr>
                <td>${item.nombre_paciente} ${item.apellido_paciente}</td>
                <td>${item.dni_paciente}</td>
                <td>${item.habitacion ?? 'N/A'}</td>
                <td>${item.fecha_hora}</td>
                <td>${item.turno}</td>
                <td>${item.tipo_dieta}</td>
                <td>${item.procesado_por}</td>
                <td>${item.estado}</td>
                <td>
                    <button class="action-btn" onclick="cambiarEstado(${item.id_dieta})">
                        ${item.estado === "Entregado" ? "Marcar como Pendiente" : "Marcar como Entregado"}
                    </button>
                </td>
            </tr>
        `;
    });

    document.getElementById("tablaPacientesBody").innerHTML = content;
    document.getElementById("prevPage").disabled = currentPage === 1;
    document.getElementById("nextPage").disabled = end >= solicitudes.length;
    document.getElementById("currentPage").textContent = currentPage;
}

function changePage(direction) {
    currentPage += direction;
    mostrarPagina();
}

function cambiarEstado(id) {
    const index = todasLasSolicitudes.findIndex(s => s.id_dieta === id);
    if (index !== -1) {
        const nuevoEstado = todasLasSolicitudes[index].estado === "Entregado" ? "Pendiente" : "Entregado";

        // Actualizar en la base de datos
        $.ajax({
            type: "POST",
            url: "update_estado_dieta.php",
            data: { id_dieta: id, estado: nuevoEstado },
            dataType: "json",
            success: function (response) {
                if (response.error) {
                    Swal.fire('Error', response.error, 'error');
                    return;
                }

                // Actualizar el estado localmente
                todasLasSolicitudes[index].estado = nuevoEstado;
                solicitudes = [...todasLasSolicitudes]; // Refrescar la lista para mantener los datos originales
                mostrarPagina();
                actualizarDashboard();
            },
            error: function (xhr) {
                console.error("Error al actualizar el estado:", xhr.responseText);
            }
        });
    }
}

function filtrarSolicitudes(tipo) {
    if (tipo === "todo") {
        solicitudes = [...todasLasSolicitudes];
    } else {
        solicitudes = todasLasSolicitudes.filter(s => s.estado === (tipo === "entregadas" ? "Entregado" : "Pendiente"));
    }
    currentPage = 1;
    mostrarPagina();
}

$(document).ready(function () {
    cargarDatosPacientes();
});
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


