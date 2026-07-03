<?php
include_once '../registros/session_check.php';
require_once 'staff_colaborador_bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$doc = $_GET['doc'] ?? '';

if ($id <= 0 || !in_array($doc, ['contrato', 'solicitud', 'psicometricas'])) {
    die('Documento no válido.');
}

// Soporte para la lista unificada de colaboradores: permite consultar el
// documento en la tabla correcta (administrativo / servicios generales /
// enfermera / doctor). Por defecto usa staff_administrative para mantener
// compatibilidad con administrativo.php que solo envia id + doc.
$tablas_permitidas = [
    'staff_administrative'   => 'idadm',
    'staff_general_services' => 'idsg',
    'nurse'                  => 'idnur',
    'doctor'                 => 'idodc',
];
$table = $_GET['table'] ?? 'staff_administrative';

// Cuentas tipo "Usuario": el contrato vive en users_rrhh_extra (solo 'contrato').
if ($table === 'users') {
    if ($doc !== 'contrato') {
        die('Documento no válido.');
    }
    $table = 'users_rrhh_extra';
    $id_col = 'id_user';
} else {
    if (!array_key_exists($table, $tablas_permitidas)) {
        die('Origen no válido.');
    }
    $id_col = $tablas_permitidas[$table];
}

$column = 'url_' . $doc;

try {
    $stmt = $connect->prepare("SELECT $column FROM {$table} WHERE {$id_col} = ? LIMIT 1");
    $stmt->execute([$id]);
    $blob = $stmt->fetchColumn();

    if (empty($blob)) {
        die('No hay documento subido.');
    }

    // Attempt to guess if it's a PDF by looking at the first 4 bytes
    $isPdf = (strpos($blob, '%PDF') === 0);
    
    if ($isPdf) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $doc . '_' . $id . '.pdf"');
    } else {
        // Fallback for images (JPEG/PNG)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($blob) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $doc . '_' . $id . '"');
    }

    echo $blob;
} catch (Exception $e) {
    die('Error al obtener el documento.');
}
