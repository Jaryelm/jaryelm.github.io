<?php
/** @var PDO|null $pdo */
/** @var object|null $candidato */
/** @var string $rrhh_error */
/** @var string $volverUrl */
/** @var string $saveUrl */
/** @var bool $isUsr */

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0 && isset($_GET['candidate_id'])) {
    $id = (int) $_GET['candidate_id'];
}
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
            'SELECT id, fullname, dni, email, phonenumber, status, academic_level, profession, previous_experience, salary_expectation FROM candidates WHERE id = ? AND deleted = 0 LIMIT 1'
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

if (empty($answers['nivel_academico']) && !empty($candidato->academic_level)) {
    $answers['nivel_academico'] = $candidato->academic_level;
}
if (empty($answers['profesion']) && !empty($candidato->profession)) {
    $answers['profesion'] = $candidato->profession;
}
if (empty($answers['experiencia']) && !empty($candidato->previous_experience)) {
    $answers['experiencia'] = $candidato->previous_experience;
}
if (empty($answers['expectativa_salarial']) && !empty($candidato->salary_expectation)) {
    $answers['expectativa_salarial'] = $candidato->salary_expectation;
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
<div class="containerss">
    <div class="alert"><strong>Error:</strong> <?php echo htmlspecialchars($rrhh_error); ?></div>
</div>
<?php else: ?>
<?php
$hora = (int) date('H');
$saludo = ($hora >= 6 && $hora < 12) ? 'Buenos Días' : (($hora >= 12 && $hora < 18) ? 'Buenas Tardes' : 'Buenas Noches');
$nameSafe = htmlspecialchars((string) ($name ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
?>
<h1 class="title"><?php echo $saludo . ', <strong>' . $nameSafe . '</strong>'; ?></h1>

<form id="rrhh-entrevista-form" method="post" action="#" autocomplete="off">
    <input type="hidden" name="candidate_id" value="<?php echo (int) $candidato->id; ?>">
    <div class="containerss">
        <h1>Formulario de entrevista</h1>
        <div class="alert-danger">
            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            <strong>Candidato:</strong>
            <?php echo htmlspecialchars($candidato->fullname ?? ''); ?>
            — DNI <?php echo htmlspecialchars($candidato->dni ?? ''); ?>
        </div>
        <p class="rrhh-panel-hint">Complete las notas al momento de entrevistar. Este registro se anexará al expediente del candidato.</p>
        <hr>

        <div class="form-grid-2">
            <div>
                <label for="date_interview"><b>Fecha de entrevista</b></label>
                <input type="date" name="date_interview" id="date_interview" required value="<?php echo htmlspecialchars((string) $dateVal); ?>">
            </div>
            <div>
                <label for="time_interview"><b>Hora de entrevista</b></label>
                <input type="time" name="time_interview" id="time_interview" required value="<?php echo htmlspecialchars((string) $timeVal); ?>">
            </div>
        </div>

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

        <hr>
        <div class="rrhh-form-actions">
            <button type="submit" class="registerbtn">Guardar entrevista</button>
            <a href="<?php echo htmlspecialchars($volverUrl); ?>" class="pabtn">Volver al detalle</a>
            <a href="<?php echo htmlspecialchars($calendarioUrl); ?>" class="registerbtn">Ver calendario</a>
        </div>
    </div>
</form>
<?php
$rrhh_entrevista_footer = [
    'saveUrl' => $saveUrl,
    'volverUrl' => $volverUrl,
];
?>
<?php endif; ?>
