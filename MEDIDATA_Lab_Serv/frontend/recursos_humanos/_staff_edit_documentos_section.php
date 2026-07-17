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
<script src="../../backend/registros/script/staff_edit_docs.js"></script>
