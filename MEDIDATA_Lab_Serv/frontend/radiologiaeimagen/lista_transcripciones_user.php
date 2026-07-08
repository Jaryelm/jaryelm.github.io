<?php
include_once '../../backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/transcripcion_informe_modal.css">
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

<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/worklist_tecnico.php')">Tecnico Radiólogo</button>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/lista_estudios_medico.php')">Médico Radiólogo</button>
<button class="button" onclick="cambiarColor(this, '../../frontend/radiologiaeimagen/lista_transcripciones_user.php')">Transcriptores</button>


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
                    <h3>Tasa de Completado</h3>
                    <p id="accuracy">0%</p>
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
                    <option value="pending_transcription">Pendientes</option>
                    <option value="in_progress">En Progreso</option>
                    <option value="completed">Completados</option>
                </select>

                <input type="date" id="dateFilter" />
                <button onclick="applyFilters()" class="filter-btn">Aplicar Filtros</button>
            </div>

            <!-- Mensaje de acceso restringido -->
            <div id="noTranscriptionMsg" style="display:none; text-align:center; color:#035c67; font-size:18px; margin:30px 0; font-weight:600;">
                <span id="noTranscriptionText"></span>
            </div>

            <!-- Tabla de Transcripciones -->
            <div class="table-container">
                <table id="transcriptionsTable">
                    <thead>
                        <tr>
                            <th>ID Paciente</th>
                            <th>Nombre</th>
                            <th>Médico radiólogo</th>
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
            <div id="transcriptionModal" class="tx-modal">
                <div class="tx-modal-dialog">
                    <div class="tx-modal-header">
                        <div>
                            <h2>Transcripción de Informe</h2>
                            <p>Revise el dictado y el informe del médico, luego complete la transcripción formal.</p>
                        </div>
                        <button type="button" class="tx-modal-close" id="transcriptionModalClose" aria-label="Cerrar">&times;</button>
                    </div>
                    <div class="tx-modal-body">
                        <div class="tx-layout">
                            <div class="tx-panel">
                                <h3 class="tx-panel-title"><i class="bx bx-microphone"></i> Dictado e informe original</h3>
                                <div class="tx-panel-body">
                                    <div class="tx-audio-wrap">
                                        <p class="tx-label" style="font-weight:600;margin:0 0 8px;color:#035c67;">Dictado de voz</p>
                                        <audio id="dictationAudio" controls style="display:none;"></audio>
                                        <p id="noAudioMsg" class="tx-no-audio">Sin audio de dictado para este informe.</p>
                                        <div class="tx-audio-btns">
                                            <button type="button" onclick="playAudio()"><i class="bx bx-play"></i> Reproducir</button>
                                            <button type="button" onclick="pauseAudio()"><i class="bx bx-pause"></i> Pausar</button>
                                            <button type="button" onclick="stopAudio()"><i class="bx bx-stop"></i> Detener</button>
                                        </div>
                                    </div>
                                    <section class="tx-section tx-section-readonly">
                                        <h4 class="tx-section-title">Informe del médico</h4>
                                        <div class="tx-field">
                                            <label for="originalClinicalHistory">Historia clínica</label>
                                            <textarea id="originalClinicalHistory" rows="3" readonly></textarea>
                                        </div>
                                        <div class="tx-field">
                                            <label for="originalFindings">Hallazgos</label>
                                            <textarea id="originalFindings" rows="5" readonly></textarea>
                                        </div>
                                        <div class="tx-field">
                                            <label for="originalImpression">Impresión diagnóstica</label>
                                            <textarea id="originalImpression" rows="4" readonly></textarea>
                                        </div>
                                    </section>
                                </div>
                            </div>

                            <div class="tx-panel">
                                <h3 class="tx-panel-title"><i class="bx bx-edit"></i> Transcripción formal</h3>
                                <div class="tx-panel-body">
                                    <form id="transcriptionForm" class="tx-form">
                                        <input type="hidden" id="reportId" name="reportId">
                                        <section class="tx-section">
                                            <h4 class="tx-section-title">Datos del informe transcrito</h4>
                                            <div class="tx-field">
                                                <label for="reportTitle">Título del informe</label>
                                                <input type="text" id="reportTitle" required placeholder="Ej: Tomografía abdominal contrastada">
                                            </div>
                                            <div class="tx-field">
                                                <label for="clinicalHistory">Indicio / historia clínica</label>
                                                <textarea id="clinicalHistory" rows="3" required placeholder="Indicación clínica..."></textarea>
                                            </div>
                                            <div class="tx-field">
                                                <label for="findings">Hallazgos</label>
                                                <textarea id="findings" rows="5" required placeholder="Hallazgos transcritos..."></textarea>
                                            </div>
                                            <div class="tx-field">
                                                <label for="impression">Impresión diagnóstica</label>
                                                <textarea id="impression" rows="4" required placeholder="Impresión diagnóstica..."></textarea>
                                            </div>
                                            <div class="tx-field">
                                                <label for="comments">Recomendaciones</label>
                                                <textarea id="comments" rows="2" placeholder="Recomendaciones opcionales..."></textarea>
                                            </div>
                                        </section>
                                        <div class="tx-actions">
                                            <button type="button" class="tx-btn-draft" onclick="saveDraft()"><i class="bx bx-save"></i> Guardar borrador</button>
                                            <button type="button" class="tx-btn-complete" onclick="completeTranscription()"><i class="bx bx-check-circle"></i> Completar transcripción</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de Informe -->
            <div id="reportModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Informe Original</h2>
                    <div class="report-container">
                        <div class="form-group">
                            <label>Historia Clínica:</label>
                            <textarea id="reportClinicalHistory" rows="3" readonly></textarea>
                        </div>
                        <div class="form-group">
                            <label>Hallazgos:</label>
                            <textarea id="reportFindings" rows="6" readonly></textarea>
                        </div>
                        <div class="form-group">
                            <label>Impresión Diagnóstica:</label>
                            <textarea id="reportImpression" rows="4" readonly></textarea>
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

    #reportModal .form-group,
    #seguimientoModal .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 15px;
    }

    #reportModal .form-group label,
    #seguimientoModal .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    #reportModal .form-group textarea,
    #seguimientoModal .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin: 0;
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
    .btn-transcribe {
        background-color: #06adbf;
        color: white;
    }
    .btn-transcribe:hover {
        background-color: #0595a5;
    }
    .btn-history {
        background-color: #035c67;
        color: white;
    }
    .btn-history:hover {
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

    .original-report {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .original-report h3 {
        color: #035c67;
        margin-bottom: 15px;
    }

    .btn-report {
        background-color: #efc25b;
        color: white;
    }
    .btn-report:hover {
        background-color: #e0b44a;
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
            document.getElementById('completed-global').textContent = data.completed_global;
            document.getElementById('avg-time').textContent = formatAvgTime(data.avgTime);
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

            if (Array.isArray(data)) {
                data.forEach(transcription => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${transcription.patient_id}</td>
                        <td>${transcription.patient_name}</td>
                        <td>${transcription.radiologist_name || '—'}</td>
                        <td>${transcription.modality}</td>
                        <td>${transcription.description}</td>
                        <td>${formatDateTime(transcription.study_date)}</td>
                        <td>${formatStatus(transcription.status)}</td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="openTranscription('${transcription.report_id}')" class="btn-transcribe">
                                    <i class='bx bx-edit'></i> Transcribir
                                </button>
                                <button onclick="verSeguimiento('${transcription.report_id}', '${transcription.study_id || ''}')" class="btn-report">
                                    <i class='bx bx-file'></i> Ver Informe
                                </button>
                                <button onclick="downloadPDF('${transcription.report_id}')" class="btn-history">
                                    <i class='bx bx-download'></i> PDF
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                // Muestra un mensaje de error si la respuesta no es un array
                tbody.innerHTML = `<tr><td colspan=\"7\" style=\"text-align:center;color:red;\">${data.error || 'Error al cargar transcripciones'}</td></tr>`;
            }
        });
    }

    // Función para ver el informe original
    function viewReport(reportId) {
        fetch(`get_report.php?report_id=${reportId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.report) {
                    const report = data.report;
                    document.getElementById('reportClinicalHistory').textContent = report.clinical_history || 'No disponible';
                    document.getElementById('reportFindings').textContent = report.findings || 'No disponible';
                    document.getElementById('reportImpression').textContent = report.impression || 'No disponible';
                    
                    // Mostrar el modal
                    const reportModal = new bootstrap.Modal(document.getElementById('reportModal'));
                    reportModal.show();
                } else {
                    alert('Error al cargar el informe: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar el informe');
            });
    }

    function openTranscriptionModal() {
        document.getElementById('transcriptionModal').classList.add('is-open');
        document.body.classList.add('tx-modal-open');
    }

    function closeTranscriptionModal() {
        document.getElementById('transcriptionModal').classList.remove('is-open');
        document.body.classList.remove('tx-modal-open');
    }

    document.getElementById('transcriptionModalClose').onclick = closeTranscriptionModal;
    document.getElementById('transcriptionModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeTranscriptionModal();
        }
    });

    // Modificar la función openTranscription para cargar también el informe original
    function openTranscription(reportId) {
        document.getElementById('reportId').value = reportId;
        // Limpiar campos antes de cargar datos
        document.getElementById('originalClinicalHistory').value = '';
        document.getElementById('originalFindings').value = '';
        document.getElementById('originalImpression').value = '';
        document.getElementById('reportTitle').value = '';
        document.getElementById('clinicalHistory').value = '';
        document.getElementById('findings').value = '';
        document.getElementById('impression').value = '';
        document.getElementById('comments').value = '';
        // Cargar datos del informe original y la transcripción
        fetch(`get_report.php?report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.report) {
                document.getElementById('originalClinicalHistory').value = data.report.clinical_history || '';
                document.getElementById('originalFindings').value = data.report.findings || '';
                document.getElementById('originalImpression').value = data.report.impression || '';
                // Si existe transcripción previa, cargarla
                if (data.transcription) {
                    document.getElementById('reportTitle').value = data.transcription.report_title || '';
                    document.getElementById('clinicalHistory').value = data.transcription.clinical_history || '';
                    document.getElementById('findings').value = data.transcription.findings || '';
                    document.getElementById('impression').value = data.transcription.impression || '';
                    document.getElementById('comments').value = data.transcription.comments || '';
                }
                window.currentReport = data.report;
                openTranscriptionModal();
                const audioPlayback = document.getElementById('dictationAudio');
                const noAudioMsg = document.getElementById('noAudioMsg');
                if (data.report.audio_url) {
                    const audioPath = '../../backend/audios/' + data.report.audio_url;
                    audioPlayback.src = audioPath;
                    audioPlayback.style.display = 'block';
                    noAudioMsg.style.display = 'none';
                } else {
                    audioPlayback.src = '';
                    audioPlayback.style.display = 'none';
                    noAudioMsg.style.display = 'block';
                }
            } else {
                Swal.fire('Error', 'No se pudo cargar el informe', 'error');
            }
        });
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
            report_title: document.getElementById('reportTitle').value,
            clinical_history: document.getElementById('clinicalHistory').value,
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
        .then(async function (response) {
            let data = {};
            try {
                data = await response.json();
            } catch (e) {
                data = { success: false };
            }
            if (response.ok && data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: 'Transcripción guardada correctamente',
                    confirmButtonColor: '#06adbf'
                });
                closeTranscriptionModal();
                loadTranscriptions();
                loadStats();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudo guardar la transcripción',
                    confirmButtonColor: '#035c67'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire("Error", "Ocurrió un error al guardar la transcripción", "error");
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
        // Devuelve solo la fecha en formato local (ej: 13/5/2025)
        return date.toLocaleDateString('es-ES');
    }

    // Formatear estado
    function formatStatus(status) {
        const statuses = {
            'pending_transcription': 'En Transcripción',
            'final': 'Finalizado',
            'completed': 'Completado',
            'in_progress': 'En Progreso'
        };
        return statuses[status] || status;
    }

    document.querySelectorAll('#reportModal .close, #seguimientoModal .close').forEach(closeBtn => {
        closeBtn.onclick = function() {
            this.closest('.modal').style.display = 'none';
        }
    });

    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal') && event.target.id !== 'transcriptionModal') {
            event.target.style.display = 'none';
        }
    });

    // Bloqueo de acceso para el rol Radiologo
    document.addEventListener('DOMContentLoaded', function() {
        const rolUsuario = '<?php echo isset($_SESSION['rol']) ? $_SESSION['rol'] : ''; ?>';
        if (rolUsuario === 'Radiologo') {
            // Oculta la tabla, filtros y paginación
            document.querySelector('.table-container').style.display = 'none';
            if(document.querySelector('.filters')) document.querySelector('.filters').style.display = 'none';
            // Si tienes paginación, ocúltala aquí también
            if(document.getElementById('pagination')) document.getElementById('pagination').style.display = 'none';
            document.getElementById('noTranscriptionMsg').style.display = 'block';
            document.getElementById('noTranscriptionText').textContent = 'No tienes permisos para ver este apartado. Solo los técnicos radiólogos pueden acceder.';
            return; // No sigas cargando nada más
        }
        // Si no es radiologo, carga la tabla normalmente
        loadStats();
        loadTranscriptions();
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

    // Función para cargar y mostrar el seguimiento completo
    function verSeguimiento(reportId, studyId) {
        fetch(`get_full_study_details.php?${reportId ? 'report_id=' + reportId : 'study_id=' + studyId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire('Error', data.message || 'No se pudo cargar el seguimiento', 'error');
                    return;
                }
                let html = '';
                // Worklist
                if (data.worklist) {
                    html += `<h3>Datos del Estudio</h3><table class='seguimiento-table'><tr><td><b>ID Paciente:</b></td><td>${data.worklist.patient_id || ''}</td></tr><tr><td><b>Nombre:</b></td><td>${data.worklist.patient_name || ''}</td></tr><tr><td><b>Modalidad:</b></td><td>${data.worklist.modality || ''}</td></tr><tr><td><b>Descripción:</b></td><td>${data.worklist.study_description || ''}</td></tr><tr><td><b>Fecha:</b></td><td>${data.worklist.study_date || ''}</td></tr><tr><td><b>Técnico asignado:</b></td><td>${data.worklist.technician_name || ''}</td></tr><tr><td><b>Radiólogo asignado:</b></td><td>${data.worklist.radiologist_name || ''}</td></tr></table>`;
                }
                // Informe radiológico
                if (data.report) {
                    html += `<h3>Informe Radiológico</h3><table class='seguimiento-table'><tr><td><b>Historia Clínica:</b></td><td>${data.report.clinical_history || ''}</td></tr><tr><td><b>Hallazgos:</b></td><td>${data.report.findings || ''}</td></tr><tr><td><b>Impresión:</b></td><td>${data.report.impression || ''}</td></tr><tr><td><b>Radiólogo:</b></td><td>${data.report.radiologist_name || ''}</td></tr><tr><td><b>Estado:</b></td><td>${data.report.status || ''}</td></tr><tr><td><b>Fecha creación:</b></td><td>${data.report.created_at || ''}</td></tr></table>`;
                }
                // Transcripciones
                if (data.transcriptions && data.transcriptions.length > 0) {
                    html += `<h3>Transcripciones</h3>`;
                    data.transcriptions.forEach(t => {
                        html += `<div class='transcripcion-card'>
                            <div class='transcripcion-header'>
                                <b>Transcriptor:</b> ${t.transcriber_name || ''} &nbsp; <span class='transcripcion-estado'>${t.status || ''}</span> &nbsp; <span class='transcripcion-fecha'>${t.created_at || ''}</span>
                            </div>
                            <div class='transcripcion-body'>
                                <div><b>Historia Clínica:</b><br>${t.clinical_history || ''}</div>
                                <div><b>Hallazgos:</b><br>${t.findings || ''}</div>
                                <div><b>Impresión:</b><br>${t.impression || ''}</div>
                                <div><b>Comentarios:</b><br>${t.comments || ''}</div>
                            </div>
                        </div>`;
                    });
                }
                // Control de calidad
                if (data.quality_controls && data.quality_controls.length > 0) {
                    html += `<h3>Control de Calidad</h3><table class='seguimiento-table'><tr><th>Usuario</th><th>Calidad Imagen</th><th>Posicionamiento</th><th>Comentarios</th><th>Fecha</th></tr>`;
                    data.quality_controls.forEach(qc => {
                        html += `<tr><td>${qc.user_name || ''}</td><td>${qc.image_quality || ''}</td><td>${qc.positioning_quality || ''}</td><td>${qc.comments || ''}</td><td>${qc.created_at || ''}</td></tr>`;
                    });
                    html += `</table>`;
                }
                // Dosis
                if (data.doses && data.doses.length > 0) {
                    html += `<h3>Dosis de Radiación</h3><table class='seguimiento-table'><tr><th>Usuario</th><th>Valor</th><th>Unidad</th><th>Tiempo Exposición</th><th>Comentarios</th><th>Fecha</th></tr>`;
                    data.doses.forEach(d => {
                        html += `<tr><td>${d.user_name || ''}</td><td>${d.dose_value || ''}</td><td>${d.dose_unit || ''}</td><td>${d.exposure_time || ''}</td><td>${d.comments || ''}</td><td>${d.recorded_at || ''}</td></tr>`;
                    });
                    html += `</table>`;
                }
                // Incidencias
                if (data.incidents && data.incidents.length > 0) {
                    html += `<h3>Incidencias</h3><table class='seguimiento-table'><tr><th>Usuario</th><th>Tipo</th><th>Descripción</th><th>Acciones</th><th>Fecha</th></tr>`;
                    data.incidents.forEach(i => {
                        html += `<tr><td>${i.user_name || ''}</td><td>${i.incident_type || ''}</td><td>${i.description || ''}</td><td>${i.actions || ''}</td><td>${i.created_at || ''}</td></tr>`;
                    });
                    html += `</table>`;
                }
                // Repeticiones
                if (data.repeats && data.repeats.length > 0) {
                    html += `<h3>Repeticiones</h3><table class='seguimiento-table'><tr><th>Usuario</th><th>Razón</th><th>Comentarios</th><th>Fecha</th></tr>`;
                    data.repeats.forEach(r => {
                        html += `<tr><td>${r.user_name || ''}</td><td>${r.reason || ''}</td><td>${r.comments || ''}</td><td>${r.created_at || ''}</td></tr>`;
                    });
                    html += `</table>`;
                }
                // Historial de estados
                if (data.status_history && data.status_history.length > 0) {
                    html += `<h3>Historial de Estados</h3><table class='seguimiento-table'><tr><th>Usuario</th><th>Estado</th><th>Fecha</th><th>Comentario</th></tr>`;
                    data.status_history.forEach(h => {
                        html += `<tr><td>${h.user_name || ''}</td><td>${h.status || ''}</td><td>${h.changed_at || ''}</td><td>${h.comment || ''}</td></tr>`;
                    });
                    html += `</table>`;
                }
                document.getElementById('seguimientoDetalle').innerHTML = html;
                document.getElementById('seguimientoModal').style.display = 'block';
            });
    }

    /* Agrego estilos visuales para el modal de seguimiento */
    const style = document.createElement('style');
    style.innerHTML = `
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
    .seguimiento-table tr:hover {
      background: #eaf6f8;
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
    .transcripcion-fecha {
      color: #888;
      font-size: 0.95em;
    }
    .transcripcion-body > div {
      margin-bottom: 6px;
    }
    `;
    document.head.appendChild(style);
    </script>

    <!-- Alertas -->
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    
    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

</body>
</html> 