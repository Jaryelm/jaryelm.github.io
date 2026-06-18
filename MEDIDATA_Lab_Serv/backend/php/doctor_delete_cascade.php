<?php
/**
 * Elimina un médico y sus dependencias directas (citas/eventos, remitentes, etc.).
 *
 * @return array{success:bool,message:string,deleted?:array<string,int>}
 */
function medidata_delete_doctor_cascade(PDO $connect, int $idodc): array
{
    if ($idodc <= 0) {
        return ['success' => false, 'message' => 'Identificador de médico no válido.'];
    }

    $check = $connect->prepare('SELECT idodc FROM doctor WHERE idodc = ? LIMIT 1');
    $check->execute([$idodc]);
    if (!$check->fetchColumn()) {
        return ['success' => false, 'message' => 'No se encontró el médico o ya fue eliminado.'];
    }

    $deleted = [];

    try {
        $connect->beginTransaction();

        // Columnas relacionadas sin FK formal en algunos entornos.
        $manualDeletes = [
            ['remitentes_honorarios', 'id_doctor_remitente'],
        ];

        foreach ($manualDeletes as [$table, $column]) {
            if (!medidata_doctor_table_exists($connect, $table)) {
                continue;
            }
            $stmt = $connect->prepare("DELETE FROM `$table` WHERE `$column` = ?");
            $stmt->execute([$idodc]);
            $deleted[$table] = $stmt->rowCount();
        }

        // Hijos con FK RESTRICT / NO ACTION hacia doctor.
        $fkStmt = $connect->query("
            SELECT kcu.TABLE_NAME, kcu.COLUMN_NAME, rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
               AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
            WHERE kcu.TABLE_SCHEMA = DATABASE()
              AND kcu.REFERENCED_TABLE_NAME = 'doctor'
              AND kcu.REFERENCED_COLUMN_NAME = 'idodc'
              AND rc.DELETE_RULE NOT IN ('CASCADE', 'SET NULL')
        ");

        if ($fkStmt) {
            while ($row = $fkStmt->fetch(PDO::FETCH_ASSOC)) {
                $table = (string) ($row['TABLE_NAME'] ?? '');
                $column = (string) ($row['COLUMN_NAME'] ?? '');
                if ($table === '' || $column === '') {
                    continue;
                }
                $stmt = $connect->prepare("DELETE FROM `$table` WHERE `$column` = ?");
                $stmt->execute([$idodc]);
                $deleted[$table] = ($deleted[$table] ?? 0) + $stmt->rowCount();
            }
        }

        $stmtDoctor = $connect->prepare('DELETE FROM doctor WHERE idodc = ? LIMIT 1');
        $stmtDoctor->execute([$idodc]);

        if ($stmtDoctor->rowCount() <= 0) {
            throw new RuntimeException('No se pudo eliminar el médico.');
        }

        $connect->commit();

        $eventsCount = (int) ($deleted['events'] ?? 0);
        $message = 'Médico eliminado correctamente.';
        if ($eventsCount > 0) {
            $message .= " También se eliminaron $eventsCount cita(s) o evento(s) vinculados.";
        }

        return [
            'success' => true,
            'message' => $message,
            'deleted' => $deleted,
        ];
    } catch (Throwable $e) {
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        error_log('medidata_delete_doctor_cascade idodc=' . $idodc . ': ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'No se pudo eliminar el médico. Si persiste, contacte al administrador del sistema.',
        ];
    }
}

function medidata_doctor_table_exists(PDO $connect, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $stmt = $connect->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}
