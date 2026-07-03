<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo json_encode(['error' => 'Base de datos RRHH no disponible']);
    exit;
}

/** @var PDO|null $connect */
global $connect;

try {
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

    $sql = "SELECT pd.id, pd.id_positions,
                   COALESCE(NULLIF(TRIM(d.name), ''), NULLIF(TRIM(pd.department), ''), '—') AS department,
                   pd.immediate_boss, pd.objective,
                   COALESCE(sl.level_name, '—') AS level_name, pd.deleted
            FROM positions_details pd
            LEFT JOIN departaments d ON pd.id_departament = d.id
            LEFT JOIN salary_levels sl ON sl.id = pd.id_salary_level AND sl.deleted = 0
            WHERE pd.deleted IN (0, 1)";

    $params = [];
    if ($search !== '') {
        $sql .= " AND (
            COALESCE(d.name, pd.department) LIKE :search1 OR
            pd.immediate_boss LIKE :search2 OR pd.objective LIKE :search3
        )";
        $like = '%' . $search . '%';
        $params[':search1'] = $like;
        $params[':search2'] = $like;
        $params[':search3'] = $like;
    }

    $sql .= ' ORDER BY pd.id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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

    $needle = $search !== '' ? mb_strtolower($search, 'UTF-8') : '';
    $result = [];
    foreach ($rows as $row) {
        $idPos = (int) ($row['id_positions'] ?? 0);
        $name = $positionNames[$idPos] ?? null;
        if ($name === null) {
            continue;
        }

        if ($needle !== '' && mb_stripos($name, $search, 0, 'UTF-8') === false) {
            $haystack = mb_strtolower(
                $name . ' ' . ($row['department'] ?? '') . ' ' . ($row['immediate_boss'] ?? '') . ' ' . ($row['objective'] ?? ''),
                'UTF-8'
            );
            if (mb_stripos($haystack, $needle, 0, 'UTF-8') === false) {
                continue;
            }
        }

        unset($row['id_positions']);
        $row['name'] = $name;
        $result[] = $row;
    }

    usort($result, static function ($a, $b) {
        return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    echo json_encode($result);
} catch (Throwable $e) {
    error_log('tabla_puestos_trabajo: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar puestos de trabajo']);
}
