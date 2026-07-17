<?php
/**
 * Asegura columna phone_ext en departaments (migración automática).
 */
if (!function_exists('medidata_ensure_departament_phone_ext')) {
    function medidata_ensure_departament_phone_ext(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $cols = $pdo->query('SHOW COLUMNS FROM departaments')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('phone_ext', $cols, true)) {
                $pdo->exec(
                    'ALTER TABLE departaments ADD COLUMN phone_ext VARCHAR(10) NULL DEFAULT NULL AFTER phone'
                );
            }
        } catch (Throwable $e) {
            error_log('medidata_ensure_departament_phone_ext: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_departament_is_usr_context')) {
    function medidata_departament_is_usr_context(): bool
    {
        $ctx = trim((string) ($_POST['dep_context'] ?? ''));
        if ($ctx === 'usr') {
            return true;
        }
        if ($ctx === 'admin') {
            return false;
        }

        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

        return strpos($ref, '_usr') !== false || strpos($script, '_usr') !== false;
    }
}

if (!function_exists('medidata_departament_return_list_url')) {
    function medidata_departament_return_list_url(): string
    {
        return medidata_departament_is_usr_context() ? 'departamentos_usr.php' : 'departamentos.php';
    }
}

if (!function_exists('medidata_departament_return_new_url')) {
    function medidata_departament_return_new_url(): string
    {
        return medidata_departament_is_usr_context() ? 'departamentos_nuevo_usr.php' : 'departamentos_nuevo.php';
    }
}

if (!function_exists('medidata_departament_return_edit_url')) {
    function medidata_departament_return_edit_url(int $id): string
    {
        $base = medidata_departament_is_usr_context() ? 'departamentos_editar_usr.php' : 'departamentos_editar.php';
        return $base . '?id=' . $id;
    }
}
