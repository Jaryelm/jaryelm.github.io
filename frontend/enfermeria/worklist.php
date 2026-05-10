<?php
include_once '../../backend/registros/session_check.php';
require_once('../../backend/bd/Conexion.php');
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
    <?php include_once '../enfermeria/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../enfermeria/perfil.php'; ?>
        </nav>

        <main>
            <h1 class="title">Lista de Trabajo - Técnico Radiólogo</h1>

            <!-- Panel de Estadísticas -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Pendientes</h3>
                    <p id="pending-count">0</p>
                </div>
                <div class="stat-card">
                    <h3>Completados Hoy</h3>
                    <p id="completed-today">0</p>
                </div>
                <div class="stat-card">
                    <h3>Tiempo Promedio</h3>
                    <p id="avg-time">0 min</p>
                </div>
                <div class="stat-card">
                    <h3>Calidad Promedio</h3>
                    <p id="quality-percentage">0%</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <select id="modalityFilter">
                    <option value="">Todas las Modalidades</option>
                    <option value="CR">Radiografía Computarizada</option>
                    <option value="CT">Tomografía Computarizada</option>
                    <option value="MR">Resonancia Magnética</option>
                    <option value="US">Ultrasonido</option>
                </select>

                <select id="priorityFilter">
                    <option value="">Todas las Prioridades</option>
                    <option value="routine">Rutina</option>
                    <option value="urgent">Urgente</option>
                    <option value="emergency">Emergencia</option>
                </select>

                <select id="statusFilter">
                    <option value="">Todos los Estados</option>
                    <option value="pending">Pendiente</option>
                    <option value="in_progress">En Progreso</option>
                    <option value="completed">Completado</option>
                </select>

                <input type="date" id="dateFilter" />
                <button onclick="applyFilters()" class="filter-btn">Aplicar Filtros</button>
            </div>

            <!-- Tabla de Lista de Trabajo -->
            <div class="table-container">
                <table id="worklistTable">
                    <thead>
                        <tr>
                            <th>ID Paciente</th>
                            <th>Nombre</th>
                            <th>Modalidad</th>
                            <th>Descripción</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="worklistBody">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Modal de Control de Calidad -->
            <div id="qualityModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Control de Calidad</h2>
                    <form id="qualityForm">
                        <input type="hidden" id="studyId" name="studyId">
                        
                        <div class="form-group">
                            <label>Calidad de Imagen:</label>
                            <select id="imageQuality" required>
                                <option value="excellent">Excelente</option>
                                <option value="acceptable">Aceptable</option>
                                <option value="poor">Pobre</option>
                                <option value="unacceptable">Inaceptable</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Posicionamiento:</label>
                            <select id="positioningQuality" required>
                                <option value="correct">Correcto</option>
                                <option value="suboptimal">Subóptimo</option>
                                <option value="incorrect">Incorrecto</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>¿Necesita Repetición?</label>
                            <input type="checkbox" id="needsRepeat">
                        </div>

                        <div class="form-group">
                            <label>Comentarios:</label>
                            <textarea id="qualityComments" rows="3"></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit">Guardar Control de Calidad</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de Incidencias Técnicas -->
            <div id="incidentModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Registro de Incidencia Técnica</h2>
                    <form id="incidentForm">
                        <input type="hidden" id="incidentStudyId" name="studyId">
                        
                        <div class="form-group">
                            <label>Tipo de Incidencia:</label>
                            <select id="incidentType" required>
                                <option value="equipment">Equipo</option>
                                <option value="patient">Paciente</option>
                                <option value="protocol">Protocolo</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Descripción:</label>
                            <textarea id="incidentDescription" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Acciones Tomadas:</label>
                            <textarea id="incidentActions" rows="3" required></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit">Registrar Incidencia</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de Repetición de Estudio -->
            <div id="repeatModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Repetición de Estudio</h2>
                    <form id="repeatForm">
                        <input type="hidden" id="repeatStudyId" name="studyId">
                        
                        <div class="form-group">
                            <label>Razón de Repetición:</label>
                            <select id="repeatReason" required>
                                <option value="positioning">Posicionamiento</option>
                                <option value="quality">Calidad de Imagen</option>
                                <option value="protocol">Protocolo</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Comentarios:</label>
                            <textarea id="repeatComments" rows="3" required></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit">Programar Repetición</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de Visor DICOM -->
            <div id="viewerModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Visor DICOM</h2>
                    <div class="viewer-container">
                        <iframe id="orthancViewer" src="" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <style>
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .stat-card {
        background-color: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-card h3 {
        margin: 0;
        color: #666;
        font-size: 14px;
        font-weight: 600;
    }

    .stat-card p {
        margin: 10px 0 0;
        color: #06adbf;
        font-size: 24px;
        font-weight: bold;
    }

    .filters {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filters select, .filters input {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-width: 150px;
    }

    .filter-btn {
        padding: 8px 16px;
        background-color: #06adbf;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .table-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: #06adbf;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

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
        max-width: 800px;
        border-radius: 8px;
        max-height: 95vh;
        overflow-y: auto;
    }

    .viewer-container {
        width: 100%;
        height: 600px;
        background: #000;
        border-radius: 4px;
    }

    .viewer-container iframe {
        width: 100%;
        height: 100%;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group select, .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .button-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .button-group button {
        padding: 10px 20px;
        background-color: #06adbf;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .filters {
            flex-direction: column;
        }

        .filters select, .filters input {
            width: 100%;
        }

        .modal-content {
            width: 95%;
            margin: 5% auto;
        }
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .action-buttons button {
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
    }

    .btn-view {
        background-color: #06adbf;
        color: white;
    }

    .btn-quality {
        background-color: #28a745;
        color: white;
    }

    .btn-incident {
        background-color: #ffc107;
        color: black;
    }

    .btn-repeat {
        background-color: #dc3545;
        color: white;
    }

    .action-buttons button:hover {
        opacity: 0.9;
    }
    </style>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script>
    // Función para cargar las estadísticas
    async function loadStats() {
        try {
            const response = await fetch('get_technician_stats.php');
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }

            // Actualizar los elementos del DOM con las estadísticas
            document.getElementById('pending-count').textContent = data.pending;
            document.getElementById('completed-today').textContent = data.completed_today;
            document.getElementById('avg-time').textContent = `${data.avg_time} min`;
            document.getElementById('quality-percentage').textContent = `${data.quality}%`;
        } catch (error) {
            console.error('Error loading stats:', error);
            swal("Error", "No se pudieron cargar las estadísticas", "error");
        }
    }

    // Función para aplicar filtros
    function applyFilters() {
        loadWorklist();
    }

    // Cargar lista de trabajo
    async function loadWorklist() {
        try {
            const filters = {
                modality: document.getElementById('modalityFilter').value,
                priority: document.getElementById('priorityFilter').value,
                status: document.getElementById('statusFilter').value,
                date: document.getElementById('dateFilter').value
            };

            const response = await fetch('get_worklist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(filters)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            const tbody = document.getElementById('worklistBody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                // Si no hay datos, mostrar mensaje
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">
                            No se encontraron estudios con los filtros seleccionados
                        </td>
                    </tr>`;
                return;
            }

            data.forEach(study => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${study.patient_id || 'N/A'}</td>
                    <td>${study.patient_name || 'N/A'}</td>
                    <td>${study.modality || 'N/A'}</td>
                    <td>${study.description || 'Sin descripción'}</td>
                    <td>${formatPriority(study.priority)}</td>
                    <td>${formatStatus(study.status)}</td>
                    <td>
                        <div class="action-buttons">
                            <button onclick="openDicomViewer('${study.series_id}')" class="btn-view">
                                <i class='bx bx-show'></i> Ver
                            </button>
                            <button onclick="openQualityControl('${study.id}')" class="btn-quality">
                                <i class='bx bx-check-circle'></i> Control
                            </button>
                            <button onclick="openIncident('${study.id}')" class="btn-incident">
                                <i class='bx bx-error'></i> Incidencia
                            </button>
                            <button onclick="openRepeat('${study.id}')" class="btn-repeat">
                                <i class='bx bx-refresh'></i> Repetir
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } catch (error) {
            console.error('Error loading worklist:', error);
            swal("Error", "No se pudo cargar la lista de trabajo", "error");
        }
    }

    // Inicializar la página
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        loadWorklist(); // Cargar la lista inicial
        // Actualizar estadísticas cada 5 minutos
        setInterval(loadStats, 300000);
    });

    function openDicomViewer(seriesId) {
        document.getElementById('orthancViewer').src = 
            `https://medicloud.medicasa.hn/orthanc/web-viewer/app/viewer.html?series=${seriesId}`;
        document.getElementById('viewerModal').style.display = 'block';
    }

    // Abrir control de calidad
    function openQualityControl(studyId) {
    document.getElementById('studyId').value = studyId;

    // Cargar datos existentes si ya hay un registro
    fetch(`get_quality_control.php?study_id=${studyId}`)
        .then(response => response.json())
        .then(data => {
            const saveButton = document.querySelector('#qualityForm button[type="submit"]');

            if (data.success && data.quality_control) {
                const qcData = data.quality_control;
                document.getElementById('imageQuality').value = qcData.image_quality;
                document.getElementById('positioningQuality').value = qcData.positioning_quality;
                document.getElementById('needsRepeat').checked = qcData.needs_repeat === 1;
                document.getElementById('qualityComments').value = qcData.comments;

                // Deshabilitar el botón "Guardar Control de Calidad"
                saveButton.disabled = true;
                saveButton.title = "Ya existe un registro de control de calidad";
            } else {
                // Reiniciar los campos si no hay datos
                document.getElementById('imageQuality').value = '';
                document.getElementById('positioningQuality').value = '';
                document.getElementById('needsRepeat').checked = false;
                document.getElementById('qualityComments').value = '';

                // Habilitar el botón "Guardar Control de Calidad"
                saveButton.disabled = false;
                saveButton.title = "";
            }

            // Mostrar el modal
            document.getElementById('qualityModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching quality control data:', error);
            swal("Error", "No se pudieron cargar los datos de control de calidad", "error");
        });
}

    // Abrir registro de incidencia
    function openIncident(studyId) {
        document.getElementById('incidentStudyId').value = studyId;
        document.getElementById('incidentModal').style.display = 'block';
    }

// Abrir registro de incidencia
function openIncident(studyId) {
    document.getElementById('incidentStudyId').value = studyId;

    // Cargar datos existentes si ya hay un registro
    fetch(`get_incident.php?study_id=${studyId}`)
        .then(response => response.json())
        .then(data => {
            const saveButton = document.querySelector('#incidentForm button[type="submit"]');

            if (data.success && data.incident) {
                const incidentData = data.incident;

                // Rellenar los campos con los datos obtenidos
                document.getElementById('incidentType').value = incidentData.incident_type || '';
                document.getElementById('incidentDescription').value = incidentData.description || '';
                document.getElementById('incidentActions').value = incidentData.actions || '';

                // Deshabilitar el botón "Guardar" si ya hay un registro
                saveButton.disabled = true;
                saveButton.title = "La incidencia ya ha sido registrada";
            } else {
                // Limpiar los campos si no hay datos
                document.getElementById('incidentType').value = '';
                document.getElementById('incidentDescription').value = '';
                document.getElementById('incidentActions').value = '';

                // Habilitar el botón "Guardar"
                saveButton.disabled = false;
                saveButton.title = "";
            }

            // Mostrar el modal
            document.getElementById('incidentModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching incident data:', error);
            swal("Error", "No se pudieron cargar los datos de la incidencia", "error");
        });
}

    // Abrir repetición de estudio
    function openRepeat(studyId) {
        document.getElementById('repeatStudyId').value = studyId;
        document.getElementById('repeatModal').style.display = 'block';
    }

    // Guardar control de calidad
    document.getElementById('qualityForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = {
        study_id: document.getElementById('studyId').value,
        image_quality: document.getElementById('imageQuality').value,
        positioning_quality: document.getElementById('positioningQuality').value,
        needs_repeat: document.getElementById('needsRepeat').checked,
        comments: document.getElementById('qualityComments').value
    };
    fetch('save_quality_control.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            swal("Éxito", "Control de calidad guardado correctamente", "success");
            document.getElementById('qualityModal').style.display = 'none';
            loadWorklist();
            loadStats();
        } else {
            swal("Error", "Error al guardar el control de calidad", "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        swal("Error", "Ocurrió un error al procesar la solicitud", "error");
    });
};

    // Guardar incidencia
    document.getElementById('incidentForm').onsubmit = function(e) {
    e.preventDefault();

    const formData = {
        study_id: document.getElementById('incidentStudyId').value,
        incident_type: document.getElementById('incidentType').value,
        description: document.getElementById('incidentDescription').value,
        actions: document.getElementById('incidentActions').value
    };

    fetch('save_incident.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            swal("Éxito", "Incidencia registrada correctamente", "success");
            document.getElementById('incidentModal').style.display = 'none';
            loadWorklist(); // Recargar la lista de trabajo
        } else {
            swal("Error", data.message || "Error al registrar la incidencia", "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        swal("Error", "Ocurrió un error al procesar la solicitud", "error");
    });
};

    // Guardar repetición
    document.getElementById('repeatForm').onsubmit = function(e) {
        e.preventDefault();
        
        const formData = {
            study_id: document.getElementById('repeatStudyId').value,
            reason: document.getElementById('repeatReason').value,
            comments: document.getElementById('repeatComments').value
        };

        fetch('save_repeat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Repetición programada correctamente');
                document.getElementById('repeatModal').style.display = 'none';
                loadWorklist();
            } else {
                alert('Error al programar la repetición');
            }
        });
    };

    // Formatear prioridad
    function formatPriority(priority) {
        const priorities = {
            'routine': 'Rutina',
            'urgent': 'Urgente',
            'emergency': 'Emergencia'
        };
        return priorities[priority] || priority;
    }

    // Formatear estado
    function formatStatus(status) {
        const statuses = {
            'pending': 'Pendiente',
            'in_progress': 'En Progreso',
            'completed': 'Completado'
        };
        return statuses[status] || status;
    }

    // Cerrar modales
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.onclick = function() {
            this.closest('.modal').style.display = 'none';
        }
    });

    // Cerrar modales al hacer clic fuera
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>

    <!-- Alertas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

</body>
</html> 