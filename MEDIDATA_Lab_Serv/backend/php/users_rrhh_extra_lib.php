<?php
/**
 * Datos extra de RRHH para cuentas tipo "Usuario" (tabla `users`).
 * Sistema: MEDIDATA - RRHH
 *
 * Las cuentas de login no tienen ficha de personal, por lo que sus campos
 * de RRHH (departamento, salario, contrato, etc.) se guardan aqui, vinculados
 * por id_user. No se modifica la tabla `users` (login) salvo identidad basica
 * (name/cedula/sexo) que ya vive en ella.
 */

if (!function_exists('medidata_users_rrhh_extra_ensure')) {
    function medidata_users_rrhh_extra_ensure(PDO $connect): void
    {
        try {
            $connect->exec("CREATE TABLE IF NOT EXISTS users_rrhh_extra (
                id_user INT(11) NOT NULL PRIMARY KEY,
                num_empleado VARCHAR(50) NULL,
                tipo_empleado VARCHAR(50) NULL,
                duracion_contrato VARCHAR(50) NULL,
                id_departamento INT(11) NULL,
                id_cargo INT(11) NULL,
                id_horario INT(11) NULL,
                id_salary_level INT(11) NULL,
                salario VARCHAR(50) NULL,
                cuenta_bac VARCHAR(50) NULL,
                fecha_ingreso DATE NULL,
                telefono VARCHAR(50) NULL,
                correo_personal VARCHAR(100) NULL,
                num_locker VARCHAR(50) NULL,
                id_biometrico VARCHAR(50) NULL,
                url_contrato LONGBLOB NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            // La tabla suele existir; si el usuario de BD no tiene CREATE no se interrumpe.
            error_log('users_rrhh_extra_ensure: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_users_rrhh_extra_fields')) {
    /** Campos de RRHH editables que se almacenan en users_rrhh_extra. */
    function medidata_users_rrhh_extra_fields(): array
    {
        return [
            'num_empleado', 'tipo_empleado', 'duracion_contrato',
            'id_departamento', 'id_cargo', 'id_horario', 'id_salary_level',
            'salario', 'cuenta_bac', 'fecha_ingreso', 'telefono',
            'correo_personal', 'num_locker',
        ];
    }
}

if (!function_exists('medidata_users_rrhh_extra_identity_fields')) {
    /** Campos de identidad/login que viven en la propia tabla `users`. */
    function medidata_users_rrhh_extra_identity_fields(): array
    {
        // 'id_biometrico' se mapea a users.uid_biometrico (lo usa el reloj biométrico).
        return ['name', 'cedula', 'sexo', 'email', 'rol', 'uid_biometrico'];
    }
}

if (!function_exists('medidata_users_rrhh_extra_save_field')) {
    function medidata_users_rrhh_extra_save_field(PDO $connect, int $idUser, string $field, $value): bool
    {
        if ($idUser <= 0 || !in_array($field, medidata_users_rrhh_extra_fields(), true)) {
            return false;
        }
        medidata_users_rrhh_extra_ensure($connect);

        if ($value !== null && in_array($field, ['id_departamento', 'id_cargo', 'id_horario', 'id_salary_level'], true)) {
            $value = (int) $value;
        }

        $sql = "INSERT INTO users_rrhh_extra (id_user, {$field}) VALUES (:id, :val)
                ON DUPLICATE KEY UPDATE {$field} = VALUES({$field})";
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':id', $idUser, PDO::PARAM_INT);
        if ($value === null) {
            $stmt->bindValue(':val', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':val', $value);
        }
        return $stmt->execute();
    }
}

if (!function_exists('medidata_users_rrhh_extra_save_contrato')) {
    function medidata_users_rrhh_extra_save_contrato(PDO $connect, int $idUser, ?string $binary): bool
    {
        if ($idUser <= 0) {
            return false;
        }
        medidata_users_rrhh_extra_ensure($connect);

        $sql = "INSERT INTO users_rrhh_extra (id_user, url_contrato) VALUES (:id, :pdf)
                ON DUPLICATE KEY UPDATE url_contrato = VALUES(url_contrato)";
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':id', $idUser, PDO::PARAM_INT);
        if ($binary === null) {
            $stmt->bindValue(':pdf', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':pdf', $binary, PDO::PARAM_LOB);
        }
        return $stmt->execute();
    }
}
