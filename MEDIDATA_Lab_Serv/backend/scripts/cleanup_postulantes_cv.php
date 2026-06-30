<?php
/**
 * Limpieza única: CV huérfanos en disco + registros sin archivo.
 * Uso: php backend/scripts/cleanup_postulantes_cv.php [--dry-run]
 */
declare(strict_types=1);

$dryRun = in_array('--dry-run', $argv ?? [], true);
$cvDir = '/home/medicasa/MedicasaDATAUpdate2/uploads/Postulantes_CV';

require_once __DIR__ . '/../registros/postulaciones_guard.php';

$pdo = new PDO(
    'mysql:host=192.168.176.2;dbname=medic9ue_postulaciones;charset=utf8mb4',
    'dev',
    'Mrecords7',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$rows = $pdo->query('SELECT id, nombre_completo, cv FROM aplica ORDER BY id')->fetchAll();

$registeredBasenames = [];
$missingFileIds = [];
$emptyCvIds = [];

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $cv = $row['cv'] ?? '';
    if ($cv === '' || $cv === null) {
        $emptyCvIds[] = $id;
        continue;
    }
    $path = medidata_postulaciones_resolver_ruta_cv($cv);
    $base = basename(str_replace('\\', '/', trim((string) $cv)));
    if ($base !== '') {
        $registeredBasenames[$base] = true;
    }
    if ($path === null || !is_readable($path)) {
        $missingFileIds[] = [
            'id' => $id,
            'nombre' => $row['nombre_completo'] ?? '',
            'expected' => $base,
        ];
    }
}

$orphanFiles = [];
if (is_dir($cvDir)) {
    foreach (scandir($cvDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $full = $cvDir . '/' . $entry;
        if (!is_file($full)) {
            continue;
        }
        if (!isset($registeredBasenames[$entry])) {
            $orphanFiles[] = $full;
        }
    }
}

echo "=== Resumen ===\n";
echo 'Registros en BD: ' . count($rows) . "\n";
echo 'Registros sin CV en BD: ' . count($emptyCvIds) . "\n";
echo 'Registros con CV pero archivo no encontrado: ' . count($missingFileIds) . "\n";
echo 'Archivos en carpeta: ' . count(glob($cvDir . '/*') ?: []) . "\n";
echo 'Archivos huérfanos (no en BD): ' . count($orphanFiles) . "\n";
echo ($dryRun ? "\n*** MODO DRY-RUN: no se borra nada ***\n\n" : "\n");

if ($missingFileIds) {
    echo "--- Registros a eliminar (sin archivo) ---\n";
    foreach (array_slice($missingFileIds, 0, 20) as $m) {
        echo "  id={$m['id']} | {$m['expected']} | {$m['nombre']}\n";
    }
    if (count($missingFileIds) > 20) {
        echo '  ... y ' . (count($missingFileIds) - 20) . " más\n";
    }
}

if ($orphanFiles) {
    echo "--- Archivos huérfanos (muestra) ---\n";
    foreach (array_slice($orphanFiles, 0, 10) as $f) {
        echo '  ' . basename($f) . "\n";
    }
    if (count($orphanFiles) > 10) {
        echo '  ... y ' . (count($orphanFiles) - 10) . " más\n";
    }
}

if ($dryRun) {
    exit(0);
}

$deletedFiles = 0;
foreach ($orphanFiles as $file) {
    if (@unlink($file)) {
        $deletedFiles++;
    } else {
        fwrite(STDERR, "No se pudo borrar: $file\n");
    }
}

$idsToDelete = array_merge(
    $emptyCvIds,
    array_column($missingFileIds, 'id')
);
$idsToDelete = array_values(array_unique($idsToDelete));

$deletedRows = 0;
if ($idsToDelete !== []) {
    $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
    $stmt = $pdo->prepare("DELETE FROM aplica WHERE id IN ($placeholders)");
    $stmt->execute($idsToDelete);
    $deletedRows = $stmt->rowCount();
}

echo "\n=== Ejecutado ===\n";
echo "Archivos huérfanos eliminados: $deletedFiles\n";
echo "Registros eliminados de BD: $deletedRows\n";
echo 'Registros restantes: ' . $pdo->query('SELECT COUNT(*) FROM aplica')->fetchColumn() . "\n";
echo 'Archivos restantes: ' . count(glob($cvDir . '/*') ?: []) . "\n";
