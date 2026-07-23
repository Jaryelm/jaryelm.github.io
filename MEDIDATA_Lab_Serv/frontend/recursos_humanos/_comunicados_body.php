<?php
/**
 * Cuerpo compartido — Comunicados RRHH (SMTP).
 * Variables esperadas: $connect (PDO), $name (string), $comunicadosListPage (string).
 */
require_once __DIR__ . '/../../backend/php/rrhh_comunicados_lib.php';

$comunicadosListPage = $comunicadosListPage ?? 'comunicados_usr.php';
$usersComunicados = [];
try {
    if (isset($connect) && $connect instanceof PDO) {
        $usersComunicados = medidata_rrhh_comunicados_fetch_users($connect);
    }
} catch (Throwable $e) {
    error_log('comunicados users: ' . $e->getMessage());
}

$usersConCorreo = array_values(array_filter($usersComunicados, static fn ($u) => !empty($u['has_email'])));
$usersSinCorreo = array_values(array_filter($usersComunicados, static fn ($u) => empty($u['has_email'])));

$hora_actual = (int) date('H');
if ($hora_actual >= 6 && $hora_actual < 12) {
    $saludo = 'Buenos Días';
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = 'Buenas Tardes';
} else {
    $saludo = 'Buenas Noches';
}
?>
<h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') . '</strong>'; ?></h1>

<button type="button" class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($comunicadosListPage, ENT_QUOTES, 'UTF-8'); ?>')">Comunicados</button>

<div class="data">
    <div class="content-data">
        <div class="head">
            <h3>Comunicados por correo</h3>
        </div>
        <p class="rrhh-comunicados-intro">
            Envíe avisos a usuarios de MEDIDATA (por ejemplo, cuando un colaborador se retira)
            o agregue correos manuales si la persona aún no está registrada en el sistema.
            El envío utiliza el mismo SMTP de Talento Humano que los correos a candidatos.
        </p>

        <form id="rrhhComunicadosForm" class="rrhh-comunicados-form" autocomplete="off">
            <div class="rrhh-comunicados-grid">
                <div class="rrhh-comunicados-panel">
                    <div class="rrhh-comunicados-panel__head">
                        <h4>Usuarios MEDIDATA</h4>
                        <div class="rrhh-comunicados-actions">
                            <button type="button" class="button rrhh-comunicados-btn-secondary" id="rrhhComSelectAll">Seleccionar todos con correo</button>
                            <button type="button" class="button rrhh-comunicados-btn-secondary" id="rrhhComClearUsers">Limpiar</button>
                        </div>
                    </div>
                    <input type="search" id="rrhhComUserFilter" class="rrhh-form-control" placeholder="Buscar por nombre, usuario, rol o correo…">
                    <p class="rrhh-comunicados-meta">
                        <span id="rrhhComSelectedCount">0</span> seleccionados ·
                        <?php echo count($usersConCorreo); ?> con correo ·
                        <?php echo count($usersSinCorreo); ?> sin correo
                    </p>
                    <div class="rrhh-comunicados-userlist" id="rrhhComUserList">
                        <?php if ($usersComunicados === []): ?>
                            <p class="rrhh-comunicados-empty">No hay usuarios activos en MEDIDATA.</p>
                        <?php else: ?>
                            <?php foreach ($usersComunicados as $u): ?>
                                <?php
                                $uid = (int) $u['id'];
                                $has = !empty($u['has_email']);
                                $label = trim($u['name'] !== '' ? $u['name'] : $u['username']);
                                $searchBlob = strtolower($label . ' ' . $u['username'] . ' ' . $u['rol'] . ' ' . $u['email']);
                                ?>
                                <label class="rrhh-comunicados-user<?php echo $has ? '' : ' is-disabled'; ?>"
                                       data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="checkbox"
                                           class="rrhh-com-user-cb"
                                           value="<?php echo $uid; ?>"
                                           data-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                           <?php echo $has ? '' : 'disabled'; ?>>
                                    <span class="rrhh-comunicados-user__body">
                                        <strong><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small>
                                            <?php
                                            echo htmlspecialchars($u['rol'] !== '' ? $u['rol'] : 'Sin rol', ENT_QUOTES, 'UTF-8');
                                            if ($has) {
                                                echo ' · ' . htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8');
                                            } else {
                                                echo ' · Sin correo en el sistema';
                                            }
                                            ?>
                                        </small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rrhh-comunicados-panel">
                    <h4>Correos manuales</h4>
                    <p class="rrhh-form-hint">Para personas que no están en la lista. Puede pegar varios separados por coma o salto de línea.</p>
                    <div class="rrhh-form-row rrhh-comunicados-manual-row">
                        <input type="email" id="rrhhComManualInput" class="rrhh-form-control" placeholder="correo@ejemplo.com">
                        <button type="button" class="button rrhh-comunicados-btn-secondary" id="rrhhComManualAdd">Agregar</button>
                    </div>
                    <textarea id="rrhhComManualBulk" class="rrhh-form-control" rows="3" placeholder="Pegue aquí varios correos…"></textarea>
                    <button type="button" class="button rrhh-comunicados-btn-secondary" id="rrhhComManualImport" style="margin-top:8px;">Añadir desde el cuadro</button>
                    <div class="rrhh-comunicados-chips" id="rrhhComManualChips" aria-live="polite"></div>
                </div>
            </div>

            <div class="rrhh-comunicados-compose">
                <div class="rrhh-comunicados-compose__title">Redacción del comunicado</div>

                <div class="rrhh-comunicados-callout" role="note">
                    <span class="rrhh-comunicados-callout__badge">Importante</span>
                    <p>
                        El <strong>asunto</strong> y el <strong>mensaje</strong> los redacta usted por completo según lo que necesite comunicar.
                        Opcional: use <code>{{nombre}}</code> para personalizar el texto con el nombre de cada destinatario.
                        Al enviar, el sistema solo agrega el saludo (<em>Estimado/a…</em>) y la firma de
                        <strong>Talento Humano — Hospital MEDICASA</strong>.
                    </p>
                </div>

                <div class="form-group rrhh-form-group rrhh-comunicados-field">
                    <label for="rrhhComSubject">Asunto</label>
                    <input type="text" id="rrhhComSubject" class="rrhh-form-control" maxlength="180" required
                           placeholder="Escriba el asunto del comunicado"
                           value="">
                </div>
                <div class="form-group rrhh-form-group rrhh-comunicados-field">
                    <label for="rrhhComMessage">Mensaje</label>
                    <textarea id="rrhhComMessage" class="rrhh-form-control rrhh-comunicados-message" rows="10" required
                              placeholder="Redacte aquí el comunicado completo. El contenido es libre y queda a cargo de Recursos Humanos."></textarea>
                </div>
                <div class="rrhh-form-actions rrhh-comunicados-submit-row">
                    <span class="rrhh-comunicados-meta" id="rrhhComReadyCount">0 destinatario(s) listos</span>
                    <button type="submit" class="button rrhh-btn-submit" id="rrhhComSubmit">Enviar comunicados</button>
                </div>
            </div>
        </form>
    </div>
</div>
