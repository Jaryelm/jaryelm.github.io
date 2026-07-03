<?php
declare(strict_types=1);

function medidata_factura_config_ensure_table(PDO $connect): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS facturacion_cai_config (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cai VARCHAR(64) NOT NULL,
  prefijo_factura VARCHAR(20) NOT NULL DEFAULT '000-001-01',
  rango_inicial BIGINT UNSIGNED NOT NULL,
  rango_final BIGINT UNSIGNED NOT NULL,
  fecha_limite DATE NULL,
  resolucion_sar VARCHAR(120) NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  created_by VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activa_created (activa, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    $connect->exec($sql);
    $ensured = true;
}

function medidata_factura_config_prefijo_normalizar(string $prefijo): string
{
    $prefijo = trim($prefijo);
    if ($prefijo === '') {
        return '000-001-01';
    }
    if (!preg_match('/^\d{3}-\d{3}-\d{2}$/', $prefijo)) {
        throw new InvalidArgumentException('Formato de prefijo inválido. Use 000-001-01.');
    }

    return $prefijo;
}

function medidata_factura_config_get_active(PDO $connect): ?array
{
    medidata_factura_config_ensure_table($connect);
    $st = $connect->query('SELECT * FROM facturacion_cai_config WHERE activa = 1 ORDER BY id DESC LIMIT 1');
    $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;

    return $row ?: null;
}

function medidata_factura_config_list(PDO $connect, int $limit = 25): array
{
    medidata_factura_config_ensure_table($connect);
    $limit = max(1, min(200, $limit));
    $sql = 'SELECT * FROM facturacion_cai_config ORDER BY id DESC LIMIT ' . $limit;
    $st = $connect->query($sql);

    return $st ? (array) $st->fetchAll(PDO::FETCH_ASSOC) : [];
}

function medidata_factura_config_save(PDO $connect, array $payload, string $createdBy): array
{
    medidata_factura_config_ensure_table($connect);

    $cai = strtoupper(trim((string) ($payload['cai'] ?? '')));
    $prefijo = medidata_factura_config_prefijo_normalizar((string) ($payload['prefijo_factura'] ?? ''));
    $rangoInicial = (int) ($payload['rango_inicial'] ?? 0);
    $rangoFinal = (int) ($payload['rango_final'] ?? 0);
    $fechaLimite = trim((string) ($payload['fecha_limite'] ?? ''));
    $resolucion = trim((string) ($payload['resolucion_sar'] ?? ''));

    if ($cai === '' || strlen($cai) < 8) {
        throw new InvalidArgumentException('CAI inválido o vacío.');
    }
    if ($rangoInicial <= 0 || $rangoFinal <= 0 || $rangoFinal < $rangoInicial) {
        throw new InvalidArgumentException('Rango de facturación inválido.');
    }
    if ($fechaLimite !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaLimite)) {
        throw new InvalidArgumentException('Fecha límite inválida.');
    }

    $connect->beginTransaction();
    try {
        $connect->exec('UPDATE facturacion_cai_config SET activa = 0 WHERE activa = 1');
        $st = $connect->prepare('
            INSERT INTO facturacion_cai_config
            (cai, prefijo_factura, rango_inicial, rango_final, fecha_limite, resolucion_sar, activa, created_by)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?)
        ');
        $st->execute([
            $cai,
            $prefijo,
            $rangoInicial,
            $rangoFinal,
            $fechaLimite !== '' ? $fechaLimite : null,
            $resolucion !== '' ? $resolucion : null,
            trim($createdBy) !== '' ? $createdBy : null,
        ]);
        $id = (int) $connect->lastInsertId();
        $connect->commit();
    } catch (Throwable $e) {
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        throw $e;
    }

    $active = medidata_factura_config_get_active($connect);
    if (!$active || (int) ($active['id'] ?? 0) !== $id) {
        throw new RuntimeException('No se pudo activar la nueva configuración.');
    }

    return $active;
}

function medidata_factura_next_invoice_from_config(PDO $connect, array $activeConfig): array
{
    $prefijo = medidata_factura_config_prefijo_normalizar((string) ($activeConfig['prefijo_factura'] ?? '000-001-01'));
    $rangoInicial = (int) ($activeConfig['rango_inicial'] ?? 0);
    $rangoFinal = (int) ($activeConfig['rango_final'] ?? 0);
    $fechaLimite = trim((string) ($activeConfig['fecha_limite'] ?? ''));

    if ($rangoInicial <= 0 || $rangoFinal <= 0 || $rangoFinal < $rangoInicial) {
        throw new RuntimeException('La configuración activa de CAI tiene rango inválido.');
    }
    if ($fechaLimite !== '' && $fechaLimite < date('Y-m-d')) {
        throw new RuntimeException('La fecha límite del CAI activo ya venció.');
    }

    $like = $prefijo . '-%';
    $st = $connect->prepare("
        SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) AS ultimo
        FROM orders
        WHERE invoice_number LIKE ?
    ");
    $st->execute([$like]);
    $ultimo = (int) ($st->fetchColumn() ?: 0);
    $siguiente = max($ultimo + 1, $rangoInicial);

    if ($siguiente > $rangoFinal) {
        throw new RuntimeException('Rango de facturación agotado para el CAI activo. Registre un nuevo rango.');
    }

    return [
        'invoice_number' => $prefijo . '-' . str_pad((string) $siguiente, 7, '0', STR_PAD_LEFT),
        'correlativo' => $siguiente,
        'prefijo' => $prefijo,
    ];
}

function medidata_factura_next_invoice_legacy(PDO $connect): array
{
    $numeroFacturaInicial = 410000;
    $stmt = $connect->prepare('SELECT MAX(invoice_number) AS last_invoice FROM orders');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastInvoice = $result['last_invoice'] ?? '000-001-01-0000000';
    $ultimoNumero = (int) substr((string) $lastInvoice, -7);
    $nuevoNumero = max($ultimoNumero + 1, $numeroFacturaInicial);

    return [
        'invoice_number' => sprintf('000-001-01-%07d', $nuevoNumero),
        'correlativo' => $nuevoNumero,
        'prefijo' => '000-001-01',
    ];
}

function medidata_factura_next_invoice(PDO $connect): array
{
    $active = medidata_factura_config_get_active($connect);
    if ($active !== null) {
        return medidata_factura_next_invoice_from_config($connect, $active);
    }

    return medidata_factura_next_invoice_legacy($connect);
}
