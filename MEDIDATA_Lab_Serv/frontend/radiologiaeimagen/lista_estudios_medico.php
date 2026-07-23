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
    <link rel="stylesheet" href="../../backend/css/informe_radiologico_modal.css">
    <link rel="stylesheet" href="../../backend/css/mhpacs_filters.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <title>MEDIDATA</title>
</head>
<body>
    <?php include_once '../radiologiaeimagen/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../radiologiaeimagen/perfil.php'; ?>
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

<?php if ($rol_usuario !== 'Radiologo'): ?>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/worklist_tecnico.php')">Tecnico Radiólogo</button>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/lista_estudios_medico.php')">Médico Radiólogo</button>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/lista_transcripciones_user.php')">Transcriptores</button>
<?php endif; ?>

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
            <?php $mhpacs_filter_mode = 'studies_medico'; include __DIR__ . '/_mhpacs_filters.inc.php'; ?>

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
                <div id="studiesPagination" class="mhpacs-pagination"></div>
                <div id="noStudiesMsg" style="display:none; text-align:center; color:#035c67; font-size:18px; margin:30px 0; font-weight:600;">
                    <span id="noStudiesText"></span>
                </div>
            </div>

            <!-- Modal de Informe -->
            <div id="reportModal" class="rx-modal">
                <div class="rx-modal-dialog">
                    <div class="rx-modal-header">
                        <div>
                            <h2>Informe Radiológico</h2>
                            <p>Redacte el informe del estudio asignado. Puede guardar borrador o finalizar cuando esté listo.</p>
                        </div>
                        <button type="button" class="rx-modal-close" id="reportModalClose" aria-label="Cerrar">&times;</button>
                    </div>
                    <div class="rx-modal-body">
                        <div class="rx-report-layout">
                            <div class="rx-dicom-panel">
                                <h3 class="rx-panel-title"><i class="bx bx-image-alt"></i> Visor DICOM</h3>
                                <div class="rx-dicom-viewer">
                                    <iframe id="orthancViewer" src="" title="Visor DICOM"></iframe>
                                </div>
                            </div>

                            <div class="rx-form-panel">
                                <h3 class="rx-panel-title"><i class="bx bx-file"></i> Formulario del informe</h3>
                                <form id="reportForm" class="rx-report-form">
                                    <input type="hidden" id="studyId" name="studyId">
                                    <input type="hidden" id="patientId" name="patientId">

                                    <section class="rx-form-section">
                                        <h4 class="rx-form-section-title">Contenido clínico</h4>
                                        <div class="rx-field">
                                            <label for="clinicalHistory">Historia clínica</label>
                                            <textarea id="clinicalHistory" rows="3" required placeholder="Antecedentes y motivo del estudio..."></textarea>
                                        </div>
                                        <div class="rx-field">
                                            <label for="findings">Hallazgos</label>
                                            <textarea id="findings" rows="6" required placeholder="Describa los hallazgos radiológicos..."></textarea>
                                        </div>
                                        <div class="rx-field">
                                            <label for="impression">Impresión diagnóstica</label>
                                            <textarea id="impression" rows="4" required placeholder="Conclusión e impresión diagnóstica..."></textarea>
                                        </div>
                                    </section>

                                    <section class="rx-form-section">
                                        <h4 class="rx-form-section-title">Alertas y dictado</h4>
                                        <div class="rx-field">
                                            <label class="rx-check-row" for="isCritical">
                                                <input type="checkbox" id="isCritical">
                                                <span>Hallazgo crítico</span>
                                            </label>
                                        </div>
                                        <div id="criticalSection" style="display: none;">
                                            <div class="rx-field">
                                                <label for="urgencyLevel">Nivel de urgencia</label>
                                                <select id="urgencyLevel">
                                                    <option value="high">Alto</option>
                                                    <option value="medium">Medio</option>
                                                    <option value="low">Bajo</option>
                                                </select>
                                            </div>
                                            <div class="rx-field">
                                                <label for="notifyTo">Notificar a</label>
                                                <input type="text" id="notifyTo" placeholder="Médico o área a notificar">
                                            </div>
                                        </div>
                                        <div class="rx-field">
                                            <span class="rx-label">Dictado de voz</span>
                                            <div class="rx-audio-controls" id="audio-controls">
                                                <button type="button" id="startRecord"><i class="bx bx-microphone"></i> Grabar</button>
                                                <button type="button" id="stopRecord" disabled><i class="bx bx-stop"></i> Detener</button>
                                            </div>
                                            <audio id="audioPlayback" controls style="display:none;"></audio>
                                        </div>
                                    </section>

                                    <div class="rx-actions">
                                        <button type="button" class="rx-btn-draft" onclick="saveAsDraft()"><i class="bx bx-save"></i> Guardar borrador</button>
                                        <button type="button" class="rx-btn-transcription" onclick="sendToTranscription()"><i class="bx bx-transfer"></i> Enviar a transcripción</button>
                                        <button type="button" class="rx-btn-final" onclick="finalizeReport()"><i class="bx bx-check-circle"></i> Finalizar informe</button>
                                    </div>
                                </form>
                            </div>
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

            <!-- Modal de Seguimiento -->
            <div id="seguimientoModal" class="modal">
                <div class="modal-content" style="max-width:900px;">
                    <span class="close" onclick="document.getElementById('seguimientoModal').style.display='none'">&times;</span>
                    <h2>Seguimiento Detallado del Estudio</h2>
                    <div id="seguimientoDetalle"></div>
                </div>
            </div>

            <!-- Modal visualización PDF -->
            <div id="pdfModal" class="modal-pdf" style="z-index:10050;">
                <div class="modal-pdf-content">
                    <div class="modal-pdf-header">
                        <h2 id="pdfModalTitle">Informe radiológico</h2>
                        <span class="close-pdf-btn" onclick="cerrarPDFModal()" aria-label="Cerrar">&times;</span>
                    </div>
                    <div class="modal-pdf-body">
                        <iframe id="pdfFrame" src="" frameborder="0" title="Vista previa del informe PDF"></iframe>
                    </div>
                    <div class="modal-pdf-footer">
                        <button type="button" class="btn-descargar-pdf" onclick="descargarPDFActual()">
                            <i class="bx bx-download"></i> Descargar PDF
                        </button>
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

    #viewerModal .modal-content,
    #transcriptionModal .modal-content {
        background-color: #fefefe;
        margin: 2% auto;
        padding: 20px;
        width: 95%;
        max-width: 1400px;
        border-radius: 8px;
        max-height: 95vh;
        overflow-y: auto;
    }

    #transcriptionModal .report-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    #transcriptionModal .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 15px;
    }

    #transcriptionModal .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    #transcriptionModal .form-group textarea,
    #transcriptionModal .form-group input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin: 0;
    }

    @media (max-width: 1200px) {
        #transcriptionModal .report-container {
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
    .btn-seguimiento {
        background-color: #efc25b;
        color: #fff;
    }
    .btn-seguimiento:hover {
        background-color: #e0b44a;
    }
    .btn-history {
        background-color: #035c67;
        color: white;
    }
    .btn-history:hover {
        background-color: #023a43;
    }
    .seguimiento-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.07);
        overflow: hidden;
    }
    .seguimiento-table th, .seguimiento-table td {
        border: 1px solid #e0e0e0;
        padding: 8px 10px;
        text-align: left;
    }
    .seguimiento-table th {
        background: #06adbf;
        color: #fff;
        font-weight: 600;
    }
    .seguimiento-table tr:nth-child(even) {
        background: #f9f9f9;
    }
    #seguimientoModal h3 {
        color: #035c67;
        margin-top: 20px;
        margin-bottom: 10px;
        font-size: 1.1em;
        border-left: 4px solid #06adbf;
        padding-left: 8px;
    }
    #seguimientoModal .modal-content {
        background: #f7fafd;
    }
    .transcripcion-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.07);
        margin-bottom: 18px;
        padding: 16px 18px;
        border-left: 5px solid #06adbf;
    }
    .transcripcion-header {
        font-size: 1em;
        color: #035c67;
        margin-bottom: 8px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }
    .transcripcion-estado {
        background: #efc25b;
        color: #fff;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 0.95em;
    }
    .transcripcion-body > div {
        margin-bottom: 6px;
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
    <script src="mhpacs_filters_core.js"></script>
    <script src="studies_list_core.js?v=20260714a"></script>
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

    const rolUsuarioMedico = '<?php echo htmlspecialchars($rol_usuario, ENT_QUOTES, 'UTF-8'); ?>';

    function renderStudyRowMedico(study) {
        const reportId = study.id || '';
        const studyId = study.study_id || '';
        const txStatus = study.transcription_status || '';
        const sentToTranscription = ['pending_transcription', 'final', 'transcribed', 'reviewed'].includes(study.status);
        const showSeguimiento = sentToTranscription || !!txStatus;
        const showPdf = txStatus === 'completed';

        let actionsHtml = `
            <button onclick="downloadStudy('${studyId}')" class="btn-download">
                <i class='bx bx-download'></i> DICOM
            </button>
            <button onclick="openReport('${studyId}', '${study.series_id || ''}', '${study.patient_id}')" class="btn-report">
                <i class='bx bx-file'></i> Informe
            </button>`;

        if (showSeguimiento && reportId) {
            actionsHtml += `
            <button onclick="verSeguimiento('${reportId}', '${studyId}')" class="btn-seguimiento">
                <i class='bx bx-list-ul'></i> Seguimiento
            </button>`;
        }
        if (showPdf && reportId) {
            actionsHtml += `
            <button onclick="downloadPDF('${reportId}')" class="btn-history">
                <i class='bx bx-download'></i> PDF
            </button>`;
        }

        return `
            <td>${study.patient_id}</td>
            <td>${study.patient_name}</td>
            <td>${study.modality}</td>
            <td>${study.study_description || 'Sin descripción'}</td>
            <td>${formatDateTime(study.study_date)}</td>
            <td>${formatStatus(study.status, txStatus)}</td>
            <td><div class="action-buttons">${actionsHtml}</div></td>
        `;
    }

    function loadStudies() {
        if (typeof StudiesListCore !== 'undefined') {
            StudiesListCore.reload();
        }
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
        openReportModal();
    }

    function openReportModal() {
        document.getElementById('reportModal').classList.add('is-open');
        document.body.classList.add('rx-modal-open');
    }

    function closeReportModal() {
        document.getElementById('reportModal').classList.remove('is-open');
        document.body.classList.remove('rx-modal-open');
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
        .then(async function (response) {
            let data = {};
            try {
                data = await response.json();
            } catch (e) {
                data = { success: false, message: 'Respuesta inválida del servidor.' };
            }
            if (response.ok && data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: data.message || 'Informe guardado correctamente',
                    confirmButtonColor: '#06adbf'
                });
                closeReportModal();
                loadStudies();
                loadStats();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al guardar el informe',
                    confirmButtonColor: '#035c67'
                });
            }
        })
        .catch(function () {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo conectar con el servidor.',
                confirmButtonColor: '#035c67'
            });
        });
    }

    // Formatear fecha y hora
    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'N/A';
        // Formato DD/MM/YYYY
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function formatStatus(status, transcriptionStatus) {
        if (transcriptionStatus === 'completed') {
            return 'Transcripción lista';
        }
        if (transcriptionStatus === 'in_progress') {
            return 'Transcripción en progreso';
        }
        const statuses = {
            'pending': 'Pendiente',
            'draft': 'Borrador',
            'pending_transcription': 'En transcripción',
            'transcribed': 'Transcrito',
            'reviewed': 'Revisado',
            'final': 'Finalizado'
        };
        return statuses[status] || status;
    }

    let pdfUrlActual = '';
    let pdfBlobUrl = '';
    let pdfNombreActual = 'informe_radiologico.pdf';

    function rxParsePdfFilename(response, fallback) {
        const cd = response.headers.get('Content-Disposition') || '';
        const utfMatch = /filename\*=UTF-8''([^;\n]+)/i.exec(cd);
        if (utfMatch && utfMatch[1]) {
            try {
                return decodeURIComponent(utfMatch[1].trim());
            } catch (e) {
                return utfMatch[1].trim();
            }
        }
        const plainMatch = /filename="([^"]+)"/i.exec(cd);
        if (plainMatch && plainMatch[1]) {
            return plainMatch[1].trim();
        }
        return fallback || 'informe_radiologico.pdf';
    }

    function downloadPDF(reportId) {
        const url = 'generar_pdf_informe.php?report_id=' + encodeURIComponent(reportId);
        verPDFInforme(url, 'Informe radiológico');
    }

    function verPDFInforme(url, titulo) {
        const inlineUrl = url + (url.includes('?') ? '&' : '?') + 'view=inline';
        fetch(inlineUrl, { credentials: 'same-origin' })
            .then(async function (response) {
                const contentType = response.headers.get('Content-Type') || '';
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(text || 'No se pudo generar el PDF.');
                }
                const blob = await response.blob();
                if (!contentType.includes('pdf') && blob.type && !blob.type.includes('pdf')) {
                    const text = await blob.text();
                    throw new Error(text || 'El servidor no devolvió un PDF válido.');
                }
                if (pdfBlobUrl) {
                    URL.revokeObjectURL(pdfBlobUrl);
                }
                pdfBlobUrl = URL.createObjectURL(blob);
                pdfUrlActual = url;
                pdfNombreActual = rxParsePdfFilename(response, 'informe_radiologico.pdf');
                document.getElementById('pdfModalTitle').textContent = titulo;
                document.getElementById('pdfFrame').src = pdfBlobUrl;
                document.getElementById('pdfModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            })
            .catch(function (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'PDF no disponible',
                    text: (error && error.message) ? error.message.trim() : 'No se pudo generar el informe PDF.',
                    confirmButtonColor: '#035c67'
                });
            });
    }

    function cerrarPDFModal() {
        document.getElementById('pdfModal').style.display = 'none';
        document.getElementById('pdfFrame').src = '';
        pdfUrlActual = '';
        pdfNombreActual = 'informe_radiologico.pdf';
        if (pdfBlobUrl) {
            URL.revokeObjectURL(pdfBlobUrl);
            pdfBlobUrl = '';
        }
        document.body.style.overflow = '';
    }

    function descargarPDFActual() {
        if (pdfBlobUrl) {
            const link = document.createElement('a');
            link.href = pdfBlobUrl;
            link.download = pdfNombreActual || 'informe_radiologico.pdf';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            return;
        }
        if (!pdfUrlActual) {
            return;
        }
        const link = document.createElement('a');
        link.href = pdfUrlActual;
        link.target = '_blank';
        link.rel = 'noopener';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    document.getElementById('pdfModal').addEventListener('click', function (event) {
        if (event.target === this) {
            cerrarPDFModal();
        }
    });

    function verSeguimiento(reportId, studyId) {
        fetch(`get_full_study_details.php?${reportId ? 'report_id=' + reportId : 'study_id=' + studyId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire('Error', data.message || 'No se pudo cargar el seguimiento', 'error');
                    return;
                }
                let html = '';
                if (data.worklist) {
                    html += `<h3>Datos del Estudio</h3><table class='seguimiento-table'><tr><td><b>ID Paciente:</b></td><td>${data.worklist.patient_id || ''}</td></tr><tr><td><b>Nombre:</b></td><td>${data.worklist.patient_name || ''}</td></tr><tr><td><b>Modalidad:</b></td><td>${data.worklist.modality || ''}</td></tr><tr><td><b>Descripción:</b></td><td>${data.worklist.study_description || ''}</td></tr><tr><td><b>Fecha:</b></td><td>${data.worklist.study_date || ''}</td></tr><tr><td><b>Técnico asignado:</b></td><td>${data.worklist.technician_name || ''}</td></tr><tr><td><b>Radiólogo asignado:</b></td><td>${data.worklist.radiologist_name || ''}</td></tr></table>`;
                }
                if (data.report) {
                    html += `<h3>Informe Radiológico</h3><table class='seguimiento-table'><tr><td><b>Historia Clínica:</b></td><td>${data.report.clinical_history || ''}</td></tr><tr><td><b>Hallazgos:</b></td><td>${data.report.findings || ''}</td></tr><tr><td><b>Impresión:</b></td><td>${data.report.impression || ''}</td></tr><tr><td><b>Radiólogo:</b></td><td>${data.report.radiologist_name || ''}</td></tr><tr><td><b>Estado:</b></td><td>${data.report.status || ''}</td></tr><tr><td><b>Fecha creación:</b></td><td>${data.report.created_at || ''}</td></tr></table>`;
                }
                if (data.transcriptions && data.transcriptions.length > 0) {
                    html += `<h3>Transcripciones</h3>`;
                    data.transcriptions.forEach(t => {
                        const estadoLabel = t.status === 'completed' ? 'Completada' : (t.status === 'in_progress' ? 'En progreso' : (t.status || ''));
                        html += `<div class='transcripcion-card'>
                            <div class='transcripcion-header'>
                                <b>Transcriptor:</b> ${t.transcriber_name || ''}
                                <span class='transcripcion-estado'>${estadoLabel}</span>
                                <span class='transcripcion-fecha'>${t.completed_at || t.created_at || ''}</span>
                            </div>
                            <div class='transcripcion-body'>
                                <div><b>Título:</b><br>${t.report_title || ''}</div>
                                <div><b>Historia Clínica:</b><br>${t.clinical_history || ''}</div>
                                <div><b>Hallazgos:</b><br>${t.findings || ''}</div>
                                <div><b>Impresión:</b><br>${t.impression || ''}</div>
                                <div><b>Comentarios:</b><br>${t.comments || ''}</div>
                            </div>
                        </div>`;
                    });
                } else if (data.report && data.report.status === 'pending_transcription') {
                    html += `<p style="color:#035c67;margin-top:12px;"><i class='bx bx-time-five'></i> El informe fue enviado a transcripción. Aún no hay transcripción registrada.</p>`;
                }
                document.getElementById('seguimientoDetalle').innerHTML = html;
                document.getElementById('seguimientoModal').style.display = 'block';
            })
            .catch(function () {
                Swal.fire('Error', 'No se pudo cargar el seguimiento del estudio', 'error');
            });
    }

    document.getElementById('reportModalClose').onclick = closeReportModal;

    document.getElementById('reportModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeReportModal();
        }
    });

    // Cargar datos iniciales
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        if (rolUsuarioMedico !== 'Radiologo') {
            document.querySelector('.table-container').style.display = 'none';
            if (document.querySelector('.mhpacs-filters')) {
                document.querySelector('.mhpacs-filters').style.display = 'none';
            }
            document.getElementById('noStudiesMsg').style.display = 'block';
            document.getElementById('noStudiesText').textContent =
                'No tienes permisos para ver este apartado. Solo los médicos radiólogos pueden acceder.';
            return;
        }
        StudiesListCore.init({
            renderRow: renderStudyRowMedico,
            canLoad: function () { return rolUsuarioMedico === 'Radiologo'; },
            onEmpty: function () {
                return 'No tienes estudios asignados con los filtros seleccionados.';
            },
        });
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

    // Descarga DICOM con aviso de peso (fase 1 rendimiento MH-PACS)
    function downloadStudy(studyId) {
        downloadStudyWithConfirm(studyId, {
            downloadUrl: `https://medicloud.medicasa.hn/orthanc/studies/${encodeURIComponent(studyId)}/archive`,
            openInNewTab: false
        });
    }

    // Función para abrir el visor DICOM
    function openDicomViewer(seriesId) {
                document.getElementById('orthancViewer').src =
            `https://medicloud.medicasa.hn/orthanc/web-viewer/app/viewer.html?series=${seriesId}`;
        document.getElementById('viewerModal').style.display = 'block';
    }

    // Cerrar modal del visor DICOM
    document.querySelector('#viewerModal .close').onclick = function() {
        document.getElementById('viewerModal').style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera (visor / transcripción / seguimiento)
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal') && event.target.id !== 'reportModal') {
            event.target.style.display = 'none';
        }
    });

    document.querySelectorAll('#seguimientoModal .close, #viewerModal .close, #transcriptionModal .close').forEach(function (closeBtn) {
        closeBtn.onclick = function () {
            this.closest('.modal').style.display = 'none';
        };
    });

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
    <script src="download_study_helper.js?v=20260717b"></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

</body>
</html> 