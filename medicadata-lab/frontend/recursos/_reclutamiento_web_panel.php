<?php
/**
 * Panel compartido: Postulantes Website (admin recursos/ y RRHH recursos/).
 *
 * @var bool|null $dbOk
 * @var string|null $queryError
 * @var string $panelTitle
 * @var string $detalleCandidatoUrl URL detalle candidato RRHH
 */
$panelTitle = $panelTitle ?? 'Postulantes Website';
$detalleCandidatoUrl = $detalleCandidatoUrl ?? '../recursos_humanos/detalle_postulante_usr.php';
?>
<div class="data">
    <div class="content-data">
        <div class="head">
            <h3><?php echo htmlspecialchars($panelTitle); ?></h3>
        </div>
        <div class="table-responsive" style="overflow-x:auto;">
            <?php if (!empty($queryError)): ?>
            <div class="alert-danger" style="margin-bottom: 15px;">
                <strong>Error:</strong> <?php echo htmlspecialchars($queryError); ?>
            </div>
            <?php endif; ?>

        <div class="rrhh-aplica-dt-wrap">
            <?php if (!empty($dbOk)): ?>
            <table id="rrhh-aplica-table" class="rrhh-aplica-dt display nowrap" style="width:100%;">
                <thead>
                    <tr>
                        <th scope="col">DNI</th>
                        <th scope="col">Nombre Completo</th>
                        <th scope="col">Puesto Aspirado</th>
                        <th scope="col">Vacante Sugerida</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Celular</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>

<div id="pdfModal" class="modal-pdf">
    <div class="modal-pdf-content">
        <div class="modal-pdf-header">
            <h2 id="pdfModalTitle">Visualizar documento</h2>
            <span class="close-pdf-btn" onclick="medidataCerrarPDFModal()">&times;</span>
        </div>
        <div class="modal-pdf-body">
            <iframe id="pdfFrame" src="" frameborder="0" title="Vista previa CV"></iframe>
        </div>
        <div class="modal-pdf-footer">
            <button type="button" class="btn-descargar-pdf" onclick="medidataDescargarPDFActual()">
                <i class="bx bx-download"></i> Descargar archivo
            </button>
        </div>
    </div>
</div>

<script>
window.MEDIDATA_RECLUTAMIENTO = {
    ajaxUrl: '../../backend/registros/fetch_postulaciones_aplica.php',
    cvBaseUrl: '../../backend/php/download_cv.php',
    vacantesUrl: '../../backend/registros/fetch_rrhh_vacantes_abiertas.php',
    incorporarUrl: '../../backend/php/rrhh_aplica_incorporar.php',
    descartarUrl: '../../backend/php/rrhh_aplica_descartar.php',
    reasignarUrl: '../../backend/php/rrhh_aplica_reasignar.php',
    detalleCandidatoUrl: <?php echo json_encode($detalleCandidatoUrl, JSON_UNESCAPED_UNICODE); ?>,
    dbOk: <?php echo !empty($dbOk) ? 'true' : 'false'; ?>
};
</script>
