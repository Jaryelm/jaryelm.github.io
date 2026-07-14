<?php
/** @var string $rrhh_error */
/** @var string $volverUrl */
/** @var string $saveUrl */

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0 && isset($_GET['candidate_id'])) {
    $id = (int) $_GET['candidate_id'];
}
$candidato = null;
$rrhh_error = null;
$psyForm = null;
$testsCatalog = medidata_rrhh_psychometric_tests_catalog();
$volverUrl = $volverUrl ?? 'detalle_postulante_usr.php?id=' . $id;
$saveUrl = $saveUrl ?? '../../backend/php/rrhh_psicometrica_guardar.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    $rrhh_error = 'Base de datos de Recursos Humanos no disponible.';
} elseif ($id <= 0) {
    $rrhh_error = 'Identificador de candidato no válido.';
} else {
    try {
        $stmt = $pdo->prepare(
            'SELECT id, fullname, dni, email, status FROM candidates WHERE id = ? AND deleted = 0 LIMIT 1'
        );
        $stmt->execute([$id]);
        $candidato = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$candidato) {
            $rrhh_error = 'Candidato no encontrado.';
        } else {
            $psyForm = medidata_rrhh_fetch_psychometric_form($id);
        }
    } catch (Throwable $e) {
        error_log('prueba_psicometrica: ' . $e->getMessage());
        $rrhh_error = 'No se pudo cargar la información.';
    }
}

$selectedTests = [];
$notes = '';
$score = '';
$docName = '';
$docFile = '';
if ($psyForm && !empty($psyForm['payload'])) {
    $payload = json_decode((string) $psyForm['payload'], true);
    if (is_array($payload)) {
        $selectedTests = $payload['tests'] ?? [];
        $notes = (string) ($payload['notes'] ?? '');
        $docFile = (string) ($payload['document'] ?? '');
        $docName = (string) ($payload['document_original'] ?? $docFile);
    }
}
if ($psyForm && isset($psyForm['score']) && $psyForm['score'] !== null && $psyForm['score'] !== '') {
    $score = (string) $psyForm['score'];
}
?>
<?php if ($rrhh_error): ?>
<div class="data"><div class="content-data"><div class="alert"><strong>Error:</strong> <?php echo htmlspecialchars($rrhh_error); ?></div></div></div>
<?php else: ?>
<h1 class="title">Pruebas psicométricas</h1>
<p style="margin:0 0 16px;color:#555;">Candidato: <strong><?php echo htmlspecialchars($candidato->fullname ?? ''); ?></strong></p>

<style>
.card-candidato-estado-form input[type="text"],
.card-candidato-estado-form input[type="number"],
.card-candidato-estado-form input[type="file"],
.card-candidato-estado-form textarea {
    height: 44px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 1rem;
    font-family: inherit;
    width: 100%;
    margin-bottom: 16px;
}
.card-candidato-estado-form textarea {
    height: auto;
    resize: vertical;
}
</style>

<div class="data">
    <div class="content-data">
        <div class="head"><h3>Registrar pruebas aplicadas</h3></div>
        <form id="rrhh-psico-form" method="post" action="#" enctype="multipart/form-data" class="card-candidato-estado-form" autocomplete="off">
            <input type="hidden" name="candidate_id" value="<?php echo (int) $candidato->id; ?>">

            <label><b>Pruebas aplicadas</b></label>
            <p style="color:#666;font-size:.9rem;margin:0 0 8px;">Seleccione las pruebas que realizó al candidato.</p>
            <div class="rrhh-psico-tests" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;margin-bottom:20px;">
                <?php foreach ($testsCatalog as $key => $label): ?>
                <label style="display:flex;align-items:flex-start;gap:10px;font-weight:500;background:#f9f9f9;border:1px solid #eaeaea;border-radius:6px;padding:12px;height:100%;box-sizing:border-box;cursor:pointer;transition:background 0.2s;">
                    <input type="checkbox" name="tests[]" value="<?php echo htmlspecialchars($key); ?>" style="margin-top:2px;width:16px;height:16px;cursor:pointer;"
                        <?php echo in_array($key, $selectedTests, true) ? 'checked' : ''; ?>>
                    <span style="line-height:1.4;flex:1;"><?php echo htmlspecialchars($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <label for="score"><b>Puntaje general (opcional)</b></label>
            <input type="number" step="0.01" name="score" id="score" value="<?php echo htmlspecialchars($score); ?>" placeholder="Ej: 85.5">

            <label for="notes"><b>Notas / observaciones</b></label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>

            <label for="document"><b>Documento de resultados (PDF)</b></label>
            <?php if ($docName !== ''): ?>
            <p style="font-size:.9rem;color:#035c67;margin:0 0 6px;">Archivo actual: 
                <a href="javascript:void(0)" onclick="openPsicoModal('<?php echo htmlspecialchars($docFile); ?>')" style="color:#06adbf;text-decoration:underline;">
                    <?php echo htmlspecialchars($docName); ?>
                </a>
            </p>
            <?php endif; ?>
            <input type="file" name="document" id="document" accept="application/pdf,.pdf">

            <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="registerbtn">Guardar pruebas</button>
                <a href="<?php echo htmlspecialchars($volverUrl); ?>" class="pabtn">Volver al detalle</a>
            </div>
        </form>
    </div>
</div>

<div id="psicoDocModal" class="modal-pdf">
    <div class="modal-pdf-content">
        <div class="modal-pdf-header">
            <h2>Documento de Resultados</h2>
            <span class="close-pdf-btn" onclick="closePsicoModal()">&times;</span>
        </div>
        <div id="psicoDocContainer" class="modal-pdf-body"></div>
        <div class="modal-pdf-footer" style="text-align: center;">
            <button id="psicoPdfDownload" type="button" class="btn-descargar-pdf">
                <i class="bx bx-download"></i> Descargar / Abrir
            </button>
        </div>
    </div>
</div>
<script>
var currentPsicoDocUrl = '';
function openPsicoModal(filename) {
    if (!filename) return;
    currentPsicoDocUrl = '../../backend/uploads/rrhh/psicometricas/' + filename + '?v=' + new Date().getTime();
    var container = document.getElementById('psicoDocContainer');
    container.innerHTML = '<iframe src="' + currentPsicoDocUrl + '"></iframe>';
    var modal = document.getElementById('psicoDocModal');
    modal.style.display = 'flex';
}
function closePsicoModal() {
    document.getElementById('psicoDocModal').style.display = 'none';
    document.getElementById('psicoDocContainer').innerHTML = '';
}
document.getElementById('psicoPdfDownload').addEventListener('click', function() {
    if (currentPsicoDocUrl) {
        window.open(currentPsicoDocUrl, '_blank');
    }
});
</script>

<?php
$rrhh_psico_footer = [
    'saveUrl' => $saveUrl,
    'volverUrl' => $volverUrl,
];
?>
<?php endif; ?>
