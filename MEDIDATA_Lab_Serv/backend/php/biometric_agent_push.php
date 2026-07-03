<?php
declare(strict_types=1);

/**
 * Inserción de marcaciones en biometric_marcas (misma lógica que agent_biometric_ingest.php).
 *
 * @return array{success: bool, site_code: string, received: int, inserted: int, skipped_or_duplicate: int, message?: string}
 */
function medidata_biometric_push_records(PDO $pdo, string $siteCode, array $records, ?string $deviceSerial = null): array
{
    $siteCode = trim($siteCode);
    if ($siteCode === '') {
        return [
            'success' => false,
            'site_code' => '',
            'received' => 0,
            'inserted' => 0,
            'skipped_or_duplicate' => 0,
            'message' => 'site_code vacío',
        ];
    }

    $deviceSerial = $deviceSerial !== null && trim($deviceSerial) !== '' ? trim($deviceSerial) : null;

    $insertSql = <<<SQL
INSERT IGNORE INTO biometric_marcas (
  site_code, uid_text, uid_numeric, estado, marca_datetime, device_serial
) VALUES (
  ?, ?, ?, ?, ?, ?
)
SQL;

    $stmt = $pdo->prepare($insertSql);
    $received = count($records);
    $inserted = 0;
    $skipped = 0;

    foreach ($records as $row) {
        if (!is_array($row) || count($row) < 4) {
            $skipped++;
            continue;
        }
        $uidTxt = trim((string) $row[0]);
        $uidNum = trim((string) $row[1]);
        $estado = trim((string) $row[2]);
        $ts = trim((string) $row[3]);

        if ($ts === '' || !preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $ts)) {
            $skipped++;
            continue;
        }

        $stmt->execute([
            $siteCode,
            $uidTxt,
            $uidNum,
            $estado,
            $ts,
            $deviceSerial,
        ]);
        if ($stmt->rowCount() > 0) {
            $inserted++;
        } else {
            $skipped++;
        }
    }

    return [
        'success' => true,
        'site_code' => $siteCode,
        'received' => $received,
        'inserted' => $inserted,
        'skipped_or_duplicate' => $skipped,
    ];
}

/**
 * Conexión PDO a MySQL remoto (agente en sede → BD producción).
 *
 * Variables: MEDIDATA_AGENT_DB_HOST, MEDIDATA_AGENT_DB_NAME, MEDIDATA_AGENT_DB_USER, MEDIDATA_AGENT_DB_PASS
 */
function medidata_biometric_agent_pdo_from_env(): PDO
{
    $host = trim((string) (getenv('MEDIDATA_AGENT_DB_HOST') ?: ''));
    $name = trim((string) (getenv('MEDIDATA_AGENT_DB_NAME') ?: 'medic9ue_medi_data'));
    $user = trim((string) (getenv('MEDIDATA_AGENT_DB_USER') ?: ''));
    $pass = (string) (getenv('MEDIDATA_AGENT_DB_PASS') ?: getenv('MEDIDATA_AGENT_DB_PASSWORD') ?: '');

    if ($host === '' || $user === '') {
        throw new RuntimeException('Faltan MEDIDATA_AGENT_DB_HOST o MEDIDATA_AGENT_DB_USER en el entorno del agente.');
    }

    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
