<?php
/**
 * Dashboard Ventas — resumen (tarjetas, tops, mix, insights).
 */
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/dashboard_ventas_helper.php';
include_once __DIR__ . '/session_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $periodo = trim((string) ($_GET['periodo'] ?? 'mes'));
    $dates = medidata_dv_period_dates($periodo);

    $params = [
        ':desde' => $dates['desde'],
        ':hasta' => $dates['hasta'],
        ':prev_desde' => $dates['prev_desde'],
        ':prev_hasta' => $dates['prev_hasta'],
    ];

    $orderFilter = medidata_dv_orders_filter_sql('o');

    // Totales del período actual
    $stmtTot = $connect->prepare("
        SELECT
            COUNT(*) AS transacciones,
            COALESCE(SUM(o.total_price), 0) AS ingresos,
            COALESCE(SUM(o.tax_amount), 0) AS isv,
            COALESCE(SUM(o.discount_amount), 0) AS descuentos
        FROM orders o
        WHERE {$orderFilter}
          AND DATE(o.placed_on) BETWEEN :desde AND :hasta
    ");
    $stmtTot->execute([':desde' => $dates['desde'], ':hasta' => $dates['hasta']]);
    $tot = $stmtTot->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmtPrev = $connect->prepare("
        SELECT COALESCE(SUM(o.total_price), 0) AS ingresos
        FROM orders o
        WHERE {$orderFilter}
          AND DATE(o.placed_on) BETWEEN :prev_desde AND :prev_hasta
    ");
    $stmtPrev->execute([':prev_desde' => $dates['prev_desde'], ':prev_hasta' => $dates['prev_hasta']]);
    $prevIngresos = (float) ($stmtPrev->fetchColumn() ?: 0);

    $ingresos = (float) ($tot['ingresos'] ?? 0);
    $transacciones = (int) ($tot['transacciones'] ?? 0);
    $ticket = $transacciones > 0 ? $ingresos / $transacciones : 0;

    $varPct = null;
    if ($prevIngresos > 0) {
        $varPct = (($ingresos - $prevIngresos) / $prevIngresos) * 100;
    }

    // Unidades y productos distintos
    $aggSql = medidata_dv_agg_subquery();
    $stmtUni = $connect->prepare("SELECT COALESCE(SUM(unidades), 0), COUNT(*) FROM ({$aggSql}) x");
    $stmtUni->execute([':desde' => $dates['desde'], ':hasta' => $dates['hasta']]);
    $uniRow = $stmtUni->fetch(PDO::FETCH_NUM) ?: [0, 0];
    $unidades = (float) ($uniRow[0] ?? 0);
    $productosCount = (int) ($uniRow[1] ?? 0);

    // Top 10 más / menos
    $stmtTop = $connect->prepare("
        SELECT codigo, nombre, tipo, ingresos, unidades
        FROM ({$aggSql}) x
        ORDER BY ingresos DESC
        LIMIT 10
    ");
    $stmtTop->execute([':desde' => $dates['desde'], ':hasta' => $dates['hasta']]);
    $topMas = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    $stmtMenos = $connect->prepare("
        SELECT codigo, nombre, tipo, ingresos, unidades
        FROM ({$aggSql}) x
        WHERE ingresos > 0
        ORDER BY ingresos ASC
        LIMIT 10
    ");
    $stmtMenos->execute([':desde' => $dates['desde'], ':hasta' => $dates['hasta']]);
    $topMenos = $stmtMenos->fetchAll(PDO::FETCH_ASSOC);

    // Mix por tipo
    $stmtMixTipo = $connect->prepare("
        SELECT tipo, SUM(ingresos) AS ingresos, SUM(unidades) AS unidades
        FROM ({$aggSql}) x
        GROUP BY tipo
    ");
    $stmtMixTipo->execute([':desde' => $dates['desde'], ':hasta' => $dates['hasta']]);
    $mixTipo = $stmtMixTipo->fetchAll(PDO::FETCH_ASSOC);

    // Mix por forma de pago
    $stmtMixPago = $connect->prepare("
        SELECT o.method AS metodo, COUNT(*) AS cantidad, COALESCE(SUM(o.total_price), 0) AS ingresos
        FROM orders o
        WHERE {$orderFilter}
          AND DATE(o.placed_on) BETWEEN :desde AND :hasta
        GROUP BY o.method
        ORDER BY ingresos DESC
    ");
    $stmtMixPago->execute([':desde' => $dates['desde'], ':hasta' => $dates['hasta']]);
    $mixPago = $stmtMixPago->fetchAll(PDO::FETCH_ASSOC);

    // Insights automáticos
    $insights = [];
    if ($ingresos <= 0) {
        $insights[] = [
            'type' => 'warning',
            'title' => 'Sin ingresos cobrados',
            'text' => 'No hay facturas cobradas en el período seleccionado.',
        ];
    } else {
        if ($varPct !== null && $varPct >= 10) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Ingresos en alza',
                'text' => 'Los ingresos cobrados superan en ' . number_format($varPct, 1) . '% al período anterior comparable.',
            ];
        } elseif ($varPct !== null && $varPct <= -10) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Ingresos en baja',
                'text' => 'Los ingresos cobrados están ' . number_format(abs($varPct), 1) . '% por debajo del período anterior comparable.',
            ];
        }
        if (!empty($topMas[0])) {
            $insights[] = [
                'type' => '',
                'title' => 'Mayor aporte',
                'text' => ($topMas[0]['nombre'] ?? '—') . ' concentra ' . medidata_dv_fmt_lempiras($topMas[0]['ingresos'] ?? 0) . ' en ingresos.',
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'periodo' => $dates,
        'actualizado' => date('d/m/Y H:i'),
        'cards' => [
            'ingresos' => medidata_dv_fmt_lempiras($ingresos),
            'ingresos_raw' => $ingresos,
            'var_ingresos' => $varPct,
            'ticket' => medidata_dv_fmt_lempiras($ticket),
            'transacciones' => $transacciones,
            'unidades' => number_format($unidades, 0, '.', ','),
            'productos_count' => $productosCount,
            'isv' => medidata_dv_fmt_lempiras($tot['isv'] ?? 0),
            'descuentos' => medidata_dv_fmt_lempiras($tot['descuentos'] ?? 0),
        ],
        'top_mas' => array_map(static function ($r) {
            return [
                'nombre' => $r['nombre'],
                'tipo' => $r['tipo'],
                'ingresos' => medidata_dv_fmt_lempiras($r['ingresos']),
            ];
        }, $topMas),
        'top_menos' => array_map(static function ($r) {
            return [
                'nombre' => $r['nombre'],
                'tipo' => $r['tipo'],
                'ingresos' => medidata_dv_fmt_lempiras($r['ingresos']),
            ];
        }, $topMenos),
        'mix_tipo' => $mixTipo,
        'mix_pago' => $mixPago,
        'insights' => $insights,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_dashboard_ventas_resumen: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar el resumen del dashboard.'], JSON_UNESCAPED_UNICODE);
}
