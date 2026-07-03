<?php
require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/rrhh_employee_form_lib.php';

$token = trim((string) ($_GET['token'] ?? ''));
$ctx = $token !== '' ? medidata_rrhh_employee_form_by_token($token) : null;
$alreadySent = $ctx && ($ctx['form_status'] ?? '') === 'Enviado';

$prefill = [];
if ($ctx) {
    $payload = json_decode((string) ($ctx['payload'] ?? '{}'), true);
    if (!is_array($payload)) {
        $payload = [];
    }
    $prefill = array_merge($payload, [
        'birthdate' => $ctx['birthdate'] ?? ($payload['birthdate'] ?? ''),
        'marital_status' => $ctx['marital_status'] ?? ($payload['marital_status'] ?? ''),
        'direction' => ($ctx['direction'] ?? '') !== 'Pendiente de completar'
            ? ($ctx['direction'] ?? '')
            : ($payload['direction'] ?? ''),
    ]);
}

function fe_val(array $prefill, string $key): string
{
    return htmlspecialchars((string) ($prefill[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>Formulario de empleado — MEDICASA</title>
    <style>
        body { background: #f4f4f4; margin: 0; }
        .fe-wrap { max-width: 720px; margin: 24px auto; padding: 0 16px 40px; }
        .fe-card { background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,.08); padding: 24px; }
        .fe-card h1 { margin: 0 0 8px; color: #035c67; font-size: 1.5rem; }
        .fe-card p.lead { color: #555; margin: 0 0 20px; }
        .fe-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .fe-grid .full { grid-column: 1 / -1; }
        .fe-grid label { display: block; font-weight: 600; margin-bottom: 4px; color: #333; font-size: .9rem; }
        .fe-grid input, .fe-grid select, .fe-grid textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: .95rem;
        }
        .fe-actions { margin-top: 20px; text-align: center; }
        .registerbtn { background: #06adbf; color: #fff; border: none; padding: 12px 28px; border-radius: 6px; font-weight: 700; cursor: pointer; }
        .registerbtn:hover { background: #035c67; }
        .alert { background: #fff3cd; border: 1px solid #ffc107; padding: 14px; border-radius: 8px; color: #664d03; }
        @media (max-width: 640px) { .fe-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="fe-wrap">
    <div class="fe-card">
        <?php if (!$ctx): ?>
            <h1>Enlace no válido</h1>
            <p class="lead">Este enlace no existe o ya no está disponible. Solicite uno nuevo a Recursos Humanos.</p>
        <?php elseif ($alreadySent): ?>
            <h1>Formulario recibido</h1>
            <p class="lead">Gracias, <?php echo htmlspecialchars((string) $ctx['fullname'], ENT_QUOTES, 'UTF-8'); ?>. Su formulario ya fue enviado correctamente.</p>
        <?php else: ?>
            <h1>Formulario de empleado</h1>
            <p class="lead">Hospital MEDICASA — <?php echo htmlspecialchars((string) $ctx['fullname'], ENT_QUOTES, 'UTF-8'); ?></p>

            <form id="fe-form" autocomplete="off">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="fe-grid">
                    <div>
                        <label for="birthdate">Fecha de nacimiento</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?php echo fe_val($prefill, 'birthdate'); ?>">
                    </div>
                    <div>
                        <label for="marital_status">Estado civil</label>
                        <select id="marital_status" name="marital_status">
                            <option value="">Seleccione...</option>
                            <?php
                            $civil = ['Soltero/a', 'Casado/a', 'Unión libre', 'Divorciado/a', 'Viudo/a'];
                            foreach ($civil as $opt):
                            ?>
                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo fe_val($prefill, 'marital_status') === $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="full">
                        <label for="direction">Dirección completa</label>
                        <textarea id="direction" name="direction" rows="2"><?php echo fe_val($prefill, 'direction'); ?></textarea>
                    </div>
                    <div>
                        <label for="emergency_contact_name">Contacto de emergencia</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo fe_val($prefill, 'emergency_contact_name'); ?>">
                    </div>
                    <div>
                        <label for="emergency_contact_phone">Teléfono emergencia</label>
                        <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo fe_val($prefill, 'emergency_contact_phone'); ?>">
                    </div>
                    <div class="full">
                        <label for="emergency_contact_relation">Parentesco (emergencia)</label>
                        <input type="text" id="emergency_contact_relation" name="emergency_contact_relation" value="<?php echo fe_val($prefill, 'emergency_contact_relation'); ?>">
                    </div>
                    <div>
                        <label for="dependents_count">Número de dependientes</label>
                        <input type="number" min="0" id="dependents_count" name="dependents_count" value="<?php echo fe_val($prefill, 'dependents_count'); ?>">
                    </div>
                    <div>
                        <label for="bank_name">Banco</label>
                        <input type="text" id="bank_name" name="bank_name" value="<?php echo fe_val($prefill, 'bank_name'); ?>" placeholder="Ej. BAC">
                    </div>
                    <div class="full">
                        <label for="bank_account">Número de cuenta</label>
                        <input type="text" id="bank_account" name="bank_account" value="<?php echo fe_val($prefill, 'bank_account'); ?>">
                    </div>
                    <div class="full">
                        <label for="dependents_names">Nombres de dependientes</label>
                        <textarea id="dependents_names" name="dependents_names" rows="2"><?php echo fe_val($prefill, 'dependents_names'); ?></textarea>
                    </div>
                    <div class="full">
                        <label for="personal_reference_1">Referencia personal 1</label>
                        <input type="text" id="personal_reference_1" name="personal_reference_1" value="<?php echo fe_val($prefill, 'personal_reference_1'); ?>">
                    </div>
                    <div class="full">
                        <label for="personal_reference_2">Referencia personal 2</label>
                        <input type="text" id="personal_reference_2" name="personal_reference_2" value="<?php echo fe_val($prefill, 'personal_reference_2'); ?>">
                    </div>
                    <div class="full">
                        <label for="professional_reference_1">Referencia profesional 1</label>
                        <input type="text" id="professional_reference_1" name="professional_reference_1" value="<?php echo fe_val($prefill, 'professional_reference_1'); ?>">
                    </div>
                    <div class="full">
                        <label for="professional_reference_2">Referencia profesional 2</label>
                        <input type="text" id="professional_reference_2" name="professional_reference_2" value="<?php echo fe_val($prefill, 'professional_reference_2'); ?>">
                    </div>
                    <div class="full">
                        <label for="observations">Observaciones adicionales</label>
                        <textarea id="observations" name="observations" rows="3"><?php echo fe_val($prefill, 'observations'); ?></textarea>
                    </div>
                </div>

                <div class="fe-actions">
                    <button type="submit" class="registerbtn">Enviar formulario</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="../../backend/js/jquery.min.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<script>
$('#fe-form').on('submit', function (e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    $.ajax({
        type: 'POST',
        url: '../../backend/php/rrhh_formulario_empleado_guardar.php',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function (res) {
        if (res.success) {
            Swal.fire('¡Gracias!', res.message, 'success').then(function () {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', res.message || 'No se pudo enviar.', 'error');
            $btn.prop('disabled', false);
        }
    }).fail(function () {
        Swal.fire('Error', 'Error de comunicación.', 'error');
        $btn.prop('disabled', false);
    });
});
</script>
</body>
</html>
