<?php
include_once '../../backend/registros/session_check.php';
$__orthanc_lab = require __DIR__ . '/../../backend/bd/orthanc_laboratorio.config.php';
// Definir la variable rol_usuario desde la sesión
$rol_usuario = $_SESSION['rol'] ?? '';

// Verificar el rol del usuario
if ($rol_usuario === 'Radiologo') {
    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../backend/css/admin.css">
        <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
        <title>MEDIDATA</title>
        <style>
            .access-denied-container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 60vh;
                padding: 40px 20px;
                background: var(--grey);
            }
            
            .access-denied-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                padding: 50px 40px;
                text-align: center;
                max-width: 600px;
                width: 100%;
                position: relative;
                overflow: hidden;
                border: 1px solid #e9ecef;
            }
            
            .access-denied-card::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 6px;
                background: linear-gradient(90deg, #dc3545, #fd7e14, #ffc107);
                border-radius: 20px 20px 0 0;
            }
            
            .access-denied-title {
                color: #dc3545;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 20px;
                text-shadow: 0 2px 4px rgba(220, 53, 69, 0.1);
            }
            
            .access-denied-subtitle {
                color: #495057;
                font-size: 1.3rem;
                font-weight: 600;
                margin-bottom: 30px;
                line-height: 1.4;
            }
            
            .access-denied-description {
                color: #6c757d;
                font-size: 1.1rem;
                line-height: 1.6;
                margin-bottom: 40px;
                padding: 0 20px;
            }
            
            .access-denied-info {
                background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
                border: 2px solid #fed7d7;
                border-radius: 15px;
                padding: 25px;
                margin-top: 30px;
            }
            
            .access-denied-info h3 {
                color: #c53030;
                font-size: 1.2rem;
                font-weight: 600;
                margin-bottom: 15px;
            }
            
            .access-denied-info p {
                color: #744210;
                font-size: 1rem;
                line-height: 1.5;
                margin: 0;
            }
            
            .access-denied-decoration {
                position: absolute;
                width: 100px;
                height: 100px;
                border: 3px solid #dc3545;
                border-radius: 50%;
                opacity: 0.1;
            }
            
            .access-denied-decoration.top-left {
                top: -50px;
                left: -50px;
            }
            
            .access-denied-decoration.bottom-right {
                bottom: -50px;
                right: -50px;
                border-color: #fd7e14;
            }
            
            .access-denied-decoration.middle {
                top: 50%;
                right: -30px;
                transform: translateY(-50%);
                border-color: #ffc107;
                width: 60px;
                height: 60px;
            }
            
            @media (max-width: 768px) {
                .access-denied-card {
                    padding: 30px 20px;
                    margin: 20px;
                }
                
                .access-denied-title {
                    font-size: 2rem;
                }
                
                .access-denied-subtitle {
                    font-size: 1.1rem;
                }
                
                .access-denied-description {
                    font-size: 1rem;
                    padding: 0 10px;
                }
            }
            
            .access-denied-button {
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 50px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
                margin-top: 20px;
            }
            
            .access-denied-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            }
            
            .access-denied-button:active {
                transform: translateY(0);
            }
        </style>
    </head>
    <body>
    ';
    
    include_once '../radiologiaeimagen/menu.php';
    
    echo '
    <section id="content">
        <nav>
            <i class="bx bx-menu toggle-sidebar"></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>';
            
    include_once '../radiologiaeimagen/perfil.php';
    
    echo '
        </nav>
        <main>
            <div class="access-denied-container">
                <div class="access-denied-card">
                    <div class="access-denied-decoration top-left"></div>
                    <div class="access-denied-decoration bottom-right"></div>
                    <div class="access-denied-decoration middle"></div>
                    
                    <h1 class="access-denied-title">Acceso Denegado</h1>
                    <h2 class="access-denied-subtitle">Acceso Restringido para Médicos Radiólogos</h2>
                    <p class="access-denied-description">
                        Lo sentimos, pero esta sección del sistema está reservada exclusivamente para técnicos radiólogos y transcriptores.
                    </p>
                    
                    <button class="access-denied-button" onclick="window.location.href=\'lista_estudios_medico.php\'">
                        Ir a Lista de Estudios
                    </button>
                </div>
            </div>
        </main>
    </section>
    
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
    </body>
    </html>';
    exit();
}

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
include_once '../radiologiaeimagen/menu.php';
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
include_once '../radiologiaeimagen/perfil.php';
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

<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/worklist_tecnico.php')">Tecnico Radiólogo</button>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/lista_estudios_medico.php')">Médico Radiólogo</button>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/lista_transcripciones_user.php')">Transcriptores</button>

<!-- Modal de carga -->
<div id="loading-modal">
    <div class="spinner"></div>
    <p style="color: white; font-size: 18px; margin-top: 10px;">Cargando MH-PACS...</p>
</div>

<script>
    // Función para mostrar el modal de carga
function showLoadingModal() {
    const loadingModal = document.getElementById('loading-modal');
    loadingModal.style.display = 'flex'; // Mostrar el modal
}
// Función para ocultar el modal de carga
function hideLoadingModal() {
    const loadingModal = document.getElementById('loading-modal');
    loadingModal.style.display = 'none'; // Ocultar el modal
}
</script>

<style>
/* Diseño del modal de carga */
#loading-modal {
    display: none; /* Oculto por defecto */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Fondo semi-transparente */
    justify-content: center; /* Centrar horizontalmente */
    align-items: center; /* Centrar verticalmente */
    z-index: 1000;
    flex-direction: column; /* Alinear elementos en columna */
}

/* Spinner circular */
.spinner {
    border: 8px solid #035c67; /* Borde del spinner */
    border-top: 8px solid #efc25b; /* Color principal */
    border-radius: 50%;
    width: 60px;
    height: 60px;
    animation: spin 1s linear infinite; /* Animación de rotación */
}

/* Texto debajo del spinner */
#loading-modal p {
    margin-top: 10px; /* Espacio entre el spinner y el texto */
    color: white; /* Color del texto */
    font-size: 18px; /* Tamaño del texto */
    text-align: center; /* Asegurar que el texto esté centrado */
}

/* Animación del spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<h2 class="table-title">MH-PACS</h2>
<p id="total-studies">Cargando Estudios...</p>

<!-- Mensaje de acceso denegado -->
<div id="noAccessMsg" style="display: none; text-align: center; padding: 20px;">
    <h2 style="color: #ff0000;">Acceso Denegado</h2>
    <p style="font-size: 18px; margin-top: 10px;">No tienes permisos para ver este apartado. Solo los técnicos radiólogos y transcriptores pueden acceder.</p>
</div>

<!-- Contenedor de la tabla -->
<div id="tableContainer">
    <!-- Barra de Búsqueda -->
    <input type="text" id="search-bar" placeholder="Buscar..." />

    <!-- Tabla de Estudios -->
    <table id="studies-table">
        <thead>
            <tr>
                <th>Paciente</th>
                <th>Sexo</th>
                <th>DNI</th>
                <th>Fecha</th>
                <th>Modalidad</th>
                <th>Descripción</th>
                <th>Hospital</th>
                <th>Médico Remitente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="studies-tbody">
            <!-- Estudios se cargan dinámicamente aquí -->
        </tbody>
    </table>

    <!-- Paginación -->
<div class="pagination" id="pagination"></div>

<!-- Modal de Visor DICOM -->
<div id="viewerModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Visor DICOM</h2>
        <div class="viewer-container">
            <iframe id="orthancViewer" src="" frameborder="0" allowfullscreen="true" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
        </div>
    </div>
</div>
</div>

<script>
    // Verificación de rol al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const rolUsuario = '<?php echo isset($_SESSION['rol']) ? $_SESSION['rol'] : ''; ?>';
        
        if (rolUsuario === 'Radiologo') {
            // Ocultar la tabla y componentes relacionados
            document.getElementById('tableContainer').style.display = 'none';
            document.getElementById('total-studies').style.display = 'none';
            // Mostrar mensaje de acceso denegado
            document.getElementById('noAccessMsg').style.display = 'block';
            return; // No cargar nada más
        }
        
        // Si no es radiólogo, iniciar la carga normal
        syncWithOrthanc();
        fetchTotalStudies();
        fetchStudies();
    });

    // Función para obtener el número total de estudios
    async function fetchTotalStudies() {
        try {
            const response = await fetch('get_total_studies.php');
            const data = await response.json();

            if (data.error) {
                console.error('Error fetching total studies:', data.error);
                document.getElementById('total-studies').textContent = 'Error al cargar el total de estudios';
            } else {
                document.getElementById('total-studies').textContent = `Total Estudios: ${data.total.toLocaleString()}`;
            }
        } catch (error) {
            console.error('Error fetching total studies:', error);
            document.getElementById('total-studies').textContent = 'Error al cargar el total de estudios';
        }
    }

    // Función para sincronizar con Orthanc
    async function syncWithOrthanc() {
        try {
            const response = await fetch('sync_orthanc.php');
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error syncing with Orthanc:', error);
        }
    }

    // Variables globales
    let currentPage = 1;
    const studiesPerPage = 10;

    // Función para obtener los estudios desde el backend
    async function fetchStudies() {
        showLoadingModal();
        const searchQuery = document.getElementById('search-bar')?.value?.trim() || '';

        try {
            const response = await fetch(`get_studies.php?page=${currentPage}&limit=${studiesPerPage}&search=${encodeURIComponent(searchQuery)}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error desconocido al cargar estudios');
            }

            showPaginatedStudies(data.studies || [], data.total || 0);

        } catch (error) {
            console.error('Error fetching studies:', error);
            swal("Error", "No se pudieron cargar los estudios: " + error.message, "error");
            document.querySelector('#studies-tbody').innerHTML = '<tr><td colspan="9">Error de conexión al cargar estudios.</td></tr>';
            updatePagination(0);
        } finally {
            hideLoadingModal();
        }
    }

    // Función para formatear la fecha de YYYYMMDD a DD/MM/YYYY
    function formatDate(dateString) {
        if (!dateString || dateString.length !== 8) return 'Fecha no disponible';
        const year = dateString.substring(0, 4);
        const month = dateString.substring(4, 6);
        const day = dateString.substring(6, 8);
        return `${day}/${month}/${year}`;
    }

    // Función para mostrar estudios paginados
    function showPaginatedStudies(studies, totalStudies) {
        const tbody = document.querySelector('#studies-tbody');
        tbody.innerHTML = '';

        if (!studies || studies.length === 0) {
            const query = document.getElementById('search-bar')?.value?.trim() || '';
            tbody.innerHTML = `<tr><td colspan="9">${query ? `No se encontraron estudios para "${query}".` : 'No hay estudios disponibles.'}</td></tr>`;
        } else {
            studies.forEach(study => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${study.PatientName || 'N/A'}</td>
                    <td>${study.PatientSex || 'N/A'}</td>
                    <td>${study.PatientID || 'N/A'}</td>
                    <td>${formatDate(study.StudyDate)}</td>
                    <td>${study.modality || 'N/A'}</td>
                    <td>${study.StudyDescription || 'N/A'}</td>
                    <td>${study.InstitutionName || 'N/A'}</td>
                    <td>${study.ReferringPhysicianName || 'N/A'}</td>
                    <td class="actions">
                        <div class="action-buttons">
                            <button onclick="viewSeries('${study.FirstSeriesId}')" ${!study.FirstSeriesId ? 'disabled' : ''}>Ver</button>
                            <button onclick="downloadStudy('${study.ID}')" ${!study.ID || study.ID === 'N/A' ? 'disabled' : ''}>Descargar</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        updatePagination(totalStudies);
    }

    // Función para actualizar la paginación
    function updatePagination(totalStudies) {
        const paginationDiv = document.getElementById('pagination');
        if (!paginationDiv) return;
        paginationDiv.innerHTML = '';

        const totalPages = Math.ceil(totalStudies / studiesPerPage);
        if (totalPages <= 0) return;

        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        // Botón "Anterior"
        if (currentPage > 1) {
            const prevButton = document.createElement('button');
            prevButton.textContent = 'Anterior';
            prevButton.onclick = () => {
                currentPage--;
                fetchStudies();
            };
            paginationDiv.appendChild(prevButton);
        }

        // Primera página y elipsis
        if (startPage > 1) {
            const firstPageButton = document.createElement('button');
            firstPageButton.textContent = '1';
            firstPageButton.onclick = () => {
                currentPage = 1;
                fetchStudies();
            };
            paginationDiv.appendChild(firstPageButton);

            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'ellipsis';
                paginationDiv.appendChild(ellipsis);
            }
        }

        // Páginas numeradas
        for (let i = startPage; i <= endPage; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.classList.toggle('active', i === currentPage);
            button.onclick = () => {
                currentPage = i;
                fetchStudies();
            };
            paginationDiv.appendChild(button);
        }

        // Última página y elipsis
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'ellipsis';
                paginationDiv.appendChild(ellipsis);
            }

            const lastPageButton = document.createElement('button');
            lastPageButton.textContent = totalPages;
            lastPageButton.onclick = () => {
                currentPage = totalPages;
                fetchStudies();
            };
            paginationDiv.appendChild(lastPageButton);
        }

        // Botón "Siguiente"
        if (currentPage < totalPages) {
            const nextButton = document.createElement('button');
            nextButton.textContent = 'Siguiente';
            nextButton.onclick = () => {
                currentPage++;
                fetchStudies();
            };
            paginationDiv.appendChild(nextButton);
        }
    }

    // Manejar cambios en la barra de búsqueda
    document.getElementById('search-bar')?.addEventListener('input', function() {
        currentPage = 1;
        fetchStudies();
    });

    // Función para ver una serie
    function viewSeries(seriesId) {
        if (!seriesId) {
            swal("Información", "No hay serie disponible para ver.", "info");
            return;
        }
        
        // URL directa - abre en nueva ventana como la descarga
        const viewerUrl = <?php echo json_encode($__orthanc_lab['viewer_series_prefix']); ?> + seriesId;
        
        // Abrir en nueva ventana
        window.open(viewerUrl, '_blank');
    }

// Cerrar modales
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal al hacer clic en X
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.onclick = function() {
            this.closest('.modal').style.display = 'none';
        }
    });

    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
});

// Función para descargar un estudio
    function downloadStudy(studyId) {
        if (!studyId || studyId === 'N/A') {
            swal("Información", "No hay ID de estudio válido para descargar.", "info");
            return;
        }
        
        // URL directa con credenciales en la URL
        const downloadUrl = <?php echo json_encode($__orthanc_lab['study_archive_prefix']); ?> + studyId + '/archive';
        
        // Abrir en nueva ventana
        window.open(downloadUrl, '_blank');
    }
</script>

<style>
       .table-title {
       margin-top: 5px;
   }

    /* Barra de búsqueda */
    #search-bar {
        width: 100%;
        padding: 10px;
        margin-bottom: 20px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    /* Tabla */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    thead {
        background-color: #06adbf;
        color: white;
    }

    th, td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
    }

    th {
        font-weight: bold;
    }

    tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tbody tr:hover {
        background-color: #f1f1f1;
    }

    /* Acciones */
    .actions button {
        margin-right: 5px;
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

/* Contenedor de botones */
.action-buttons {
    display: flex;
    gap: 5px; /* Espacio entre los botones */
}

/* Estilo de los botones */
.action-buttons button {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

/* Colores específicos para cada botón */
.action-buttons button:first-child {
    background-color: #06adbf;
    color: white;
}

.action-buttons button:nth-child(2) {
    background-color: #efc25b;
    color: white;
}

.action-buttons button:last-child {
    background-color: #4caf50; /* Nuevo color para "Cargar Adjunto" */
    color: white;
}

/* Ancho mínimo para la columna Acciones */
th:nth-child(9), td:nth-child(9) {
    min-width: 100px; /* Ajusta según sea necesario */
}

/* Diseño responsivo para pantallas pequeñas */
@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column; /* Botones uno debajo del otro */
        align-items: flex-end; /* Alinear botones a la derecha */
    }

    .action-buttons button {
        margin-bottom: 5px; /* Espacio vertical entre botones en modo móvil */
    }
}

.pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }
    .pagination button {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        background-color: #f0f0f0;
        color: #035c67;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: background-color 0.3s, color 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .pagination button.active {
        background-color: #06adbf;
        color: white;
    }
    .pagination button:disabled {
        background-color: #e0e0e0;
        color: #aaa;
        cursor: not-allowed;
    }
    .pagination button:hover:not(:disabled):not(.active) {
        background-color: #d0f0f7;
        color: #035c67;
    }
    .pagination .arrow {
        font-size: 18px;
        font-weight: bold;
        display: flex;
        align-items: center;
    }

    /* Estilos para el modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 2% auto;
        padding: 20px;
        width: 95%;
        max-width: 1200px;
        border-radius: 8px;
        max-height: 95vh;
        overflow-y: auto;
    }

    .viewer-container {
        width: 100%;
        height: 600px;
        background: #000;
        border-radius: 4px;
        position: relative;
        overflow: hidden;
    }

    .viewer-container iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }
    
    /* Modal específico para el visor DICOM */
    #viewerModal .modal-content {
        width: 95%;
        max-width: 1200px;
        height: 90vh;
        max-height: 800px;
        padding: 10px;
    }
    
    #viewerModal .modal-content h2 {
        margin: 0 0 10px 0;
        padding: 10px;
        background: #06adbf;
        color: white;
        border-radius: 4px;
    }

    /* Diseño responsivo */
    @media screen and (max-width: 768px) {
        table {
            font-size: 14px;
        }

        tbody tr {
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            border-radius: 4px;
            overflow: hidden;
        }

        td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border: none;
            border-bottom: 1px solid #ddd;
        }

        td:last-child {
            border: none;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 5px;
        }
    }
</style>

        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- Alertas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

</body>
</html>