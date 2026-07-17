<?php

if (!function_exists('medidata_staff_ensure_tables')) {
    function medidata_staff_ensure_tables(PDO $connect): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $connect->exec("CREATE TABLE IF NOT EXISTS `staff_administrative` (
            `idadm` int(11) NOT NULL AUTO_INCREMENT,
            `id_user` int(11) DEFAULT NULL,
            `numide` char(14) COLLATE utf8_unicode_ci NOT NULL,
            `nomadm` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
            `apeadm` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
            `nacadm` date NOT NULL,
            `fecha_ingreso` date DEFAULT NULL,
            `sexadm` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
            `cargo` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
            `state` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
            `fere` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`idadm`),
            UNIQUE KEY `uq_staff_administrative_numide` (`numide`),
            KEY `idx_staff_administrative_user` (`id_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $connect->exec("CREATE TABLE IF NOT EXISTS `staff_general_services` (
            `idsg` int(11) NOT NULL AUTO_INCREMENT,
            `id_user` int(11) DEFAULT NULL,
            `numide` char(14) COLLATE utf8_unicode_ci NOT NULL,
            `nomsg` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
            `apesg` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
            `nacsg` date NOT NULL,
            `fecha_ingreso` date DEFAULT NULL,
            `sexsg` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
            `area` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
            `state` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
            `fere` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`idsg`),
            UNIQUE KEY `uq_staff_general_services_numide` (`numide`),
            KEY `idx_staff_general_services_user` (`id_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $connect->exec("CREATE TABLE IF NOT EXISTS `staff_medifarma` (
            `idmf` int(11) NOT NULL AUTO_INCREMENT,
            `id_user` int(11) DEFAULT NULL,
            `numide` char(14) COLLATE utf8_unicode_ci NOT NULL,
            `nommf` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
            `apemf` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
            `nacmf` date NOT NULL,
            `sexmf` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
            
            `num_empleado` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `tipo_empleado` varchar(50) COLLATE utf8_unicode_ci DEFAULT 'Permanente',
            `duracion_contrato` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
            `fecha_ingreso` date DEFAULT NULL,
            `id_departamento` int(11) DEFAULT NULL,
            `id_cargo` int(11) DEFAULT NULL,
            `id_horario` int(11) DEFAULT NULL,
            `id_salary_level` int(11) DEFAULT NULL,
            `salario` decimal(10,2) DEFAULT NULL,
            `cuenta_bac` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            
            `telefono` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
            `correo_personal` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
            `correo_institucional` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
            `num_locker` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
            `id_biometrico` int(11) DEFAULT NULL,
            `id_candidate_rrhh` int(11) DEFAULT NULL,
            
            `url_contrato` longblob DEFAULT NULL,
            `url_solicitud` longblob DEFAULT NULL,
            `url_psicometricas` longblob DEFAULT NULL,
            
            `area` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
            `state` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
            `fere` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`idmf`),
            UNIQUE KEY `uq_staff_medifarma_numide` (`numide`),
            KEY `idx_staff_medifarma_user` (`id_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        medidata_staff_ensure_id_user_column($connect, 'staff_administrative');
        medidata_staff_ensure_id_user_column($connect, 'staff_general_services');
        medidata_staff_ensure_id_user_column($connect, 'staff_medifarma');

        medidata_staff_ensure_fecha_ingreso_column($connect, 'staff_administrative');
        medidata_staff_ensure_fecha_ingreso_column($connect, 'staff_general_services');
        medidata_staff_ensure_fecha_ingreso_column($connect, 'staff_medifarma');

        $done = true;
    }
}

if (!function_exists('medidata_staff_ensure_id_user_column')) {
    function medidata_staff_ensure_id_user_column(PDO $connect, string $table): void
    {
        $allowed = ['staff_administrative', 'staff_general_services', 'staff_medifarma'];
        if (!in_array($table, $allowed, true)) {
            return;
        }

        $stmt = $connect->query("SHOW COLUMNS FROM `$table` LIKE 'id_user'");
        if ($stmt && $stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        $pk = 'id';
        if ($table === 'staff_administrative') $pk = 'idadm';
        if ($table === 'staff_general_services') $pk = 'idsg';
        if ($table === 'staff_medifarma') $pk = 'idmf';

        $connect->exec("ALTER TABLE `$table`
            ADD COLUMN `id_user` int(11) DEFAULT NULL AFTER `" . $pk . "`,
            ADD KEY `idx_{$table}_user` (`id_user`)");
    }
}

if (!function_exists('medidata_staff_ensure_fecha_ingreso_column')) {
    function medidata_staff_ensure_fecha_ingreso_column(PDO $connect, string $table): void
    {
        $allowed = ['staff_administrative', 'staff_general_services', 'staff_medifarma'];
        if (!in_array($table, $allowed, true)) {
            return;
        }

        $stmt = $connect->query("SHOW COLUMNS FROM `$table` LIKE 'fecha_ingreso'");
        if ($stmt && $stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        $after = 'nac';
        if ($table === 'staff_administrative') $after = 'nacadm';
        if ($table === 'staff_general_services') $after = 'nacsg';
        if ($table === 'staff_medifarma') $after = 'nacmf';

        $connect->exec("ALTER TABLE `$table`
            ADD COLUMN `fecha_ingreso` date DEFAULT NULL AFTER `$after`");
    }
}

if (!function_exists('medidata_staff_parse_id_user')) {
    function medidata_staff_parse_id_user(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $id = (int) $raw;
        return $id > 0 ? $id : null;
    }
}

if (!function_exists('medidata_staff_id_user_in_use')) {
    function medidata_staff_id_user_in_use(PDO $connect, int $idUser, string $table, int $excludeId = 0): bool
    {
        $pk = 'id';
        if ($table === 'staff_administrative') $pk = 'idadm';
        if ($table === 'staff_general_services') $pk = 'idsg';
        if ($table === 'staff_medifarma') $pk = 'idmf';

        $sql = "SELECT COUNT(*) FROM `$table` WHERE id_user = :id_user";
        $params = [':id_user' => $idUser];
        if ($excludeId > 0) {
            $sql .= " AND `$pk` <> :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('medidata_staff_fetch_users_for_select')) {
    function medidata_staff_fetch_users_for_select(PDO $connect): array
    {
        $stmt = $connect->query('SELECT id, name, username, rol FROM users ORDER BY name ASC');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}

if (!function_exists('medidata_staff_id_user_linked')) {
    /** @return array{table:string,label:string}|null */
    function medidata_staff_id_user_linked(PDO $connect, int $idUser, string $currentTable, int $excludeId = 0): ?array
    {
        $map = [
            'staff_administrative' => ['pk' => 'idadm', 'label' => 'administrativo'],
            'staff_general_services' => ['pk' => 'idsg', 'label' => 'servicios generales'],
            'staff_medifarma' => ['pk' => 'idmf', 'label' => 'medifarma'],
        ];

        foreach ($map as $table => $meta) {
            $exclude = ($table === $currentTable) ? $excludeId : 0;
            if (medidata_staff_id_user_in_use($connect, $idUser, $table, $exclude)) {
                return ['table' => $table, 'label' => $meta['label']];
            }
        }

        return null;
    }
}

if (!function_exists('medidata_staff_return_page')) {
    function medidata_staff_return_page(array $post, string $default): string
    {
        $page = basename(trim((string) ($post['return_page'] ?? $default)));
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.php$/', $page)) {
            return $default;
        }
        return $page;
    }
}

if (!function_exists('medidata_staff_linked_user_ids_subquery')) {
    function medidata_staff_linked_user_ids_subquery(): string
    {
        return "SELECT id_user FROM staff_administrative WHERE id_user IS NOT NULL
                UNION
                SELECT id_user FROM staff_general_services WHERE id_user IS NOT NULL
                UNION
                SELECT id_user FROM staff_medifarma WHERE id_user IS NOT NULL";
    }
}
