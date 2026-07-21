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
$done = (int) ($estado['done'] ?? 0);
$total = (int) ($estado['total'] ?? 0);
?>
<?php if ($rrhh_error): ?>
<div class="data"><div class="content-data"><div class="alert"><strong>Error:</strong> <?php echo htmlspecialchars($rrhh_error); ?></div></div></div>
<?php else: ?>
<div class="rrhh-exp-page">
    <div class="rrhh-exp-page__top">
        <div>
            <h1 class="title" style="margin-bottom:6px;">Expediente de contratación</h1>
            <p class="rrhh-exp-page__subtitle">
                Candidato: <strong><?php echo htmlspecialchars((string) ($candidato->fullname ?? '')); ?></strong>
                <?php if (!empty($candidato->dni)): ?>
                    <span class="rrhh-exp-page__meta"> · DNI <?php echo htmlspecialchars((string) $candidato->dni); ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="data">
        <div class="content-data rrhh-exp-card">
            <div class="rrhh-exp-progress">
                <div class="rrhh-exp-progress__head">
                    <h3>Estado del expediente</h3>
                    <span class="rrhh-exp-progress__pct"><?php echo $done; ?> / <?php echo $total; ?> · <?php echo $pct; ?>%</span>
                </div>
                <div class="rrhh-exp-progress__bar" role="progressbar" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100">
                    <span style="width:<?php echo max(0, min(100, $pct)); ?>%;"></span>
                </div>
                <p class="rrhh-exp-progress__hint">Los documentos completados se pueden abrir con el visor PDF. Si faltan archivos, genere o reenvíe el enlace para que el candidato los suba.</p>
            </div>

            <div class="rrhh-exp-grid">
                <section class="rrhh-exp-panel rrhh-exp-panel--ok">
                    <header class="rrhh-exp-panel__head">
                        <h4><i class="fa fa-check-circle"></i> Completados</h4>
                        <span class="rrhh-exp-panel__count"><?php echo count($estado['completed']); ?></span>
                    </header>
                    <?php if ($estado['completed'] === []): ?>
                    <p class="rrhh-exp-empty">Aún no hay documentos cargados.</p>
                    <?php else: ?>
                    <ul class="rrhh-exp-list">
                        <?php foreach ($estado['completed'] as $item): ?>
                        <?php
                            $viewUrl = (string) ($item['view_url'] ?? '');
                            $label = (string) ($item['label'] ?? '');
                        ?>
                        <li class="rrhh-exp-item rrhh-exp-item--ok">
                            <div class="rrhh-exp-item__info">
                                <i class="fa fa-check-circle" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars($label); ?></span>
                            </div>
                            <div class="rrhh-exp-item__actions">
                                <?php if ($viewUrl !== ''): ?>
                                <button type="button"
                                    class="rrhh-exp-btn rrhh-exp-btn--view"
                                    onclick="verDocumentoStaff(<?php echo htmlspecialchars(json_encode($viewUrl), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($label), ENT_QUOTES, 'UTF-8'); ?>)">
                                    <i class="bx bx-show"></i> Ver
                                </button>
                                <a class="rrhh-exp-btn rrhh-exp-btn--ghost"
                                   href="<?php echo htmlspecialchars($viewUrl); ?>"
                                   target="_blank"
                                   rel="noopener"
                                   title="Abrir en nueva pestaña">
                                    <i class="bx bx-link-external"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>

                <section class="rrhh-exp-panel rrhh-exp-panel--missing">
                    <header class="rrhh-exp-panel__head">
                        <h4><i class="fa fa-times-circle"></i> Faltantes</h4>
                        <span class="rrhh-exp-panel__count"><?php echo count($estado['missing']); ?></span>
                    </header>
                    <?php if ($estado['missing'] === []): ?>
                    <p class="rrhh-exp-complete-msg">¡Expediente completo!</p>
                    <?php else: ?>
                    <ul class="rrhh-exp-list">
                        <?php foreach ($estado['missing'] as $item): ?>
                        <li class="rrhh-exp-item rrhh-exp-item--missing">
                            <div class="rrhh-exp-item__info">
                                <i class="fa fa-times-circle" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars((string) ($item['label'] ?? '')); ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>
            </div>

            <div class="rrhh-exp-actions">
                <a href="<?php echo htmlspecialchars($volverUrl); ?>" class="rrhh-exp-btn rrhh-exp-btn--secondary">
                    <i class="bx bx-arrow-back"></i> Volver al detalle
                </a>
                <button type="button" class="rrhh-exp-btn rrhh-exp-btn--primary" id="btn-expediente-link">
                    <i class="bx bx-link-alt"></i> Copiar / reenviar enlace
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.rrhh-exp-page { max-width: 1100px; }
.rrhh-exp-page__subtitle { margin: 0 0 18px; color: #555; font-size: .98rem; }
.rrhh-exp-page__meta { color: #777; font-weight: 400; }
.rrhh-exp-card { padding: 22px 24px 20px !important; }
.rrhh-exp-progress { margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid #e8eef0; }
.rrhh-exp-progress__head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
.rrhh-exp-progress__head h3 { margin: 0; color: #035c67; font-size: 1.15rem; }
.rrhh-exp-progress__pct { font-weight: 700; color: #06adbf; font-size: .95rem; }
.rrhh-exp-progress__bar { height: 10px; background: #e9f4f6; border-radius: 999px; overflow: hidden; }
.rrhh-exp-progress__bar > span { display: block; height: 100%; background: linear-gradient(90deg, #06adbf, #035c67); border-radius: 999px; }
.rrhh-exp-progress__hint { margin: 10px 0 0; color: #666; font-size: .88rem; line-height: 1.4; }
.rrhh-exp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.rrhh-exp-panel { border: 1px solid #e6ecee; border-radius: 10px; background: #fafcfc; padding: 14px 16px 10px; min-height: 180px; }
.rrhh-exp-panel--ok { border-left: 4px solid #81D43A; }
.rrhh-exp-panel--missing { border-left: 4px solid #FC3B56; }
.rrhh-exp-panel__head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.rrhh-exp-panel__head h4 { margin: 0; font-size: 1rem; }
.rrhh-exp-panel--ok .rrhh-exp-panel__head h4 { color: #2e7d32; }
.rrhh-exp-panel--missing .rrhh-exp-panel__head h4 { color: #c62828; }
.rrhh-exp-panel__count { background: #fff; border: 1px solid #dde7ea; border-radius: 999px; padding: 2px 10px; font-size: .82rem; font-weight: 700; color: #035c67; }
.rrhh-exp-list { list-style: none; padding: 0; margin: 0; }
.rrhh-exp-item { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 4px; border-bottom: 1px solid #edf2f3; }
.rrhh-exp-item:last-child { border-bottom: none; }
.rrhh-exp-item__info { display: flex; align-items: flex-start; gap: 8px; flex: 1; min-width: 0; font-size: .93rem; color: #333; line-height: 1.35; }
.rrhh-exp-item--ok .rrhh-exp-item__info i { color: #81D43A; margin-top: 2px; }
.rrhh-exp-item--missing .rrhh-exp-item__info i { color: #FC3B56; margin-top: 2px; }
.rrhh-exp-item__actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.rrhh-exp-empty, .rrhh-exp-complete-msg { margin: 8px 0 12px; color: #888; font-size: .92rem; }
.rrhh-exp-complete-msg { color: #035c67; font-weight: 600; }
.rrhh-exp-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid #e8eef0;
}
.rrhh-exp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 8px;
    font-weight: 700;
    font-size: .92rem;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
    padding: 11px 18px;
    line-height: 1.2;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.rrhh-exp-btn--primary { background: #06adbf; color: #fff; border-color: #06adbf; }
.rrhh-exp-btn--primary:hover { background: #035c67; border-color: #035c67; color: #fff; }
.rrhh-exp-btn--secondary { background: #fff; color: #035c67; border-color: #035c67; }
.rrhh-exp-btn--secondary:hover { background: #035c67; color: #fff; }
.rrhh-exp-btn--view { background: #06adbf; color: #fff; border-color: #06adbf; padding: 7px 12px; font-size: .82rem; }
.rrhh-exp-btn--view:hover { background: #035c67; border-color: #035c67; }
.rrhh-exp-btn--ghost { background: #fff; color: #035c67; border-color: #c5d9dd; padding: 7px 10px; }
.rrhh-exp-btn--ghost:hover { background: #e8f7f9; border-color: #06adbf; color: #035c67; }
@media (max-width: 768px) {
    .rrhh-exp-grid { grid-template-columns: 1fr; }
    .rrhh-exp-actions { flex-direction: column-reverse; }
    .rrhh-exp-actions .rrhh-exp-btn { width: 100%; }
    .rrhh-exp-item { flex-direction: column; align-items: stretch; }
    .rrhh-exp-item__actions { justify-content: flex-start; }
}
</style>

<script>
window.MEDIDATA_EXPEDIENTE = {
    candidateId: <?php echo (int) ($candidato->id ?? 0); ?>,
    apiUrl: <?php echo json_encode($expedienteApiUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?>,
    candidateEmail: <?php echo json_encode($candidato->email ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>
};
</script>
<?php endif; ?>
