<?php
/**
 * Repara facturas ya COBRADAS que no tienen partida CIERRE_VENTA en el diario.
 * No cambia el estado de la factura; solo genera la partida contable.
 *
 * Uso en producción (SSH o terminal cPanel):
 *   php backend/php/tools/reparar_partidas_cobradas.php --dry-run
 *   php backend/php/tools/reparar_partidas_cobradas.php --fecha=2026-05-09
 *   php backend/php/tools/reparar_partidas_cobradas.php --id=324
 *   php backend/php/tools/reparar_partidas_cobradas.php --factura=000-001-01-0413300
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Solo CLI. Ejemplo: php backend/php/tools/reparar_partidas_cobradas.php --fecha=2026-05-09\n";
    exit(1);
}

$baseDir = dirname(__DIR__, 3);
chdir($baseDir);

require_once __DIR__ . '/../../bd/Conexion.php';
require_once __DIR__ . '/../funciones_diario_general.php';

$dry = in_array('--dry-run', $argv, true);
$fecha = null;
$idFiltro = null;
$facturaFiltro = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--fecha=') === 0) {
        $fecha = substr($arg, 8);
    } elseif (strpos($arg, '--id=') === 0) {
        $idFiltro = (int) substr($arg, 5);
    } elseif (strpos($arg, '--factura=') === 0) {
        $facturaFiltro = trim(substr($arg, 10));
    }
}

$sql = "
SELECT o.idord, o.invoice_number, o.invoice_status, o.placed_on, o.total_price
FROM orders o
WHERE o.invoice_status = 'Cobrada'
  AND o.invoice_number IS NOT NULL
  AND TRIM(o.invoice_number) <> ''
";
$params = [];

if ($idFiltro > 0) {
    $sql .= ' AND o.idord = ? ';
    $params[] = $idFiltro;
}
if ($facturaFiltro !== null && $facturaFiltro !== '') {
    $sql .= ' AND o.invoice_number = ? ';
    $params[] = $facturaFiltro;
}
if ($fecha !== null && $fecha !== '') {
    $sql .= ' AND DATE(o.placed_on) = ? ';
    $params[] = $fecha;
}

$sql .= "
  AND NOT EXISTS (
      SELECT 1 FROM diario_general_transacciones d
      WHERE d.referencia = o.invoice_number
        AND d.tipo_transaccion = 'CIERRE_VENTA'
  )
ORDER BY o.placed_on ASC
";

$st = $connect->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

echo 'Facturas cobradas sin partida: ' . count($rows) . PHP_EOL;

$ok = 0;
$err = 0;

foreach ($rows as $r) {
    $id = (int) $r['idord'];
    $inv = (string) $r['invoice_number'];

    if ($dry) {
        echo "[dry-run] idord={$id} factura={$inv} total={$r['total_price']}" . PHP_EOL;
        $ok++;
        continue;
    }

    try {
        $res = medidata_asegurar_partida_diario_factura_cobrada($id);
        if ($res['ok'] && !$res['skipped']) {
            echo "OK idord={$id} factura={$inv} partida=" . ($res['numero_partida'] ?? '') . PHP_EOL;
            $ok++;
        } elseif ($res['ok'] && $res['skipped']) {
            echo "SKIP idord={$id} factura={$inv} (ya existía)" . PHP_EOL;
            $ok++;
        } else {
            fwrite(STDERR, "ERROR idord={$id} factura={$inv}: " . ($res['message'] ?? '') . PHP_EOL);
            $err++;
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "ERROR idord={$id} factura={$inv}: " . $e->getMessage() . PHP_EOL);
        $err++;
    }
}

echo PHP_EOL . 'Resumen: ok=' . $ok . ' errores=' . $err . PHP_EOL;
