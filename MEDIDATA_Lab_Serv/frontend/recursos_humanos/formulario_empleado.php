<?php
require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/rrhh_employee_form_lib.php';
require_once __DIR__ . '/../../backend/php/rrhh_employee_form_fields_lib.php';

$token = trim((string) ($_GET['token'] ?? ''));
$ctx = $token !== '' ? medidata_rrhh_employee_form_by_token($token) : null;
$alreadySent = $ctx && ($ctx['form_status'] ?? '') === 'Enviado';
$prefill = $ctx ? medidata_rrhh_employee_form_prefill($ctx) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
    <style>
        body { background: #f4f4f4; margin: 0; }
        .fe-wrap { max-width: 960px; margin: 24px auto; padding: 0 16px 40px; }
        .fe-card { background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,.08); padding: 24px; }
        .fe-card h1 { margin: 0 0 8px; color: #035c67; font-size: 1.5rem; }
        .fe-card p.lead { color: #555; margin: 0 0 20px; }
        .fe-section { margin: 28px 0 0; padding-top: 8px; border-top: 2px solid #e8f4f5; }
        .fe-section h2 { margin: 0 0 12px; color: #035c67; font-size: 1.15rem; }
        .fe-subsection { margin: 16px 0; padding: 12px; background: #fafafa; border-radius: 8px; }
        .fe-subsection h3 { margin: 0 0 10px; font-size: 1rem; color: #333; }
        .fe-hint { color: #666; font-size: .88rem; margin: 0 0 12px; }
        .fe-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: start; }
        .fe-grid > div { display: flex; flex-direction: column; justify-content: flex-start; }
        .fe-grid .full { grid-column: 1 / -1; }
        .fe-grid label { font-weight: 600; margin-bottom: 4px; color: #333; font-size: .9rem; }
        .fe-grid input:not([type="radio"]):not([type="checkbox"]), .fe-grid select, .fe-grid textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: .95rem; box-sizing: border-box; font-family: inherit;
        }
        .fe-grid input:not([type="radio"]):not([type="checkbox"]), .fe-grid select { height: 44px; }
        .fe-actions { margin-top: 24px; text-align: center; position: sticky; bottom: 0; background: #fff; padding: 16px 0; }
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
            <p class="lead">Gracias, <?php echo htmlspecialchars((string) $ctx['fullname'], ENT_QUOTES, 'UTF-8'); ?>. Su solicitud ya fue enviada correctamente.</p>
        <?php else: ?>
            <h1>Solicitud de empleo</h1>
            <p class="lead">Hospital MEDICASA — <?php echo htmlspecialchars((string) $ctx['fullname'], ENT_QUOTES, 'UTF-8'); ?></p>

            <form id="fe-form" method="post" action="#" autocomplete="off">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                <?php include __DIR__ . '/_formulario_empleado_campos.php'; ?>
                <div class="fe-actions">
                    <button type="submit" class="registerbtn">Enviar solicitud</button>
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
