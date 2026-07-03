<?php
/**
 * Endpoint server-side para "Historial de Cierres de Caja" (DataTables).
 * Sistema: MEDIDATA
 *
 * Por defecto muestra solo los cierres del usuario logueado (usuario_cierre = username
 * de sesion), conservando el comportamiento de caja/facturacion. Con scope=all devuelve
 * TODOS los cierres (comportamiento de admin/contabilidad/auxcontable).
 * Paginacion server-side, 10 por pagina.
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

    $scope = (string) ($_GET['scope'] ?? '');
    if ($scope === 'all') {
        // admin / contabilidad / auxcontable: historial completo.
        $whereBase = ' WHERE 1=1';
        $baseParams = [];
    } else {
        // caja / facturacion: solo los cierres del usuario logueado.
        $usuario = (string) ($_SESSION['username'] ?? '');
        $whereBase = ' WHERE usuario_cierre = :usuario';
        $baseParams = [':usuario' => $usuario];
    }

    $stmtT = $connect->prepare("SELECT COUNT(*) FROM cierre_caja $whereBase");
    $stmtT->execute($baseParams);
    $recordsTotal = (int) $stmtT->fetchColumn();

    $where = $whereBase;
    $params = $baseParams;
    if ($searchValue !== '') {
        $where .= ' AND (fecha_cierre LIKE :s0 OR nombre_completo LIKE :s1)';
        $like = '%' . $searchValue . '%';
        $params[':s0'] = $like;
        $params[':s1'] = $like;
    }

    $stmtF = $connect->prepare("SELECT COUNT(*) FROM cierre_caja $where");
    $stmtF->execute($params);
    $recordsFiltered = (int) $stmtF->fetchColumn();

    $allowedOrderFields = ['fecha_cierre', 'total_ventas', 'total_facturas', 'facturas_cobradas', 'facturas_pendientes', 'usuario_cierre', 'nombre_completo'];
    $orderColIdx = intval($_GET['order'][0]['column'] ?? 0);
    $orderField = (string) ($_GET['columns'][$orderColIdx]['data'] ?? 'fecha_cierre');
    $orderBy = in_array($orderField, $allowedOrderFields, true) ? $orderField : 'fecha_cierre';
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $orderClause = " ORDER BY $orderBy $orderDir, id DESC";

    $sql = "SELECT fecha_cierre, total_ventas, total_facturas, facturas_cobradas, facturas_pendientes,
                   total_por_metodo, usuario_cierre, nombre_completo
            FROM cierre_caja $where $orderClause LIMIT :start, :length";
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
            'fecha_cierre' => $r['fecha_cierre'],
            'total_ventas' => $r['total_ventas'],
            'total_facturas' => $r['total_facturas'],
            'facturas_cobradas' => $r['facturas_cobradas'],
            'facturas_pendientes' => $r['facturas_pendientes'],
            'total_por_metodo' => $r['total_por_metodo'],
            'usuario_cierre' => $r['usuario_cierre'],
            'nombre_completo' => $r['nombre_completo'],
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_cierres_caja: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['draw' => intval($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Error al cargar datos']);
}
