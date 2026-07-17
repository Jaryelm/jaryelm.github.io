<?php
/** @var string $rrhh_error */
/** @var string $volverUrl */
/** @var string $expedienteApiUrl */

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$candidato = null;
$rrhh_error = null;
$estado = ['completed' => [], 'missing' => [], 'total' => 0, 'done' => 0];
$volverUrl = $volverUrl ?? 'detalle_postulante_usr.php?id=' . $id;
$expedienteApiUrl = $expedienteApiUrl ?? '../../backend/php/rrhh_expediente_link.php';

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
            $estado = medidata_rrhh_expediente_estado($id);
        }
    } catch (Throwable $e) {
        error_log('expediente_estado: ' . $e->getMessage());
        $rrhh_error = 'No se pudo cargar la información.';
    }
}

$pct = ($estado['total'] > 0) ? round(($estado['done'] / $estado['total']) * 100) : 0;
?>
<?php if ($rrhh_error): ?>
<div class="data"><div class="content-data"><div class="alert"><strong>Error:</strong> <?php echo htmlspecialchars($rrhh_error); ?></div></div></div>
<?php else: ?>
<h1 class="title">Expediente de contratación</h1>
<p style="margin:0 0 16px;color:#555;">Candidato: <strong><?php echo htmlspecialchars($candidato->fullname ?? ''); ?></strong></p>

<div class="data">
    <div class="content-data">
        <div class="head" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <h3>Estado del expediente (<?php echo (int) $estado['done']; ?> / <?php echo (int) $estado['total']; ?> — <?php echo $pct; ?>%)</h3>
            <button type="button" class="registerbtn" id="btn-expediente-link">Generar enlace para candidato</button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:12px;">
            <div>
                <h4 style="color:#81D43A;margin:0 0 10px;">Completados</h4>
                <?php if ($estado['completed'] === []): ?>
                <p style="color:#888;">Aún no hay documentos cargados.</p>
                <?php else: ?>
                <ul class="rrhh-exp-list rrhh-exp-ok">
                    <?php foreach ($estado['completed'] as $item): ?>
                    <li><i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($item['label']); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <div>
                <h4 style="color:#FC3B56;margin:0 0 10px;">Faltantes</h4>
                <?php if ($estado['missing'] === []): ?>
                <p style="color:#035c67;font-weight:600;">¡Expediente completo!</p>
                <?php else: ?>
                <ul class="rrhh-exp-list rrhh-exp-missing">
                    <?php foreach ($estado['missing'] as $item): ?>
                    <li><i class="fa fa-times-circle"></i> <?php echo htmlspecialchars($item['label']); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top:20px;">
            <a href="<?php echo htmlspecialchars($volverUrl); ?>" class="pabtn">Volver al detalle</a>
        </div>
    </div>
</div>

<style>
.rrhh-exp-list { list-style:none;padding:0;margin:0; }
.rrhh-exp-list li { padding:6px 0;border-bottom:1px solid #eee;font-size:.95rem; }
.rrhh-exp-ok li i { color:#81D43A;margin-right:6px; }
.rrhh-exp-missing li i { color:#FC3B56;margin-right:6px; }
@media (max-width:768px) { .data .content-data > div[style*="grid"] { grid-template-columns:1fr !important; } }
</style>

<script>
window.MEDIDATA_EXPEDIENTE = {
    candidateId: <?php echo (int) ($candidato->id ?? 0); ?>,
    apiUrl: <?php echo json_encode($expedienteApiUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?>,
    candidateEmail: <?php echo json_encode($candidato->email ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>
};
</script>
<script src="../../backend/registros/script/rrhh_expediente_estado.js"></script>
<?php endif; ?>
