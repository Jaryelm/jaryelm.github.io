<?php
include_once '../../backend/registros/session_check.php';
// Obtener el rol del usuario desde la sesión
session_start();
$rol_usuario = $_SESSION['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <title>MEDIDATA</title>
</head>
<body>
    <?php include_once '../admin/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>

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

            <h1 class="title">Estudios Pendientes de Interpretación</h1>

            <!-- Panel de Estadísticas -->
            <div class="stats-panel">
                <div class="stat-card">
                    <h3>Listados</h3>
                    <p id="listados-count">0</p>
                </div>
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
                <div class="stat-card">
                    <h3>Completados Globales</h3>
                    <p id="completed-global">0</p>
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
                <div id="noStudiesMsg" style="display:none; text-align:center; color:#035c67; font-size:18px; margin:30px 0; font-weight:600;">
                    <span id="noStudiesText"></span>
                </div>
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
                                <input type="hidden" id="patientId" name="patientId">
                                
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

                                <div class="form-group">
                                    <label>Dictado de voz:</label>
                                    <div id="audio-controls">
                                        <button type="button" id="startRecord">Grabar</button>
                                        <button type="button" id="stopRecord" disabled>Detener</button>
                                        <audio id="audioPlayback" controls style="display:none;"></audio>
                                    </div>
                                </div>

                                <div class="button-group">
                                    <button type="button" onclick="saveAsDraft()">Guardar Borrador</button>
                                    <button type="button" onclick="sendToTranscription()">Enviar a Transcripción</button>
                                    <button type="button" onclick="finalizeReport()">Finalizar Informe</button>
                                </div>
                            </form>
                        </div>
                    </div>
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

            <!-- Modal de Transcripción -->
            <div id="transcriptionModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Informe Radiológico</h2>
                    <div class="report-container">
                        <!-- Sección solo lectura -->
                        <div class="original-report">
                            <h3>Informe Original del Médico</h3>
                            <div class="form-group">
                                <label>Historia Clínica:</label>
                                <textarea id="originalClinicalHistory" rows="3" readonly></textarea>
                            </div>
                            <div class="form-group">
                                <label>Hallazgos:</label>
                                <textarea id="originalFindings" rows="6" readonly></textarea>
                            </div>
                            <div class="form-group">
                                <label>Impresión Diagnóstica:</label>
                                <textarea id="originalImpression" rows="4" readonly></textarea>
                            </div>
                        </div>
                        <hr class="divider">
                        <!-- Sección editable -->
                        <div class="editable-report">
                            <h3>Corrección/Transcripción</h3>
                            <div class="form-group">
                                <label>Historia Clínica:</label>
                                <textarea id="clinicalHistory" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Hallazgos:</label>
                                <textarea id="findings" rows="6"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Impresión Diagnóstica:</label>
                                <textarea id="impression" rows="4"></textarea>
                            </div>
                        </div>
                        <button id="btnGeneratePDF" class="btn-transcribe" style="margin-top:20px;">
                            <i class='bx bx-download'></i> Generar PDF
                        </button>
                        <div class="button-group">
                            <button type="button" onclick="saveDraft()">Guardar Borrador</button>
                            <button type="button" onclick="completeTranscription()">Completar Transcripción</button>
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
        transition: background 0.2s, color 0.2s;
    }
    .btn-download {
        background-color: #06adbf;
        color: white;
    }
    .btn-download:hover {
        background-color: #0595a5;
    }
    .btn-report {
        background-color: #035c67;
        color: white;
    }
    .btn-report:hover {
        background-color: #023a43;
    }
    .action-buttons button:active {
        opacity: 0.9;
    }
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            align-items: flex-end;
        }
        .action-buttons button {
            margin-bottom: 5px;
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
            document.getElementById('listados-count').textContent = data.listados;
            document.getElementById('pending-count').textContent = data.pending;
            document.getElementById('today-count').textContent = data.today;
            document.getElementById('completed-global').textContent = data.completed_global;
            document.getElementById('avg-time').textContent = formatAvgTime(data.avgTime);
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

        fetch('get_completed_studies.php', {
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
            const noStudiesMsg = document.getElementById('noStudiesMsg');
            const noStudiesText = document.getElementById('noStudiesText');
            const rolUsuario = '<?php echo $rol_usuario; ?>';
            if (!data || data.length === 0) {
                noStudiesMsg.style.display = 'block';
                if (rolUsuario !== 'Radiologo') {
                    noStudiesText.textContent = 'No tienes permisos para ver este apartado. Solo los médicos radiólogos pueden acceder.';
                } else {
                    noStudiesText.textContent = 'No tienes estudios asignados actualmente. Espera a que se te asignen nuevos estudios.';
                }
            } else {
                noStudiesMsg.style.display = 'none';
                data.forEach(study => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${study.patient_id}</td>
                        <td>${study.patient_name}</td>
                        <td>${study.modality}</td>
                        <td>${study.study_description || 'Sin descripción'}</td>
                        <td>${formatDateTime(study.study_date)}</td>
                        <td>${formatStatus(study.status)}</td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="downloadStudy('${study.study_id}')" class="btn-download">
                                    <i class='bx bx-download'></i> Descargar
                                </button>
                                <button onclick="openReport('${study.study_id}', '${study.series_id}', '${study.patient_id}')" class="btn-report">
                                    <i class='bx bx-file'></i> Informe
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
        });
    }

    // Abrir informe
    function openReport(studyId, seriesId, patientId) {
        document.getElementById('clinicalHistory').value = '';
        document.getElementById('findings').value = '';
        document.getElementById('impression').value = '';
        document.getElementById('isCritical').checked = false;
        document.getElementById('criticalSection').style.display = 'none';
        document.getElementById('urgencyLevel').value = '';
        document.getElementById('notifyTo').value = '';

        document.getElementById('studyId').value = studyId;
        document.getElementById('patientId').value = patientId;
        const dicomViewer = document.getElementById('orthancViewer');
        if (seriesId && seriesId !== 'null' && seriesId !== 'N/A') {
            dicomViewer.src = `https://medicloud.medicasa.hn/orthanc/web-viewer/app/viewer.html?series=${seriesId}`;
            dicomViewer.style.display = '';
        } else {
            dicomViewer.src = '';
            dicomViewer.style.display = 'none';
            // Opcional: muestra un mensaje de error en el visor
            if (!document.getElementById('dicomErrorMsg')) {
                const errorMsg = document.createElement('div');
                errorMsg.id = 'dicomErrorMsg';
                errorMsg.style.color = 'red';
                errorMsg.style.textAlign = 'center';
                errorMsg.style.marginTop = '20px';
                errorMsg.textContent = 'No se encontró la serie DICOM para este estudio.';
                dicomViewer.parentNode.appendChild(errorMsg);
            }
        }
        // Elimina el mensaje de error si el seriesId es válido
        if (seriesId && seriesId !== 'null' && seriesId !== 'N/A' && document.getElementById('dicomErrorMsg')) {
            document.getElementById('dicomErrorMsg').remove();
        }

        fetch(`get_report.php?study_id=${studyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.report) {
                document.getElementById('clinicalHistory').value = data.report.clinical_history || '';
                document.getElementById('findings').value = data.report.findings || '';
                document.getElementById('impression').value = data.report.impression || '';
                document.getElementById('isCritical').checked = !!data.report.is_critical;
                if (data.report.is_critical) {
                    document.getElementById('criticalSection').style.display = 'block';
                    document.getElementById('urgencyLevel').value = data.report.urgency_level || '';
                    document.getElementById('notifyTo').value = data.report.notified_to || '';
                }
                if (data.report.audio_url) {
                    document.getElementById('audioPlayback').src = '../../backend/audios/' + data.report.audio_url;
                    document.getElementById('audioPlayback').style.display = 'block';
                } else {
                    document.getElementById('audioPlayback').style.display = 'none';
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

    // Finalizar informe
    function finalizeReport() {
        saveReport('final');
    }

    // Guardar informe
    function saveReport(status) {
        const formData = {
            study_id: document.getElementById('studyId').value,
            patient_id: document.getElementById('patientId').value,
            clinical_history: document.getElementById('clinicalHistory').value,
            findings: document.getElementById('findings').value,
            impression: document.getElementById('impression').value,
            is_critical: document.getElementById('isCritical').checked,
            status: status
        };

        if (formData.is_critical) {
            formData.urgency_level = document.getElementById('urgencyLevel').value;
            formData.notified_to = document.getElementById('notifyTo').value;
        } else {
            formData.urgency_level = null;
            formData.notified_to = null;
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
                Swal.fire('Éxito', 'Informe guardado correctamente', 'success');
                document.getElementById('reportModal').style.display = 'none';
                loadStudies();
                loadStats();
            } else {
                Swal.fire('Error', 'Error al guardar el informe', 'error');
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

    function formatAvgTime(minutes) {
        if (!minutes || isNaN(minutes)) return '0 min';
        minutes = Math.round(minutes);
        if (minutes < 60) {
            return `${minutes} min`;
        } else {
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return m > 0 ? `${h} h ${m} min` : `${h} h`;
        }
    }

    // Función para descargar un estudio (igual que en tabladeestudios.php y worklist.php)
    function downloadStudy(studyId) {
        const orthancDownloadUrl = `https://medicloud.medicasa.hn/orthanc/studies/${studyId}/archive`;
        window.location.href = orthancDownloadUrl;
    }

    // Función para abrir el visor DICOM
    function openDicomViewer(seriesId) {
                document.getElementById('orthancViewer').src =
            `https://medicloud.medicasa.hn/orthanc/web-viewer/app/viewer.html?series=${seriesId}`;
        document.getElementById('viewerModal').style.display = 'block';
    }

    // Cerrar modal del visor DICOM
    document.querySelector('.close').onclick = function() {
        document.getElementById('viewerModal').style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    function openTranscription(reportId) {
        fetch(`get_report.php?report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.report) {
                // Original
                document.getElementById('originalClinicalHistory').value = data.report.clinical_history || '';
                document.getElementById('originalFindings').value = data.report.findings || '';
                document.getElementById('originalImpression').value = data.report.impression || '';
                // Editable (puedes cargar aquí la transcripción previa si existe)
                document.getElementById('clinicalHistory').value = data.report.transcribed_clinical_history || data.report.clinical_history || '';
                document.getElementById('findings').value = data.report.transcribed_findings || data.report.findings || '';
                document.getElementById('impression').value = data.report.transcribed_impression || data.report.impression || '';
                document.getElementById('transcriptionModal').style.display = 'block';
                window.currentReport = data.report;
            } else {
                alert('No se pudo cargar el informe');
            }
        });
    }

    // PDF del informe EDITADO (puedes cambiar a original si lo prefieres)
    document.getElementById('btnGeneratePDF').onclick = function() {
        if (!window.currentReport) return alert('No hay informe cargado');
        const clinical_history = document.getElementById('clinicalHistory').value;
        const findings = document.getElementById('findings').value;
        const impression = document.getElementById('impression').value;
        const { patient_name, study_id } = window.currentReport;

        const doc = new window.jspdf.jsPDF();
        doc.setFontSize(16);
        doc.text('Informe Radiológico', 10, 15);
        doc.setFontSize(12);
        doc.text(`Paciente: ${patient_name || ''}`, 10, 25);
        doc.text(`ID Estudio: ${study_id || ''}`, 10, 32);

        doc.setFontSize(12);
        doc.text('Historia Clínica:', 10, 45);
        doc.setFontSize(10);
        doc.text(clinical_history || '', 10, 52);

        doc.setFontSize(12);
        doc.text('Hallazgos:', 10, 65);
        doc.setFontSize(10);
        doc.text(findings || '', 10, 72);

        doc.setFontSize(12);
        doc.text('Impresión Diagnóstica:', 10, 85);
        doc.setFontSize(10);
        doc.text(impression || '', 10, 92);

        doc.save(`Informe_Radiologico_${study_id || 'sin_id'}.pdf`);
    };

    let mediaRecorder;
    let audioChunks = [];

    document.getElementById('startRecord').onclick = async function() {
        audioChunks = [];
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        mediaRecorder.start();
        document.getElementById('startRecord').disabled = true;
        document.getElementById('stopRecord').disabled = false;

        mediaRecorder.ondataavailable = e => {
            audioChunks.push(e.data);
        };
    };

    document.getElementById('stopRecord').onclick = function() {
        mediaRecorder.stop();
        document.getElementById('startRecord').disabled = false;
        document.getElementById('stopRecord').disabled = true;

        mediaRecorder.onstop = async () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const audioUrl = URL.createObjectURL(audioBlob);
            document.getElementById('audioPlayback').src = audioUrl;
            document.getElementById('audioPlayback').style.display = 'block';

            // Enviar al servidor
            const formData = new FormData();
            formData.append('audio', audioBlob);
            formData.append('study_id', document.getElementById('studyId').value);

            await fetch('save_audio.php', {
                method: 'POST',
                body: formData
            });
        };
    };
    </script>

    <!-- Alertas -->
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

        <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

</body>
</html> 