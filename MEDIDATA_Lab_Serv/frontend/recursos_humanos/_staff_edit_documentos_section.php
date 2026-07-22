<?php
/**
 * Sección de documentos — formularios de edición de personal.
 */
require_once __DIR__ . '/../../backend/php/staff_form_docs_lib.php';

$staffDocId = (int) ($staffDocId ?? 0);
$staffDocTable = $staffDocTable ?? 'staff_administrative';
$staffDocIdcol = $staffDocIdcol ?? null;
$staffDocRrhh = is_array($staffDocRrhh ?? null) ? $staffDocRrhh : [];

if (!isset($staffDocHas) && isset($staffDocRow) && is_object($staffDocRow)) {
    $staffDocHas = medidata_staff_doc_flags_from_row($staffDocRow);
}
$staffDocHas = is_array($staffDocHas ?? null) ? $staffDocHas : [];

$blobUrl = static function (string $doc) use ($staffDocId, $staffDocTable, $staffDocIdcol): string {
    return medidata_staff_blob_doc_url($staffDocId, $doc, $staffDocTable, $staffDocIdcol);
};
$pathUrl = static function (?string $col) use ($staffDocRrhh): ?string {
    return medidata_staff_path_doc_url($staffDocRrhh[$col] ?? null);
};
$pathHas = static function (?string $col) use ($staffDocRrhh): bool {
    return medidata_staff_has_path_doc($staffDocRrhh[$col] ?? null);
};
?>
<div class="staff-form-section staff-form-section--docs">
    <h3 class="staff-form-section__title">Documentos</h3>
    <p class="staff-form-section__hint">Los documentos en verde ya están guardados. Puede verlos, eliminarlos o subir uno nuevo (la subida es inmediata al seleccionar el archivo).</p>

    <div class="staff-doc-grid"
         data-staff-id="<?php echo $staffDocId; ?>"
         data-staff-table="<?php echo htmlspecialchars($staffDocTable); ?>"
         data-endpoint="../../backend/php/staff_doc_manage.php">
        <?php
        medidata_staff_render_doc_field([
            'label' => 'Solicitud de empleo',
            'name' => 'doc_solicitud',
            'doc_key' => 'solicitud',
            'doc_kind' => 'blob',
            'accept' => '.pdf,.doc,.docx,.jpg,.png',
            'has' => !empty($staffDocHas['solicitud']),
            'view_url' => !empty($staffDocHas['solicitud']) ? $blobUrl('solicitud') : null,
            'view_title' => 'Solicitud de empleo',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Pruebas psicométricas',
            'name' => 'doc_psicometricas',
            'doc_key' => 'psicometricas',
            'doc_kind' => 'blob',
            'accept' => '.pdf,.doc,.docx,.jpg,.png',
            'has' => !empty($staffDocHas['psicometricas']),
            'view_url' => !empty($staffDocHas['psicometricas']) ? $blobUrl('psicometricas') : null,
            'view_title' => 'Pruebas psicométricas',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Contrato firmado',
            'name' => 'doc_contrato',
            'doc_key' => 'contrato',
            'doc_kind' => 'blob',
            'accept' => '.pdf,.jpg,.png',
            'has' => !empty($staffDocHas['contrato']),
            'view_url' => !empty($staffDocHas['contrato']) ? $blobUrl('contrato') : null,
            'view_title' => 'Contrato firmado',
            'highlight' => true,
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Curriculum Vitae',
            'name' => 'doc_curriculum_vitae',
            'doc_key' => 'curriculum_vitae',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.doc,.docx',
            'has' => $pathHas('curriculum_vitae'),
            'view_url' => $pathUrl('curriculum_vitae'),
            'view_title' => 'Curriculum Vitae',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Partida de nacimiento de hijos',
            'name' => 'doc_birth_cert_children',
            'doc_key' => 'birth_cert_children',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.jpg,.png',
            'has' => $pathHas('birth_cert_children'),
            'view_url' => $pathUrl('birth_cert_children'),
            'view_title' => 'Partida de nacimiento',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Foto (carnet)',
            'name' => 'doc_photo_id_card',
            'doc_key' => 'photo_id_card',
            'doc_kind' => 'hiring',
            'accept' => '.jpg,.png',
            'has' => $pathHas('photo_id_card'),
            'view_url' => $pathUrl('photo_id_card'),
            'view_title' => 'Foto carnet',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Documento de identidad',
            'name' => 'doc_id_document',
            'doc_key' => 'id_document',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.jpg,.png',
            'has' => $pathHas('id_document'),
            'view_url' => $pathUrl('id_document'),
            'view_title' => 'Documento de identidad',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Recibo (agua, luz, teléfono)',
            'name' => 'doc_utility_bill',
            'doc_key' => 'utility_bill',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.jpg,.png',
            'has' => $pathHas('utility_bill'),
            'view_url' => $pathUrl('utility_bill'),
            'view_title' => 'Recibo de servicios',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Antecedentes penales',
            'name' => 'doc_criminal_record',
            'doc_key' => 'criminal_record',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.jpg,.png',
            'has' => $pathHas('criminal_record'),
            'view_url' => $pathUrl('criminal_record'),
            'view_title' => 'Antecedentes penales',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Antecedentes policiales',
            'name' => 'doc_police_record',
            'doc_key' => 'police_record',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.jpg,.png',
            'has' => $pathHas('police_record'),
            'view_url' => $pathUrl('police_record'),
            'view_title' => 'Antecedentes policiales',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Referencias personales',
            'name' => 'doc_personal_references',
            'doc_key' => 'personal_references',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.zip,.rar',
            'has' => $pathHas('personal_references'),
            'view_url' => $pathUrl('personal_references'),
            'view_title' => 'Referencias personales',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Referencias profesionales',
            'name' => 'doc_professional_references',
            'doc_key' => 'professional_references',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.zip,.rar',
            'has' => $pathHas('professional_references'),
            'view_url' => $pathUrl('professional_references'),
            'view_title' => 'Referencias profesionales',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Diplomas o títulos',
            'name' => 'doc_diplomas',
            'doc_key' => 'diplomas',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.zip,.rar',
            'has' => $pathHas('diplomas'),
            'view_url' => $pathUrl('diplomas'),
            'view_title' => 'Diplomas o títulos',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Croquis de vivienda',
            'name' => 'doc_home_sketch',
            'doc_key' => 'home_sketch',
            'doc_kind' => 'hiring',
            'accept' => '.pdf,.jpg,.png',
            'has' => $pathHas('home_sketch'),
            'view_url' => $pathUrl('home_sketch'),
            'view_title' => 'Croquis de vivienda',
        ]);
        ?>
    </div>
</div>

<div class="card p-3 mb-3">
    <h5 class="mb-2">Expediente del Colaborador (Documentos)</h5>
    <p class="text-muted" style="font-size: 13px;">Envíe este enlace para que el colaborador pueda subir directamente sus propios documentos faltantes (CV, actas, certificados) desde su casa o móvil.</p>
    <button type="button" id="btn-staff-expediente-link" style="background:#28a745;color:#fff;border:none;padding:10px 15px;border-radius:4px;cursor:pointer;font-weight:bold;">
        <i class="bx bx-upload" style="margin-right:5px;"></i> Generar / Enviar enlace de Expediente
    </button>
</div>

<script>
window.MEDIDATA_STAFF_EXPEDIENTE = {
    staffId: <?php echo (int) $staffDocId; ?>,
    staffTable: '<?php echo htmlspecialchars($staffDocTable); ?>',
    apiUrl: '../../backend/php/staff_expediente_link.php'
};
</script>
<script src="../../backend/registros/script/staff_expediente_link.js"></script>

<div class="card p-3 mb-3">
    <h5 class="mb-2">Formulario de Empleado</h5>
    <p class="text-muted" style="font-size: 13px;">Al enviar este enlace, se generará un perfil para que el colaborador llene sus datos (dirección, fecha nacimiento, etc).</p>
    <button type="button" class="btn-enviar-solicitud" style="background:#06adbf;color:#fff;border:none;padding:10px 15px;border-radius:4px;cursor:pointer;font-weight:bold;">
        <i class="bx bx-link" style="margin-right:5px;"></i> Generar / Enviar enlace de Formulario
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.querySelector('.btn-enviar-solicitud');
    if (btn) {
        btn.addEventListener('click', function() {
            Swal.fire({
                title: 'Generando enlace...',
                text: 'Creando o verificando perfil del empleado para el formulario.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            var fd = new FormData();
            fd.append('action', 'get_link');
            fd.append('id', '<?php echo $staffDocId; ?>');
            fd.append('table', '<?php echo htmlspecialchars($staffDocTable); ?>');

            fetch('../../backend/php/rrhh_enviar_formulario_empleado.php', {
                method: 'POST',
                body: fd
            }).then(r => r.json()).then(res => {
                if (!res.success) {
                    Swal.fire('Error', res.message, 'error');
                    return;
                }
                
                var url = res.url;
                var email = res.email;
                var html = '<p style="text-align:left;margin:0 0 10px;">Enlace para que el colaborador complete su formulario:</p>'
                    + '<input id="swal-form-url" type="text" readonly class="swal2-input" style="width:100%;font-size:13px;" value="' + url + '">'
                    + (email 
                        ? '<p style="text-align:left;font-size:13px;color:#555;margin-top:10px;">Correo del empleado: <strong>' + email + '</strong></p>' 
                        : '<p style="text-align:left;font-size:13px;color:#856404;margin-top:10px;">El empleado no tiene correo registrado. Copie el enlace y envíelo manualmente.</p>');

                Swal.fire({
                    title: 'Enlace Generado',
                    html: html,
                    width: 600,
                    showCancelButton: true,
                    showDenyButton: !!email,
                    confirmButtonText: 'Copiar enlace',
                    denyButtonText: 'Enviar por correo',
                    cancelButtonText: 'Cerrar',
                    reverseButtons: true,
                    didOpen: () => {
                        $('.swal2-actions').append('<a class="swal2-confirm swal2-styled" style="background-color: #06adbf; margin-left: 5px; display: inline-block; text-decoration: none;" href="' + url + '" target="_blank">Ver</a>');
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        navigator.clipboard.writeText(url).then(() => {
                            Swal.fire({ icon: 'success', title: 'Copiado', timer: 1200, showConfirmButton: false });
                        });
                    } else if (result.isDenied) {
                        Swal.fire({
                            title: 'Enviando...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                        var fdSend = new FormData();
                        fdSend.append('action', 'send_email');
                        fdSend.append('id', '<?php echo $staffDocId; ?>');
                        fdSend.append('table', '<?php echo htmlspecialchars($staffDocTable); ?>');
                        fetch('../../backend/php/rrhh_enviar_formulario_empleado.php', {
                            method: 'POST',
                            body: fdSend
                        }).then(r => r.json()).then(resSend => {
                            if (resSend.success) Swal.fire('Enviado', resSend.message, 'success');
                            else Swal.fire('Error', resSend.message, 'error');
                        });
                    }
                });
            });
        });
    }
});
</script>
<script src="../../backend/registros/script/staff_edit_docs.js"></script>
