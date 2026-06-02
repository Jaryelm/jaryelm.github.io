<?php
include_once '../../backend/registros/session_check.php';
require_once('../../backend/bd/Conexion.php'); // Incluir el archivo de conexión
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
            <h1 class="title">Transcripción de Informes</h1>

            <!-- Panel de Estadísticas -->
            <div class="stats-panel">
                <div class="stat-card">
                    <h3>Pendientes</h3>
                    <p id="pending-count">0</p>
                </div>
                <div class="stat-card">
                    <h3>Transcritos Hoy</h3>
                    <p id="today-count">0</p>
                </div>
                <div class="stat-card">
                    <h3>Tiempo Promedio</h3>
                    <p id="avg-time">0 min</p>
                </div>
                <div class="stat-card">
                    <h3>Precisión</h3>
                    <p id="accuracy">0%</p>
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
                    <option value="pending_transcription">Pendientes</option>
                    <option value="in_progress">En Progreso</option>
                    <option value="completed">Completados</option>
                </select>

                <input type="date" id="dateFilter" />
                <button onclick="applyFilters()" class="filter-btn">Aplicar Filtros</button>
            </div>

            <!-- Tabla de Transcripciones -->
            <div class="table-container">
                <table id="transcriptionsTable">
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
                    <tbody id="transcriptionsBody">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Modal de Transcripción -->
            <div id="transcriptionModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Transcripción de Informe</h2>
                    <div class="transcription-container">
                        <!-- Sección de Audio -->
                        <div class="audio-section">
                            <h3>Dictado</h3>
                            <audio id="dictationAudio" controls></audio>
                            <div class="audio-controls">
                                <button onclick="playAudio()">Reproducir</button>
                                <button onclick="pauseAudio()">Pausar</button>
                                <button onclick="stopAudio()">Detener</button>
                            </div>
                        </div>

                        <!-- Sección de Transcripción -->
                        <div class="transcription-form">
                            <form id="transcriptionForm">
                                <input type="hidden" id="reportId" name="reportId">
                                
                                <div class="form-group">
                                    <label>Historia Clínica:</label>
                                    <textarea id="clinicalHistory" rows="3" readonly></textarea>
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
                                    <label>Comentarios:</label>
                                    <textarea id="comments" rows="2"></textarea>
                                </div>

                                <div class="button-group">
                                    <button type="button" onclick="saveDraft()">Guardar Borrador</button>
                                    <button type="button" onclick="completeTranscription()">Completar Transcripción</button>
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
        max-width: 1200px;
        border-radius: 8px;
        max-height: 95vh;
        overflow-y: auto;
    }

    .transcription-container {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 20px;
        margin-top: 20px;
    }

    .audio-section {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 4px;
    }

    .audio-controls {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .audio-controls button {
        padding: 8px 16px;
        background-color: #06adbf;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .transcription-form {
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

    .form-group textarea {
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

    .button-group button:last-child {
        background-color: #06adbf;
        color: white;
    }

    @media (max-width: 1200px) {
        .transcription-container {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script>
    // Cargar estadísticas
    function loadStats() {
        fetch('get_transcription_stats.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('pending-count').textContent = data.pending;
            document.getElementById('today-count').textContent = data.today;
            document.getElementById('avg-time').textContent = data.avgTime + ' min';
            document.getElementById('accuracy').textContent = data.accuracy + '%';
        });
    }

    // Cargar transcripciones
    function loadTranscriptions() {
        const filters = {
            modality: document.getElementById('modalityFilter').value,
            status: document.getElementById('statusFilter').value,
            date: document.getElementById('dateFilter').value
        };

        fetch('get_transcriptions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(filters)
        })
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('transcriptionsBody');
            tbody.innerHTML = '';

            data.forEach(transcription => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${transcription.patient_id}</td>
                    <td>${transcription.patient_name}</td>
                    <td>${transcription.modality}</td>
                    <td>${transcription.description}</td>
                    <td>${formatDateTime(transcription.report_date)}</td>
                    <td>${formatStatus(transcription.status)}</td>
                    <td>
                        <button onclick="openTranscription('${transcription.id}')">Transcribir</button>
                        <button onclick="viewHistory('${transcription.patient_id}')">Historial</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        });
    }

    // Abrir transcripción
    function openTranscription(reportId) {
        document.getElementById('reportId').value = reportId;
        
        // Cargar datos del informe
        fetch(`get_report.php?report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.report) {
                document.getElementById('clinicalHistory').value = data.report.clinical_history;
                document.getElementById('findings').value = data.report.findings || '';
                document.getElementById('impression').value = data.report.impression || '';
                document.getElementById('comments').value = data.report.comments || '';

                // Cargar audio si existe
                if (data.report.audio_url) {
                    document.getElementById('dictationAudio').src = data.report.audio_url;
                }
            }
        });

        document.getElementById('transcriptionModal').style.display = 'block';
    }

    // Guardar borrador
    function saveDraft() {
        saveTranscription('in_progress');
    }

    // Completar transcripción
    function completeTranscription() {
        saveTranscription('completed');
    }

    // Guardar transcripción
    function saveTranscription(status) {
        const formData = {
            report_id: document.getElementById('reportId').value,
            findings: document.getElementById('findings').value,
            impression: document.getElementById('impression').value,
            comments: document.getElementById('comments').value,
            status: status
        };

        fetch('save_transcription.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Transcripción guardada correctamente');
                document.getElementById('transcriptionModal').style.display = 'none';
                loadTranscriptions();
                loadStats();
            } else {
                alert('Error al guardar la transcripción');
            }
        });
    }

    // Control de audio
    function playAudio() {
        document.getElementById('dictationAudio').play();
    }

    function pauseAudio() {
        document.getElementById('dictationAudio').pause();
    }

    function stopAudio() {
        const audio = document.getElementById('dictationAudio');
        audio.pause();
        audio.currentTime = 0;
    }

    // Formatear fecha y hora
    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('es-ES');
    }

    // Formatear estado
    function formatStatus(status) {
        const statuses = {
            'pending_transcription': 'Pendiente',
            'in_progress': 'En Progreso',
            'completed': 'Completado'
        };
        return statuses[status] || status;
    }

    // Cerrar modal
    document.querySelector('.close').onclick = function() {
        document.getElementById('transcriptionModal').style.display = 'none';
    }

    // Cargar datos iniciales
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        loadTranscriptions();
    });
    </script>
</body>
</html> 