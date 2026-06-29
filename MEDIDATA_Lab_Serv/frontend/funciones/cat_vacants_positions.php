<?php
require '../../backend/bd/Conexion.php';
require_once '../../backend/registros/rrhh_guard.php';

echo '<option value="0">Seleccione una Plaza de Trabajo</option>';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    return;
}

/** @var PDO|null $connect */
global $connect;

try {
    $sql = "SELECT vp.id, vp.priority, pd.id_positions
            FROM vacant_positions vp
            LEFT JOIN positions_details pd ON vp.id_position = pd.id
            WHERE vp.deleted = 0 AND vp.end_date >= CURDATE()
            ORDER BY vp.priority ASC, vp.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $positionNames = [];
    if ($connect instanceof PDO && $rows !== []) {
        $ids = array_values(array_unique(array_filter(array_map(static function ($row) {
            return (int) ($row['id_positions'] ?? 0);
        }, $rows))));
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $posStmt = $connect->prepare("SELECT id, name FROM positions WHERE id IN ($placeholders)");
            $posStmt->execute($ids);
            foreach ($posStmt->fetchAll(PDO::FETCH_ASSOC) as $pos) {
                $positionNames[(int) $pos['id']] = (string) $pos['name'];
            }
        }
    }

    $options = [];
    foreach ($rows as $row) {
        $idPos = (int) ($row['id_positions'] ?? 0);
        $positionName = $positionNames[$idPos] ?? null;
        if ($positionName === null) {
            continue;
        }
        $options[] = [
            'id' => (int) $row['id'],
            'label' => $positionName . ' — ' . ($row['priority'] ?? ''),
        ];
    }

    usort($options, static function ($a, $b) {
        return strcasecmp($a['label'], $b['label']);
    });

    foreach ($options as $opt) {
        echo '<option value="' . $opt['id'] . '">' . htmlspecialchars($opt['label']) . '</option>';
    }
} catch (Throwable $e) {
    error_log('cat_vacants_positions.php: ' . $e->getMessage());
}
