<?php
/**
 * Elimina postulaciones duplicadas de la misma persona (repostuló).
 * Conserva el registro más reciente.
 *
 * Uso: php backend/scripts/dedupe_postulaciones.php [--dry-run]
 */
declare(strict_types=1);

$dryRun = in_array('--dry-run', $argv ?? [], true);

$pdo = new PDO(
    'mysql:host=192.168.176.2;dbname=medic9ue_postulaciones;charset=utf8mb4',
    'dev',
    'Mrecords7',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

if (!function_exists('medidata_postulacion_nombre_norm')) {
    function medidata_postulacion_nombre_norm(string $nombre): string
    {
        $n = strtolower(preg_replace('/\s+/', ' ', trim($nombre)));
        $n = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $n);
        return $n;
    }
}

if (!function_exists('medidata_postulacion_keys')) {
    /** @return list<string> */
    function medidata_postulacion_keys(array $row): array
    {
        $keys = [];
        $email = strtolower(trim((string) ($row['correo'] ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $keys[] = 'email:' . $email;
        }
        $dni = preg_replace('/\D/', '', (string) ($row['numero_id'] ?? ''));
        $nombre = medidata_postulacion_nombre_norm((string) ($row['nombre_completo'] ?? ''));
        $dniPlaceholder = ['1111222233333'];
        if ($dni !== '' && strlen($dni) >= 8 && $nombre !== '') {
            $keys[] = 'dni:' . $dni . '|' . $nombre;
        }
        if ($dni !== '' && strlen($dni) === 13 && !in_array($dni, $dniPlaceholder, true)) {
            $keys[] = 'dni13:' . $dni;
        }
        $cv = (string) ($row['cv'] ?? '');
        $base = basename(str_replace('\\', '/', trim($cv)));
        if ($base !== '' && $nombre !== '') {
            $keys[] = 'cv:' . $base . '|' . $nombre;
        }
        if ($keys === []) {
            $keys[] = 'id:' . ($row['id'] ?? '0');
        }
        return $keys;
    }
}

$rows = $pdo->query(
    'SELECT id, nombre_completo, numero_id, correo, fecha_registro, CONVERT(cv USING utf8) AS cv
     FROM aplica ORDER BY fecha_registro DESC, id DESC'
)->fetchAll();

$parent = [];
$keyToIds = [];

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $parent[$id] = $id;
    foreach (medidata_postulacion_keys($row) as $key) {
        $keyToIds[$key][] = $id;
    }
}

$find = static function (int $x) use (&$parent, &$find): int {
    if ($parent[$x] !== $x) {
        $parent[$x] = $find($parent[$x]);
    }
    return $parent[$x];
};

$union = static function (int $a, int $b) use (&$parent, $find): void {
    $ra = $find($a);
    $rb = $find($b);
    if ($ra !== $rb) {
        $parent[$rb] = $ra;
    }
};

foreach ($keyToIds as $ids) {
    $first = (int) $ids[0];
    for ($i = 1; $i < count($ids); $i++) {
        $union($first, (int) $ids[$i]);
    }
}

$byRoot = [];
$rowById = [];
foreach ($rows as $row) {
    $id = (int) $row['id'];
    $rowById[$id] = $row;
    $root = $find($id);
    $byRoot[$root][] = $row;
}

$idsToDelete = [];
$duplicateGroups = 0;

echo "=== Postulaciones duplicadas (misma persona) ===\n";
echo ($dryRun ? "*** DRY-RUN ***\n\n" : "\n");

foreach ($byRoot as $group) {
    if (count($group) < 2) {
        continue;
    }
    usort($group, static function ($a, $b) {
        $cmp = strcmp((string) $b['fecha_registro'], (string) $a['fecha_registro']);
        return $cmp !== 0 ? $cmp : ((int) $b['id'] <=> (int) $a['id']);
    });
    $duplicateGroups++;
    $keep = $group[0];
    $keys = medidata_postulacion_keys($keep);
    echo '--- ' . implode(', ', $keys) . " (mantener id={$keep['id']}) ---\n";
    echo "  KEEP | {$keep['fecha_registro']} | {$keep['nombre_completo']}\n";
    for ($i = 1; $i < count($group); $i++) {
        $del = $group[$i];
        echo "  DEL  | {$del['fecha_registro']} | {$del['nombre_completo']} | id={$del['id']}\n";
        $idsToDelete[] = (int) $del['id'];
    }
    echo "\n";
}

echo "Grupos con duplicados: $duplicateGroups\n";
echo 'Registros a eliminar: ' . count($idsToDelete) . "\n";

if ($dryRun || $idsToDelete === []) {
    exit(0);
}

$placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
$stmt = $pdo->prepare("DELETE FROM aplica WHERE id IN ($placeholders)");
$stmt->execute($idsToDelete);

echo "\nEliminados: " . $stmt->rowCount() . "\n";
echo 'Registros restantes: ' . $pdo->query('SELECT COUNT(*) FROM aplica')->fetchColumn() . "\n";
