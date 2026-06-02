<?php
/** @var object|null $candidato */
/** @var string $vacanteNombre */
/** @var string $puestoNombre */
/** @var string $volverUrl */
/** @var string|null $rrhh_error */

require_once __DIR__ . '/../../backend/registros/rrhh_aplica_bridge.php';
$rrhhEstados = medidata_rrhh_estados_candidato();

$estadoActual = $candidato->status ?? 'N/A';
$badgeEstado = 'badge-rrhh badge-rrhh-pending';
if ($estadoActual === 'Contratado') {
    $badgeEstado = 'badge-rrhh badge-rrhh-yes';
} elseif ($estadoActual === 'Descartado') {
    $badgeEstado = 'badge-rrhh badge-rrhh-no';
}

$puntajeTexto = (isset($candidato->overall_score) && $candidato->overall_score !== null)
    ? htmlspecialchars((string) $candidato->overall_score) . '%'
    : 'Pendiente';
$salarioTexto = (isset($candidato->salary_expectation) && $candidato->salary_expectation !== null)
    ? 'L ' . htmlspecialchars((string) $candidato->salary_expectation)
    : 'N/A';
?>
<h1 class="title">Detalle del candidato</h1>

<button class="button" type="button" id="btn-volver-candidato" data-return-url="<?php echo htmlspecialchars($volverUrl, ENT_QUOTES, 'UTF-8'); ?>">Volver</button>

<?php if (!empty($rrhh_error)): ?>
<div class="alert">
    <strong>Aviso:</strong> <?php echo htmlspecialchars($rrhh_error); ?>
</div>
<?php else: ?>

<div class="data">
    <div class="content-data candidato-detalle-wrap">
        <div class="mgmt-card card-candidato-detalle">
            <div class="card-header">
                <h3 class="card-title"><?php echo htmlspecialchars($candidato->fullname ?? 'Candidato'); ?></h3>
                <span class="<?php echo htmlspecialchars($badgeEstado); ?>"><?php echo htmlspecialchars($estadoActual); ?></span>
            </div>

            <div class="card-body card-candidato-grid">
                <section class="card-candidato-section">
                    <h4 class="card-candidato-section-title">Gestión del proceso</h4>

                    <span class="rrhh-estado-rapidos-label">Avance rápido</span>
                    <div class="rrhh-estado-rapidos">
                        <button type="button" class="button rrhh-estado-rapido" data-status="Formulario Empleados">Formulario</button>
                        <button type="button" class="button rrhh-estado-rapido" data-status="Entrevista">Entrevista</button>
                        <button type="button" class="button rrhh-estado-rapido" data-status="Pruebas Psicometricas">Psicométricas</button>
                        <button type="button" class="button rrhh-estado-rapido" data-status="Llenando Expediente">Expediente</button>
                        <button type="button" class="button rrhh-estado-rapido" data-status="Contratado">Contratado</button>
                        <button type="button" class="pabtn rrhh-estado-rapido" data-status="Descartado">Descartar</button>
                    </div>

                    <form id="rrhh-candidato-estado-form" class="card-candidato-estado-form" method="post" action="#" autocomplete="off">
                        <label for="candidato_status">Cambiar estado</label>
                        <select class="select2" name="status" id="candidato_status" required>
                            <?php foreach ($rrhhEstados as $estado): ?>
                            <option value="<?php echo htmlspecialchars($estado); ?>" <?php echo ($candidato->status ?? '') === $estado ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($estado); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="candidato_obs">Observaciones RRHH</label>
                        <textarea name="observaciones" id="candidato_obs" rows="4" placeholder="Notas del cambio de estado (opcional)"><?php echo htmlspecialchars($candidato->rrhh_observations ?? ''); ?></textarea>

                        <button type="submit" class="registerbtn">Guardar estado</button>
                    </form>
                </section>

                <section class="card-candidato-section">
                    <h4 class="card-candidato-section-title">Información general</h4>

                    <?php if (!empty($candidato->id_aplica)): ?>
                    <div class="info-item">
                        <i class="fa fa-globe"></i>
                        <strong>Origen:</strong>&nbsp;<span>Sitio web (aplica #<?php echo (int) $candidato->id_aplica; ?>)</span>
                    </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <i class="fa fa-id-card"></i>
                        <strong>DNI:</strong>&nbsp;<span><?php echo htmlspecialchars($candidato->dni ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-envelope"></i>
                        <strong>Email:</strong>&nbsp;<span><?php echo htmlspecialchars($candidato->email ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-phone"></i>
                        <strong>Teléfono:</strong>&nbsp;<span><?php echo htmlspecialchars($candidato->phonenumber ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-map-marker-alt"></i>
                        <strong>Dirección:</strong>&nbsp;<span><?php echo htmlspecialchars($candidato->direction ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-briefcase"></i>
                        <strong>Vacante:</strong>&nbsp;<span><?php echo htmlspecialchars($vacanteNombre); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-user-tie"></i>
                        <strong>Puesto:</strong>&nbsp;<span><?php echo htmlspecialchars($puestoNombre); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-graduation-cap"></i>
                        <strong>Nivel académico:</strong>&nbsp;<span><?php echo htmlspecialchars($candidato->academic_level ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-certificate"></i>
                        <strong>Profesión:</strong>&nbsp;<span><?php echo htmlspecialchars($candidato->profession ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item info-item-block">
                        <i class="fa fa-history"></i>
                        <strong>Experiencia previa:</strong>
                        <span><?php echo htmlspecialchars($candidato->previous_experience ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-money-bill-wave"></i>
                        <strong>Expectativa salarial:</strong>&nbsp;<span><?php echo $salarioTexto; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-star"></i>
                        <strong>Puntaje general:</strong>&nbsp;<span><?php echo $puntajeTexto; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-share-alt"></i>
                        <strong>Fuente:</strong>&nbsp;<span><?php echo htmlspecialchars($candidato->referral_source ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-calendar-check"></i>
                        <strong>Fecha de postulación:</strong>&nbsp;<span><?php echo htmlspecialchars($candidato->created_at ?? 'N/A'); ?></span>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
window.MEDIDATA_CANDIDATO_ESTADO = {
    candidateId: <?php echo (int) ($candidato->id ?? 0); ?>,
    estadoUrl: '../../backend/php/rrhh_candidato_estado.php',
    returnUrl: <?php echo json_encode($volverUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?>
};
</script>

<?php endif; ?>
