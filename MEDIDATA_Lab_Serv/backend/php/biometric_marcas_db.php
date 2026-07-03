<?php
declare(strict_types=1);

/**
 * Lectura de biometric_marcas (agente sede → MySQL). Sin protocolo ZK.
 */

function medidata_biometric_resolve_site_code(): string
{
    $site = getenv('MEDIDATA_RELJO_DB_SITE');
    $site = is_string($site) ? trim($site) : '';
    if ($site !== '') {
        return substr($site, 0, 48);
    }
    $httpHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
    if ($httpHost !== '' && str_contains($httpHost, 'medidata.medicasa.hn')) {
        return 'Sucursal_1';
    }

    return 'Sucursal_1';
}

function medidata_biometric_today_range(): array
{
    $tz = date_default_timezone_get();
    if ($tz === '' || $tz === 'UTC') {
        date_default_timezone_set('America/Tegucigalpa');
    }

    return [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
}

/**
 * @return array{total: int, ultima: ?string, hoy: int}
 */
function medidata_biometric_fetch_marcas_stats_db(
    PDO $connect,
    string $siteCode,
    ?string $hoyInicio = null,
    ?string $hoyFin = null
): array {
    $empty = ['total' => 0, 'ultima' => null, 'hoy' => 0];
    $siteCode = trim($siteCode);
    if ($siteCode === '') {
        return $empty;
    }

    if ($hoyInicio === null || $hoyFin === null) {
        [$hoyInicio, $hoyFin] = medidata_biometric_today_range();
    }

    try {
        $stmt = $connect->prepare(
            'SELECT COUNT(*) AS total,
                    MAX(marca_datetime) AS ultima,
                    SUM(CASE WHEN marca_datetime >= ? AND marca_datetime <= ? THEN 1 ELSE 0 END) AS hoy
             FROM biometric_marcas
             WHERE site_code = ?'
        );
        $stmt->execute([$hoyInicio, $hoyFin, $siteCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return $empty;
        }
        $ultima = $row['ultima'] ?? null;
        if ($ultima instanceof \DateTimeInterface) {
            $ultima = $ultima->format('Y-m-d H:i:s');
        } elseif ($ultima !== null) {
            $ultima = preg_replace('/\.\d+$/', '', trim((string) $ultima));
        }

        return [
            'total' => (int) ($row['total'] ?? 0),
            'ultima' => $ultima !== '' ? (string) $ultima : null,
            'hoy' => (int) ($row['hoy'] ?? 0),
        ];
    } catch (\Throwable $e) {
        error_log('medidata_biometric_fetch_marcas_stats_db: ' . $e->getMessage());

        return $empty;
    }
}

/**
 * KPIs para escritorios (admin, etc.): marcas crudas + pares entrada/salida de hoy.
 *
 * @return array{total: int, ultima: ?string, hoy: int, pares_hoy: int, site_code: string}
 */
function medidata_biometric_fetch_dash_stats(
    PDO $connect,
    ?string $siteCode = null,
    ?string $hoyInicio = null,
    ?string $hoyFin = null
): array {
    $siteCode = $siteCode !== null ? trim($siteCode) : medidata_biometric_resolve_site_code();
    if ($hoyInicio === null || $hoyFin === null) {
        [$hoyInicio, $hoyFin] = medidata_biometric_today_range();
    }

    $base = medidata_biometric_fetch_marcas_stats_db($connect, $siteCode, $hoyInicio, $hoyFin);
    $base['pares_hoy'] = 0;
    $base['site_code'] = substr($siteCode, 0, 48);

    if ($base['site_code'] === '') {
        return $base;
    }

    try {
        $sql = 'WITH marks AS (
                SELECT
                    m.id,
                    m.marca_datetime,
                    IFNULL(u.id, 0) AS user_id,
                    IF(u.id IS NULL, CONCAT(IFNULL(m.uid_text, \'\'), \'|\', IFNULL(m.uid_numeric, \'\')), \'\') AS uid_fallback,
                    ROW_NUMBER() OVER (
                        PARTITION BY DATE(m.marca_datetime),
                            IFNULL(u.id, 0),
                            IF(u.id IS NULL, CONCAT(IFNULL(m.uid_text, \'\'), \'|\', IFNULL(m.uid_numeric, \'\')), \'\')
                        ORDER BY m.marca_datetime ASC, m.id ASC
                    ) AS seq_in_day
                FROM biometric_marcas m
                LEFT JOIN users u ON u.uid_biometrico = m.uid_text
                    OR u.uid_biometrico = m.uid_numeric
                WHERE m.site_code = :site_code
                  AND m.marca_datetime >= :hoy_inicio
                  AND m.marca_datetime <= :hoy_fin
            )
            SELECT COUNT(*) FROM marks e
            WHERE MOD(e.seq_in_day, 2) = 1';

        $stmt = $connect->prepare($sql);
        $stmt->execute([
            ':site_code' => $base['site_code'],
            ':hoy_inicio' => $hoyInicio,
            ':hoy_fin' => $hoyFin,
        ]);
        $base['pares_hoy'] = (int) $stmt->fetchColumn();
    } catch (\Throwable $e) {
        error_log('medidata_biometric_fetch_dash_stats: ' . $e->getMessage());
    }

    return $base;
}

/**
 * Formatea marca_datetime para la vista.
 */
function medidata_biometric_format_marca_datetime(mixed $ts): string
{
    if ($ts instanceof \DateTimeInterface) {
        return $ts->format('Y-m-d H:i:s');
    }
    $tsStr = trim((string) $ts);

    return preg_replace('/\.\d+$/', '', $tsStr);
}

/**
 * Marcas con nombre de empleado (users.uid_biometrico ↔ reloj).
 *
 * @return list<array{id:int, uid_text:string, uid_numeric:string, empleado:string, email:string, rol:string, estado:string, marca_datetime:string}>
 */
function medidata_biometric_fetch_marcas_agent_db(PDO $connect, string $siteCode, int $limit = 2000): array
{
    $siteCode = trim($siteCode);
    if ($siteCode === '') {
        return [];
    }
    $siteCode = substr($siteCode, 0, 48);
    $limit = max(1, min(5000, $limit));

    try {
        $sql = 'SELECT m.id,
                       m.uid_text,
                       m.uid_numeric,
                       u.name AS empleado,
                       u.email AS email,
                       u.rol AS rol,
                       m.estado,
                       m.marca_datetime
                FROM biometric_marcas m
                LEFT JOIN users u ON u.uid_biometrico = m.uid_text
                    OR u.uid_biometrico = m.uid_numeric
                WHERE m.site_code = ?
                ORDER BY m.marca_datetime DESC
                LIMIT ' . $limit;

        $stmt = $connect->prepare($sql);
        $stmt->execute([$siteCode]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $empleado = trim((string) ($row['empleado'] ?? ''));
            if ($empleado === '') {
                $empleado = '—';
            }

            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'uid_text' => (string) ($row['uid_text'] ?? ''),
                'uid_numeric' => (string) ($row['uid_numeric'] ?? ''),
                'empleado' => $empleado,
                'email' => medidata_biometric_empty_dash($row['email'] ?? null),
                'rol' => medidata_biometric_empty_dash($row['rol'] ?? null),
                'estado' => (string) ($row['estado'] ?? ''),
                'marca_datetime' => medidata_biometric_format_marca_datetime($row['marca_datetime'] ?? ''),
            ];
        }

        return $out;
    } catch (\Throwable $e) {
        error_log('medidata_biometric_fetch_marcas_agent_db: ' . $e->getMessage());

        return medidata_biometric_fetch_marcas_agent_db_plain($connect, $siteCode, $limit);
    }
}

function medidata_biometric_empty_dash(mixed $value): string
{
    $s = trim((string) $value);

    return $s !== '' ? $s : '—';
}

/**
 * Respaldo si users.uid_biometrico aún no existe en la BD.
 *
 * @return list<array{id:int, uid_text:string, uid_numeric:string, empleado:string, email:string, rol:string, estado:string, marca_datetime:string}>
 */
function medidata_biometric_fetch_marcas_agent_db_plain(PDO $connect, string $siteCode, int $limit): array
{
    try {
        $sql = 'SELECT id, uid_text, uid_numeric, estado, marca_datetime
                FROM biometric_marcas
                WHERE site_code = ?
                ORDER BY marca_datetime DESC
                LIMIT ' . $limit;

        $stmt = $connect->prepare($sql);
        $stmt->execute([$siteCode]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'uid_text' => (string) ($row['uid_text'] ?? ''),
                'uid_numeric' => (string) ($row['uid_numeric'] ?? ''),
                'empleado' => '—',
                'email' => '—',
                'rol' => '—',
                'estado' => (string) ($row['estado'] ?? ''),
                'marca_datetime' => medidata_biometric_format_marca_datetime($row['marca_datetime'] ?? ''),
            ];
        }

        return $out;
    } catch (\Throwable $e) {
        error_log('medidata_biometric_fetch_marcas_agent_db_plain: ' . $e->getMessage());

        return [];
    }
}

if (!function_exists('medidata_biometric_format_uid_display')) {
    function medidata_biometric_format_uid_display(string $uidNumeric, string $uidText): string
    {
        $uidReloj = $uidNumeric !== '' ? $uidNumeric : $uidText;
        if ($uidReloj !== '' && $uidText !== '' && $uidText !== $uidReloj) {
            $uidReloj .= ' / ' . $uidText;
        }

        return $uidReloj !== '' ? $uidReloj : '—';
    }
}

if (!function_exists('medidata_biometric_format_grouped_uid_display')) {
    /**
     * Texto de ID reloj para fila agrupada (puede haber varios uid en el mismo día).
     *
     * @param array<string, mixed> $row
     */
    function medidata_biometric_format_grouped_uid_display(array $row): string
    {
        $uidUser = trim((string) ($row['uid_biometrico_user'] ?? ''));
        if ($uidUser !== '') {
            return $uidUser;
        }

        $numerics = trim((string) ($row['uid_numerics'] ?? ''));
        $texts = trim((string) ($row['uid_texts'] ?? ''));
        if ($numerics !== '' && $texts !== '' && $numerics !== $texts) {
            return $numerics . ' · ' . $texts;
        }

        return $numerics !== '' ? $numerics : ($texts !== '' ? $texts : '—');
    }
}

if (!function_exists('medidata_biometric_format_display_datetime')) {
    function medidata_biometric_format_display_datetime(mixed $ts): string
    {
        $raw = medidata_biometric_format_marca_datetime($ts);
        if ($raw === '') {
            return '—';
        }
        try {
            $dt = new DateTime($raw);

            return $dt->format('d/m/Y H:i:s');
        } catch (Throwable $e) {
            return $raw;
        }
    }
}

if (!function_exists('medidata_biometric_format_display_date')) {
    function medidata_biometric_format_display_date(mixed $ts): string
    {
        $raw = medidata_biometric_format_marca_datetime($ts);
        if ($raw === '') {
            return '—';
        }
        try {
            $dt = new DateTime($raw);

            return $dt->format('d/m/Y');
        } catch (Throwable $e) {
            return $raw;
        }
    }
}

if (!function_exists('medidata_biometric_format_display_time')) {
    function medidata_biometric_format_display_time(mixed $ts): string
    {
        $raw = medidata_biometric_format_marca_datetime($ts);
        if ($raw === '') {
            return '—';
        }
        try {
            $dt = new DateTime($raw);

            return $dt->format('H:i:s');
        } catch (Throwable $e) {
            return $raw;
        }
    }
}

if (!function_exists('medidata_biometric_format_display_weekday')) {
    function medidata_biometric_format_display_weekday(mixed $ts): string
    {
        $raw = medidata_biometric_format_marca_datetime($ts);
        if ($raw === '') {
            return '—';
        }
        try {
            $dt = new DateTime($raw);
            $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

            return $days[(int) $dt->format('w')];
        } catch (Throwable $e) {
            return '—';
        }
    }
}

if (!function_exists('medidata_biometric_datatables')) {
    /**
     * DataTables server-side: una fila por par entrada/salida del mismo empleado y día.
     * Regla: 1.ª marca del día = entrada, 2.ª = salida, 3.ª = entrada, 4.ª = salida, etc.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    function medidata_biometric_datatables(PDO $connect, array $request, string $siteCode): array
    {
        $draw = (int) ($request['draw'] ?? 1);
        $fail = static function (string $msg) use ($draw): array {
            return [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $msg,
            ];
        };

        $siteCode = substr(trim($siteCode), 0, 48);
        if ($siteCode === '') {
            return $fail('Sitio no configurado.');
        }

        try {
            $start = max(0, (int) ($request['start'] ?? 0));
            $lengthRaw = (int) ($request['length'] ?? 10);
            $length = ($lengthRaw <= 0) ? 100 : min($lengthRaw, 100);
            $searchValue = trim((string) ($request['search']['value'] ?? ''));
            $fechaDesde = trim((string) ($request['fechaDesde'] ?? ''));
            $fechaHasta = trim((string) ($request['fechaHasta'] ?? ''));

            if ($fechaDesde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
                $fechaDesde = '';
            }
            if ($fechaHasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
                $fechaHasta = '';
            }

            $where = 'm.site_code = :site_code';
            $baseParams = [':site_code' => $siteCode];

            if ($fechaDesde !== '') {
                $where .= ' AND m.marca_datetime >= :fecha_desde';
                $baseParams[':fecha_desde'] = $fechaDesde . ' 00:00:00';
            }
            if ($fechaHasta !== '') {
                $where .= ' AND m.marca_datetime <= :fecha_hasta';
                $baseParams[':fecha_hasta'] = $fechaHasta . ' 23:59:59';
            }

            $paresCte = 'WITH marks AS (
                SELECT
                    m.id,
                    m.uid_text,
                    m.uid_numeric,
                    m.marca_datetime,
                    DATE(m.marca_datetime) AS marca_dia,
                    COALESCE(NULLIF(TRIM(u.name), \'\'), \'—\') AS empleado,
                    u.email,
                    u.rol,
                    IFNULL(u.id, 0) AS user_id,
                    IF(u.id IS NULL, CONCAT(IFNULL(m.uid_text, \'\'), \'|\', IFNULL(m.uid_numeric, \'\')), \'\') AS uid_fallback,
                    ROW_NUMBER() OVER (
                        PARTITION BY DATE(m.marca_datetime),
                            IFNULL(u.id, 0),
                            IF(u.id IS NULL, CONCAT(IFNULL(m.uid_text, \'\'), \'|\', IFNULL(m.uid_numeric, \'\')), \'\')
                        ORDER BY m.marca_datetime ASC, m.id ASC
                    ) AS seq_in_day
                FROM biometric_marcas m
                LEFT JOIN users u ON u.uid_biometrico = m.uid_text
                    OR u.uid_biometrico = m.uid_numeric
                WHERE ' . $where . '
            ),
            pares AS (
                SELECT
                    e.id AS entrada_id,
                    e.empleado,
                    e.email,
                    e.rol,
                    e.uid_text,
                    e.uid_numeric,
                    e.marca_dia,
                    e.marca_datetime AS fecha_entrada_raw,
                    s.marca_datetime AS fecha_salida_raw,
                    e.seq_in_day AS par_num
                FROM marks e
                LEFT JOIN marks s ON
                    s.marca_dia = e.marca_dia
                    AND s.user_id = e.user_id
                    AND s.uid_fallback = e.uid_fallback
                    AND s.seq_in_day = e.seq_in_day + 1
                WHERE MOD(e.seq_in_day, 2) = 1
            )';

            $searchSql = '';
            $searchParams = [];
            if ($searchValue !== '') {
                $searchSql = ' AND (
                    p.empleado LIKE :search1 OR
                    COALESCE(p.email, \'\') LIKE :search2 OR
                    COALESCE(p.rol, \'\') LIKE :search3 OR
                    COALESCE(p.uid_text, \'\') LIKE :search4 OR
                    COALESCE(p.uid_numeric, \'\') LIKE :search5 OR
                    CAST(p.marca_dia AS CHAR) LIKE :search6 OR
                    CAST(p.fecha_entrada_raw AS CHAR) LIKE :search7 OR
                    CAST(p.fecha_salida_raw AS CHAR) LIKE :search8
                )';
                $like = '%' . $searchValue . '%';
                $searchParams = [
                    ':search1' => $like,
                    ':search2' => $like,
                    ':search3' => $like,
                    ':search4' => $like,
                    ':search5' => $like,
                    ':search6' => $like,
                    ':search7' => $like,
                    ':search8' => $like,
                ];
            }

            $countTotalSql = $paresCte . ' SELECT COUNT(*) FROM pares p';
            $countTotalStmt = $connect->prepare($countTotalSql);
            foreach ($baseParams as $key => $value) {
                $countTotalStmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $countTotalStmt->execute();
            $recordsTotal = (int) $countTotalStmt->fetchColumn();

            $countFilteredSql = $paresCte . ' SELECT COUNT(*) FROM pares p WHERE 1=1' . $searchSql;
            $countFilteredStmt = $connect->prepare($countFilteredSql);
            foreach (array_merge($baseParams, $searchParams) as $key => $value) {
                $countFilteredStmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $countFilteredStmt->execute();
            $recordsFiltered = (int) $countFilteredStmt->fetchColumn();

            $orderColumn = (int) ($request['order'][0]['column'] ?? 6);
            $orderDir = strtoupper((string) ($request['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
            $orderMap = [
                1 => 'empleado',
                2 => 'email',
                3 => 'rol',
                4 => 'uid_reloj',
                5 => 'marca_dia',
                6 => 'marca_dia',
                7 => 'fecha_entrada_raw',
                8 => 'fecha_salida_raw',
            ];
            $orderExpr = $orderMap[$orderColumn] ?? 'fecha_entrada_raw';
            $allowedOrderExpr = [
                'empleado' => 'p.empleado',
                'email' => 'COALESCE(p.email, \'\')',
                'rol' => 'COALESCE(p.rol, \'\')',
                'uid_reloj' => 'COALESCE(NULLIF(TRIM(p.uid_numeric), \'\'), NULLIF(TRIM(p.uid_text), \'\'), \'\')',
                'marca_dia' => 'p.marca_dia',
                'fecha_entrada_raw' => 'p.fecha_entrada_raw',
                'fecha_salida_raw' => 'p.fecha_salida_raw',
            ];
            $orderSqlExpr = $allowedOrderExpr[$orderExpr] ?? 'p.fecha_entrada_raw';

            $dataSql = $paresCte . ' SELECT p.* FROM pares p WHERE 1=1' . $searchSql .
                ' ORDER BY ' . $orderSqlExpr . ' ' . $orderDir . ', p.fecha_entrada_raw DESC, p.entrada_id DESC' .
                ' LIMIT :start, :length';

            $stmt = $connect->prepare($dataSql);
            foreach (array_merge($baseParams, $searchParams) as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':start', $start, PDO::PARAM_INT);
            $stmt->bindValue(':length', $length, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            $rowNum = $start;
            foreach ($rows as $row) {
                $rowNum++;
                $uidReloj = medidata_biometric_format_uid_display(
                    trim((string) ($row['uid_numeric'] ?? '')),
                    trim((string) ($row['uid_text'] ?? ''))
                );
                $salidaRaw = $row['fecha_salida_raw'] ?? null;
                $entradaRaw = $row['fecha_entrada_raw'] ?? '';
                $data[] = [
                    'row_num' => $rowNum,
                    'empleado' => (string) ($row['empleado'] ?? '—'),
                    'email' => medidata_biometric_empty_dash($row['email'] ?? null),
                    'rol' => medidata_biometric_empty_dash($row['rol'] ?? null),
                    'uid_reloj' => $uidReloj,
                    'dia_semana' => medidata_biometric_format_display_weekday($entradaRaw),
                    'fecha' => medidata_biometric_format_display_date($entradaRaw),
                    'hora_entrada' => medidata_biometric_format_display_time($entradaRaw),
                    'hora_salida' => ($salidaRaw !== null && (string) $salidaRaw !== '')
                        ? medidata_biometric_format_display_time($salidaRaw)
                        : '—',
                    // Compatibilidad con exportaciones o integraciones antiguas
                    'fecha_entrada' => medidata_biometric_format_display_datetime($entradaRaw),
                    'fecha_salida' => ($salidaRaw !== null && (string) $salidaRaw !== '')
                        ? medidata_biometric_format_display_datetime($salidaRaw)
                        : '—',
                ];
            }

            return [
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ];
        } catch (Throwable $e) {
            error_log('medidata_biometric_datatables: ' . $e->getMessage());

            return $fail('Error al cargar marcaciones.');
        }
    }
}
