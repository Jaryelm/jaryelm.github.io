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

/**
 * @return array{total: int, ultima: ?string, hoy: int}
 */
function medidata_biometric_fetch_marcas_stats_db(PDO $connect, string $siteCode): array
{
    $empty = ['total' => 0, 'ultima' => null, 'hoy' => 0];
    $siteCode = trim($siteCode);
    if ($siteCode === '') {
        return $empty;
    }

    try {
        $stmt = $connect->prepare(
            'SELECT COUNT(*) AS total,
                    MAX(marca_datetime) AS ultima,
                    SUM(CASE WHEN DATE(marca_datetime) = CURDATE() THEN 1 ELSE 0 END) AS hoy
             FROM biometric_marcas
             WHERE site_code = ?'
        );
        $stmt->execute([$siteCode]);
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

if (!function_exists('medidata_biometric_datatables')) {
    /**
     * DataTables server-side: una fila por empleado y día (entrada = primera marca, salida = última si hay más de una).
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

            $innerParams = [':site_code' => $siteCode];
            $innerWhere = 'm.site_code = :site_code';

            if ($fechaDesde !== '') {
                $innerWhere .= ' AND m.marca_datetime >= :fecha_desde';
                $innerParams[':fecha_desde'] = $fechaDesde . ' 00:00:00';
            }
            if ($fechaHasta !== '') {
                $innerWhere .= ' AND m.marca_datetime <= :fecha_hasta';
                $innerParams[':fecha_hasta'] = $fechaHasta . ' 23:59:59';
            }

            // Agrupar por empleado MediDATA (users.id) + día, no por cada uid del reloj
            // (un mismo colaborador puede marcar con uid_text/uid_numeric distintos).
            $groupedSelect = 'SELECT
                    COALESCE(NULLIF(TRIM(MAX(u.name)), \'\'), \'—\') AS empleado,
                    MAX(u.email) AS email,
                    MAX(u.rol) AS rol,
                    GROUP_CONCAT(DISTINCT NULLIF(TRIM(m.uid_text), \'\') ORDER BY m.marca_datetime SEPARATOR \' · \') AS uid_texts,
                    GROUP_CONCAT(DISTINCT NULLIF(TRIM(m.uid_numeric), \'\') ORDER BY m.marca_datetime SEPARATOR \' · \') AS uid_numerics,
                    MAX(NULLIF(TRIM(u.uid_biometrico), \'\')) AS uid_biometrico_user,
                    DATE(m.marca_datetime) AS marca_dia,
                    MIN(m.marca_datetime) AS fecha_entrada_raw,
                    CASE WHEN COUNT(*) > 1 THEN MAX(m.marca_datetime) ELSE NULL END AS fecha_salida_raw,
                    COUNT(*) AS num_marcas
                FROM biometric_marcas m
                LEFT JOIN users u ON u.uid_biometrico = m.uid_text
                    OR u.uid_biometrico = m.uid_numeric
                WHERE ' . $innerWhere . '
                GROUP BY DATE(m.marca_datetime),
                    IFNULL(u.id, 0),
                    IF(u.id IS NULL, CONCAT(IFNULL(m.uid_text, \'\'), \'|\', IFNULL(m.uid_numeric, \'\')), \'\')';

            $outerSearch = '';
            $searchParams = [];
            if ($searchValue !== '') {
                $outerSearch = ' AND (
                    paired.empleado LIKE :search1 OR
                    COALESCE(paired.email, \'\') LIKE :search2 OR
                    COALESCE(paired.rol, \'\') LIKE :search3 OR
                    COALESCE(paired.uid_texts, \'\') LIKE :search4 OR
                    COALESCE(paired.uid_numerics, \'\') LIKE :search5 OR
                    COALESCE(paired.uid_biometrico_user, \'\') LIKE :search6 OR
                    CAST(paired.marca_dia AS CHAR) LIKE :search7 OR
                    CAST(paired.fecha_entrada_raw AS CHAR) LIKE :search8 OR
                    CAST(paired.fecha_salida_raw AS CHAR) LIKE :search9
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
                    ':search9' => $like,
                ];
            }

            $countTotalSql = 'SELECT COUNT(*) FROM (' . $groupedSelect . ') AS paired';
            $countTotalStmt = $connect->prepare($countTotalSql);
            foreach ($innerParams as $key => $value) {
                $countTotalStmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $countTotalStmt->execute();
            $recordsTotal = (int) $countTotalStmt->fetchColumn();

            $countFilteredSql = 'SELECT COUNT(*) FROM (' . $groupedSelect . ') AS paired WHERE 1=1' . $outerSearch;
            $countFilteredStmt = $connect->prepare($countFilteredSql);
            foreach (array_merge($innerParams, $searchParams) as $key => $value) {
                $countFilteredStmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $countFilteredStmt->execute();
            $recordsFiltered = (int) $countFilteredStmt->fetchColumn();

            $orderColumn = (int) ($request['order'][0]['column'] ?? 5);
            $orderDir = strtoupper((string) ($request['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
            $orderMap = [
                1 => 'paired.empleado',
                2 => 'paired.email',
                3 => 'paired.rol',
                4 => 'paired.uid_numeric',
                5 => 'paired.fecha_entrada_raw',
                6 => 'paired.fecha_salida_raw',
            ];
            $orderExpr = $orderMap[$orderColumn] ?? 'paired.fecha_entrada_raw';

            $dataSql = 'SELECT paired.* FROM (' . $groupedSelect . ') AS paired WHERE 1=1' . $outerSearch .
                ' ORDER BY ' . $orderExpr . ' ' . $orderDir . ', paired.fecha_entrada_raw DESC LIMIT :start, :length';

            $stmt = $connect->prepare($dataSql);
            foreach (array_merge($innerParams, $searchParams) as $key => $value) {
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
                $uidReloj = medidata_biometric_format_grouped_uid_display($row);
                $data[] = [
                    'row_num' => $rowNum,
                    'empleado' => (string) ($row['empleado'] ?? '—'),
                    'email' => medidata_biometric_empty_dash($row['email'] ?? null),
                    'rol' => medidata_biometric_empty_dash($row['rol'] ?? null),
                    'uid_reloj' => $uidReloj,
                    'fecha_entrada' => medidata_biometric_format_display_datetime($row['fecha_entrada_raw'] ?? ''),
                    'fecha_salida' => ($row['fecha_salida_raw'] ?? null) !== null && (string) $row['fecha_salida_raw'] !== ''
                        ? medidata_biometric_format_display_datetime($row['fecha_salida_raw'])
                        : '—',
                    'num_marcas' => (int) ($row['num_marcas'] ?? 1),
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
