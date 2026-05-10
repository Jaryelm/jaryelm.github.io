<?php
// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

include_once '../../backend/registros/session_check.php';
$__orthanc_lab = require __DIR__ . '/../../backend/bd/orthanc_laboratorio.config.php';
session_start();
$rol_usuario = $_SESSION['rol'] ?? '';
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
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
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
                    <h3>Completados Globales</h3>
                    <p id="completed-global">0</p>
                </div>
                <div class="stat-card">
                    <h3>En Progreso</h3>
                    <p id="in-progress-count">0</p>
                </div>
                <div class="stat-card">
                    <h3>Cancelados Hoy</h3>
                    <p id="cancelled-today">0</p>
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
                <input type="text" id="searchBar" placeholder="Buscar por nombre, ID o descripción..." oninput="applyFilters()" />
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
                    <option value="cancelled">Cancelado</option>
                </select>

                <input type="date" id="dateFilter" />
                <button onclick="applyFilters()" class="filter-btn">Aplicar Filtros</button>
            </div>

            <!-- Tabla de Lista de Trabajo -->
            <div id="noWorklistMsg" style="display:none; text-align:center; color:#035c67; font-size:18px; margin:30px 0; font-weight:600;">
                <span id="noWorklistText"></span>
            </div>
            <div class="table-container">
            <table id="worklistTable">
                <thead>
                    <tr>
                        <th>ID Paciente</th>
                        <th>Nombre</th>
                        <th>Modalidad</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
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
            <div class="pagination" id="pagination"></div>

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
                        <iframe id="orthancViewer" src="" frameborder="0" allowfullscreen="true" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
                    </div>
                </div>
            </div>

            <!-- Modal de Registro de Dosis -->
            <div id="doseModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Registro de Dosis de Radiación</h2>
                    <form id="doseForm">
                        <input type="hidden" id="doseStudyId" name="studyId">
                        
                        <div class="form-group">
                            <label>Valor de Dosis:</label>
                            <input type="number" id="doseValue" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label>Unidad de Dosis:</label>
                            <select id="doseUnit" required>
                                <option value="mGy">mGy</option>
                                <option value="mSv">mSv</option>
                                <option value="Gy">Gy</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tiempo de Exposición (segundos):</label>
                            <input type="number" id="exposureTime" step="0.1" required>
                        </div>

                        <div class="form-group">
                            <label>Comentarios:</label>
                            <textarea id="doseComments" rows="3"></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit">Guardar Registro de Dosis</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de Cancelación de Estudio -->
            <div id="cancelModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeCancelModal()">&times;</span>
                    <h2>Cancelar Estudio</h2>
                    <form id="cancelForm">
                        <input type="hidden" id="cancelStudyId" name="studyId">
                        <div class="form-group">
                            <label>Motivo de Cancelación:</label>
                            <select id="cancelReason" required>
                                <option value="">Seleccione un motivo</option>
                                <option value="no_show">El paciente no se presentó</option>
                                <option value="admin_error">Error administrativo</option>
                                <option value="equipment_failure">Fallo de equipo</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Comentarios adicionales:</label>
                            <textarea id="cancelComments" rows="3"></textarea>
                        </div>
                        <div class="button-group">
                            <button type="submit">Confirmar Cancelación</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de Detalle de Cancelación -->
            <div id="cancelDetailModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeCancelDetailModal()">&times;</span>
                    <h2>Detalle de Cancelación</h2>
                    <div class="form-group">
                        <label>Motivo:</label>
                        <p id="cancelDetailReason"></p>
                    </div>
                    <div class="form-group">
                        <label>Comentarios:</label>
                        <p id="cancelDetailComments"></p>
                    </div>
                    <div class="form-group">
                        <label>Cancelado por:</label>
                        <p id="cancelDetailUser"></p>
                    </div>
                    <div class="form-group">
                        <label>Fecha y hora:</label>
                        <p id="cancelDetailDate"></p>
                    </div>
                </div>
            </div>

            <!-- Modal para ver detalles de control de calidad -->
            <div id="qualityDetailModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="this.closest('.modal').style.display='none'">&times;</span>
                    <h2>Detalle de Control de Calidad</h2>
                    <div class="form-group">
                        <label>Calidad de Imagen:</label>
                        <p id="detailImageQuality"></p>
                    </div>
                    <div class="form-group">
                        <label>Calidad de Posicionamiento:</label>
                        <p id="detailPositioningQuality"></p>
                    </div>
                    <div class="form-group">
                        <label>¿Necesita Repetición?</label>
                        <p id="detailNeedsRepeat"></p>
                    </div>
                    <div class="form-group">
                        <label>Comentarios:</label>
                        <p id="detailQualityComments"></p>
                    </div>
                    <div class="form-group">
                        <label>Registrado por:</label>
                        <p id="detailQualityUser"></p>
                    </div>
                    <div class="form-group">
                        <label>Fecha y hora:</label>
                        <p id="detailQualityDate"></p>
                    </div>
                </div>
            </div>

            <!-- Modal para ver detalles de dosis -->
            <div id="doseDetailModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="this.closest('.modal').style.display='none'">&times;</span>
                    <h2>Detalle de Dosis</h2>
                    <div class="form-group">
                        <label>Valor:</label>
                        <p id="detailDoseValue"></p>
                    </div>
                    <div class="form-group">
                        <label>Unidad:</label>
                        <p id="detailDoseUnit"></p>
                    </div>
                    <div class="form-group">
                        <label>Tiempo de Exposición:</label>
                        <p id="detailExposureTime"></p>
                    </div>
                    <div class="form-group">
                        <label>Comentarios:</label>
                        <p id="detailDoseComments"></p>
                    </div>
                    <div class="form-group">
                        <label>Registrado por:</label>
                        <p id="detailDoseUser"></p>
                    </div>
                    <div class="form-group">
                        <label>Fecha y hora:</label>
                        <p id="detailDoseDate"></p>
                    </div>
                </div>
            </div>

            <!-- Modal para ver detalles de repetición -->
            <div id="repeatDetailModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="this.closest('.modal').style.display='none'">&times;</span>
                    <h2>Detalle de Repetición</h2>
                    <div class="form-group">
                        <label>Motivo:</label>
                        <p id="detailRepeatReason"></p>
                    </div>
                    <div class="form-group">
                        <label>Comentarios:</label>
                        <p id="detailRepeatComments"></p>
                    </div>
                    <div class="form-group">
                        <label>Registrado por:</label>
                        <p id="detailRepeatUser"></p>
                    </div>
                    <div class="form-group">
                        <label>Fecha y hora:</label>
                        <p id="detailRepeatDate"></p>
                    </div>
                </div>
            </div>

            <!-- Modal para ver detalles de incidencia -->
            <div id="incidentDetailModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="this.closest('.modal').style.display='none'">&times;</span>
                    <h2>Detalle de Incidencia</h2>
                    <div class="form-group">
                        <label>Tipo:</label>
                        <p id="detailIncidentType"></p>
                    </div>
                    <div class="form-group">
                        <label>Descripción:</label>
                        <p id="detailIncidentDescription"></p>
                    </div>
                    <div class="form-group">
                        <label>Acciones:</label>
                        <p id="detailIncidentActions"></p>
                    </div>
                    <div class="form-group">
                        <label>Registrado por:</label>
                        <p id="detailIncidentUser"></p>
                    </div>
                    <div class="form-group">
                        <label>Fecha y hora:</label>
                        <p id="detailIncidentDate"></p>
                    </div>
                </div>
            </div>

            <!-- Modal de Asignación de Radiólogo -->
            <div id="assignRadiologistModal" class="modal">
                <div class="modal-content" style="max-width: 1200px;">
                    <span class="close" onclick="closeAssignModal()">&times;</span>
                    <h2>Finalizar Estudio y Asignar Médico</h2>
                    
                    <!-- Información del estudio -->
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0; color: #035c67;">Información del Estudio</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <strong>Paciente:</strong> <span id="modalPatientName">-</span><br>
                                <strong>DNI:</strong> <span id="modalPatientId">-</span>
                            </div>
                            <div>
                                <strong>Modalidad:</strong> <span id="modalModality">-</span><br>
                                <strong>Descripción:</strong> <span id="modalDescription">-</span>
                            </div>
                        </div>
                    </div>

                    <form id="assignRadiologistForm">
                        <input type="hidden" id="assignStudyId">
                        
                        <!-- Selección de Radiólogo -->
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="radiologistSelect"><strong>Seleccionar Médico Radiólogo:</strong></label>
                            <select id="radiologistSelect" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px;">
                                <option value="">Seleccione un radiólogo...</option>
                            </select>
                        </div>

                        <!-- Selección de Factura para Honorarios -->
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="facturaSelect"><strong>Vincular con Factura (Para Honorarios):</strong></label>
                            <select id="facturaSelect" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px;">
                                <option value="">No vincular con factura (sin honorarios)</option>
                            </select>
                            <small style="color: #666; display: block; margin-top: 5px;">
                                💡 Solo se muestran facturas <strong>PENDIENTES</strong> del paciente mismo.
                            </small>
                            <small style="color: #28a745; display: block; margin-top: 2px;">
                                ✅ Las facturas pagadas no aparecen en esta lista por seguridad administrativa.
                            </small>
                        </div>

                        <!-- Mostrar información de la factura seleccionada -->
                        <div id="facturaInfo" style="display: none; background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                            <h4 style="margin-top: 0; color: #155724;">Factura Seleccionada:</h4>
                            <div class="factura-resumen">
                                <div>
                                    <strong>Número:</strong> <span id="selectedFacturaNumber">-</span><br>
                                    <strong>Paciente:</strong> <span id="selectedFacturaPaciente">-</span>
                                </div>
                                <div>
                                    <strong>Total:</strong> <span id="selectedFacturaTotal">-</span><br>
                                    <strong>Fecha:</strong> <span id="selectedFacturaFecha">-</span>
                                </div>
                            </div>
                            <div style="margin-top: 10px;">
                                <strong>Médico Actual:</strong> 
                                <span id="selectedFacturaMedicoActual" style="padding: 5px 10px; border-radius: 4px; font-weight: bold;">-</span>
                            </div>
                        </div>

                        <!-- Notas adicionales -->
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="assignmentNotes"><strong>Notas adicionales (opcional):</strong></label>
                            <textarea id="assignmentNotes" rows="3" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px;" 
                                      placeholder="Comentarios sobre la asignación..."></textarea>
                        </div>

                        <div class="button-group" style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" onclick="closeAssignModal()" style="background: #035c67; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer;">
                                Cancelar
                            </button>
                            <button type="submit" style="background: #06adbf; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                                Asignar y Finalizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de Información de Asignación -->
            <div id="assignmentInfoModal" class="modal">
                <div class="modal-content" style="max-width: 600px;">
                    <span class="close" onclick="closeAssignmentInfoModal()">&times;</span>
                    <h2>Información de Asignación</h2>
                    
                    <div id="assignmentInfoContent">
                        <!-- La información se cargará dinámicamente -->
                    </div>
                    
                    <div class="button-group" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                        <button onclick="closeAssignmentInfoModal()" class="btn-cerrar-modal">
                            Cerrar
                        </button>
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

    /* Estilos para el botón de dosis */
    .dose-btn {
        background-color: #ff9800;
        color: white;
    }

    .dose-btn:hover {
        background-color: #f57c00;
    }

    /* Estilos para el modal de dosis */
    #doseModal .modal-content {
        max-width: 500px;
    }

    #doseModal .form-group {
        margin-bottom: 15px;
    }

    #doseModal label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    #doseModal input[type="number"],
    #doseModal select,
    #doseModal textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    #doseModal .button-group {
        margin-top: 20px;
        text-align: right;
    }

    #doseModal button[type="submit"] {
        background-color: #06adbf;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    #doseModal button[type="submit"]:hover {
        background-color: #0595a5;
    }

    .btn-cancel {
        background-color:rgb(0, 0, 0);
        color: white;
    }
    .btn-cancel:disabled {
        background-color: #ccc;
        color: #888;
        cursor: not-allowed;
    }
    .btn-cancel.cancelled {
        background-color: #607d8b;
        color: white;
        cursor: pointer;
    }

    .btn-assignment {
        background-color: #28a745;
        color: white;
    }
    .btn-assignment:hover {
        background-color: #218838;
    }

    .detail-container {
        margin-top: 20px;
    }
    .detail-row {
        margin-bottom: 15px;
        display: flex;
        align-items: flex-start;
    }
    .detail-row label {
        font-weight: bold;
        width: 200px;
        flex-shrink: 0;
    }
    .detail-row span {
        flex-grow: 1;
        word-break: break-word;
    }

    /* Estilos para el botón Finalizar */
    .btn-complete {
        background-color: #035c67;
        color: white;
    }
    .btn-complete:hover {
        background-color: #023a43;
    }

    /* Estilos específicos para el modal de asignación */
    #assignRadiologistModal .modal-content {
        max-height: 90vh;
        overflow-y: auto;
    }

    /* Estilos para Select2 en el modal */
    #assignRadiologistModal .select2-container {
        z-index: 1051 !important;
    }

    #assignRadiologistModal .select2-dropdown {
        z-index: 1052 !important;
    }

    #assignRadiologistModal .select2-selection {
        border: 2px solid #ddd !important;
        border-radius: 6px !important;
        min-height: 40px !important;
    }

    #assignRadiologistModal .select2-selection__rendered {
        padding: 8px 12px !important;
        line-height: 24px !important;
    }

    #assignRadiologistModal .select2-selection__placeholder {
        color: #999 !important;
    }

    /* Estilo para opciones de facturas pendientes */
    .factura-pendiente {
        background-color: #f8fff4;
        border-left: 3px solid #28a745;
    }

    .factura-con-medico {
        background-color: #e3f2fd;
        border-left: 3px solid #2196f3;
    }

    .factura-sin-medico {
        background-color: #fff8e1;
        border-left: 3px solid #ff9800;
    }

    /* Estilo para el badge de estado */
    .status-badge {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsivo para el modal */
    @media (max-width: 768px) {
        #assignRadiologistModal .modal-content {
            width: 95%;
            margin: 10px auto;
            max-height: 95vh;
        }
        
        #assignRadiologistModal .form-group {
            margin-bottom: 15px;
        }
    }
    </style>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
    // Variables globales para paginación y datos
    let allStudies = [];
    let currentPage = 1;
    const studiesPerPage = 10;

    document.addEventListener('DOMContentLoaded', function() {
        const rolUsuario = '<?php echo $rol_usuario; ?>';
        if (rolUsuario === 'Radiologo') {
            // Oculta la tabla, filtros y paginación
            document.querySelector('.table-container').style.display = 'none';
            document.getElementById('pagination').style.display = 'none';
            document.querySelector('.filters').style.display = 'none';
            document.getElementById('noWorklistMsg').style.display = 'block';
            document.getElementById('noWorklistText').textContent = 'No tienes permisos para ver este apartado. Solo los técnicos radiólogos pueden acceder.';
            return; // No sigas cargando nada más
        }
        // Si no es radiologo, carga la tabla y las estadísticas normalmente
        loadStats();
        loadWorklist();
    });

    function updateTable() {
        const rolUsuario = '<?php echo $rol_usuario; ?>';
        const noWorklistMsg = document.getElementById('noWorklistMsg');
        const noWorklistText = document.getElementById('noWorklistText');

        if (rolUsuario === 'Radiologo') {
            // Oculta la tabla y muestra el mensaje
            noWorklistMsg.style.display = 'block';
            noWorklistText.textContent = 'No tienes permisos para ver este apartado. Solo los técnicos radiólogos pueden acceder.';
            document.querySelector('.table-container').style.display = 'none';
            return;
        } else {
            // Si no es radiologo, muestra la tabla normalmente
            document.querySelector('.table-container').style.display = '';
            noWorklistMsg.style.display = 'none';
        }

        // ... el resto de la función updateTable ...
    }

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
            document.getElementById('completed-global').textContent = data.completed_global;
            document.getElementById('in-progress-count').textContent = data.in_progress;
            document.getElementById('cancelled-today').textContent = data.cancelled_today;
            document.getElementById('avg-time').textContent = formatAvgTime(data.avg_time);
            document.getElementById('quality-percentage').textContent = `${data.quality}%`;
        } catch (error) {
            console.error('Error loading stats:', error);
            swal("Error", "No se pudieron cargar las estadísticas", "error");
        }
    }

    // Nueva función para formatear el tiempo promedio
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

    // Función para aplicar filtros y búsqueda
    function applyFilters() {
        currentPage = 1;
        updateTable();
    }

    // Cargar todos los estudios una sola vez
    async function loadWorklist() {
        try {
            const filters = {
                modality: document.getElementById('modalityFilter').value,
                priority: document.getElementById('priorityFilter').value,
                status: document.getElementById('statusFilter').value,
                date: document.getElementById('dateFilter').value
            };
            // Traer todos los estudios (sin búsqueda, solo filtros principales)
            const response = await fetch('get_worklist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(filters)
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            allStudies = await response.json();
            updateTable();
        } catch (error) {
            console.error('Error loading worklist:', error);
            swal("Error", "No se pudo cargar la lista de trabajo", "error");
        }
    }

    // Filtrar estudios según búsqueda y filtros
    function filterStudies() {
        const search = document.getElementById('searchBar').value.trim().toLowerCase();
        const modality = document.getElementById('modalityFilter').value;
        const priority = document.getElementById('priorityFilter').value;
        const status = document.getElementById('statusFilter').value;
        const date = document.getElementById('dateFilter').value;
        return allStudies.filter(study => {
            const patientName = (study.patient_name || '').toLowerCase();
            const patientId = (study.patient_id || '').toLowerCase();
            const description = (study.description || '').toLowerCase();
            // Filtro de búsqueda
            const matchesSearch = (
                patientName.includes(search) ||
                patientId.includes(search) ||
                description.includes(search)
            );
            // Filtros adicionales
            const matchesModality = !modality || study.modality === modality;
            const matchesPriority = !priority || study.priority === priority;
            const matchesStatus = !status || study.status === status;
            // Filtro de fecha (solo comparar la parte de la fecha, no la hora)
            let matchesDate = true;
            if (date) {
                const studyDate = study.study_date ? study.study_date.substring(0, 10) : '';
                matchesDate = studyDate === date;
            }
            return matchesSearch && matchesModality && matchesPriority && matchesStatus && matchesDate;
        });
    }

    // Actualizar la tabla según la página y búsqueda
    function updateTable() {
        const filtered = filterStudies();
        const totalPages = Math.ceil(filtered.length / studiesPerPage) || 1;
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * studiesPerPage;
        const end = start + studiesPerPage;
        const studiesToShow = filtered.slice(start, end);
        const tbody = document.getElementById('worklistBody');
        tbody.innerHTML = '';
        if (studiesToShow.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 20px;">No se encontraron estudios con los filtros seleccionados</td></tr>`;
        } else {
            studiesToShow.forEach(study => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${study.patient_id || 'N/A'}</td>
                    <td>${study.patient_name || 'N/A'}</td>
                    <td>${study.Modality || study.modality || 'N/A'}</td>
                    <td>${study.description || 'Sin descripción'}</td>
                    <td>${formatStudyDate(study.study_date)}</td>
                    <td>${formatPriority(study.priority)}</td>
                    <td>${formatStatus(study.status)}</td>
                    <td>
                        <div class="action-buttons">
                            <button onclick="openDicomViewer('${study.series_id}')" class="btn-view"><i class='bx bx-show'></i> Ver</button>
                            <button onclick="openQualityControl('${study.id}')" class="btn-quality"><i class='bx bx-check-circle'></i> Control</button>
                            <button onclick="openIncident('${study.id}')" class="btn-incident"><i class='bx bx-error'></i> Incidencia</button>
                            <button onclick="openRepeat('${study.id}')" class="btn-repeat"><i class='bx bx-refresh'></i> Repetir</button>
                            <button onclick="openDoseModal('${study.id}')" class="dose-btn"><i class='bx bx-radiation'></i> Dosis</button>
                            <button onclick="showAssignmentInfo('${study.id}')" class="btn-assignment"><i class='bx bx-user-check'></i> Asignado</button>
                            <button onclick="${study.status === 'cancelled' ? `showCancelDetail('${study.id}')` : `openCancelModal('${study.id}', '${study.status}')`}" class="btn-cancel" ${study.status === 'cancelled' ? '' : ''}><i class='bx bx-x-circle'></i> ${study.status === 'cancelled' ? 'Ver motivo' : 'Cancelar'}</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
                row.dataset.studyId = study.id;
                row.dataset.status = study.status;
                row.dataset.seriesId = study.series_id;
                updateActionButtons(row);
            });
        }
        updatePagination(totalPages);
    }

    // Paginación visual
    function updatePagination(totalPages) {
        let paginationDiv = document.getElementById('pagination');
        if (!paginationDiv) {
            paginationDiv = document.createElement('div');
            paginationDiv.id = 'pagination';
            paginationDiv.className = 'pagination';
            document.querySelector('.table-container').appendChild(paginationDiv);
        }
        paginationDiv.innerHTML = '';
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        // Botón anterior
        const prevButton = document.createElement('button');
        prevButton.textContent = 'Anterior';
        prevButton.disabled = currentPage === 1;
        prevButton.onclick = () => { if (currentPage > 1) { currentPage--; updateTable(); } };
        paginationDiv.appendChild(prevButton);
        if (startPage > 1) {
            const ellipsisBefore = document.createElement('button');
            ellipsisBefore.textContent = '...';
            ellipsisBefore.disabled = true;
            paginationDiv.appendChild(ellipsisBefore);
        }
        for (let i = startPage; i <= endPage; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.classList.toggle('active', i === currentPage);
            button.onclick = () => { currentPage = i; updateTable(); };
            paginationDiv.appendChild(button);
        }
        if (endPage < totalPages) {
            const ellipsisAfter = document.createElement('button');
            ellipsisAfter.textContent = '...';
            ellipsisAfter.disabled = true;
            paginationDiv.appendChild(ellipsisAfter);
        }
        // Botón siguiente
        const nextButton = document.createElement('button');
        nextButton.textContent = 'Siguiente';
        nextButton.disabled = currentPage === totalPages;
        nextButton.onclick = () => { if (currentPage < totalPages) { currentPage++; updateTable(); } };
        paginationDiv.appendChild(nextButton);
    }

    // Actualizar tabla al escribir en la barra de búsqueda
    document.getElementById('searchBar').addEventListener('input', function() {
        currentPage = 1;
        updateTable();
    });

    function openDicomViewer(seriesId) {
        console.log('Abriendo visor DICOM para series:', seriesId);
        
        const iframe = document.getElementById('orthancViewer');
        const modal = document.getElementById('viewerModal');
        
        // Mostrar indicador de carga
        iframe.style.background = '#000';
        iframe.style.display = 'block';
        
        // Construir URL directa (funcionaba antes)
        const viewerUrl = <?php echo json_encode($__orthanc_lab['viewer_series_prefix']); ?> + seriesId;
        console.log('URL del visor:', viewerUrl);
        
        // Configurar el iframe
        iframe.src = viewerUrl;
        
        // Mostrar el modal
        modal.style.display = 'block';
        
        // Agregar event listeners para debugging
        iframe.onload = function() {
            console.log('Iframe cargado exitosamente');
            iframe.style.background = 'transparent';
        };
        
        iframe.onerror = function() {
            console.error('Error cargando el iframe');
            // Fallback a URL directa si el proxy falla
            const directUrl = <?php echo json_encode($__orthanc_lab['viewer_series_prefix']); ?> + seriesId;
            console.log('Intentando URL directa:', directUrl);
            iframe.src = directUrl;
        };
        
        // Timeout para verificar si carga
        setTimeout(() => {
            if (iframe.contentDocument && iframe.contentDocument.body) {
                console.log('Contenido del iframe cargado');
            } else {
                console.log('Iframe aún cargando...');
                // Si después de 5 segundos no carga, mostrar mensaje
                iframe.style.background = '#333';
                iframe.innerHTML = '<div style="color: white; text-align: center; padding: 20px;">Cargando visor DICOM...</div>';
            }
        }, 5000);
    }

    // Función para abrir el visor en una nueva ventana
    function openDicomViewerNewWindow(seriesId) {
        console.log('Abriendo visor DICOM en nueva ventana para series:', seriesId);
        
        const viewerUrl = <?php echo json_encode($__orthanc_lab['viewer_series_prefix']); ?> + seriesId;
        console.log('URL del visor (nueva ventana):', viewerUrl);
        
        // Abrir en nueva ventana
        const newWindow = window.open(viewerUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
        
        if (newWindow) {
            console.log('Nueva ventana abierta exitosamente');
        } else {
            console.error('No se pudo abrir la nueva ventana');
            swal('Error', 'No se pudo abrir el visor en nueva ventana. Verifique que el bloqueador de ventanas emergentes esté deshabilitado.', 'error');
        }
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
        const saveButton = document.querySelector('#incidentForm button[type="submit"]');

        fetch(`get_incident.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.incident) {
                    const incidentData = data.incident;
                    document.getElementById('incidentType').value = incidentData.incident_type || '';
                    document.getElementById('incidentDescription').value = incidentData.description || '';
                    document.getElementById('incidentActions').value = incidentData.actions || '';
                    saveButton.disabled = true;
                    saveButton.title = "La incidencia ya ha sido registrada";
                } else {
                    document.getElementById('incidentType').value = '';
                    document.getElementById('incidentDescription').value = '';
                    document.getElementById('incidentActions').value = '';
                    saveButton.disabled = false;
                    saveButton.title = "";
                }
                document.getElementById('incidentModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching incident data:', error);
                swal("Error", "No se pudieron cargar los datos de la incidencia", "error");
                document.getElementById('incidentType').value = '';
                document.getElementById('incidentDescription').value = '';
                document.getElementById('incidentActions').value = '';
                saveButton.disabled = false;
                saveButton.title = "";
                document.getElementById('incidentModal').style.display = 'block';
            });
    }

    // Abrir repetición de estudio
    function openRepeat(studyId) {
        document.getElementById('repeatStudyId').value = studyId;
        const saveButton = document.querySelector('#repeatForm button[type="submit"]');

        fetch(`get_repeat.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.repeat) {
                    document.getElementById('repeatReason').value = data.repeat.reason || '';
                    document.getElementById('repeatComments').value = data.repeat.comments || '';
                    saveButton.disabled = true;
                    saveButton.title = "Ya existe un registro de repetición";
                } else {
                    document.getElementById('repeatReason').value = '';
                    document.getElementById('repeatComments').value = '';
                    saveButton.disabled = false;
                    saveButton.title = "";
                }
                document.getElementById('repeatModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching repeat data:', error);
                swal("Error", "No se pudieron cargar los datos de repetición", "error");
                document.getElementById('repeatReason').value = '';
                document.getElementById('repeatComments').value = '';
                saveButton.disabled = false;
                saveButton.title = "";
                document.getElementById('repeatModal').style.display = 'block';
            });
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
                swal("Éxito", "Repetición programada correctamente", "success");
                document.getElementById('repeatModal').style.display = 'none';
                loadWorklist();
            } else {
                swal("Error", "Error al programar la repetición", "error");
            }
        })
        .catch(error => {
            swal("Error", "Ocurrió un error al procesar la solicitud", "error");
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
            'completed': 'Completado',
            'cancelled': 'Cancelado'
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

    // Función para abrir el modal de dosis
    function openDoseModal(studyId) {
        document.getElementById('doseStudyId').value = studyId;
        const saveButton = document.querySelector('#doseForm button[type="submit"]');

        fetch(`get_dose.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.dose) {
                    document.getElementById('doseValue').value = data.dose.dose_value || '';
                    document.getElementById('doseUnit').value = data.dose.dose_unit || 'mGy';
                    document.getElementById('exposureTime').value = data.dose.exposure_time || '';
                    document.getElementById('doseComments').value = data.dose.comments || '';
                    saveButton.disabled = true;
                    saveButton.title = "Ya existe un registro de dosis";
                } else {
                    document.getElementById('doseValue').value = '';
                    document.getElementById('doseUnit').value = 'mGy';
                    document.getElementById('exposureTime').value = '';
                    document.getElementById('doseComments').value = '';
                    saveButton.disabled = false;
                    saveButton.title = "";
                }
                document.getElementById('doseModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching dose data:', error);
                swal("Error", "No se pudieron cargar los datos de dosis", "error");
                document.getElementById('doseValue').value = '';
                document.getElementById('doseUnit').value = 'mGy';
                document.getElementById('exposureTime').value = '';
                document.getElementById('doseComments').value = '';
                saveButton.disabled = false;
                saveButton.title = "";
                document.getElementById('doseModal').style.display = 'block';
            });
    }

    // Función para guardar el registro de dosis
    document.getElementById('doseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const studyId = document.getElementById('doseStudyId').value;
        const doseValue = document.getElementById('doseValue').value;
        const doseUnit = document.getElementById('doseUnit').value;
        const exposureTime = document.getElementById('exposureTime').value;
        const comments = document.getElementById('doseComments').value;

        fetch('save_dose.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                study_id: studyId,
                dose_value: doseValue,
                dose_unit: doseUnit,
                exposure_time: exposureTime,
                comments: comments
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                swal("Éxito", "Registro de dosis guardado correctamente", "success");
                document.getElementById('doseModal').style.display = 'none';
                loadWorklist(); // Recargar la lista de trabajo
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            swal("Error", error.message, "error");
        });
    });

    // Actualizar la función de acciones
    function updateActionButtons(row) {
        const actionsCell = row.querySelector('td:last-child .action-buttons');
        const studyId = row.dataset.studyId;
        const status = row.dataset.status;
        const seriesId = row.dataset.seriesId;

        let buttons = `
            <button onclick="openDicomViewer('${seriesId}')" class="btn-view">
                <i class='bx bx-show'></i> Ver
            </button>
            <button onclick="openDicomViewerNewWindow('${seriesId}')" class="btn-view" style="background-color: #28a745;">
                <i class='bx bx-external-link'></i> Nueva Ventana
            </button>
            <button onclick="showAssignmentInfo('${studyId}')" class="btn-assignment">
                <i class='bx bx-user-check'></i> Asignado
            </button>
        `;

        if (status === 'pending' || status === 'in_progress') {
            Promise.all([
                fetch(`get_quality_control.php?study_id=${studyId}`).then(r => r.json()),
                fetch(`get_dose.php?study_id=${studyId}`).then(r => r.json()),
                fetch(`get_incident.php?study_id=${studyId}`).then(r => r.json()),
                fetch(`get_repeat.php?study_id=${studyId}`).then(r => r.json())
            ]).then(([qualityData, doseData, incidentData, repeatData]) => {
                // Botones dinámicos
                if (qualityData.success && qualityData.quality_control) {
                    buttons += `<button onclick="showQualityDetail('${studyId}')" class="btn-quality"><i class='bx bx-check-circle'></i> Ver Control</button>`;
                } else {
                    buttons += `<button onclick="openQualityControl('${studyId}')" class="btn-quality"><i class='bx bx-check-circle'></i> Control</button>`;
                }
                if (doseData.success && doseData.dose) {
                    buttons += `<button onclick="showDoseDetail('${studyId}')" class="dose-btn"><i class='bx bx-radiation'></i> Ver Dosis</button>`;
                } else {
                    buttons += `<button onclick="openDoseModal('${studyId}')" class="dose-btn"><i class='bx bx-radiation'></i> Dosis</button>`;
                }
                if (incidentData.success && incidentData.incident) {
                    buttons += `<button onclick="showIncidentDetail('${studyId}')" class="btn-incident"><i class='bx bx-error'></i> Ver Incidencia</button>`;
                } else {
                    buttons += `<button onclick="openIncident('${studyId}')" class="btn-incident"><i class='bx bx-error'></i> Incidencia</button>`;
                }
                if (repeatData.success && repeatData.repeat) {
                    buttons += `<button onclick="showRepeatDetail('${studyId}')" class="btn-repeat"><i class='bx bx-refresh'></i> Ver Repetición</button>`;
                } else {
                    buttons += `<button onclick="openRepeat('${studyId}')" class="btn-repeat"><i class='bx bx-refresh'></i> Repetir</button>`;
                }
                // Botón Finalizar
                buttons += `<button onclick="openAssignModal('${studyId}')" class="btn-complete"><i class='bx bx-check'></i> Finalizar</button>`;
                // Cancelar o ver motivo
                if (status === 'cancelled') {
                    buttons += `<button onclick="showCancelDetail('${studyId}')" class="btn-cancel"><i class='bx bx-x-circle'></i> Ver motivo</button>`;
                } else {
                    buttons += `<button onclick="openCancelModal('${studyId}', '${status}')" class="btn-cancel"><i class='bx bx-x-circle'></i> Cancelar</button>`;
                }
                actionsCell.innerHTML = buttons;
            });
        } else if (status === 'cancelled') {
            // Solo mostrar el botón de ver motivo y el de ver
            buttons += `<button onclick="showCancelDetail('${studyId}')" class="btn-cancel"><i class='bx bx-x-circle'></i> Ver motivo</button>`;
            actionsCell.innerHTML = buttons;
        } else if (status === 'completed') {
            // Solo mostrar el botón Ver
            actionsCell.innerHTML = buttons;
        } else {
            // Para otros estados, solo el botón Ver
            actionsCell.innerHTML = buttons;
        }
    }

    function openCancelModal(studyId, status) {
        if (status === 'cancelled') return;
        document.getElementById('cancelStudyId').value = studyId;
        document.getElementById('cancelReason').value = '';
        document.getElementById('cancelComments').value = '';
        document.getElementById('cancelModal').style.display = 'block';
    }
    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }
    document.getElementById('cancelForm').onsubmit = function(e) {
        e.preventDefault();
        const studyId = document.getElementById('cancelStudyId').value;
        const reason = document.getElementById('cancelReason').value;
        const comments = document.getElementById('cancelComments').value;
        if (!reason) {
            swal('Error', 'Debe seleccionar un motivo de cancelación', 'error');
            return;
        }
        fetch('cancel_study.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ study_id: studyId, reason, comments })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                swal('Éxito', 'Estudio cancelado correctamente', 'success');
                closeCancelModal();
                loadWorklist();
                loadStats();
            } else {
                swal('Error', data.message || 'No se pudo cancelar el estudio', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            swal('Error', 'Ocurrió un error al cancelar el estudio', 'error');
        });
    };

    function showCancelDetail(studyId) {
        fetch(`get_cancellation.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cancellation) {
                    const motivo = translateCancelReason(data.cancellation.reason);
                    document.getElementById('cancelDetailReason').textContent = motivo;
                    document.getElementById('cancelDetailComments').textContent = data.cancellation.comments || '-';
                    document.getElementById('cancelDetailUser').textContent = data.cancellation.user_name || '-';
                    document.getElementById('cancelDetailDate').textContent = formatDateTime(data.cancellation.created_at);
                    document.getElementById('cancelDetailModal').style.display = 'block';
                } else {
                    swal('Info', 'No se encontró información de cancelación para este estudio.', 'info');
                }
            })
            .catch(error => {
                swal('Error', 'No se pudo obtener el detalle de cancelación', 'error');
            });
    }
    function closeCancelDetailModal() {
        document.getElementById('cancelDetailModal').style.display = 'none';
    }
    function translateCancelReason(reason) {
        const reasons = {
            'no_show': 'El paciente no se presentó',
            'admin_error': 'Error administrativo',
            'equipment_failure': 'Fallo de equipo',
            'other': 'Otro'
        };
        return reasons[reason] || reason;
    }
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString.replace(' ', 'T'));
        return date.toLocaleString('es-ES');
    }

    // Agregar las nuevas funciones para mostrar detalles
    function showQualityDetail(studyId) {
        fetch(`get_quality_control.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.quality_control) {
                    const qc = data.quality_control;
                    // Traducción de valores a español
                    const imageQualityMap = {
                        'excellent': 'Excelente',
                        'acceptable': 'Aceptable',
                        'poor': 'Pobre',
                        'unacceptable': 'Inaceptable'
                    };
                    const positioningQualityMap = {
                        'correct': 'Correcto',
                        'suboptimal': 'Subóptimo',
                        'incorrect': 'Incorrecto'
                    };
                    document.getElementById('detailImageQuality').textContent = imageQualityMap[qc.image_quality] || qc.image_quality;
                    document.getElementById('detailPositioningQuality').textContent = positioningQualityMap[qc.positioning_quality] || qc.positioning_quality;
                    document.getElementById('detailNeedsRepeat').textContent = qc.needs_repeat ? 'Sí' : 'No';
                    document.getElementById('detailQualityComments').textContent = qc.comments || '-';
                    document.getElementById('detailQualityUser').textContent = qc.user_name;
                    document.getElementById('detailQualityDate').textContent = qc.created_at;
                    document.getElementById('qualityDetailModal').style.display = 'block';
                }
            });
    }

    function showDoseDetail(studyId) {
        fetch(`get_dose.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.dose) {
                    const dose = data.dose;
                    document.getElementById('detailDoseValue').textContent = dose.dose_value;
                    document.getElementById('detailDoseUnit').textContent = dose.dose_unit;
                    document.getElementById('detailExposureTime').textContent = dose.exposure_time;
                    document.getElementById('detailDoseComments').textContent = dose.comments || '-';
                    document.getElementById('detailDoseUser').textContent = dose.user_name;
                    document.getElementById('detailDoseDate').textContent = dose.created_at;
                    document.getElementById('doseDetailModal').style.display = 'block';
                }
            });
    }

    function showRepeatDetail(studyId) {
        fetch(`get_repeat.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.repeat) {
                    const repeat = data.repeat;
                    document.getElementById('detailRepeatReason').textContent = repeat.reason;
                    document.getElementById('detailRepeatComments').textContent = repeat.comments || '-';
                    document.getElementById('detailRepeatUser').textContent = repeat.user_name;
                    document.getElementById('detailRepeatDate').textContent = repeat.created_at;
                    document.getElementById('repeatDetailModal').style.display = 'block';
                }
            });
    }

    function showIncidentDetail(studyId) {
        fetch(`get_incident.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.incident) {
                    const incident = data.incident;
                    document.getElementById('detailIncidentType').textContent = incident.incident_type;
                    document.getElementById('detailIncidentDescription').textContent = incident.description || '-';
                    document.getElementById('detailIncidentActions').textContent = incident.actions || '-';
                    document.getElementById('detailIncidentUser').textContent = incident.user_name;
                    document.getElementById('detailIncidentDate').textContent = incident.created_at;
                    document.getElementById('incidentDetailModal').style.display = 'block';
                }
            });
    }

    function formatStudyDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'N/A';
        // Formato DD/MM/YYYY
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    // Función para abrir el modal y cargar radiólogos
    function openAssignModal(studyId) {
        document.getElementById('assignStudyId').value = studyId;
        
        // Buscar información del estudio
        const study = allStudies.find(s => s.id == studyId);
        if (study) {
            document.getElementById('modalPatientName').textContent = study.patient_name || 'No disponible';
            document.getElementById('modalPatientId').textContent = study.patient_id || 'No disponible';
            document.getElementById('modalModality').textContent = study.Modality || study.modality || 'No disponible';
            document.getElementById('modalDescription').textContent = study.description || 'Sin descripción';
        }
        
        // Limpiar formulario
        document.getElementById('facturaSelect').innerHTML = '<option value="">No vincular con factura (sin honorarios)</option>';
        document.getElementById('assignmentNotes').value = '';
        document.getElementById('facturaInfo').style.display = 'none';
        
        document.getElementById('assignRadiologistModal').style.display = 'block';
        
        // Cargar radiólogos
        fetch('get_radiologists.php')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('radiologistSelect');
                select.innerHTML = '<option value="">Seleccione un radiólogo...</option>';
                data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name;
                    select.appendChild(option);
                });
                // Activar select2
                $(select).select2({
                    dropdownParent: $('#assignRadiologistModal'),
                    width: '100%',
                    placeholder: 'Selecciona un radiólogo',
                    allowClear: true
                });
            });
            
        // Cargar facturas disponibles
        cargarFacturasDisponibles(study);
    }
    function closeAssignModal() {
        document.getElementById('assignRadiologistModal').style.display = 'none';
    }
    
    // Función para cargar facturas disponibles
    function cargarFacturasDisponibles(study) {
        const patientName = study ? study.patient_name : '';
        const patientId = study ? study.patient_id : '';
        
        fetch('get_facturas_disponibles.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                patient_name: patientName,
                patient_id: patientId 
            })
        })
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('facturaSelect');
            
            if (data.success && data.facturas.length > 0) {
                data.facturas.forEach(factura => {
                    const option = document.createElement('option');
                    option.value = factura.idord;
                    option.dataset.numero = factura.invoice_number;
                    option.dataset.paciente = factura.nomcl;
                    option.dataset.total = factura.total_price;
                    option.dataset.fecha = factura.placed_on;
                    option.dataset.medicoActual = factura.remitente || 'Sin médico';
                    option.dataset.status = factura.invoice_status;
                    
                    const fechaCorta = new Date(factura.placed_on).toLocaleDateString('es-ES');
                    const medicoStatus = factura.remitente ? '(Con médico)' : '(Sin médico)';
                    const statusBadge = 'PENDIENTE';
                    
                    option.textContent = `${factura.invoice_number} - ${factura.nomcl} - ${fechaCorta} - ${statusBadge} - ${medicoStatus}`;
                    select.appendChild(option);
                });
                
                // Activar select2 para facturas con configuración mejorada
                $(select).select2({
                    dropdownParent: $('#assignRadiologistModal'),
                    width: '100%',
                    placeholder: 'Seleccione una factura para vincular...',
                    allowClear: true,
                    theme: 'default',
                    language: {
                        noResults: function() {
                            return "No se encontraron facturas pendientes";
                        },
                        searching: function() {
                            return "Buscando facturas...";
                        }
                    }
                });
            } else {
                // Si no hay facturas, mostrar mensaje informativo
                const option = document.createElement('option');
                option.disabled = true;
                option.textContent = `No hay facturas pendientes para ${patientName || patientId}`;
                select.appendChild(option);
                
                // Agregar información de debug si está disponible
                if (data.debug) {
                    console.log('🔍 DEBUG INFO:', data.debug);
                    const debugOption = document.createElement('option');
                    debugOption.disabled = true;
                    debugOption.textContent = `Debug: ${data.debug.total_brutas} facturas encontradas en total`;
                    select.appendChild(debugOption);
                }
                
                // Activar select2 aunque no haya opciones
                $(select).select2({
                    dropdownParent: $('#assignRadiologistModal'),
                    width: '100%',
                    placeholder: 'No hay facturas pendientes disponibles',
                    allowClear: true
                });
                
                console.log('No se encontraron facturas pendientes para el paciente');
            }
        })
        .catch(error => {
            console.error('Error cargando facturas:', error);
        });
    }
    
    // Manejar cambio en selección de factura
    $(document).on('change', '#facturaSelect', function() {
        const selectedOption = $(this).find('option:selected');
        const facturaInfo = document.getElementById('facturaInfo');
        
        if (selectedOption.val()) {
            // Mostrar información de la factura
            document.getElementById('selectedFacturaNumber').textContent = selectedOption.data('numero');
            document.getElementById('selectedFacturaPaciente').textContent = selectedOption.data('paciente');
            document.getElementById('selectedFacturaTotal').textContent = 'LPS. ' + parseFloat(selectedOption.data('total')).toFixed(2);
            document.getElementById('selectedFacturaFecha').textContent = new Date(selectedOption.data('fecha')).toLocaleDateString('es-ES');
            
            const medicoActual = selectedOption.data('medicoactual');
            const statusFactura = selectedOption.data('status') || 'pendiente';
            const medicoElement = document.getElementById('selectedFacturaMedicoActual');
            
            // Mostrar médico actual con estado
            if (medicoActual === 'Sin médico') {
                medicoElement.textContent = 'Sin médico asignado';
                medicoElement.style.backgroundColor = '#fff3cd';
                medicoElement.style.color = '#856404';
                medicoElement.style.border = '1px solid #ffeaa7';
            } else {
                medicoElement.textContent = '🩺 ' + medicoActual;
                medicoElement.style.backgroundColor = '#d1ecf1';
                medicoElement.style.color = '#0c5460';
                medicoElement.style.border = '1px solid #bee5eb';
            }
            
            // Agregar indicador de estado pendiente
            const facturaInfoDiv = document.getElementById('facturaInfo');
            if (!facturaInfoDiv.querySelector('.status-badge')) {
                const statusBadge = document.createElement('div');
                statusBadge.className = 'status-badge';
                statusBadge.style.cssText = 'background: #28a745; color: white; padding: 5px 10px; border-radius: 4px; text-align: center; margin-bottom: 10px; font-weight: bold;';
                statusBadge.textContent = '✅ FACTURA PENDIENTE - Válida para honorarios';
                facturaInfoDiv.insertBefore(statusBadge, facturaInfoDiv.firstChild.nextSibling);
            }
            
            facturaInfo.style.display = 'block';
        } else {
            facturaInfo.style.display = 'none';
            // Limpiar badge de estado si existe
            const statusBadge = document.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.remove();
            }
        }
    });
    document.getElementById('assignRadiologistForm').onsubmit = function(e) {
        e.preventDefault();
        const studyId = document.getElementById('assignStudyId').value;
        const radiologistId = document.getElementById('radiologistSelect').value;
        const facturaId = document.getElementById('facturaSelect').value;
        const notes = document.getElementById('assignmentNotes').value;
        
        if (!radiologistId) {
            swal('Error', 'Debe seleccionar un médico radiólogo', 'error');
            return;
        }
        
        // Preparar datos para enviar
        const requestData = {
            study_id: studyId,
            radiologist_id: radiologistId,
            factura_id: facturaId || null,
            assignment_notes: notes,
            technician_id: <?php echo $_SESSION['id'] ?? 'null'; ?> // Agregar technician_id desde la sesión PHP
        };
        
        fetch('assign_radiologist_with_factura.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let mensaje = 'Estudio finalizado exitosamente\n\n';
                mensaje += '🩺 Radiólogo: ' + (data.radiologist_name || 'Médico seleccionado') + '\n';
                
                if (data.factura_updated && data.factura_info) {
                    mensaje += '\nFACTURA VINCULADA PARA HONORARIOS:';
                    mensaje += '\nNúmero: ' + data.factura_info.numero;
                    mensaje += '\nPaciente: ' + data.factura_info.paciente;
                    mensaje += '\nFecha: ' + data.factura_info.fecha;
                    mensaje += '\nMédico anterior: ' + data.factura_info.medico_anterior;
                    mensaje += '\n🩺 Médico nuevo: ' + data.factura_info.medico_nuevo;
                    mensaje += '\n\nEl médico aparecerá en el sistema de honorarios.';
                } else if (facturaId) {
                    mensaje += '\nFACTURA NO ACTUALIZADA:';
                    mensaje += '\n' + (data.message || 'No se pudo actualizar la factura seleccionada');
                } else {
                    mensaje += '\nSin vinculación a factura';
                    mensaje += '\nEste estudio no generará honorarios automáticos.';
                }
                
                if (notes) {
                    mensaje += '\n\nNotas: ' + notes;
                }
                
                swal({
                    title: 'Estudio Finalizado',
                    text: mensaje,
                    icon: 'success',
                    button: 'Entendido'
                });
                closeAssignModal();
                loadWorklist();
                loadStats();
            } else {
                swal('Error', data.message || 'No se pudo procesar la asignación', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            swal('Error', 'Ocurrió un error al procesar la solicitud', 'error');
        });
    };

    // Función para mostrar información de asignación
    function showAssignmentInfo(studyId) {
        fetch(`get_assignment_info.php?study_id=${studyId}`)
            .then(response => response.json())
            .then(data => {
                const content = document.getElementById('assignmentInfoContent');
                
                if (data.success && data.assignment) {
                    const assignment = data.assignment;
                    content.innerHTML = `
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <h3 style="margin-top: 0; color: #035c67;">Información del Estudio</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <strong>Paciente:</strong> <span>${assignment.patient_name || 'N/A'}</span><br>
                                    <strong>DNI:</strong> <span>${assignment.patient_id || 'N/A'}</span>
                                </div>
                                <div>
                                    <strong>Modalidad:</strong> <span>${assignment.modality || 'N/A'}</span><br>
                                    <strong>Descripción:</strong> <span>${assignment.description || 'Sin descripción'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                            <h3 style="margin-top: 0; color: #155724; margin-bottom: 15px;">Médico Asignado</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div>
                                        <strong style="display: block; margin-bottom: 5px; color: #155724;">Nombre:</strong>
                                        <span style="font-weight: bold; color: #28a745; font-size: 16px;">${assignment.radiologist_name || 'No asignado'}</span>
                                    </div>
                                    <div>
                                        <strong style="display: block; margin-bottom: 5px; color: #155724;">ID Médico:</strong>
                                        <span style="color: #666;">${assignment.radiologist_id || 'N/A'}</span>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div>
                                        <strong style="display: block; margin-bottom: 5px; color: #155724;">Estado:</strong>
                                        <span style="padding: 8px 12px; background: ${assignment.radiologist_name && assignment.radiologist_name !== 'No asignado' ? '#28a745' : '#ffc107'}; color: white; border-radius: 6px; font-weight: bold; display: inline-block; margin-top: 5px;">${assignment.radiologist_name && assignment.radiologist_name !== 'No asignado' ? (assignment.status || 'Pendiente') : 'Sin Asignar'}</span>
                                    </div>
                                    <div>
                                        <strong style="display: block; margin-bottom: 5px; color: #155724;">Fecha Asignación:</strong>
                                        <span style="color: #666; font-size: 14px;">${assignment.radiologist_name && assignment.radiologist_name !== 'No asignado' ? (assignment.assignment_date || 'N/A') : 'No aplica'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #06adbf;">
                            <h3 style="margin-top: 0; color: #035c67; margin-bottom: 15px;">Técnico Responsable</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div>
                                        <strong style="display: block; margin-bottom: 5px; color: #035c67;">Nombre:</strong>
                                        <span style="font-weight: bold; color: #06adbf; font-size: 16px;">${assignment.technician_name || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <strong style="display: block; margin-bottom: 5px; color: #035c67;">ID Técnico:</strong>
                                        <span style="color: #666;">${assignment.technician_id || 'N/A'}</span>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div>
                                        <strong style="display: block; margin-bottom: 5px; color: #035c67;">Fecha Finalización:</strong>
                                        <span style="color: #666; font-size: 14px;">${assignment.completion_date || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${assignment.assignment_notes ? `
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                            <h3 style="margin-top: 0; color: #856404;">Notas de Asignación</h3>
                            <p style="margin: 0;">${assignment.assignment_notes}</p>
                        </div>
                        ` : ''}
                    `;
                } else {
                    content.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class='bx bx-info-circle' style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <h3>No hay información de asignación</h3>
                            <p>Este estudio no ha sido asignado a ningún médico radiólogo.</p>
                        </div>
                    `;
                }
                
                document.getElementById('assignmentInfoModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                swal('Error', 'No se pudo cargar la información de asignación', 'error');
            });
    }

    // Función para cerrar el modal de información de asignación
    function closeAssignmentInfoModal() {
        document.getElementById('assignmentInfoModal').style.display = 'none';
    }
    </script>

    <style>
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
    </style>

    <!-- Alertas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

    <script src="../../backend/js/submenu.js"></script>
    <script src="../../backend/registros/script/botones_color.js"></script>

    <style>
    #facturaInfo .factura-resumen {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        word-break: break-word;
    }
    @media (max-width: 600px) {
        #facturaInfo .factura-resumen {
            grid-template-columns: 1fr;
        }
    }
    #facturaInfo {
        overflow-x: unset !important;
        white-space: normal !important;
    }

    /* Estilos responsivos para el modal de asignación */
    @media (max-width: 768px) {
        #assignmentInfoModal .modal-content {
            width: 95%;
            margin: 10px auto;
            padding: 15px;
        }
        
        #assignmentInfoModal .modal-content h2 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        #assignmentInfoModal .modal-content h3 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        #assignmentInfoModal .modal-content div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
            gap: 15px !important;
        }
        
        #assignmentInfoModal .modal-content strong {
            font-size: 14px;
        }
        
        #assignmentInfoModal .modal-content span {
            font-size: 14px;
        }
        
        #assignmentInfoModal .modal-content div[style*="padding: 20px"] {
            padding: 15px !important;
        }
    }

    @media (max-width: 480px) {
        #assignmentInfoModal .modal-content {
            width: 98%;
            margin: 5px auto;
            padding: 10px;
        }
        
        #assignmentInfoModal .modal-content h2 {
            font-size: 16px;
        }
        
        #assignmentInfoModal .modal-content h3 {
            font-size: 14px;
        }
        
        #assignmentInfoModal .modal-content strong {
            font-size: 13px;
        }
        
        #assignmentInfoModal .modal-content span {
            font-size: 13px;
        }
    }

    /* Estilos para el botón Cerrar del modal */
    .btn-cerrar-modal {
        background: #035c67 !important;
        color: white !important;
        padding: 10px 20px !important;
        border: none !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        font-weight: 500 !important;
        transition: background-color 0.3s ease !important;
    }

    .btn-cerrar-modal:hover {
        background: #06adbf !important;
    }
    </style>

</body>
</html> 