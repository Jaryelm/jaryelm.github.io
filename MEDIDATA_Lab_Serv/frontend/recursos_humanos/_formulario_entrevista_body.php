<?php
/** @var PDO|null $pdo */
/** @var object|null $candidato */
/** @var string $rrhh_error */
/** @var string $volverUrl */
/** @var string $saveUrl */
/** @var bool $isUsr */

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$candidato = null;
$rrhh_error = null;
$interviewForm = null;
$questions = medidata_rrhh_interview_questions_default();
$volverUrl = $volverUrl ?? 'detalle_postulante_usr.php?id=' . $id;
$saveUrl = $saveUrl ?? '../../backend/php/rrhh_entrevista_guardar.php';
$calendarioUrl = $calendarioUrl ?? 'entrevista_usr.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    $rrhh_error = 'Base de datos de Recursos Humanos no disponible.';
} elseif ($id <= 0) {
    $rrhh_error = 'Identificador de candidato no válido.';
} else {
    try {
        $stmt = $pdo->prepare(
            'SELECT id, fullname, dni, email, phonenumber, status FROM candidates WHERE id = ? AND deleted = 0 LIMIT 1'
        );
        $stmt->execute([$id]);
        $candidato = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$candidato) {
            $rrhh_error = 'Candidato no encontrado.';
        } else {
            $interviewForm = medidata_rrhh_fetch_interview_form($id);
        }
    } catch (Throwable $e) {
        error_log('formulario_entrevista: ' . $e->getMessage());
        $rrhh_error = 'No se pudo cargar la información.';
    }
}

$answers = [];
if ($interviewForm && !empty($interviewForm['payload'])) {
    $decoded = json_decode((string) $interviewForm['payload'], true);
    if (is_array($decoded)) {
        $answers = $decoded;
    }
}

$dateVal = $interviewForm['date_interview'] ?? date('Y-m-d');
$timeVal = $interviewForm['time_interview'] ?? '09:00';
if (strpos((string) $timeVal, ':') === false) {
    $timeVal = '09:00';
} else {
    $timeVal = substr((string) $timeVal, 0, 5);
}

function fe_int_val(array $answers, string $key): string
{
    return htmlspecialchars((string) ($answers[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<?php if ($rrhh_error): ?>
<div class="data"><div class="content-data"><div class="alert"><strong>Error:</strong> <?php echo htmlspecialchars($rrhh_error); ?></div></div></div>
<?php else: ?>
<h1 class="title">Formulario de entrevista</h1>
<p style="margin:0 0 16px;color:#555;">Candidato: <strong><?php echo htmlspecialchars($candidato->fullname ?? ''); ?></strong> — DNI <?php echo htmlspecialchars($candidato->dni ?? ''); ?></p>

<div class="data">
    <div class="content-data">
        <div class="head"><h3>Agendar y registrar entrevista</h3></div>
        <form id="rrhh-entrevista-form" class="card-candidato-estado-form" autocomplete="off">
            <input type="hidden" name="candidate_id" value="<?php echo (int) $candidato->id; ?>">

            <div class="fe-grid-inline" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label for="date_interview"><b>Fecha de entrevista</b></label>
                    <input type="date" name="date_interview" id="date_interview" required value="<?php echo htmlspecialchars((string) $dateVal); ?>">
                </div>
                <div>
                    <label for="time_interview"><b>Hora de entrevista</b></label>
                    <input type="time" name="time_interview" id="time_interview" required value="<?php echo htmlspecialchars((string) $timeVal); ?>">
                </div>
            </div>

            <p style="color:#666;font-size:.9rem;margin:0 0 12px;">Complete las notas al momento de entrevistar. Este registro se anexará al expediente del candidato.</p>

            <?php foreach ($questions as $key => $label): ?>
            <label for="<?php echo htmlspecialchars($key); ?>"><b><?php echo htmlspecialchars($label); ?></b></label>
            <?php if ($key === 'observaciones'): ?>
            <textarea name="<?php echo htmlspecialchars($key); ?>" id="<?php echo htmlspecialchars($key); ?>" rows="4"><?php echo fe_int_val($answers, $key); ?></textarea>
            <?php elseif ($key === 'resultado'): ?>
            <select class="select2" name="<?php echo htmlspecialchars($key); ?>" id="<?php echo htmlspecialchars($key); ?>">
                <option value="">Seleccione...</option>
                <?php foreach (['Apto', 'No apto', 'Pendiente', 'En proceso'] as $opt): ?>
                <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo fe_int_val($answers, $key) === $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
            <input type="text" name="<?php echo htmlspecialchars($key); ?>" id="<?php echo htmlspecialchars($key); ?>" value="<?php echo fe_int_val($answers, $key); ?>">
            <?php endif; ?>
            <?php endforeach; ?>

            <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="registerbtn">Guardar entrevista</button>
                <a href="<?php echo htmlspecialchars($volverUrl); ?>" class="pabtn">Volver al detalle</a>
                <a href="<?php echo htmlspecialchars($calendarioUrl); ?>" class="button">Ver calendario</a>
            </div>
        </form>
    </div>
</div>

<script>
window.__rrhh_entrevista_ready || function(){})();
window.MEDIDATA_ENTREVISTA = {
    saveUrl: <?php echo json_encode($saveUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?>,
    volverUrl: <?php echo json_encode($volverUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?>
};
</script>
<script src="../../backend/registros/script/rrhh_formulario_entrevista.js"></script>
<?php endif; ?>
