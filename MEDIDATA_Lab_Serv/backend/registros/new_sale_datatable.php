<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

require_once __DIR__ . '/../bd/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function ns_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

if (!isset($connect) || !($connect instanceof PDO)) {
    ns_json([
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'No hay conexión a la base de datos.',
    ], 500);
    exit;
}

try {
    $draw = (int) ($_POST['draw'] ?? 0);
    $start = max(0, (int) ($_POST['start'] ?? 0));
    $length = (int) ($_POST['length'] ?? 10);
    if ($length < 1 || $length > 200) {
        $length = 10;
    }

    $searchRaw = $_POST['search'] ?? '';
    $search = is_array($searchRaw)
        ? trim((string) ($searchRaw['value'] ?? ''))
        : trim((string) $searchRaw);

    $tableType = (string) ($_POST['table'] ?? 'productos');
    $userId = (int) ($_SESSION['id'] ?? 0);

    $orderRaw = $_POST['order'] ?? [];
    $orderFirst = (is_array($orderRaw) && isset($orderRaw[0]) && is_array($orderRaw[0])) ? $orderRaw[0] : [];
    $orderIdx = (int) ($orderFirst['column'] ?? 0);
    $orderDir = strtolower((string) ($orderFirst['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

    if ($tableType === 'servicios') {
        $columns = [
            0 => 'codigo_servicio',
            1 => 'nombre_servicio',
            2 => 'nomservicio',
            3 => 'categoria_servicio',
            4 => 'uso_servicio',
            5 => 'impuesto',
            6 => 'total',
            7 => 'id',
        ];
        $orderBy = $columns[$orderIdx] ?? 'codigo_servicio';

        $baseFrom = " FROM servicios_hospital WHERE COALESCE(estado, 'habilitado') = 'habilitado' ";
        $whereSql = '';
        $params = [];
        if ($search !== '') {
            $whereSql = " AND (
                codigo_servicio LIKE :q_codigo
                OR nombre_servicio LIKE :q_cuenta
                OR nomservicio LIKE :q_nombre
                OR categoria_servicio LIKE :q_categoria
                OR uso_servicio LIKE :q_uso
                OR impuesto LIKE :q_impuesto
                OR CAST(total AS CHAR) LIKE :q_total
            )";
            $q = '%' . $search . '%';
            $params = [
                'q_codigo' => $q,
                'q_cuenta' => $q,
                'q_nombre' => $q,
                'q_categoria' => $q,
                'q_uso' => $q,
                'q_impuesto' => $q,
                'q_total' => $q,
            ];
        }

        $stmtTotal = $connect->query("SELECT COUNT(*)" . $baseFrom);
        $total = (int) $stmtTotal->fetchColumn();

        $stmtFiltered = $connect->prepare("SELECT COUNT(*)" . $baseFrom . $whereSql);
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        $sql = "SELECT id, codigo_servicio, nombre_servicio, nomservicio, categoria_servicio, uso_servicio, impuesto, total"
            . $baseFrom . $whereSql . " ORDER BY {$orderBy} {$orderDir} LIMIT {$start}, {$length}";
        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            $codigo = htmlspecialchars((string) ($r['codigo_servicio'] ?? ''), ENT_QUOTES, 'UTF-8');
            $cuenta = htmlspecialchars((string) ($r['nombre_servicio'] ?? ''), ENT_QUOTES, 'UTF-8');
            $nombre = htmlspecialchars((string) ($r['nomservicio'] ?? ''), ENT_QUOTES, 'UTF-8');
            $categoria = htmlspecialchars((string) ($r['categoria_servicio'] ?? ''), ENT_QUOTES, 'UTF-8');
            $uso = htmlspecialchars((string) ($r['uso_servicio'] ?? ''), ENT_QUOTES, 'UTF-8');
            $impuesto = htmlspecialchars((string) ($r['impuesto'] ?? ''), ENT_QUOTES, 'UTF-8');
            $precio = (float) ($r['total'] ?? 0);
            $precioFmt = 'LPS. ' . number_format($precio, 2);
            $nombreAttr = htmlspecialchars((string) ($r['nomservicio'] ?? ''), ENT_QUOTES, 'UTF-8');
            $precioAttr = htmlspecialchars((string) $precio, ENT_QUOTES, 'UTF-8');

            $formHtml = '<form class="form-inline" method="post" action="new_sale.php">
    <input type="hidden" name="add_to_cart" value="1">
    <input type="hidden" name="prdt" value="' . $id . '">
    <input type="hidden" name="pdrus" value="' . $userId . '">
    <input type="hidden" name="name" value="' . $nombreAttr . '">
    <input type="hidden" name="prec" value="' . $precioAttr . '">
    <input type="hidden" name="type" value="servicio">
    <div class="form-group">
        <input type="number" name="p_qty" value="1" style="width:100px;" min="1" class="form-control" placeholder="Cantidad">
    </div>
    <button type="submit" name="add_to_cart" class="registerbtn">Agregar</button>
</form>';

            $data[] = [$codigo, $cuenta, $nombre, $categoria, $uso, $impuesto, $precioFmt, $formHtml];
        }

        ns_json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
        exit;
    }

    $columns = [
        0 => 'codpro',
        1 => 'nompro',
        2 => 'stock',
        3 => 'impuesto',
        4 => 'precio_venta',
        5 => 'idprcd',
    ];
    $orderBy = $columns[$orderIdx] ?? 'codpro';

    $baseFrom = " FROM product ";
    $whereSql = '';
    $params = [];
    if ($search !== '') {
        $whereSql = " WHERE (
            codpro LIKE :q_codigo
            OR nompro LIKE :q_nombre
            OR impuesto LIKE :q_impuesto
            OR CAST(stock AS CHAR) LIKE :q_stock
            OR CAST(precio_venta AS CHAR) LIKE :q_precio
        )";
        $q = '%' . $search . '%';
        $params = [
            'q_codigo' => $q,
            'q_nombre' => $q,
            'q_impuesto' => $q,
            'q_stock' => $q,
            'q_precio' => $q,
        ];
    }

    $stmtTotal = $connect->query("SELECT COUNT(*)" . $baseFrom);
    $total = (int) $stmtTotal->fetchColumn();

    $stmtFiltered = $connect->prepare("SELECT COUNT(*)" . $baseFrom . $whereSql);
    $stmtFiltered->execute($params);
    $filtered = (int) $stmtFiltered->fetchColumn();

    $sql = "SELECT idprcd, codpro, nompro, stock, impuesto, precio_venta"
        . $baseFrom . $whereSql . " ORDER BY {$orderBy} {$orderDir} LIMIT {$start}, {$length}";
    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $id = (int) ($r['idprcd'] ?? 0);
        $codigo = htmlspecialchars((string) ($r['codpro'] ?? ''), ENT_QUOTES, 'UTF-8');
        $nombre = htmlspecialchars((string) ($r['nompro'] ?? ''), ENT_QUOTES, 'UTF-8');
        $stock = (int) ($r['stock'] ?? 0);
        $stockHtml = (string) $stock;
        if ($stock <= 5) {
            $stockHtml .= '<div style="color: #d9534f; font-weight: bold;">Stock Agotado</div>';
        }
        $impuesto = htmlspecialchars((string) ($r['impuesto'] ?? ''), ENT_QUOTES, 'UTF-8');
        $precio = (float) ($r['precio_venta'] ?? 0);
        $precioFmt = 'LPS. ' . number_format($precio, 2);
        $nombreAttr = htmlspecialchars((string) ($r['nompro'] ?? ''), ENT_QUOTES, 'UTF-8');
        $precioAttr = htmlspecialchars((string) $precio, ENT_QUOTES, 'UTF-8');

        $formHtml = '<form class="form-inline" method="post" action="new_sale.php">
    <input type="hidden" name="add_to_cart" value="1">
    <input type="hidden" name="prdt" value="' . $id . '">
    <input type="hidden" name="pdrus" value="' . $userId . '">
    <input type="hidden" name="name" value="' . $nombreAttr . '">
    <input type="hidden" name="prec" value="' . $precioAttr . '">
    <input type="hidden" name="type" value="producto">
    <div class="form-group">
        <input type="number" name="p_qty" value="1" style="width:100px;" min="1" class="form-control" placeholder="Cantidad">
    </div>
    <button type="submit" name="add_to_cart" class="registerbtn">Agregar</button>
</form>';

        $data[] = [$codigo, $nombre, $stockHtml, $impuesto, $precioFmt, $formHtml];
    }

    ns_json([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data,
    ]);
} catch (Throwable $e) {
    ns_json([
        'draw' => (int) ($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'new_sale_datatable: ' . $e->getMessage(),
    ], 500);
}

