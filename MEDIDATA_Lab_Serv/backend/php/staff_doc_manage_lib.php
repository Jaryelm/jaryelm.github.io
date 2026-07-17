<?php
/**
 * Subir / eliminar documentos de personal (BLOB en staff + archivos hiring_requirements).
 */

require_once __DIR__ . '/staff_colaborador_bootstrap.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';

if (!function_exists('medidata_staff_doc_tables')) {
    function medidata_staff_doc_tables(): array
    {
        return [
            'staff_administrative'   => ['id' => 'idadm', 'numide' => 'numide'],
            'staff_general_services' => ['id' => 'idsg', 'numide' => 'numide'],
            'nurse'                  => ['id' => 'idnur', 'numide' => 'numide'],
            'doctor'                 => ['id' => 'idodc', 'numide' => 'ceddoc'],
            'staff_medifarma'        => ['id' => 'idmf', 'numide' => 'numide'],
        ];
    }
}

if (!function_exists('medidata_staff_doc_blob_keys')) {
    function medidata_staff_doc_blob_keys(): array
    {
        return ['solicitud', 'psicometricas', 'contrato'];
    }
}

if (!function_exists('medidata_staff_doc_hiring_keys')) {
    function medidata_staff_doc_hiring_keys(): array
    {
        return [
            'birth_cert_children',
            'photo_id_card',
            'id_document',
            'utility_bill',
            'criminal_record',
            'police_record',
            'personal_references',
            'professional_references',
            'diplomas',
            'home_sketch',
        ];
    }
}

if (!function_exists('medidata_staff_doc_resolve_table')) {
    function medidata_staff_doc_resolve_table(?string $table): array
    {
        $tables = medidata_staff_doc_tables();
        if ($table === null || $table === '') {
            $table = 'staff_administrative';
        }
        if (!isset($tables[$table])) {
            throw new InvalidArgumentException('Tabla no permitida.');
        }
        return [$table, $tables[$table]['id'], $tables[$table]['numide']];
    }
}

if (!function_exists('medidata_staff_doc_fetch_staff_row')) {
    function medidata_staff_doc_fetch_staff_row(PDO $connect, string $table, int $id): ?array
    {
        [$table, $idCol, $numideCol] = medidata_staff_doc_resolve_table($table);
        $stmt = $connect->prepare("
            SELECT {$idCol} AS staff_id, {$numideCol} AS numide, id_candidate_rrhh
            FROM {$table}
            WHERE {$idCol} = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('medidata_staff_doc_ensure_candidate')) {
    function medidata_staff_doc_ensure_candidate(PDO $connect, string $table, int $id): int
    {
        $staff = medidata_staff_doc_fetch_staff_row($connect, $table, $id);
        if (!$staff) {
            throw new RuntimeException('Colaborador no encontrado.');
        }
        if (!empty($staff['id_candidate_rrhh'])) {
            return (int) $staff['id_candidate_rrhh'];
        }

        $pdoRrhh = medidata_rrhh_pdo();
        if (!$pdoRrhh) {
            throw new RuntimeException('Base de datos RRHH no disponible.');
        }

        $numide = trim((string) ($staff['numide'] ?? ''));
        if ($numide !== '') {
            $stmtC = $pdoRrhh->prepare('SELECT id FROM candidates WHERE dni = ? LIMIT 1');
            $stmtC->execute([$numide]);
            $found = $stmtC->fetchColumn();
            if ($found) {
                $idCandidate = (int) $found;
                [$tableName, $idCol] = medidata_staff_doc_resolve_table($table);
                $connect->prepare("UPDATE {$tableName} SET id_candidate_rrhh = ? WHERE {$idCol} = ? LIMIT 1")
                    ->execute([$idCandidate, $id]);
                return $idCandidate;
            }
        }

        throw new RuntimeException('No hay expediente RRHH vinculado a este colaborador.');
    }
}

if (!function_exists('medidata_staff_doc_delete_blob')) {
    function medidata_staff_doc_delete_blob(PDO $connect, string $table, int $id, string $doc): void
    {
        if (!in_array($doc, medidata_staff_doc_blob_keys(), true)) {
            throw new InvalidArgumentException('Documento no válido.');
        }
        [$tableName, $idCol] = medidata_staff_doc_resolve_table($table);
        $column = 'url_' . $doc;
        $stmt = $connect->prepare("UPDATE {$tableName} SET {$column} = NULL WHERE {$idCol} = ? LIMIT 1");
        $stmt->execute([$id]);
    }
}

if (!function_exists('medidata_staff_doc_upload_blob')) {
    function medidata_staff_doc_upload_blob(PDO $connect, string $table, int $id, string $doc, string $fileContent): void
    {
        if (!in_array($doc, medidata_staff_doc_blob_keys(), true)) {
            throw new InvalidArgumentException('Documento no válido.');
        }
        [$tableName, $idCol] = medidata_staff_doc_resolve_table($table);
        $column = 'url_' . $doc;
        $stmt = $connect->prepare("UPDATE {$tableName} SET {$column} = ? WHERE {$idCol} = ? LIMIT 1");
        $stmt->bindValue(1, $fileContent, PDO::PARAM_LOB);
        $stmt->bindValue(2, $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}

if (!function_exists('medidata_staff_doc_delete_hiring')) {
    function medidata_staff_doc_delete_hiring(PDO $connect, string $table, int $id, string $doc): void
    {
        if (!in_array($doc, medidata_staff_doc_hiring_keys(), true)) {
            throw new InvalidArgumentException('Documento no válido.');
        }
        $pdoRrhh = medidata_rrhh_pdo();
        if (!$pdoRrhh) {
            throw new RuntimeException('Base de datos RRHH no disponible.');
        }

        $idCandidate = medidata_staff_doc_ensure_candidate($connect, $table, $id);
        $stmt = $pdoRrhh->prepare("SELECT {$doc} FROM hiring_requirements WHERE id_candidate = ? LIMIT 1");
        $stmt->execute([$idCandidate]);
        $path = $stmt->fetchColumn();

        if ($path) {
            $fullPath = realpath(__DIR__ . '/../../' . ltrim((string) $path, '/'));
            $uploadsRoot = realpath(__DIR__ . '/../../uploads/staff');
            if ($fullPath && $uploadsRoot && strpos($fullPath, $uploadsRoot) === 0 && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $pdoRrhh->prepare("UPDATE hiring_requirements SET {$doc} = NULL WHERE id_candidate = ? LIMIT 1")
            ->execute([$idCandidate]);
    }
}

if (!function_exists('medidata_staff_doc_upload_hiring')) {
    function medidata_staff_doc_upload_hiring(PDO $connect, string $table, int $id, string $doc, string $tmpPath, string $originalName): void
    {
        if (!in_array($doc, medidata_staff_doc_hiring_keys(), true)) {
            throw new InvalidArgumentException('Documento no válido.');
        }
        $pdoRrhh = medidata_rrhh_pdo();
        if (!$pdoRrhh) {
            throw new RuntimeException('Base de datos RRHH no disponible.');
        }

        $staff = medidata_staff_doc_fetch_staff_row($connect, $table, $id);
        if (!$staff) {
            throw new RuntimeException('Colaborador no encontrado.');
        }

        $idCandidate = medidata_staff_doc_ensure_candidate($connect, $table, $id);
        $stmt = $pdoRrhh->prepare("SELECT {$doc} FROM hiring_requirements WHERE id_candidate = ? LIMIT 1");
        $stmt->execute([$idCandidate]);
        $oldPath = $stmt->fetchColumn();
        if ($oldPath) {
            $fullPath = realpath(__DIR__ . '/../../' . ltrim((string) $oldPath, '/'));
            $uploadsRoot = realpath(__DIR__ . '/../../uploads/staff');
            if ($fullPath && $uploadsRoot && strpos($fullPath, $uploadsRoot) === 0 && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $numide = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($staff['numide'] ?? 'staff')) ?: 'staff';

        $uploadDir = __DIR__ . '/../../uploads/staff/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = $doc . '_' . $numide . '_' . time() . ($ext ? '.' . $ext : '');
        if (!move_uploaded_file($tmpPath, $uploadDir . $filename)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        $dbPath = '/uploads/staff/' . $filename;
        $stmt = $pdoRrhh->prepare('SELECT id FROM hiring_requirements WHERE id_candidate = ? LIMIT 1');
        $stmt->execute([$idCandidate]);
        if (!$stmt->fetchColumn()) {
            $pdoRrhh->prepare('INSERT INTO hiring_requirements (id_candidate, created_by) VALUES (?, ?)')
                ->execute([$idCandidate, $_SESSION['name'] ?? 'System']);
        }

        $pdoRrhh->prepare("UPDATE hiring_requirements SET {$doc} = ? WHERE id_candidate = ? LIMIT 1")
            ->execute([$dbPath, $idCandidate]);
    }
}

if (!function_exists('medidata_staff_doc_manage')) {
    function medidata_staff_doc_manage(PDO $connect, string $action, string $table, int $id, string $doc, ?array $file = null): array
    {
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'ID incorrecto.'];
        }

        $isBlob = in_array($doc, medidata_staff_doc_blob_keys(), true);
        $isHiring = in_array($doc, medidata_staff_doc_hiring_keys(), true);
        if (!$isBlob && !$isHiring) {
            return ['status' => 'error', 'message' => 'Tipo de documento no válido.'];
        }

        try {
            if ($action === 'delete') {
                if ($isBlob) {
                    medidata_staff_doc_delete_blob($connect, $table, $id, $doc);
                } else {
                    medidata_staff_doc_delete_hiring($connect, $table, $id, $doc);
                }
                return ['status' => 'success', 'message' => 'Documento eliminado.'];
            }

            if ($action === 'upload') {
                if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    return ['status' => 'error', 'message' => 'No se recibió un archivo válido.'];
                }
                if ($isBlob) {
                    $content = file_get_contents($file['tmp_name']);
                    medidata_staff_doc_upload_blob($connect, $table, $id, $doc, $content);
                } else {
                    medidata_staff_doc_upload_hiring($connect, $table, $id, $doc, $file['tmp_name'], $file['name'] ?? 'documento');
                }
                return ['status' => 'success', 'message' => 'Documento subido correctamente.'];
            }

            return ['status' => 'error', 'message' => 'Acción no permitida.'];
        } catch (Throwable $e) {
            error_log('medidata_staff_doc_manage: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
