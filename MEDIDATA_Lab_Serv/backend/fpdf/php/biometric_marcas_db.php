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
