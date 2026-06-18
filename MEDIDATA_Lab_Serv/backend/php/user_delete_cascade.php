<?php
/**
 * Elimina un usuario y registros vinculados (requisiciones, carrito, FK RESTRICT).
 *
 * @return array{success:bool,message:string,deleted?:array<string,int>}
 */
function medidata_delete_user_cascade(PDO $connect, int $userId): array
{
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'Identificador de usuario no válido.'];
    }

    $check = $connect->prepare('SELECT id, username FROM users WHERE id = ? LIMIT 1');
    $check->execute([$userId]);
    $row = $check->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['success' => false, 'message' => 'Usuario no encontrado o ya fue eliminado.'];
    }

    $username = (string) ($row['username'] ?? '');
    $deleted = [];

    try {
        $connect->beginTransaction();

        $deleted['requisiciones'] = medidata_delete_user_requisiciones($connect, $userId);

        if (medidata_user_table_exists($connect, 'cart')) {
            $stmt = $connect->prepare('DELETE FROM cart WHERE user_id = ?');
            $stmt->execute([$userId]);
            $deleted['cart'] = $stmt->rowCount();
        }

        $skipTables = ['requisiciones', 'requisicion_detalles', 'user_signatures'];
        $fkStmt = $connect->query("
            SELECT kcu.TABLE_NAME, kcu.COLUMN_NAME, rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
               AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
            WHERE kcu.TABLE_SCHEMA = DATABASE()
              AND kcu.REFERENCED_TABLE_NAME = 'users'
              AND kcu.REFERENCED_COLUMN_NAME = 'id'
              AND rc.DELETE_RULE NOT IN ('CASCADE', 'SET NULL')
        ");

        if ($fkStmt) {
            while ($fkRow = $fkStmt->fetch(PDO::FETCH_ASSOC)) {
                $table = (string) ($fkRow['TABLE_NAME'] ?? '');
                $column = (string) ($fkRow['COLUMN_NAME'] ?? '');
                if ($table === '' || $column === '' || in_array($table, $skipTables, true)) {
                    continue;
                }
                if (!medidata_user_table_exists($connect, $table)) {
                    continue;
                }
                $stmt = $connect->prepare("DELETE FROM `$table` WHERE `$column` = ?");
                $stmt->execute([$userId]);
                $deleted[$table] = ($deleted[$table] ?? 0) + $stmt->rowCount();
            }
        }

        $stmtUser = $connect->prepare('DELETE FROM users WHERE id = ? LIMIT 1');
        $stmtUser->execute([$userId]);

        if ($stmtUser->rowCount() <= 0) {
            throw new RuntimeException('No se pudo eliminar el usuario.');
        }

        $connect->commit();

        $reqCount = (int) ($deleted['requisiciones'] ?? 0);
        $message = "Usuario '$username' eliminado correctamente.";
        if ($reqCount > 0) {
            $message .= " Se eliminaron $reqCount requisición(es) asociada(s).";
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
        error_log('medidata_delete_user_cascade id=' . $userId . ': ' . $e->getMessage());

        if (strpos($e->getMessage(), '1451') !== false || stripos($e->getMessage(), 'foreign key') !== false) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el usuario porque aún tiene registros vinculados en el sistema (ventas, historial u otros). Contacte a Soporte TI.',
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo eliminar el usuario. Intente de nuevo o contacte a Soporte TI.',
        ];
    }
}

/**
 * Quita autorizaciones y borra requisiciones donde el usuario es solicitante o creador.
 */
function medidata_delete_user_requisiciones(PDO $connect, int $userId): int
{
    if (!medidata_user_table_exists($connect, 'requisiciones')) {
        return 0;
    }

    $stmtNull = $connect->prepare(
        'UPDATE requisiciones SET usuario_autorizacion = NULL, fecha_autorizacion = NULL WHERE usuario_autorizacion = ?'
    );
    $stmtNull->execute([$userId]);

    $idsStmt = $connect->prepare(
        'SELECT id FROM requisiciones WHERE solicitante_id = ? OR usuario_solicitud = ?'
    );
    $idsStmt->execute([$userId, $userId]);
    $ids = $idsStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$ids) {
        return 0;
    }

    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    if (medidata_user_table_exists($connect, 'requisicion_detalles')) {
        $stmtDet = $connect->prepare("DELETE FROM requisicion_detalles WHERE requisicion_id IN ($placeholders)");
        $stmtDet->execute($ids);
    }

    $stmtReq = $connect->prepare("DELETE FROM requisiciones WHERE id IN ($placeholders)");
    $stmtReq->execute($ids);

    return $stmtReq->rowCount();
}

function medidata_user_table_exists(PDO $connect, string $table): bool
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
