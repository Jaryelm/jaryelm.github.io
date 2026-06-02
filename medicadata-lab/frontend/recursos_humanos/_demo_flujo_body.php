<?php
/** @var string $MEDIDATA_RRHH_BASE */
/** @var array<int, object> $demoCandidatos */
/** @var array<int, array<string, mixed>> $demoEtapas */
/** @var int $demoVacanteId */
/** @var object|null $maria */

$base = $MEDIDATA_RRHH_BASE ?? './';
$isUsr = str_contains(basename($_SERVER['SCRIPT_NAME'] ?? ''), '_usr');
$detallePage = $isUsr ? 'detalle_postulante_usr.php' : 'detalle_postulante.php';
$mariaId = ($maria && isset($maria->id)) ? (int) $maria->id : 0;

$demoBadgeClass = static function (string $estado): string {
    if ($estado === 'Contratado' || $estado === 'Incorporado') {
        return 'badge-pcv-ok';
    }
    if ($estado === 'Descartado') {
        return 'badge-danger';
    }
    return 'badge-pcv-warn';
};
?>
<h1 class="title">Demo — Flujo completo de reclutamiento</h1>

<div class="data">
    <div class="content-data">
        <div class="head">
            <h3>Recorrido del proceso (ejemplo real en datos)</h3>
        </div>
        <div class="containerss">
            <p class="rrhh-panel-hint">
                Los registros marcados con <strong>[DEMO]</strong> fueron creados para capacitación.
                Puede eliminarlos después de revisar el flujo. Para regenerar datos ejecute:
                <code>php backend/scripts/seed_rrhh_demo_flujo_completo.php</code>
            </p>

            <?php if (empty($demoCandidatos)): ?>
            <div class="alert">
                <strong>Sin datos demo.</strong> Ejecute el script de seed en el servidor y recargue esta página.
            </div>
            <?php else: ?>

            <h4 class="demo-flujo-subtitle">Caso principal — proceso terminado</h4>
            <?php if ($maria): ?>
            <div class="demo-flujo-hero">
                <p><strong><?php echo htmlspecialchars($maria->fullname); ?></strong></p>
                <p>Vacante: <?php echo htmlspecialchars($maria->vacant_name ?? 'N/A'); ?> ·
                   Estado: <span class="<?php echo htmlspecialchars($demoBadgeClass($maria->status ?? '')); ?>"><?php echo htmlspecialchars($maria->status); ?></span> ·
                   Puntaje: <?php echo $maria->overall_score !== null ? htmlspecialchars((string) $maria->overall_score) . '%' : 'N/A'; ?></p>
                <a class="button" href="<?php echo htmlspecialchars($base . $detallePage . '?id=' . $mariaId); ?>">Ver ficha completa de María</a>
            </div>
            <?php endif; ?>

            <h4 class="demo-flujo-subtitle">Línea de tiempo</h4>
            <ol class="demo-flujo-timeline">
                <?php foreach ($demoEtapas as $etapa): ?>
                <?php
                $link = $isUsr ? ($base . basename($etapa['usr_url'])) : ($base . basename($etapa['admin_url']));
                if (str_contains($etapa['pantalla'], 'Website')) {
                    $link = $isUsr ? '../recursos/reclutamiento_usr.php' : '../recursos/reclutamiento.php';
                }
                if ($etapa['estado'] !== 'Contratado' && $etapa['estado'] !== 'Pendiente → Incorporado' && $demoVacanteId > 0) {
                    $link .= (str_contains($link, '?') ? '&' : '?') . 'id_vacante=' . $demoVacanteId;
                }
                if ($mariaId > 0 && in_array($etapa['estado'], ['Contratado', 'Formulario Empleados'], true)) {
                    $link = $base . $detallePage . '?id=' . $mariaId;
                }
                ?>
                <li class="demo-flujo-step">
                    <div class="demo-flujo-step-head">
                        <span class="demo-flujo-step-num"><?php echo (int) $etapa['orden']; ?></span>
                        <strong><?php echo htmlspecialchars($etapa['titulo']); ?></strong>
                        <span class="<?php echo htmlspecialchars($demoBadgeClass($etapa['estado'])); ?>"><?php echo htmlspecialchars($etapa['estado']); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($etapa['descripcion']); ?></p>
                    <p><em>Pantalla:</em> <?php echo htmlspecialchars($etapa['pantalla']); ?></p>
                    <a class="pabtn" href="<?php echo htmlspecialchars($link); ?>">Abrir pantalla →</a>
                </li>
                <?php endforeach; ?>
            </ol>

            <h4 class="demo-flujo-subtitle">Candidatos demo por etapa (filtre vacante [DEMO])</h4>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Estado actual</th>
                        <th>DNI</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demoCandidatos as $c): ?>
                    <?php
                    $detUrl = $base . $detallePage . '?id=' . (int) $c->id;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c->fullname); ?></td>
                        <td><span class="<?php echo htmlspecialchars($demoBadgeClass($c->status ?? '')); ?>"><?php echo htmlspecialchars($c->status); ?></span></td>
                        <td><?php echo htmlspecialchars($c->dni); ?></td>
                        <td><a href="<?php echo htmlspecialchars($detUrl); ?>">Ver detalle</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($demoVacanteId > 0): ?>
<div class="data">
    <div class="content-data">
        <div class="head"><h3>Enlaces rápidos (vacante demo #<?php echo $demoVacanteId; ?>)</h3></div>
        <div class="containerss rrhh-btn-row">
            <?php
            $suffix = $isUsr ? '_usr' : '';
            $v = '?id_vacante=' . $demoVacanteId;
            $links = [
                'Escritorio' => $base . 'escritorio.php',
                'Postulantes Web' => $isUsr ? '../recursos/reclutamiento_usr.php' : '../recursos/reclutamiento.php',
                'Postulantes' => $base . 'postulantes' . $suffix . '.php' . $v,
                'Solicitudes empleo' => $base . 'reclutamiento' . $suffix . '.php' . $v,
                'Entrevistas' => $base . 'entrevista' . $suffix . '.php' . $v,
                'Psicométricas' => $base . 'pruebas_psicometricas' . $suffix . '.php' . $v,
                'Requisitos' => $base . 'requisitos_contratacion' . $suffix . '.php' . $v,
                'Por vacante (tarjetas)' => $base . 'postulantes_vacante' . $suffix . '.php?id_vacante=' . $demoVacanteId,
            ];
            foreach ($links as $label => $href):
            ?>
            <a class="button" href="<?php echo htmlspecialchars($href); ?>"><?php echo htmlspecialchars($label); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
