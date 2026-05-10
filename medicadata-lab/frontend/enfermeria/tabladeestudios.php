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

<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/worklist.php')">Tecnico Radiólogo</button>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/lista_estudios.php')">Médico Radiólogo</button>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/lista_transcripciones.php')">Transcriptores</button>

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

<script>
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

    // Cargar estudios al cargar la página
    document.addEventListener('DOMContentLoaded', async function() {
        await syncWithOrthanc(); // Sincronizar primero
        fetchTotalStudies();
    });
</script>
            
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

<script>
    // Variables globales
    let currentPage = 1; // Página actual
    const studiesPerPage = 10; // Estudios por página
    let allStudies = []; // Almacenar todos los estudios
    let documentStatusCache = {}; // Cache para almacenar el estado de los documentos

// Cargar estudios al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    fetchStudies();
});

// Función para obtener los estudios desde el backend
async function fetchStudies() {
    showLoadingModal();
    
    try {
        const response = await fetch('get_studies.php');
        const data = await response.json();
        allStudies = data;
        
        // Verificar estado de documentos para cada estudio
        await checkDocumentStatuses();
        
        updateTable();
    } catch (error) {
        console.error('Error fetching studies:', error);
        swal("error", "Error", "No se pudieron cargar los estudios");
    } finally {
        hideLoadingModal();
    }
}

// Verificar estado de documentos para todos los estudios
async function checkDocumentStatuses() {
    const checkPromises = allStudies.map(async study => {
        try {
            const response = await fetch(`check_document.php?dni=${study.PatientID}`);
            const result = await response.json();
            documentStatusCache[study.PatientID] = result.exists;
        } catch (error) {
            console.error(`Error checking document for ${study.PatientID}:`, error);
            documentStatusCache[study.PatientID] = false;
        }
    });
    
    await Promise.all(checkPromises);
}

    // Función para actualizar la tabla
    function updateTable() {
        const query = document.getElementById('search-bar')?.value?.trim() || ''; // Obtener término de búsqueda
        const filteredStudies = filterStudies(query); // Filtrar estudios
        showPaginatedStudies(filteredStudies); // Mostrar estudios paginados
    }

    // Función para filtrar estudios según el término de búsqueda
    function filterStudies(query) {
        return allStudies.filter(study => {
            const patientName = study.PatientName || '';
            const description = study.StudyDescription || '';
            const modality = study.Modality || ''; // Añadimos la modalidad

            const lowerCaseQuery = query.toLowerCase();

            return patientName.toLowerCase().includes(lowerCaseQuery) ||
                   description.toLowerCase().includes(lowerCaseQuery) ||
                   modality.toLowerCase().includes(lowerCaseQuery);
        });
    }

// Función para formatear la fecha de YYYYMMDD a DD/MM/YYYY
function formatDate(dateString) {
    if (!dateString || dateString.length !== 8) return 'Fecha no disponible'; // Manejar casos inválidos
    const year = dateString.substring(0, 4);
    const month = dateString.substring(4, 6);
    const day = dateString.substring(6, 8);
    return `${day}/${month}/${year}`;
}

// Función para mostrar estudios paginados
function showPaginatedStudies(filteredStudies) {
    const totalPages = Math.ceil(filteredStudies.length / studiesPerPage);
    const start = (currentPage - 1) * studiesPerPage;
    const end = start + studiesPerPage;
    const studiesToShow = filteredStudies.slice(start, end);

    const tbody = document.querySelector('#studies-tbody');
    tbody.innerHTML = '';

    studiesToShow.forEach(study => {
        const row = document.createElement('tr');
        const hasDocument = documentStatusCache[study.PatientID] || false;

        row.innerHTML = `
            <td>${study.PatientName}</td>
            <td>${study.PatientSex}</td>
            <td>${study.PatientID}</td>
            <td>${formatDate(study.StudyDate)}</td>
            <td>${study.Modality}</td>
            <td>${study.StudyDescription}</td>
            <td>${study.InstitutionName}</td>
            <td>${study.ReferringPhysicianName}</td>
            <td class="actions">
                <div class="action-buttons">
                    <button onclick="viewSeries('${study.FirstSeriesId}')">Ver</button>
                    <button onclick="downloadStudy('${study.ID}')">Descargar</button>
                    <button id="file-button-${study.PatientID}" 
                            onclick="handleFile('${study.PatientID}')"
                            ${hasDocument ? 'class="has-document"' : ''}>
                        ${hasDocument ? 'Descargar Adjunto' : 'Cargar Adjunto'}
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    updatePagination(totalPages);
}

    // Función para actualizar los botones de paginación
    function updatePagination(totalPages) {
        const paginationDiv = document.getElementById('pagination');
        paginationDiv.innerHTML = ''; // Limpiar botones anteriores

        const maxVisiblePages = 5; // Número máximo de botones de página visibles
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        // Botón "Anterior"
        const prevButton = document.createElement('button');
        prevButton.textContent = 'Anterior';
        prevButton.disabled = currentPage === 1;
        prevButton.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                updateTable();
            }
        };
        paginationDiv.appendChild(prevButton);

        // Botón "..." antes del rango
        if (startPage > 1) {
            const ellipsisBefore = document.createElement('button');
            ellipsisBefore.textContent = '...';
            ellipsisBefore.disabled = true;
            paginationDiv.appendChild(ellipsisBefore);
        }

        // Botones numerados
        for (let i = startPage; i <= endPage; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.classList.toggle('active', i === currentPage);
            button.onclick = () => {
                currentPage = i;
                updateTable();
            };
            paginationDiv.appendChild(button);
        }

        // Botón "..." después del rango
        if (endPage < totalPages) {
            const ellipsisAfter = document.createElement('button');
            ellipsisAfter.textContent = '...';
            ellipsisAfter.disabled = true;
            paginationDiv.appendChild(ellipsisAfter);
        }

        // Botón "Siguiente"
        const nextButton = document.createElement('button');
        nextButton.textContent = 'Siguiente';
        nextButton.disabled = currentPage === totalPages;
        nextButton.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                updateTable();
            }
        };
        paginationDiv.appendChild(nextButton);
    }

    // Manejar cambios en la barra de búsqueda
    document.getElementById('search-bar')?.addEventListener('input', function() {
        currentPage = 1; // Reiniciar página al buscar
        updateTable(); // Actualizar tabla
    });

    // Función para ver una serie
    function viewSeries(seriesId) {
    const orthancViewerUrl = `https://dev:Mrecords7@medicloud.medicasa.hn/orthanc/web-viewer/app/viewer.html?series=${seriesId}`;
    window.open(orthancViewerUrl, '_blank'); // Abrir en una nueva pestaña
}

    // Función para descargar un estudio
    function downloadStudy(studyId) {
    const orthancDownloadUrl = `https://dev:Mrecords7@medicloud.medicasa.hn/orthanc/studies/${studyId}/archive`;
    window.location.href = orthancDownloadUrl; // Iniciar descarga
}
</script>

<script>
// Función para manejar archivos adjuntos
async function handleFile(dni) {
    const button = document.getElementById(`file-button-${dni}`);
    const hasDocument = documentStatusCache[dni] || false;

    if (hasDocument) {
        // Si ya existe un documento, descargarlo
        await downloadFile(dni);
    } else {
        // Si no hay documento, permitir subir uno nuevo
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.doc,.docx,.pdf';

        fileInput.onchange = async () => {
            const file = fileInput.files[0];
            if (!file) return;

            button.textContent = 'Subiendo...';
            button.disabled = true;

            try {
                const formData = new FormData();
                formData.append('dni', dni);
                formData.append('file', file);

                const response = await fetch('upload_document.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Actualizar el estado del botón y mostrar alerta de éxito
                    documentStatusCache[dni] = true;
                    button.textContent = 'Descargar Adjunto';
                    button.classList.add('has-document');
                    swal("Éxito", result.message, "success");
                } else {
                    // Mostrar advertencia o error
                    swal(result.type.charAt(0).toUpperCase() + result.type.slice(1), result.message, result.type);
                }
            } catch (error) {
                console.error('Error:', error);
                swal("Error", "Ocurrió un error al cargar el archivo.", "error");
            } finally {
                button.disabled = false;
                if (!documentStatusCache[dni]) {
                    button.textContent = 'Cargar Adjunto';
                }
            }
        };

        fileInput.click();
    }
}

// Función para descargar archivo adjunto
async function downloadFile(dni) {
    try {
        const response = await fetch(`download_document.php?dni=${dni}`);
        if (!response.ok) {
            throw new Error(`Error ${response.status}`);
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `documento_${dni}`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

        // Mostrar mensaje de éxito
        swal("Éxito", "El archivo se descargó correctamente.", "success");
    } catch (error) {
        console.error('Error:', error);
        swal("Error", "No se pudo descargar el archivo.", "error");
    }
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

    /* Paginación */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .pagination button {
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        background-color: #f0f0f0;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .pagination button.active {
        background-color: #06adbf;
        color: white;
    }

    .pagination button:hover {
        background-color: #ddd;
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