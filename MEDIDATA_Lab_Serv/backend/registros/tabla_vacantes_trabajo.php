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
    $inactivas = isset($_GET['inactivas']) && $_GET['inactivas'] == '1' ? 1 : 0;

    $sql = "SELECT 
	vp.id, 
	vp.priority, 
    vp.available_slots, 
    vp.reason,
    vp.init_date,
    vp.end_date,
    vp.deleted, 
    vp.status,
    pd.id_positions,
    COALESCE(NULLIF(TRIM(d.name), ''), '—') AS department_name,
    pd.immediate_boss,
    (
		SELECT 
			COUNT(*) 
		FROM candidates c 
        WHERE c.id_vacant_position = vp.id 
			AND 
            c.deleted = 0
	) AS total_applicants
FROM vacant_positions vp
    LEFT JOIN 
		positions_details pd 
			ON vp.id_position = pd.id
	LEFT JOIN 
		departaments d 
			ON pd.id_departament = d.id
WHERE vp.deleted = $inactivas
ORDER BY FIELD(vp.priority, 'Urgente', 'Alta', 'Media', 'Baja'), vp.init_date DESC";

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

    $needle = $search !== '' ? mb_strtolower($search, 'UTF-8') : '';
    $result = [];
    foreach ($rows as $row) {
        $idPos = (int) ($row['id_positions'] ?? 0);
        $positionName = $positionNames[$idPos] ?? null;
        if ($positionName === null) {
            continue;
        }

        if ($needle !== '' && mb_stripos($positionName, $search, 0, 'UTF-8') === false) {
            $haystack = mb_strtolower(
                $positionName . ' ' . ($row['department_name'] ?? '') . ' ' .
                ($row['immediate_boss'] ?? '') . ' ' . ($row['reason'] ?? ''),
                'UTF-8'
            );
            if (mb_stripos($haystack, $needle, 0, 'UTF-8') === false) {
                continue;
            }
        }

        unset($row['id_positions']);
        $row['position_name'] = $positionName;
        $result[] = $row;
    }

    echo json_encode($result);
} catch (Throwable $e) {
    error_log('tabla_vacantes_trabajo: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar vacantes de trabajo', 'explicit' => $e->getMessage()]);
}
