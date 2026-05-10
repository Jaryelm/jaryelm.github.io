<?php
/**
 * Obtiene las transacciones del Diario General para DataTable
 * Sistema: MEDIDATA
 *
 * Incluye partida_total_debe / partida_total_haber (suma de TODA la partida bajo el mismo
 * filtro de búsqueda), para que el encabezado de grupo no marque DESBALANCEADA al paginar
 * server-side con solo parte de los renglones en la página actual.
 */

include_once '../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/diario_tipo_etiqueta.php';
header('Content-Type: application/json');

try {
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $searchValue = $_GET['search']['value'] ?? '';

    $fechaDesde = $_GET['fechaDesde'] ?? null;
    $fechaHasta = $_GET['fechaHasta'] ?? null;
    $numeroPartida = $_GET['numeroPartida'] ?? null;
    $cuenta = $_GET['cuenta'] ?? null;
    $tipoTransaccion = isset($_GET['tipoTransaccion']) ? trim((string) $_GET['tipoTransaccion']) : '';

    $whereInner = ' WHERE 1=1 ';
    $whereOuter = ' WHERE 1=1 ';
    $params = [];
    $types = [];

    if ($fechaDesde) {
        $whereInner .= ' AND dg.fecha_ocurrencia >= :fechaDesde_in';
        $whereOuter .= ' AND d.fecha_ocurrencia >= :fechaDesde_out';
        $params[':fechaDesde_in'] = $fechaDesde;
        $params[':fechaDesde_out'] = $fechaDesde;
        $types[':fechaDesde_in'] = PDO::PARAM_STR;
        $types[':fechaDesde_out'] = PDO::PARAM_STR;
    }

    if ($fechaHasta) {
        $whereInner .= ' AND dg.fecha_ocurrencia <= :fechaHasta_in';
        $whereOuter .= ' AND d.fecha_ocurrencia <= :fechaHasta_out';
        $params[':fechaHasta_in'] = $fechaHasta;
        $params[':fechaHasta_out'] = $fechaHasta;
        $types[':fechaHasta_in'] = PDO::PARAM_STR;
        $types[':fechaHasta_out'] = PDO::PARAM_STR;
    }

    if ($numeroPartida) {
        $whereInner .= ' AND dg.numero_partida = :numeroPartida_in';
        $whereOuter .= ' AND d.numero_partida = :numeroPartida_out';
        $params[':numeroPartida_in'] = $numeroPartida;
        $params[':numeroPartida_out'] = $numeroPartida;
        $types[':numeroPartida_in'] = PDO::PARAM_STR;
        $types[':numeroPartida_out'] = PDO::PARAM_STR;
    }

    if ($cuenta) {
        $whereInner .= ' AND dg.cuenta = :cuenta_in';
        $whereOuter .= ' AND d.cuenta = :cuenta_out';
        $params[':cuenta_in'] = $cuenta;
        $params[':cuenta_out'] = $cuenta;
        $types[':cuenta_in'] = PDO::PARAM_STR;
        $types[':cuenta_out'] = PDO::PARAM_STR;
    }

    if ($tipoTransaccion !== '') {
        $whereInner .= ' AND dg.tipo_transaccion = :tipoTransaccion_in';
        $whereOuter .= ' AND d.tipo_transaccion = :tipoTransaccion_out';
        $params[':tipoTransaccion_in'] = $tipoTransaccion;
        $params[':tipoTransaccion_out'] = $tipoTransaccion;
        $types[':tipoTransaccion_in'] = PDO::PARAM_STR;
        $types[':tipoTransaccion_out'] = PDO::PARAM_STR;
    }

    if ($searchValue !== '') {
        $whereInner .= ' AND (
            dg.numero_partida LIKE :search_in OR
            dg.cuenta LIKE :search_in OR
            dg.nombre_cuenta LIKE :search_in OR
            dg.descripcion LIKE :search_in OR
            dg.unidad_servicio LIKE :search_in OR
            dg.usuario LIKE :search_in OR
            dg.referencia LIKE :search_in
        )';
        $whereOuter .= ' AND (
            d.numero_partida LIKE :search_out OR
            d.cuenta LIKE :search_out OR
            d.nombre_cuenta LIKE :search_out OR
            d.descripcion LIKE :search_out OR
            d.unidad_servicio LIKE :search_out OR
            d.usuario LIKE :search_out OR
            d.referencia LIKE :search_out
        )';
        $like = '%' . $searchValue . '%';
        $params[':search_in'] = $like;
        $params[':search_out'] = $like;
        $types[':search_in'] = PDO::PARAM_STR;
        $types[':search_out'] = PDO::PARAM_STR;
    }

    $fromJoin = '
FROM diario_general_transacciones d
INNER JOIN (
    SELECT dg.numero_partida, SUM(dg.debe) AS sum_debe, SUM(dg.haber) AS sum_haber
    FROM diario_general_transacciones dg
    ' . $whereInner . '
    GROUP BY dg.numero_partida
) pt ON pt.numero_partida = d.numero_partida
' . $whereOuter;

    $selectFields = 'SELECT d.id, d.numero_partida, d.fecha_ocurrencia, d.fecha_registro, d.unidad_servicio,
    d.cuenta, d.nombre_cuenta, d.descripcion, d.debe, d.haber, d.neto, d.turno, d.usuario, d.tipo_transaccion, d.referencia,
    pt.sum_debe AS partida_total_debe, pt.sum_haber AS partida_total_haber';

    $countQuery = 'SELECT COUNT(d.id) ' . $fromJoin;

    $stmtTotal = $connect->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmtTotal->bindValue($key, $value, $types[$key] ?? PDO::PARAM_STR);
    }
    $stmtTotal->execute();
    $totalRecords = (int) $stmtTotal->fetchColumn();

    $orderColumn = intval($_GET['order'][0]['column'] ?? 2);
    $orderDir = strtoupper($_GET['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

    $columns = [
        0 => 'numero_partida',
        1 => 'fecha_ocurrencia',
        2 => 'fecha_registro',
        3 => 'referencia',
        4 => 'tipo_transaccion',
        5 => 'unidad_servicio',
        6 => 'cuenta',
        7 => 'nombre_cuenta',
        8 => 'descripcion',
        9 => 'debe',
        10 => 'haber',
        11 => 'neto',
        12 => 'turno',
        13 => 'usuario',
    ];

    $orderBy = $columns[$orderColumn] ?? 'numero_partida';
    if ($orderBy !== 'numero_partida') {
        $orderClause = " ORDER BY d.numero_partida DESC, d.$orderBy $orderDir, d.id DESC";
    } else {
        $orderClause = " ORDER BY d.$orderBy $orderDir, d.fecha_ocurrencia DESC, d.id DESC";
    }

    $query = $selectFields . $fromJoin . $orderClause . ' LIMIT :start, :length';

    $params[':start'] = $start;
    $params[':length'] = $length;
    $types[':start'] = PDO::PARAM_INT;
    $types[':length'] = PDO::PARAM_INT;

    $stmt = $connect->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, $types[$key] ?? PDO::PARAM_STR);
    }
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($resultados as $row) {
        $fechaOcurrencia = date('d/m/Y', strtotime($row['fecha_ocurrencia']));
        $fechaRegistro = date('d/m/Y H:i', strtotime($row['fecha_registro']));

        $debe = number_format((float) $row['debe'], 2, '.', ',');
        $haber = number_format((float) $row['haber'], 2, '.', ',');
        $neto = number_format((float) $row['neto'], 2, '.', ',');
        $ptDebe = number_format((float) $row['partida_total_debe'], 2, '.', ',');
        $ptHaber = number_format((float) $row['partida_total_haber'], 2, '.', ',');

        $data[] = [
            'id' => $row['id'],
            'numero_partida' => $row['numero_partida'],
            'fecha_ocurrencia' => $fechaOcurrencia,
            'fecha_registro' => $fechaRegistro,
            'referencia' => $row['referencia'] ?? '',
            'tipo_etiqueta' => medidata_etiqueta_tipo_transaccion($row['tipo_transaccion'] ?? null),
            'unidad_servicio' => $row['unidad_servicio'] ?? '',
            'cuenta' => $row['cuenta'],
            'nombre_cuenta' => $row['nombre_cuenta'],
            'descripcion' => $row['descripcion'],
            'debe' => $debe,
            'haber' => $haber,
            'neto' => $neto,
            'turno' => $row['turno'] ?? '',
            'usuario' => $row['usuario'] ?? '',
            'partida_total_debe' => $ptDebe,
            'partida_total_haber' => $ptHaber,
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
    ]);
} catch (Exception $e) {
    error_log('Error en get_diariogeneral_transacciones.php: ' . $e->getMessage());
    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al obtener los datos',
    ]);
}
