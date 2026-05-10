<?php
/**
 * Lista partidas manuales para DataTables (formato server-side)
 * Sistema: MEDIDATA
 */

include_once '../../backend/registros/session_check.php';
header('Content-Type: application/json');

try {
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $searchValue = $_GET['search']['value'] ?? '';
    $fechaDesde = $_GET['fechaDesde'] ?? null;
    $fechaHasta = $_GET['fechaHasta'] ?? null;
    $numeroPartida = $_GET['numeroPartida'] ?? null;

    $whereBase = " WHERE tipo_transaccion = 'PARTIDA_MANUAL'";
    $params = [];
    $types = [];

    if ($fechaDesde) {
        $whereBase .= " AND fecha_ocurrencia >= :fechaDesde";
        $params[':fechaDesde'] = $fechaDesde;
        $types[':fechaDesde'] = PDO::PARAM_STR;
    }
    if ($fechaHasta) {
        $whereBase .= " AND fecha_ocurrencia <= :fechaHasta";
        $params[':fechaHasta'] = $fechaHasta;
        $types[':fechaHasta'] = PDO::PARAM_STR;
    }
    if ($numeroPartida) {
        $whereBase .= " AND numero_partida = :numeroPartida";
        $params[':numeroPartida'] = $numeroPartida;
        $types[':numeroPartida'] = PDO::PARAM_STR;
    }

    /**
     * Modo simple: una sola consulta + DataTables en el cliente.
     * Evita 3 queries pesadas (2× COUNT sobre subquery GROUP BY + SELECT) por cada interacción.
     */
    if (!empty($_GET['simple'])) {
        $limite = min(5000, max(1, intval($_GET['limite'] ?? 5000)));
        $sqlSimple = "SELECT numero_partida, MIN(fecha_ocurrencia) AS fecha_ocurrencia,
            MAX(referencia) AS referencia, MAX(descripcion) AS descripcion,
            SUM(debe) AS total_debe, SUM(haber) AS total_haber
            FROM diario_general_transacciones $whereBase
            GROUP BY numero_partida
            ORDER BY numero_partida DESC
            LIMIT " . $limite;
        $stmt = $connect->prepare($sqlSimple);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $types[$key] ?? PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'numero_partida' => $r['numero_partida'],
                'fecha_ocurrencia' => date('d/m/Y', strtotime($r['fecha_ocurrencia'])),
                'referencia' => $r['referencia'] ?? '',
                'descripcion' => $r['descripcion'] ?? '',
                'total_debe' => (float) ($r['total_debe'] ?? 0),
                'total_haber' => (float) ($r['total_haber'] ?? 0),
            ];
        }
        echo json_encode(['data' => $data]);
        exit;
    }

    $subQuery = "SELECT numero_partida, MIN(fecha_ocurrencia) AS fecha_ocurrencia, MIN(fecha_registro) AS fecha_registro,
        MAX(referencia) AS referencia, MAX(descripcion) AS descripcion, SUM(debe) AS total_debe, SUM(haber) AS total_haber, MAX(usuario) AS usuario
        FROM diario_general_transacciones $whereBase GROUP BY numero_partida";

    /* Total sin búsqueda global: COUNT(DISTINCT) evita subquery + GROUP BY completo (escala mejor con miles de partidas) */
    $countDistinctSql = "SELECT COUNT(DISTINCT numero_partida) FROM diario_general_transacciones $whereBase";
    $stmtCount = $connect->prepare($countDistinctSql);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value, $types[$key] ?? PDO::PARAM_STR);
    }
    $stmtCount->execute();
    $totalRecords = (int) $stmtCount->fetchColumn();

    $searchWhere = "";
    if (!empty($searchValue)) {
        $searchWhere = " AND (numero_partida LIKE :search OR referencia LIKE :search OR descripcion LIKE :search OR usuario LIKE :search)";
        $params[':search'] = '%' . $searchValue . '%';
        $types[':search'] = PDO::PARAM_STR;
    }

    $orderColumn = intval($_GET['order'][0]['column'] ?? 0);
    $orderDirRaw = strtoupper(trim((string)($_GET['order'][0]['dir'] ?? 'DESC')));
    $orderDir = ($orderDirRaw === 'ASC') ? 'ASC' : 'DESC';
    $columns = [0 => 'numero_partida', 1 => 'fecha_ocurrencia', 2 => 'referencia', 3 => 'descripcion', 4 => 'total_debe', 5 => 'total_haber'];
    $orderBy = $columns[$orderColumn] ?? 'numero_partida';

    $dataQuery = "SELECT * FROM ($subQuery) AS t WHERE 1=1 $searchWhere ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $params[':start'] = $start;
    $params[':length'] = $length;
    $types[':start'] = PDO::PARAM_INT;
    $types[':length'] = PDO::PARAM_INT;

    if ($searchWhere === '') {
        $recordsFiltered = $totalRecords;
    } else {
        $stmtTotal = $connect->prepare("SELECT COUNT(*) FROM ($subQuery) AS t WHERE 1=1 $searchWhere");
        foreach ($params as $key => $value) {
            if ($key !== ':start' && $key !== ':length') {
                $stmtTotal->bindValue($key, $value, $types[$key] ?? PDO::PARAM_STR);
            }
        }
        $stmtTotal->execute();
        $recordsFiltered = (int) $stmtTotal->fetchColumn();
    }

    $stmt = $connect->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, $types[$key] ?? PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $data[] = [
            'numero_partida' => $r['numero_partida'],
            'fecha_ocurrencia' => date('d/m/Y', strtotime($r['fecha_ocurrencia'])),
            'referencia' => $r['referencia'] ?? '',
            'descripcion' => $r['descripcion'] ?? '',
            'total_debe' => number_format($r['total_debe'], 2, '.', ','),
            'total_haber' => number_format($r['total_haber'], 2, '.', ',')
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("Error get_partidas_manuales: " . $e->getMessage());
    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
