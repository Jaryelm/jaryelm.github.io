<?php
/**
 * Genera partidas faltantes tipo COMPRA_PROVEEDOR en diario_general_transacciones
 * a partir de compras + detalle_compras (todos los términos de pago: crédito, contado, etc.).
 *
 * Uso (desde la carpeta del proyecto, con PHP de XAMPP):
 *   php backend/php/tools/backfill_partidas_compra.php
 *   php backend/php/tools/backfill_partidas_compra.php --dry-run
 *   php backend/php/tools/backfill_partidas_compra.php --id=562
 *
 * Requisito: columna compras.numero_partida_contable (ejecutar database backup si aplica).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Este script solo se ejecuta desde la linea de comandos. Ejemplo: php backend/php/tools/backfill_partidas_compra.php\n";
    exit(1);
}

$baseDir = dirname(__DIR__, 3);
chdir($baseDir);

require_once __DIR__ . '/../../bd/Conexion.php';
require_once __DIR__ . '/../partida_compra_proveedor.php';

$dry = in_array('--dry-run', $argv, true);
$idFiltro = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--id=') === 0) {
        $idFiltro = (int) substr($arg, 5);
    }
}

$col = $connect->query("SHOW COLUMNS FROM compras LIKE 'numero_partida_contable'")->fetch(PDO::FETCH_ASSOC);
if (!$col) {
    fwrite(STDERR, "Falta la columna compras.numero_partida_contable. Ejecute el ALTER correspondiente y vuelva a intentar.\n");
    exit(1);
}

$sql = '
SELECT c.id_compra, c.cred_cont, c.sucursal
FROM compras c
WHERE EXISTS (SELECT 1 FROM detalle_compras d WHERE d.id_compra = c.id_compra)
  AND (c.numero_partida_contable IS NULL OR TRIM(c.numero_partida_contable) = \'\')
';
$params = [];
if ($idFiltro > 0) {
    $sql .= ' AND c.id_compra = ? ';
    $params[] = $idFiltro;
}
$sql .= ' ORDER BY c.id_compra ASC';

$st = $connect->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$n = count($rows);
echo "Compras candidatas: {$n}" . PHP_EOL;

$ok = 0;
$err = 0;

foreach ($rows as $r) {
    $id = (int) $r['id_compra'];
    $unidad = trim((string) ($r['sucursal'] ?? ''));
    if ($unidad === '') {
        $unidad = 'Hospital Medicasa';
    }

    if ($dry) {
        echo "[dry-run] id_compra={$id} cred_cont=" . ($r['cred_cont'] ?? '') . PHP_EOL;
        $ok++;
        continue;
    }

    try {
        $np = medidata_generar_partida_desde_compra(
            $connect,
            $id,
            'BACKFILL_PARTIDAS',
            $unidad
        );
        echo "OK id_compra={$id} partida={$np}\n";
        $ok++;
    } catch (Throwable $e) {
        fwrite(STDERR, "ERROR id_compra={$id}: " . $e->getMessage() . PHP_EOL);
        $err++;
    }
}

echo PHP_EOL . 'Resumen: ' . ($dry ? 'simulados' : 'procesados') . "={$ok} errores={$err}" . PHP_EOL;
