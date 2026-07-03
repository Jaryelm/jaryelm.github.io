<?php
/**
 * Endpoint server-side para tablas de facturas (DataTables) en los escritorios.
 * Sistema: MEDIDATA
 *
 * Reutilizable mediante parametros:
 *   estado = Cobrada | Pendiente      (invoice_status)
 *   tipo   = Hospitalizado (opcional) (orders.tipo)
 *
 * Devuelve solo la pagina solicitada (LIMIT start,length). 10 por pagina.
 * Respuesta: { draw, recordsTotal, recordsFiltered, data: [...] }
 */

require_once __DIR__ . '/../bd/Conexion.php';
header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['draw' => intval($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'No autorizado']);
    exit;
}

try {
    $draw = intval($_GET['draw'] ?? 1);
    $start = max(0, intval($_GET['start'] ?? 0));
    $lengthRaw = intval($_GET['length'] ?? 10);
    $length = ($lengthRaw <= 0) ? 500 : min($lengthRaw, 500);
    $searchValue = trim((string) ($_GET['search']['value'] ?? ''));

    // Filtros base (whitelist).
    $estado = (string) ($_GET['estado'] ?? 'Cobrada');
    if (!in_array($estado, ['Cobrada', 'Pendiente'], true)) {
        $estado = 'Cobrada';
    }
    $tipo = trim((string) ($_GET['tipo'] ?? ''));

    $whereBase = ' WHERE invoice_status = :estado';
    $baseParams = [':estado' => $estado];
    if ($tipo !== '') {
        $whereBase .= ' AND tipo = :tipo';
        $baseParams[':tipo'] = $tipo;
    }

    // Total sin busqueda (pero con filtros base).
    $stmtT = $connect->prepare("SELECT COUNT(*) FROM orders $whereBase");
    $stmtT->execute($baseParams);
    $recordsTotal = (int) $stmtT->fetchColumn();

    // Busqueda.
    $where = $whereBase;
    $params = $baseParams;
    if ($searchValue !== '') {
        $where .= ' AND (invoice_number LIKE :s0 OR nomcl LIKE :s1 OR tipo LIKE :s2 OR processed_by LIKE :s3)';
        $like = '%' . $searchValue . '%';
        $params[':s0'] = $like;
        $params[':s1'] = $like;
        $params[':s2'] = $like;
        $params[':s3'] = $like;
    }

    $stmtF = $connect->prepare("SELECT COUNT(*) FROM orders $where");
    $stmtF->execute($params);
    $recordsFiltered = (int) $stmtF->fetchColumn();

    // Ordenamiento por nombre de campo (whitelist).
    $allowedOrderFields = ['invoice_number', 'nomcl', 'tipo', 'processed_by', 'placed_on', 'total_price'];
    $orderColIdx = intval($_GET['order'][0]['column'] ?? 4);
    $orderField = (string) ($_GET['columns'][$orderColIdx]['data'] ?? 'placed_on');
    $orderBy = in_array($orderField, $allowedOrderFields, true) ? $orderField : 'placed_on';
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $orderClause = " ORDER BY $orderBy $orderDir, idord DESC";

    $sql = "SELECT invoice_number, nomcl, tipo, processed_by, placed_on, total_price
            FROM orders $where $orderClause LIMIT :start, :length";
    $stmt = $connect->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $data[] = [
            'invoice_number' => $r['invoice_number'],
            'nomcl' => $r['nomcl'],
            'tipo' => $r['tipo'],
            'processed_by' => $r['processed_by'],
            'placed_on' => $r['placed_on'],
            'total_price' => $r['total_price'],
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_facturas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['draw' => intval($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Error al cargar datos']);
}
