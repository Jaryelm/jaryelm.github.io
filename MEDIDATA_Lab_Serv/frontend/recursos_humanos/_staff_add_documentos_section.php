<?php
/**
 * Documentos en formulario de alta (subida al guardar el registro).
 */
require_once __DIR__ . '/../../backend/php/staff_form_docs_lib.php';
?>
<div class="staff-form-section staff-form-section--docs">
    <h3 class="staff-form-section__title">Documentos</h3>
    <p class="staff-form-section__hint">Todos los documentos son opcionales. Se guardarán al registrar el colaborador.</p>
    <div class="staff-doc-grid">
        <?php
        medidata_staff_render_doc_field([
            'label' => 'Solicitud de empleo',
            'name' => 'doc_solicitud',
            'doc_key' => 'solicitud',
            'accept' => '.pdf,.doc,.docx,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Solicitud de empleo',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Pruebas psicométricas',
            'name' => 'doc_psicometricas',
            'doc_key' => 'psicometricas',
            'accept' => '.pdf,.doc,.docx,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Pruebas psicométricas',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Contrato firmado',
            'name' => 'doc_contrato',
            'doc_key' => 'contrato',
            'accept' => '.pdf,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Contrato firmado',
            'highlight' => true,
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Curriculum Vitae',
            'name' => 'doc_curriculum_vitae',
            'doc_key' => 'curriculum_vitae',
            'accept' => '.pdf,.doc,.docx',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Curriculum Vitae',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Partida de nacimiento de hijos',
            'name' => 'doc_birth_cert_children',
            'doc_key' => 'birth_cert_children',
            'accept' => '.pdf,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Partida de nacimiento',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Foto (carnet)',
            'name' => 'doc_photo_id_card',
            'doc_key' => 'photo_id_card',
            'accept' => '.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Foto carnet',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Documento de identidad',
            'name' => 'doc_id_document',
            'doc_key' => 'id_document',
            'accept' => '.pdf,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Documento de identidad',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Recibo (agua, luz, teléfono)',
            'name' => 'doc_utility_bill',
            'doc_key' => 'utility_bill',
            'accept' => '.pdf,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Recibo de servicios',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Antecedentes penales',
            'name' => 'doc_criminal_record',
            'doc_key' => 'criminal_record',
            'accept' => '.pdf,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Antecedentes penales',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Antecedentes policiales',
            'name' => 'doc_police_record',
            'doc_key' => 'police_record',
            'accept' => '.pdf,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Antecedentes policiales',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Referencias personales',
            'name' => 'doc_personal_references',
            'doc_key' => 'personal_references',
            'accept' => '.pdf,.zip,.rar',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Referencias personales',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Referencias profesionales',
            'name' => 'doc_professional_references',
            'doc_key' => 'professional_references',
            'accept' => '.pdf,.zip,.rar',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Referencias profesionales',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Diplomas o títulos',
            'name' => 'doc_diplomas',
            'doc_key' => 'diplomas',
            'accept' => '.pdf,.zip,.rar',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Diplomas o títulos',
        ]);
        medidata_staff_render_doc_field([
            'label' => 'Croquis de vivienda',
            'name' => 'doc_home_sketch',
            'doc_key' => 'home_sketch',
            'accept' => '.pdf,.jpg,.png',
            'has' => false,
            'view_url' => null,
            'view_title' => 'Croquis de vivienda',
        ]);
        ?>
    </div>
</div>
<script src="../../backend/registros/script/staff_edit_docs.js"></script>
