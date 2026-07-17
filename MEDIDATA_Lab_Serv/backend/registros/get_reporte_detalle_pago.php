<?php
/**
 * DataTables server-side: Detalle Pago (órdenes / ventas).
 */
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/reporte_datatable_helper.php';
include_once __DIR__ . '/session_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $draw = (int) ($_GET['draw'] ?? 1);
    $start = max(0, (int) ($_GET['start'] ?? 0));
    $lengthRaw = (int) ($_GET['length'] ?? 10);
    $length = ($lengthRaw <= 0) ? 10 : min($lengthRaw, 100);
    $searchValue = trim((string) ($_GET['search']['value'] ?? ''));

    $desde = trim((string) ($_GET['fechaDesde'] ?? $_GET['desde'] ?? ''));
    $hasta = trim((string) ($_GET['fechaHasta'] ?? $_GET['hasta'] ?? ''));

    $baseFrom = medidata_reporte_detalle_pago_from_sql($connect);
    $selectSql = medidata_reporte_detalle_pago_select_sql($connect);

    $params = [];
    $whereExtra = '';

    if ($desde !== '' && $hasta !== '') {
        $whereExtra .= ' AND DATE(o.placed_on) BETWEEN :desde AND :hasta';
        $params[':desde'] = $desde;
        $params[':hasta'] = $hasta;
    } elseif ($desde !== '') {
        $whereExtra .= ' AND DATE(o.placed_on) >= :desde';
        $params[':desde'] = $desde;
    } elseif ($hasta !== '') {
        $whereExtra .= ' AND DATE(o.placed_on) <= :hasta';
        $params[':hasta'] = $hasta;
    }

    if ($searchValue !== '') {
        $whereExtra .= ' AND (
            CAST(o.idord AS CHAR) LIKE :s0
            OR o.invoice_number LIKE :s1
            OR o.nomcl LIKE :s2
            OR o.method LIKE :s3
            OR o.invoice_status LIKE :s4
            OR det.detalle_examen LIKE :s5
        )';
        medidata_reporte_dt_like_params($params, $searchValue, [':s0', ':s1', ':s2', ':s3', ':s4', ':s5']);
    }

    $fromWhere = $baseFrom . $whereExtra;

    $stmtTotal = $connect->prepare('SELECT COUNT(o.idord) ' . $fromWhere);
    foreach ($params as $key => $value) {
        $stmtTotal->bindValue($key, $value);
    }
    $stmtTotal->execute();
    $recordsTotal = (int) $stmtTotal->fetchColumn();
    $recordsFiltered = $recordsTotal;

    $orderColumn = (int) ($_GET['order'][0]['column'] ?? 0);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $telOrderCol = medidata_reporte_detalle_pago_has_tel_column($connect) ? 'o.telefono_paciente' : 'p.phon';
    $columns = [
        0 => 'o.placed_on',
        1 => 'o.placed_on',
        2 => 'o.method',
        3 => 'o.invoice_status',
        4 => 'o.invoice_number',
        5 => 'det.detalle_examen',
        6 => 'o.nomcl',
        7 => $telOrderCol,
        8 => 'o.discount_amount',
        9 => 'o.price_without_discount',
        10 => 'o.discount_amount',
        11 => 'o.tax_amount',
        12 => 'o.total_price',
    ];
    $orderBy = $columns[$orderColumn] ?? 'o.placed_on';

    $query = $selectSql . $fromWhere . " ORDER BY {$orderBy} {$orderDir}, o.idord DESC"
        . medidata_reporte_dt_limit_sql($start, $length);
    $stmt = $connect->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fhRaw = $row['placed_on'] ?? '';
        $celular = trim((string) ($row['telefono_paciente'] ?? ''));
        if ($celular === '') {
            $celular = trim((string) ($row['paciente_phon'] ?? ''));
        }
        if ($celular === '') {
            $celular = '-';
        }

        $data[] = [
            'fecha' => $fhRaw ? date('d-m-Y', strtotime($fhRaw)) : '-',
            'fecha_iso' => $fhRaw,
            'hora' => $fhRaw ? date('H:i', strtotime($fhRaw)) : '-',
            'forma_pago' => $row['method'] ?? '-',
            'estado' => $row['invoice_status'] ?? '-',
            'factura' => !empty($row['invoice_number']) ? $row['invoice_number'] : '-',
            'detalle_examen' => $row['detalle_examen'] ?? '-',
            'cliente' => $row['nomcl'] ?? '-',
            'cliente_celular' => $celular,
            'tipo_descuento' => medidata_reporte_tipo_descuento_predominante($row),
            'subtotal' => medidata_reporte_fmt_lempiras($row['price_without_discount'] ?? 0),
            'descuento' => medidata_reporte_fmt_lempiras($row['discount_amount'] ?? 0),
            'impuesto' => medidata_reporte_fmt_lempiras($row['tax_amount'] ?? 0),
            'total' => medidata_reporte_fmt_lempiras($row['total_price'] ?? 0),
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? 1), 'get_reporte_detalle_pago', $e);
}
