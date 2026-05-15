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
            <h1 class="title">Estudios Pendientes de Interpretación</h1>

            <!-- Panel de Estadísticas -->
            <div class="stats-panel">
                <div class="stat-card">
                    <h3>Pendientes</h3>
                    <p id="pending-count">0</p>
                </div>
                <div class="stat-card">
                    <h3>Interpretados Hoy</h3>
                    <p id="today-count">0</p>
                </div>
                <div class="stat-card">
                    <h3>Tiempo Promedio</h3>
                    <p id="avg-time">0 min</p>
                </div>
                <div class="stat-card">
                    <h3>Hallazgos Críticos</h3>
                    <p id="critical-count">0</p>
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

                <select id="statusFilter">
                    <option value="">Todos los Estados</option>
                    <option value="pending">Pendientes</option>
                    <option value="draft">Borradores</option>
                    <option value="completed">Completados</option>
                </select>

                <input type="date" id="dateFilter" />
                <button onclick="applyFilters()" class="filter-btn">Aplicar Filtros</button>
            </div>

            <!-- Tabla de Estudios -->
            <div class="table-container">
                <table id="studiesTable">
                    <thead>
                        <tr>
                            <th>ID Paciente</th>
                            <th>Nombre</th>
                            <th>Modalidad</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="studiesBody">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Modal de Informe -->
            <div id="reportModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Informe Radiológico</h2>
                    <div class="report-container">
                        <!-- Sección de Visor DICOM -->
                        <div class="dicom-viewer">
                            <iframe id="orthancViewer" src="" frameborder="0"></iframe>
                        </div>

                        <!-- Sección de Informe -->
                        <div class="report-form">
                            <form id="reportForm">
                                <input type="hidden" id="studyId" name="studyId">
                                
                                <div class="form-group">
                                    <label>Historia Clínica:</label>
                                    <textarea id="clinicalHistory" rows="3" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Hallazgos:</label>
                                    <textarea id="findings" rows="6" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Impresión Diagnóstica:</label>
                                    <textarea id="impression" rows="4" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="isCritical">
                                        Hallazgo Crítico
                                    </label>
                                </div>

                                <div id="criticalSection" style="display: none;">
                                    <div class="form-group">
                                        <label>Nivel de Urgencia:</label>
                                        <select id="urgencyLevel">
                                            <option value="high">Alto</option>
                                            <option value="medium">Medio</option>
                                            <option value="low">Bajo</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Notificar a:</label>
                                        <input type="text" id="notifyTo">
                                    </div>
                                </div>

                                <div class="button-group">
                                    <button type="button" onclick="saveAsDraft()">Guardar Borrador</button>
                                    <button type="button" onclick="sendToTranscription()">Enviar a Transcripción</button>
                                    <button type="submit">Finalizar Informe</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <style>
    .stats-panel {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-card h3 {
        margin: 0;
        color: #666;
        font-size: 14px;
    }

    .stat-card p {
        margin: 10px 0 0;
        font-size: 24px;
        font-weight: bold;
        color: #06adbf;
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
        max-width: 1400px;
        border-radius: 8px;
        max-height: 95vh;
        overflow-y: auto;
    }

    .report-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    .dicom-viewer {
        background: #000;
        border-radius: 4px;
        min-height: 500px;
    }

    .dicom-viewer iframe {
        width: 100%;
        height: 100%;
        min-height: 500px;
    }

    .report-form {
        padding: 20px;
        background: #f9f9f9;
        border-radius: 4px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group textarea, .form-group input, .form-group select {
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
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .button-group button:first-child {
        background-color: #6c757d;
        color: white;
    }

    .button-group button:nth-child(2) {
        background-color: #efc25b;
        color: white;
    }

    .button-group button:last-child {
        background-color: #06adbf;
        color: white;
    }

    @media (max-width: 1200px) {
        .report-container {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script>
    // Cargar estadísticas
    function loadStats() {
        fetch('get_stats.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('pending-count').textContent = data.pending;
            document.getElementById('today-count').textContent = data.today;
            document.getElementById('avg-time').textContent = data.avgTime + ' min';
            document.getElementById('critical-count').textContent = data.critical;
        });
    }

    // Cargar estudios
    function loadStudies() {
        const filters = {
            modality: document.getElementById('modalityFilter').value,
            status: document.getElementById('statusFilter').value,
            date: document.getElementById('dateFilter').value
        };

        fetch('get_studies.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(filters)
        })
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('studiesBody');
            tbody.innerHTML = '';

            data.forEach(study => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${study.patient_id}</td>
                    <td>${study.patient_name}</td>
                    <td>${study.modality}</td>
                    <td>${study.description}</td>
                    <td>${formatDateTime(study.study_date)}</td>
                    <td>${formatStatus(study.status)}</td>
                    <td>
                        <button onclick="openReport('${study.id}', '${study.series_id}')">Informar</button>
                        <button onclick="viewHistory('${study.patient_id}')">Historial</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        });
    }

    // Abrir informe
    function openReport(studyId, seriesId) {
        document.getElementById('studyId').value = studyId;
        document.getElementById('orthancViewer').src = 
            `https://medicloud.medicasa.hn/orthanc/web-viewer/app/viewer.html?series=${seriesId}`;
        
        // Cargar informe existente si hay
        fetch(`get_report.php?study_id=${studyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.report) {
                document.getElementById('clinicalHistory').value = data.report.clinical_history;
                document.getElementById('findings').value = data.report.findings;
                document.getElementById('impression').value = data.report.impression;
                document.getElementById('isCritical').checked = data.report.is_critical;
                if (data.report.is_critical) {
                    document.getElementById('criticalSection').style.display = 'block';
                    document.getElementById('urgencyLevel').value = data.report.urgency_level;
                    document.getElementById('notifyTo').value = data.report.notified_to;
                }
            }
        });

        document.getElementById('reportModal').style.display = 'block';
    }

    // Manejar checkbox de hallazgo crítico
    document.getElementById('isCritical').addEventListener('change', function() {
        document.getElementById('criticalSection').style.display = 
            this.checked ? 'block' : 'none';
    });

    // Guardar como borrador
    function saveAsDraft() {
        saveReport('draft');
    }

    // Enviar a transcripción
    function sendToTranscription() {
        saveReport('pending_transcription');
    }

    // Guardar informe
    function saveReport(status) {
        const formData = {
            study_id: document.getElementById('studyId').value,
            clinical_history: document.getElementById('clinicalHistory').value,
            findings: document.getElementById('findings').value,
            impression: document.getElementById('impression').value,
            is_critical: document.getElementById('isCritical').checked,
            status: status
        };

        if (formData.is_critical) {
            formData.urgency_level = document.getElementById('urgencyLevel').value;
            formData.notified_to = document.getElementById('notifyTo').value;
        }

        fetch('save_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Informe guardado correctamente');
                document.getElementById('reportModal').style.display = 'none';
                loadStudies();
                loadStats();
            } else {
                alert('Error al guardar el informe');
            }
        });
    }

    // Formatear fecha y hora
    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('es-ES');
    }

    // Formatear estado
    function formatStatus(status) {
        const statuses = {
            'pending': 'Pendiente',
            'draft': 'Borrador',
            'pending_transcription': 'En Transcripción',
            'transcribed': 'Transcrito',
            'reviewed': 'Revisado',
            'final': 'Finalizado'
        };
        return statuses[status] || status;
    }

    // Cerrar modal
    document.querySelector('.close').onclick = function() {
        document.getElementById('reportModal').style.display = 'none';
    }

    // Cargar datos iniciales
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        loadStudies();
    });
    </script>
</body>
</html> 