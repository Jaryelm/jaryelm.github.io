<?php
/**
 * Portal público de documentos del colaborador (misma lista que la sección Documentos).
 */

require_once __DIR__ . '/staff_doc_manage_lib.php';
require_once __DIR__ . '/rrhh_candidato_workflow_lib.php';

if (!function_exists('medidata_staff_expediente_documentos')) {
    /**
     * Catálogo alineado con _staff_edit_documentos_section.php
     * @return array<string, array{label:string, kind:string, accept:string}>
     */
    function medidata_staff_expediente_documentos(): array
    {
        return [
            'curriculum_vitae' => [
                'label' => 'Curriculum Vitae',
                'kind' => 'hiring',
                'accept' => '.pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'birth_cert_children' => [
                'label' => 'Partida de nacimiento de hijos',
                'kind' => 'hiring',
                'accept' => '.pdf,.jpg,.png,application/pdf,image/jpeg,image/png',
            ],
            'photo_id_card' => [
                'label' => 'Foto (carnet)',
                'kind' => 'hiring',
                'accept' => '.jpg,.png,image/jpeg,image/png',
            ],
            'id_document' => [
                'label' => 'Documento de identidad',
                'kind' => 'hiring',
                'accept' => '.pdf,.jpg,.png,application/pdf,image/jpeg,image/png',
            ],
            'utility_bill' => [
                'label' => 'Recibo (agua, luz, teléfono)',
                'kind' => 'hiring',
                'accept' => '.pdf,.jpg,.png,application/pdf,image/jpeg,image/png',
            ],
            'criminal_record' => [
                'label' => 'Antecedentes penales',
                'kind' => 'hiring',
                'accept' => '.pdf,.jpg,.png,application/pdf,image/jpeg,image/png',
            ],
            'police_record' => [
                'label' => 'Antecedentes policiales',
                'kind' => 'hiring',
                'accept' => '.pdf,.jpg,.png,application/pdf,image/jpeg,image/png',
            ],
            'personal_references' => [
                'label' => 'Referencias personales',
                'kind' => 'hiring',
                'accept' => '.pdf,.zip,.rar,application/pdf,application/zip',
            ],
            'professional_references' => [
                'label' => 'Referencias profesionales',
                'kind' => 'hiring',
                'accept' => '.pdf,.zip,.rar,application/pdf,application/zip',
            ],
            'diplomas' => [
                'label' => 'Diplomas o títulos',
                'kind' => 'hiring',
                'accept' => '.pdf,.zip,.rar,application/pdf,application/zip',
            ],
            'home_sketch' => [
                'label' => 'Croquis de vivienda',
                'kind' => 'hiring',
                'accept' => '.pdf,.jpg,.png,application/pdf,image/jpeg,image/png',
            ],
        ];
    }
}

if (!function_exists('medidata_staff_expediente_public_path')) {
    function medidata_staff_expediente_public_path(string $token): string
    {
        return '/frontend/recursos_humanos/expediente_colaborador.php?token=' . rawurlencode($token);
    }
}

if (!function_exists('medidata_staff_expediente_public_url')) {
    function medidata_staff_expediente_public_url(string $token): string
    {
        return medidata_absolute_url(medidata_staff_expediente_public_path($token));
    }
}

if (!function_exists('medidata_staff_find_by_candidate_id')) {
    /**
     * @return array{table:string, staff_id:int, numide:string, nombres:string, apellidos:string, correo:string}|null
     */
    function medidata_staff_find_by_candidate_id(PDO $connect, int $candidateId): ?array
    {
        if ($candidateId <= 0) {
            return null;
        }
        require_once __DIR__ . '/staff_areas_lib.php';
        foreach (medidata_staff_doc_tables() as $table => $meta) {
            $cols = medidata_staff_area_columns($table);
            if (!$cols) {
                continue;
            }
            $idCol = $meta['id'];
            $numideCol = $meta['numide'];
            $sql = "SELECT {$idCol} AS staff_id, {$numideCol} AS numide,
                           {$cols['nombres']} AS nombres, {$cols['apellidos']} AS apellidos";
            try {
                $sqlFull = $sql . ', correo_personal FROM ' . $table
                    . ' WHERE id_candidate_rrhh = ? LIMIT 1';
                $stmt = $connect->prepare($sqlFull);
                $stmt->execute([$candidateId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $stmt = $connect->prepare($sql . ' FROM ' . $table . ' WHERE id_candidate_rrhh = ? LIMIT 1');
                $stmt->execute([$candidateId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $row['correo_personal'] = '';
                }
            }
            if ($row) {
                return [
                    'table' => $table,
                    'staff_id' => (int) $row['staff_id'],
                    'numide' => (string) ($row['numide'] ?? ''),
                    'nombres' => (string) ($row['nombres'] ?? ''),
                    'apellidos' => (string) ($row['apellidos'] ?? ''),
                    'correo' => trim((string) ($row['correo_personal'] ?? '')),
                ];
            }
        }
        return null;
    }
}

if (!function_exists('medidata_staff_expediente_by_token')) {
    /**
     * @return array<string, mixed>|null
     */
    function medidata_staff_expediente_by_token(string $token, PDO $connect): ?array
    {
        $ctx = medidata_rrhh_expediente_by_token($token);
        if (!$ctx) {
            return null;
        }
        $candidateId = (int) ($ctx['candidate_id'] ?? 0);
        $staff = medidata_staff_find_by_candidate_id($connect, $candidateId);
        if (!$staff) {
            return null;
        }

        $fullname = trim($staff['nombres'] . ' ' . $staff['apellidos']);
        if ($fullname === '') {
            $fullname = (string) ($ctx['fullname'] ?? 'Colaborador/a');
        }

        return [
            'token' => $token,
            'candidate_id' => $candidateId,
            'staff_table' => $staff['table'],
            'staff_id' => $staff['staff_id'],
            'fullname' => $fullname,
            'email' => $staff['correo'] !== '' ? $staff['correo'] : trim((string) ($ctx['email'] ?? '')),
            'numide' => $staff['numide'],
        ];
    }
}

if (!function_exists('medidata_staff_expediente_estado')) {
    /**
     * @return array{
     *   completed:array<int, array{key:string,label:string}>,
     *   missing:array<int, array{key:string,label:string}>,
     *   total:int,
     *   done:int
     * }
     */
    function medidata_staff_expediente_estado(PDO $connect, string $table, int $staffId): array
    {
        $docs = medidata_staff_expediente_documentos();
        $completed = [];
        $missing = [];

        $blobFlags = ['solicitud' => false, 'psicometricas' => false, 'contrato' => false];
        try {
            [$tableName, $idCol] = medidata_staff_doc_resolve_table($table);
            $stmt = $connect->prepare("
                SELECT
                    (url_solicitud IS NOT NULL AND OCTET_LENGTH(url_solicitud) > 0) AS has_solicitud,
                    (url_psicometricas IS NOT NULL AND OCTET_LENGTH(url_psicometricas) > 0) AS has_psicometricas,
                    (url_contrato IS NOT NULL AND OCTET_LENGTH(url_contrato) > 0) AS has_contrato,
                    id_candidate_rrhh
                FROM {$tableName}
                WHERE {$idCol} = ?
                LIMIT 1
            ");
            $stmt->execute([$staffId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $blobFlags['solicitud'] = !empty($row['has_solicitud']);
            $blobFlags['psicometricas'] = !empty($row['has_psicometricas']);
            $blobFlags['contrato'] = !empty($row['has_contrato']);
            $candidateId = (int) ($row['id_candidate_rrhh'] ?? 0);
        } catch (Throwable $e) {
            $candidateId = 0;
        }

        $hiringFlags = [];
        if ($candidateId > 0) {
            require_once __DIR__ . '/staff_form_docs_lib.php';
            $hiringFlags = medidata_staff_load_hiring_docs_flags($candidateId);
        }

        foreach ($docs as $key => $doc) {
            $item = ['key' => $key, 'label' => $doc['label']];
            $has = false;
            if ($doc['kind'] === 'blob') {
                $has = !empty($blobFlags[$key]);
            } else {
                $v = $hiringFlags[$key] ?? 0;
                $has = ($v === 1 || $v === '1' || $v === true);
            }
            if ($has) {
                $completed[] = $item;
            } else {
                $missing[] = $item;
            }
        }

        return [
            'completed' => $completed,
            'missing' => $missing,
            'total' => count($docs),
            'done' => count($completed),
        ];
    }
}

if (!function_exists('medidata_staff_expediente_issue_link')) {
    /**
     * @return array{success:bool, message?:string, url?:string, email?:string, candidate_name?:string, estado?:array}
     */
    function medidata_staff_expediente_issue_link(PDO $connect, string $table, int $staffId, string $usuario): array
    {
        try {
            $candidateId = medidata_staff_doc_ensure_candidate($connect, $table, $staffId, true);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $issued = medidata_rrhh_expediente_issue_link($candidateId, $usuario, false);
        if (!$issued['success']) {
            return $issued;
        }

        $token = '';
        $pdo = medidata_rrhh_pdo();
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT expediente_access_token FROM candidates WHERE id = ? LIMIT 1');
            $stmt->execute([$candidateId]);
            $token = trim((string) $stmt->fetchColumn());
        }
        if ($token === '') {
            return ['success' => false, 'message' => 'No se pudo obtener el token del enlace.'];
        }

        $staff = medidata_staff_doc_fetch_staff_row($connect, $table, $staffId);
        $fullname = trim((string) (($staff['nombres'] ?? '') . ' ' . ($staff['apellidos'] ?? '')));
        if ($fullname === '') {
            $fullname = (string) ($issued['candidate_name'] ?? 'Colaborador/a');
        }
        $email = trim((string) ($staff['correo_personal'] ?? ''));
        if ($email === '') {
            $email = trim((string) ($issued['email'] ?? ''));
        }

        $estado = medidata_staff_expediente_estado($connect, $table, $staffId);

        return [
            'success' => true,
            'url' => medidata_staff_expediente_public_url($token),
            'email' => $email,
            'candidate_name' => $fullname,
            'candidate_id' => $candidateId,
            'estado' => $estado,
        ];
    }
}

if (!function_exists('medidata_staff_expediente_file_allowed')) {
    function medidata_staff_expediente_file_allowed(string $docKey, array $file): bool
    {
        $docs = medidata_staff_expediente_documentos();
        if (!isset($docs[$docKey])) {
            return false;
        }
        $name = strtolower((string) ($file['name'] ?? ''));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $accept = strtolower($docs[$docKey]['accept']);
        $allowedExt = [];
        foreach (explode(',', $accept) as $part) {
            $part = trim($part);
            if (str_starts_with($part, '.')) {
                $allowedExt[] = substr($part, 1);
            }
        }
        if ($ext !== '' && in_array($ext, $allowedExt, true)) {
            return true;
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            return false;
        }
        $mime = '';
        if (function_exists('medidata_rrhh_upload_mime_type')) {
            $mime = (string) medidata_rrhh_upload_mime_type($tmp);
        } elseif (class_exists('finfo')) {
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        }
        $okMimes = [
            'application/pdf', 'image/jpeg', 'image/png', 'image/jpg',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed',
        ];
        return $mime !== '' && in_array($mime, $okMimes, true);
    }
}

if (!function_exists('medidata_staff_expediente_upload')) {
    /** @return array{success:bool, message:string} */
    function medidata_staff_expediente_upload(PDO $connect, string $token, string $docKey, array $file): array
    {
        $ctx = medidata_staff_expediente_by_token($token, $connect);
        if (!$ctx) {
            return ['success' => false, 'message' => 'Enlace no válido o expirado.'];
        }

        $docs = medidata_staff_expediente_documentos();
        if (!isset($docs[$docKey])) {
            return ['success' => false, 'message' => 'Documento no reconocido.'];
        }

        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                return ['success' => false, 'message' => 'El archivo es demasiado grande. Verifique el límite de tamaño.'];
            }
            return ['success' => false, 'message' => 'Seleccione un archivo válido. (Error: ' . $err . ')'];
        }

        if (!medidata_staff_expediente_file_allowed($docKey, $file)) {
            return ['success' => false, 'message' => 'Tipo de archivo no permitido para este documento.'];
        }

        $result = medidata_staff_doc_manage(
            $connect,
            'upload',
            (string) $ctx['staff_table'],
            (int) $ctx['staff_id'],
            $docKey,
            $file
        );

        if (($result['status'] ?? '') === 'success') {
            return ['success' => true, 'message' => $result['message'] ?? 'Documento cargado correctamente.'];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'No se pudo subir el documento.'];
    }
}
