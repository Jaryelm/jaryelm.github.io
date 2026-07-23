<?php
/**
 * Portal público: el colaborador sube los mismos documentos de su ficha.
 * Sin sesión — acceso por token.
 */
require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/staff_expediente_lib.php';
require_once __DIR__ . '/../../backend/php/rrhh_employee_form_lib.php';

$token = trim((string) ($_GET['token'] ?? ''));
$ctx = ($token !== '' && isset($connect) && $connect instanceof PDO)
    ? medidata_staff_expediente_by_token($token, $connect)
    : null;

$docs = medidata_staff_expediente_documentos();
$estado = null;
$completedKeys = [];
$formLinkUrl = "#";
if ($ctx) {
    $estado = medidata_staff_expediente_estado($connect, (string) $ctx['staff_table'], (int) $ctx['staff_id']);
    foreach ($estado['completed'] as $item) {
        $completedKeys[$item['key']] = true;
    }
    
    if (isset($ctx['candidate_id']) && $ctx['candidate_id'] > 0) {
        $issue = medidata_rrhh_employee_form_issue_link((int) $ctx['candidate_id'], 'sistema_empleados', false);
        if ($issue['success']) {
            $formLinkUrl = $issue['url'];
        }
    }
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
    <title>MEDIDATA</title>
    <style>
        body { background: #f4f4f4; margin: 0; font-family: Arial, sans-serif; }
        .ex-wrap { max-width: 760px; margin: 24px auto; padding: 0 16px 40px; }
        .ex-card { background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .ex-card h1 { margin: 0 0 8px; color: #035c67; font-size: 1.45rem; }
        .ex-doc { border: 1px solid #e0e0e0; border-radius: 8px; padding: 14px; margin-bottom: 12px; }
        .ex-doc.done { border-color: #81D43A; background: #f8fff5; }
        .ex-doc label { font-weight: 600; display: block; margin-bottom: 8px; color: #333; }
        .ex-doc input[type=file] { width: 100%; }
        .ex-badge { font-size: .8rem; padding: 2px 8px; border-radius: 4px; margin-left: 8px; }
        .ex-badge-ok { background: #d4edda; color: #155724; }
        .ex-badge-pending { background: #fff3cd; color: #856404; }
        .registerbtn { background: #06adbf; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 700; cursor: pointer; margin-top: 6px; }
        .registerbtn:hover { background: #035c67; }
        .alert { background: #fff3cd; border: 1px solid #ffc107; padding: 14px; border-radius: 8px; }
    </style>
</head>
<body>
<div class="ex-wrap">
    <div class="ex-card">
        <?php if (!$ctx): ?>
        <div class="alert"><strong>Enlace no válido.</strong> Solicite un nuevo enlace a Recursos Humanos.</div>
        <?php else: ?>
        <h1>Documentos del colaborador</h1>
        <p style="color:#555;margin:0 0 16px;">Hola, <strong><?php echo htmlspecialchars((string) ($ctx['fullname'] ?? '')); ?></strong>. Suba cada documento pendiente.</p>
        <p style="font-size:.9rem;color:#666;margin:0 0 20px;">Progreso: <?php echo (int) ($estado['done'] ?? 0); ?> de <?php echo (int) ($estado['total'] ?? 0); ?> documentos.</p>

        <div class="ex-doc" style="background:#e8f4f8; border-color:#bce4eb;">
            <label style="color:#035c67;">
                Solicitud de empleo / Datos personales
            </label>
            <p style="font-size:0.9rem; color:#555; margin-bottom:10px; margin-top:0;">Por favor, ingrese al siguiente enlace para llenar o actualizar su solicitud de empleo de forma digital.</p>
            <a href="<?php echo htmlspecialchars($formLinkUrl); ?>" target="_blank" class="registerbtn" style="text-decoration:none; display:inline-block; padding:8px 14px;">Llenar Formulario</a>
        </div>

        <?php foreach ($docs as $key => $doc): ?>
        <?php $isDone = isset($completedKeys[$key]); ?>
        <div class="ex-doc <?php echo $isDone ? 'done' : ''; ?>" data-doc="<?php echo htmlspecialchars($key); ?>">
            <label>
                <?php echo htmlspecialchars($doc['label']); ?>
                <span class="ex-badge <?php echo $isDone ? 'ex-badge-ok' : 'ex-badge-pending'; ?>">
                    <?php echo $isDone ? 'Recibido' : 'Pendiente'; ?>
                </span>
            </label>
            <?php if (!$isDone): ?>
            <input type="file" class="ex-file" accept="<?php echo htmlspecialchars($doc['accept']); ?>" data-key="<?php echo htmlspecialchars($key); ?>">
            <button type="button" class="registerbtn ex-upload-btn" data-key="<?php echo htmlspecialchars($key); ?>">Subir archivo</button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script src="../../backend/js/jquery.min.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<script>
(function () {
    var token = <?php echo json_encode($token); ?>;
    var saveUrl = '../../backend/php/staff_expediente_guardar.php';

    $('.ex-upload-btn').on('click', function () {
        var key = $(this).data('key');
        var $file = $('.ex-file[data-key="' + key + '"]');
        if (!$file.length || !$file[0].files.length) {
            Swal.fire('Atención', 'Seleccione un archivo.', 'warning');
            return;
        }
        var fd = new FormData();
        fd.append('token', token);
        fd.append('doc_key', key);
        fd.append('document', $file[0].files[0]);

        Swal.fire({ title: 'Subiendo...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });

        $.ajax({
            url: saveUrl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (res) {
            if (res.success) {
                Swal.fire('Listo', res.message, 'success').then(function () { location.reload(); });
            } else {
                Swal.fire('Aviso', res.message || 'No se pudo subir.', 'warning');
            }
        }).fail(function () {
            Swal.fire('Error', 'Error de comunicación.', 'error');
        });
    });
})();
</script>
</body>
</html>
