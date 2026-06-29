<?php
/**
 * Endpoint server-side para "Invitados WiFi" (DataTables).
 * Sistema: MEDIDATA - CRM Marketing
 *
 * Los datos viven en la base `medic9ue_hospital_medicasa`, tabla `wifi_access`.
 * Devuelve solo la pagina solicitada (LIMIT start,length) para no cargar todos
 * los registros en el navegador (paginacion optimizada, 10 por pagina).
 *
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
    // Conexion dedicada a la base de invitados wifi (reusa credenciales de Conexion.php).
    $dsn = 'mysql:host=' . dbhost . ';dbname=medic9ue_hospital_medicasa;charset=utf8mb4';
    $pdo = new PDO($dsn, dbuser, dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]);

    $draw = intval($_GET['draw'] ?? 1);
    $start = max(0, intval($_GET['start'] ?? 0));
    $lengthRaw = intval($_GET['length'] ?? 10);
    $length = ($lengthRaw <= 0) ? 500 : min($lengthRaw, 500);

    $searchValue = trim((string) ($_GET['search']['value'] ?? ''));

    // Total sin filtro.
    $recordsTotal = (int) $pdo->query("SELECT COUNT(*) FROM wifi_access")->fetchColumn();

    // Filtro de busqueda.
    $whereSearch = '';
    $params = [];
    if ($searchValue !== '') {
        $whereSearch = " WHERE (nombre LIKE :s0 OR celular LIKE :s1 OR email LIKE :s2 OR servicios LIKE :s3)";
        $like = '%' . $searchValue . '%';
        $params = [':s0' => $like, ':s1' => $like, ':s2' => $like, ':s3' => $like];
    }

    $stmtF = $pdo->prepare("SELECT COUNT(*) FROM wifi_access $whereSearch");
    $stmtF->execute($params);
    $recordsFiltered = (int) $stmtF->fetchColumn();

    // Ordenamiento (whitelist por indice de columna de DataTables).
    $orderable = [
        0 => 'nombre',
        1 => 'celular',
        2 => 'email',
        3 => 'servicios',
        4 => 'userip',
        5 => 'created_at',
    ];
    $orderColIdx = intval($_GET['order'][0]['column'] ?? 5);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $orderBy = $orderable[$orderColIdx] ?? 'created_at';
    $orderClause = " ORDER BY $orderBy $orderDir, id DESC";

    $sql = "SELECT id, nombre, celular, email, servicios, userip, usermac, created_at
            FROM wifi_access $whereSearch $orderClause LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
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
            'id' => (int) $r['id'],
            'nombre' => $r['nombre'],
            'celular' => $r['celular'],
            'email' => $r['email'],
            'servicios' => $r['servicios'],
            'userip' => $r['userip'],
            'usermac' => $r['usermac'],
            'created_at' => $r['created_at'],
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_wifi_invitados: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['draw' => intval($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Error al cargar datos']);
}
