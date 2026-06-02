<?php
declare(strict_types=1);

require_once __DIR__ . '/../bd/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($connect) || !($connect instanceof PDO)) {
    http_response_code(500);
    echo json_encode([
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'No hay conexión a la base de datos.',
    ]);
    exit;
}

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start = max(0, (int) ($_POST['start'] ?? 0));
$length = (int) ($_POST['length'] ?? 10);
if ($length < 1 || $length > 200) {
    $length = 10;
}
$search = trim((string) ($_POST['search']['value'] ?? ''));
$includeAnular = isset($_POST['include_anular'])
    ? filter_var((string) $_POST['include_anular'], FILTER_VALIDATE_BOOLEAN)
    : true;

$orderIdx = (int) ($_POST['order'][0]['column'] ?? 2);
$orderDir = strtolower((string) ($_POST['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

$columns = [
    0 => 'invoice_number',
    1 => 'processed_by',
    2 => 'placed_on',
    3 => 'nomcl',
    4 => 'method',
    5 => 'price_without_discount',
    6 => 'total_price',
    7 => 'tipc',
    8 => 'tipc',
    9 => 'idord',
    10 => 'invoice_status',
    11 => 'updated_by',
    12 => 'idord',
];
$orderBy = $columns[$orderIdx] ?? 'placed_on';

$total = (int) $connect->query('SELECT COUNT(*) FROM orders')->fetchColumn();

$whereSql = '';
$params = [];
if ($search !== '') {
    $whereSql = ' WHERE invoice_number LIKE :q_invoice
                  OR processed_by LIKE :q_processed_by
                  OR placed_on LIKE :q_placed_on
                  OR nomcl LIKE :q_nomcl
                  OR method LIKE :q_method
                  OR invoice_status LIKE :q_invoice_status
                  OR updated_by LIKE :q_updated_by';
    $searchLike = '%' . $search . '%';
    $params = [
        ':q_invoice' => $searchLike,
        ':q_processed_by' => $searchLike,
        ':q_placed_on' => $searchLike,
        ':q_nomcl' => $searchLike,
        ':q_method' => $searchLike,
        ':q_invoice_status' => $searchLike,
        ':q_updated_by' => $searchLike,
    ];
}

$countFilteredSql = 'SELECT COUNT(*) FROM orders' . $whereSql;
$stmtCount = $connect->prepare($countFilteredSql);
foreach ($params as $k => $v) {
    $stmtCount->bindValue($k, $v, PDO::PARAM_STR);
}
$stmtCount->execute();
$filtered = (int) $stmtCount->fetchColumn();

$sql = 'SELECT idord, invoice_number, processed_by, placed_on, nomcl, method, price_without_discount, total_price, tipc, invoice_status, updated_by
        FROM orders'
        . $whereSql .
        " ORDER BY {$orderBy} {$orderDir} LIMIT :start, :length";
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
    $idord = (int) ($r['idord'] ?? 0);
    $invoiceNumber = htmlspecialchars((string) ($r['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8');
    $tipc = (string) ($r['tipc'] ?? '');
    $invoiceStatus = (string) ($r['invoice_status'] ?? '');
    $disabledAnulada = $invoiceStatus === 'Anulada' ? 'disabled title="Factura anulada"' : '';

    $iconGeneral = '';
    $iconDesglosada = '';
    if ($tipc === 'Boleta') {
        $iconGeneral = '<i class="bx bx-show" title="Ver Factura General" onclick="verPDF(\'../../frontend/almacen/documento_general.php?id=' . $idord . '\', \'Factura General\')" style="cursor: pointer; color: #06adbf; font-size: 24px; display: inline-block; vertical-align: middle;"></i>';
        $iconDesglosada = '<i class="bx bx-show" title="Ver Factura Desglosada" onclick="verPDF(\'../../frontend/almacen/documento.php?id=' . $idord . '\', \'Factura Desglosada\')" style="cursor: pointer; color: #06adbf; font-size: 24px; display: inline-block; vertical-align: middle;"></i>';
    }

    $checked = $invoiceStatus === 'Cobrada' ? 'checked' : '';
    $disabledSwitch = $invoiceStatus === 'Anulada' ? 'disabled' : '';
    $statusHtml = '<label class="status-switch">
        <input type="checkbox" data-id="' . $idord . '" data-current-status="' . htmlspecialchars($invoiceStatus, ENT_QUOTES, 'UTF-8') . '"
        onchange="updateStatus(' . $idord . ', this.checked ? \'Cobrada\' : \'Pendiente\')" ' . $checked . ' ' . $disabledSwitch . '>
        <span class="status-slider"></span>
    </label>';
    if ($invoiceStatus === 'Anulada') {
        $statusHtml .= '<span style="color:#6c757d;font-size:11px;">Anulada</span>';
    }

    $accionesHtml = '<div class="acciones-btns">
            <button class="btn_devolucion" onclick="iniciarDevolucion(' . $idord . ')" ' . $disabledAnulada . '>Devolución</button>';
    if ($includeAnular) {
        $accionesHtml .= '<button class="btn_anular" onclick="abrirModalAnulacion(this)" data-order-id="' . $idord . '" data-invoice-number="' . $invoiceNumber . '" ' . $disabledAnulada . '>Anular Factura</button>';
    }
    $accionesHtml .= '</div>';

    $data[] = [
        htmlspecialchars((string) ($r['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) ($r['processed_by'] ?? ''), ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) ($r['placed_on'] ?? ''), ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) ($r['nomcl'] ?? ''), ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) ($r['method'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'LPS. ' . number_format((float) ($r['price_without_discount'] ?? 0), 2),
        'LPS. ' . number_format((float) ($r['total_price'] ?? 0), 2),
        $iconGeneral,
        $iconDesglosada,
        '<button class="btn_ver_detalles" onclick="viewDetails(' . $idord . ')">Ver Detalles</button>',
        $statusHtml,
        htmlspecialchars((string) ($r['updated_by'] ?? ''), ENT_QUOTES, 'UTF-8'),
        $accionesHtml,
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filtered,
    'data' => $data,
], JSON_UNESCAPED_UNICODE);

