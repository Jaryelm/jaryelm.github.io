<?php
/**
 * DataTables server-side: Detalle por producto/servicio — Dashboard Ventas.
 */
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/reporte_datatable_helper.php';
require_once __DIR__ . '/../php/dashboard_ventas_helper.php';
include_once __DIR__ . '/session_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $draw = (int) ($_GET['draw'] ?? 1);
    $start = max(0, (int) ($_GET['start'] ?? 0));
    $lengthRaw = (int) ($_GET['length'] ?? 10);
    $length = ($lengthRaw <= 0) ? 10 : min($lengthRaw, 100);
    $searchValue = trim((string) ($_GET['search']['value'] ?? ''));

    $periodo = trim((string) ($_GET['periodo'] ?? 'mes'));
    $dates = medidata_dv_period_dates($periodo);

    $aggSql = medidata_dv_agg_subquery(':desde', ':hasta');
    $prevAggSql = medidata_dv_agg_subquery(':prev_desde', ':prev_hasta');

    $stmtTotalIng = $connect->prepare("SELECT COALESCE(SUM(ingresos), 0) FROM ({$aggSql}) cur");
    $stmtTotalIng->execute([':desde' => $dates['desde'], ':hasta' => $dates['hasta']]);
    $totalIngresos = (float) ($stmtTotalIng->fetchColumn() ?: 0);

    $baseFrom = "
        FROM (
            SELECT
                cur.codigo,
                cur.nombre,
                cur.tipo,
                cur.linea,
                cur.unidades,
                cur.ingresos,
                cur.facturas,
                cur.stock,
                COALESCE(prev.ingresos, 0) AS ingresos_prev
            FROM ({$aggSql}) cur
            LEFT JOIN ({$prevAggSql}) prev
                ON prev.codigo = cur.codigo
               AND prev.nombre = cur.nombre
               AND prev.tipo = cur.tipo
        ) t
        WHERE 1=1
    ";

    $params = [];
    $countParams = [
        ':desde' => $dates['desde'],
        ':hasta' => $dates['hasta'],
        ':prev_desde' => $dates['prev_desde'],
        ':prev_hasta' => $dates['prev_hasta'],
    ];

    if ($searchValue !== '') {
        $baseFrom .= ' AND (t.codigo LIKE :s0 OR t.nombre LIKE :s1 OR t.linea LIKE :s2 OR t.tipo LIKE :s3)';
        medidata_reporte_dt_like_params($params, $searchValue, [':s0', ':s1', ':s2', ':s3']);
    }

    $bindAll = function (PDOStatement $stmt) use ($countParams, $params): void {
        foreach ($countParams as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
    };

    $stmtCount = $connect->prepare('SELECT COUNT(*) ' . $baseFrom);
    $bindAll($stmtCount);
    $stmtCount->execute();
    $recordsFiltered = (int) $stmtCount->fetchColumn();

    $stmtTotal = $connect->prepare('SELECT COUNT(*) FROM (' . $aggSql . ') cur');
    $stmtTotal->execute([':desde' => $dates['desde'], ':hasta' => $dates['hasta']]);
    $recordsTotal = (int) $stmtTotal->fetchColumn();

    $orderColumn = (int) ($_GET['order'][0]['column'] ?? 6);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $columns = [
        0 => 't.codigo',
        1 => 't.codigo',
        2 => 't.nombre',
        3 => 't.tipo',
        4 => 't.linea',
        5 => 't.unidades',
        6 => 't.ingresos',
        7 => 't.ingresos',
        8 => 't.ingresos',
        9 => 't.facturas',
        10 => 't.stock',
        11 => 't.ingresos',
        12 => 't.nombre',
    ];
    $orderBy = $columns[$orderColumn] ?? 't.ingresos';

    $query = 'SELECT t.* ' . $baseFrom . " ORDER BY {$orderBy} {$orderDir}, t.nombre ASC LIMIT :start, :length";
    $stmt = $connect->prepare($query);
    $bindAll($stmt);
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    $rowNum = $start + 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ingresos = (float) ($row['ingresos'] ?? 0);
        $facturas = (int) ($row['facturas'] ?? 0);
        $unidades = (float) ($row['unidades'] ?? 0);
        $pct = $totalIngresos > 0 ? ($ingresos / $totalIngresos) * 100 : 0;
        $ticketLinea = $facturas > 0 ? $ingresos / $facturas : 0;
        $tend = medidata_dv_tendencia($ingresos, (float) ($row['ingresos_prev'] ?? 0));
        $rec = medidata_dv_recomendacion($row, $pct, $tend);

        $tipoLabel = ($row['tipo'] ?? '') === 'servicio' ? 'Servicio' : 'Producto';
        $stockVal = $row['stock'];
        $stockDisplay = ($row['tipo'] ?? '') === 'servicio' || $stockVal === null
            ? '—'
            : number_format((float) $stockVal, 0, '.', ',');

        $data[] = [
            'num' => $rowNum++,
            'codigo' => $row['codigo'] ?? '—',
            'nombre' => $row['nombre'] ?? '—',
            'tipo' => $tipoLabel,
            'linea' => $row['linea'] ?? '—',
            'unidades' => number_format($unidades, 0, '.', ','),
            'ingresos' => medidata_dv_fmt_lempiras($ingresos),
            'pct_ingresos' => number_format($pct, 1) . '%',
            'ticket_linea' => medidata_dv_fmt_lempiras($ticketLinea),
            'facturas' => (string) $facturas,
            'stock' => $stockDisplay,
            'tendencia' => $tend['label'],
            'tendencia_class' => $tend['class'],
            'recomendacion' => $rec,
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? 1), 'get_dashboard_ventas_detalle', $e);
}
