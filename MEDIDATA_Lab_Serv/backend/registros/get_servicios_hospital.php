<?php
/**
 * DataTables server-side: catálogo servicios_hospital (IT / almacén).
 */
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/reporte_datatable_helper.php';
include_once __DIR__ . '/session_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Asegurar columna estado
    try {
        $stmt_check = $connect->query("SHOW COLUMNS FROM servicios_hospital LIKE 'estado'");
        if (!$stmt_check || !$stmt_check->fetch()) {
            $connect->exec("ALTER TABLE servicios_hospital ADD COLUMN estado ENUM('habilitado', 'deshabilitado') DEFAULT 'habilitado'");
        }
    } catch (Throwable $e) {
        error_log('get_servicios_hospital estado column: ' . $e->getMessage());
    }

    $draw = (int) ($_GET['draw'] ?? $_POST['draw'] ?? 1);
    $start = max(0, (int) ($_GET['start'] ?? $_POST['start'] ?? 0));
    $lengthRaw = (int) ($_GET['length'] ?? $_POST['length'] ?? 10);
    $length = ($lengthRaw <= 0) ? 10 : min($lengthRaw, 100);

    $searchRaw = $_GET['search'] ?? $_POST['search'] ?? [];
    $searchValue = is_array($searchRaw)
        ? trim((string) ($searchRaw['value'] ?? ''))
        : trim((string) $searchRaw);

    $baseFrom = " FROM servicios_hospital WHERE 1=1 ";
    $params = [];
    $whereExtra = '';

    if ($searchValue !== '') {
        $whereExtra .= " AND (
            codigo_servicio LIKE :s0
            OR nombre_servicio LIKE :s1
            OR nomservicio LIKE :s2
            OR categoria_servicio LIKE :s3
            OR uso_servicio LIKE :s4
            OR impuesto LIKE :s5
            OR CAST(precio_costo AS CHAR) LIKE :s6
            OR CAST(margen_ganancia AS CHAR) LIKE :s7
            OR CAST(precio_venta AS CHAR) LIKE :s8
            OR CAST(total AS CHAR) LIKE :s9
            OR CAST(fecha_creacion AS CHAR) LIKE :s10
        )";
        medidata_reporte_dt_like_params($params, $searchValue, [
            ':s0', ':s1', ':s2', ':s3', ':s4', ':s5', ':s6', ':s7', ':s8', ':s9', ':s10',
        ]);
    }

    $fromWhere = $baseFrom . $whereExtra;

    $stmtTotal = $connect->prepare('SELECT COUNT(id) ' . $fromWhere);
    foreach ($params as $key => $value) {
        $stmtTotal->bindValue($key, $value);
    }
    $stmtTotal->execute();
    $recordsTotal = (int) $stmtTotal->fetchColumn();
    $recordsFiltered = $recordsTotal;

    $orderRaw = $_GET['order'] ?? $_POST['order'] ?? [];
    $orderColumn = (int) ($orderRaw[0]['column'] ?? 10);
    $orderDir = strtoupper((string) ($orderRaw[0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $columns = [
        0 => 'codigo_servicio',
        1 => 'nombre_servicio',
        2 => 'nomservicio',
        3 => 'categoria_servicio',
        4 => 'uso_servicio',
        5 => 'precio_costo',
        6 => 'margen_ganancia',
        7 => 'impuesto',
        8 => 'precio_venta',
        9 => 'total',
        10 => 'fecha_creacion',
        11 => 'estado',
    ];
    $orderBy = $columns[$orderColumn] ?? 'fecha_creacion';

    $query = 'SELECT id, codigo_servicio, nombre_servicio, nomservicio, categoria_servicio, uso_servicio,'
        . ' precio_costo, margen_ganancia, impuesto, precio_venta, total, fecha_creacion,'
        . " COALESCE(estado, 'habilitado') AS estado"
        . $fromWhere
        . " ORDER BY {$orderBy} {$orderDir}, id DESC"
        . medidata_reporte_dt_limit_sql($start, $length);

    $stmt = $connect->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $estado = (string) ($row['estado'] ?? 'habilitado');
        $data[] = [
            'id' => (int) $row['id'],
            'codigo_servicio' => (string) ($row['codigo_servicio'] ?? ''),
            'nombre_servicio' => (string) ($row['nombre_servicio'] ?? ''),
            'nomservicio' => (string) ($row['nomservicio'] ?? ''),
            'categoria_servicio' => (string) ($row['categoria_servicio'] ?? ''),
            'uso_servicio' => (string) ($row['uso_servicio'] ?? ''),
            'precio_costo' => (float) ($row['precio_costo'] ?? 0),
            'margen_ganancia' => (float) ($row['margen_ganancia'] ?? 0),
            'impuesto' => (string) ($row['impuesto'] ?? ''),
            'precio_venta' => (float) ($row['precio_venta'] ?? 0),
            'total' => (float) ($row['total'] ?? 0),
            'fecha_creacion' => (string) ($row['fecha_creacion'] ?? ''),
            'estado' => $estado,
            'precio_costo_fmt' => number_format((float) ($row['precio_costo'] ?? 0), 2),
            'margen_ganancia_fmt' => number_format((float) ($row['margen_ganancia'] ?? 0), 2) . '%',
            'precio_venta_fmt' => number_format((float) ($row['precio_venta'] ?? 0), 2),
            'total_fmt' => number_format((float) ($row['total'] ?? 0), 2),
            'btn_estado_texto' => ($estado === 'deshabilitado') ? 'Habilitar' : 'Deshabilitar',
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    medidata_reporte_dt_json_error((int) ($_GET['draw'] ?? $_POST['draw'] ?? 1), 'get_servicios_hospital', $e);
}
