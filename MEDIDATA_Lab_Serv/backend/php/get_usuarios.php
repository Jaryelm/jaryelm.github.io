<?php
/**
 * Endpoint server-side para "Lista de Usuarios" (DataTables).
 * Sistema: MEDIDATA - Usuarios / IT
 *
 * Devuelve solo la pagina solicitada (LIMIT start,length) en lugar de cargar
 * todos los usuarios en el navegador. Paginacion optimizada, 10 por pagina,
 * igual que Diario General / Invitados WiFi.
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
    $draw = intval($_GET['draw'] ?? 1);
    $start = max(0, intval($_GET['start'] ?? 0));
    $lengthRaw = intval($_GET['length'] ?? 10);
    $length = ($lengthRaw <= 0) ? 500 : min($lengthRaw, 500);

    $searchValue = trim((string) ($_GET['search']['value'] ?? ''));

    // Total sin filtro.
    $recordsTotal = (int) $connect->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Filtro de busqueda.
    $whereSearch = '';
    $params = [];
    if ($searchValue !== '') {
        $whereSearch = " WHERE (username LIKE :s0 OR name LIKE :s1 OR cedula LIKE :s2 OR email LIKE :s3 OR rol LIKE :s4)";
        $like = '%' . $searchValue . '%';
        $params = [':s0' => $like, ':s1' => $like, ':s2' => $like, ':s3' => $like, ':s4' => $like];
    }

    $stmtF = $connect->prepare("SELECT COUNT(*) FROM users $whereSearch");
    $stmtF->execute($params);
    $recordsFiltered = (int) $stmtF->fetchColumn();

    // Ordenamiento: se resuelve por el NOMBRE de campo que envia DataTables
    // (columns[idx][data]) contra una whitelist, para que distintas tablas con
    // distinto orden de columnas puedan reutilizar este endpoint con seguridad.
    $allowedOrderFields = ['username', 'name', 'cedula', 'sexo', 'email', 'rol', 'created_at', 'last_activity', 'state'];
    $orderColIdx = intval($_GET['order'][0]['column'] ?? 0);
    $orderField = (string) ($_GET['columns'][$orderColIdx]['data'] ?? 'created_at');
    $orderBy = in_array($orderField, $allowedOrderFields, true) ? $orderField : 'created_at';
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $orderClause = " ORDER BY $orderBy $orderDir, id DESC";

    $sql = "SELECT id, username, name, cedula, sexo, email, rol, created_at, last_activity, state
            FROM users $whereSearch $orderClause LIMIT :start, :length";
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
            'id' => (int) $r['id'],
            'username' => $r['username'],
            'name' => $r['name'],
            'cedula' => $r['cedula'],
            'sexo' => $r['sexo'],
            'email' => $r['email'],
            'rol' => $r['rol'],
            'created_at' => $r['created_at'],
            'last_activity' => $r['last_activity'],
            'state' => $r['state'],
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_usuarios: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['draw' => intval($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Error al cargar datos']);
}
