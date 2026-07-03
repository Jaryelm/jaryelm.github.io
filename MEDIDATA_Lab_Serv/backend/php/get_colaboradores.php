<?php
/**
 * Endpoint server-side para la Lista de Colaboradores (DataTables).
 * Sistema: MEDIDATA - RRHH
 *
 * Devuelve solo la pagina solicitada (LIMIT start,length) de la UNION de las
 * 4 tablas de personal, evitando cargar TODOS los registros en el navegador.
 * NO trae el BLOB del contrato; solo un indicador booleano (tiene_contrato).
 *
 * Respuesta: { draw, recordsTotal, recordsFiltered, data: [...] }
 */

require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/users_rrhh_extra_lib.php';
header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['draw' => intval($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'No autorizado']);
    exit;
}

try {
    medidata_users_rrhh_extra_ensure($connect);

    $draw = intval($_GET['draw'] ?? 1);
    $start = max(0, intval($_GET['start'] ?? 0));
    $lengthRaw = intval($_GET['length'] ?? 10);
    // DataTables envia length=-1 con "Todos"; se limita para no saturar memoria/red.
    $length = ($lengthRaw <= 0) ? 500 : min($lengthRaw, 500);

    $searchValue = trim((string) ($_GET['search']['value'] ?? ''));

    // Estado: '1' activos (colaboradores) | '0' inactivos (excolaboradores)
    $estado = (isset($_GET['estado']) && $_GET['estado'] === '0') ? '0' : '1';

    // Filtro opcional por tipo de personal (whitelist de tablas de origen).
    $tipoAllow = ['doctor', 'nurse', 'staff_administrative', 'staff_general_services'];
    $tipo = trim((string) ($_GET['tipo'] ?? ''));
    $tipoFilter = in_array($tipo, $tipoAllow, true) ? " AND t.source_table = '" . $tipo . "'" : '';

    // Exclusión opcional (ej. la lista de colaboradores excluye médicos).
    $excluir = trim((string) ($_GET['excluir'] ?? ''));
    $excluirFilter = in_array($excluir, $tipoAllow, true) ? " AND t.source_table <> '" . $excluir . "'" : '';

    /*
     * Caso "médico + usuario" (misma persona con ficha de médico Y cuenta de login):
     *  - Listas ACTIVAS (estado=1): debe verse DOS veces -> como médico en "Lista de
     *    Médicos" (tipo=doctor) y como usuario en "Lista de Colaboradores" (excluir=doctor).
     *    Por eso NO se oculta la fila de usuario aunque tenga ficha de médico enlazada.
     *  - EXCOLABORADORES (estado=0): la lista muestra todo junto, así que se oculta la
     *    fila de usuario cuando tiene ficha de médico enlazada, para que aparezca UNA
     *    sola vez (como médico). La desactivación enlazada deja ambos en estado 0.
     */
    $usuarioMedicoFilter = ($estado === '0')
        ? "AND u.id NOT IN ( SELECT id_user FROM doctor WHERE id_user IS NOT NULL )"
        : '';

    // UNION de las 4 tablas de personal con columnas normalizadas.
    $union = "
        SELECT 'staff_administrative' AS source_table, idadm AS id,
               num_empleado, numide AS identificacion, nomadm AS nombres, apeadm AS apellidos, sexadm AS sexo,
               tipo_empleado, id_departamento, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacadm AS fecha_nacimiento, NULL AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM staff_administrative
        UNION ALL
        SELECT 'doctor' AS source_table, idodc AS id,
               num_empleado, ceddoc AS identificacion, nodoc AS nombres, apdoc AS apellidos, sexd AS sexo,
               tipo_empleado, id_departamento, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacd AS fecha_nacimiento, nomesp AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM doctor
        UNION ALL
        SELECT 'nurse' AS source_table, idnur AS id,
               num_empleado, numide AS identificacion, nomnur AS nombres, apenur AS apellidos, sexnur AS sexo,
               tipo_empleado, id_departamento, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacinur AS fecha_nacimiento, NULL AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM nurse
        UNION ALL
        SELECT 'staff_general_services' AS source_table, idsg AS id,
               num_empleado, numide AS identificacion, nomsg AS nombres, apesg AS apellidos, sexsg AS sexo,
               tipo_empleado, id_departamento, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacsg AS fecha_nacimiento, NULL AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM staff_general_services
        UNION ALL
        SELECT 'users' AS source_table, u.id AS id,
               ue.num_empleado, u.cedula AS identificacion, u.name AS nombres, '' AS apellidos, u.sexo AS sexo,
               ue.tipo_empleado, ue.id_departamento, ue.id_salary_level, ue.salario, ue.cuenta_bac, ue.fecha_ingreso, ue.telefono,
               ue.correo_personal, u.email AS correo_institucional, NULL AS fecha_nacimiento, u.rol AS especialidad,
               u.uid_biometrico AS id_biometrico, ue.num_locker,
               (ue.url_contrato IS NOT NULL) AS tiene_contrato, u.state
        FROM users u
        LEFT JOIN users_rrhh_extra ue ON ue.id_user = u.id
        WHERE u.username NOT IN ('dev')
          /* Ocultar la fila \"Usuario\" cuando ya existe como ficha de personal NO medica
             (administrativo/enfermeria/servicios generales): evita verlo dos veces en la
             MISMA lista (Colaboradores). El caso medico se maneja aparte mas abajo. */
          AND u.id NOT IN (
            SELECT id_user FROM staff_administrative WHERE id_user IS NOT NULL
            UNION SELECT id_user FROM nurse WHERE id_user IS NOT NULL
            UNION SELECT id_user FROM staff_general_services WHERE id_user IS NOT NULL
        )
          $usuarioMedicoFilter
    ";

    $baseFrom = " FROM ( $union ) AS t WHERE t.state = :estado" . $tipoFilter . $excluirFilter;

    // Total sin filtro de busqueda (solo estado).
    $stmtTotal = $connect->prepare("SELECT COUNT(*) $baseFrom");
    $stmtTotal->bindValue(':estado', $estado, PDO::PARAM_STR);
    $stmtTotal->execute();
    $recordsTotal = (int) $stmtTotal->fetchColumn();

    // Filtro de busqueda sobre campos de texto principales.
    $whereSearch = '';
    $searchParams = [];
    if ($searchValue !== '') {
        $whereSearch = " AND (t.nombres LIKE :s0 OR t.apellidos LIKE :s1 OR t.identificacion LIKE :s2 OR t.num_empleado LIKE :s3)";
        $like = '%' . $searchValue . '%';
        $searchParams = [':s0' => $like, ':s1' => $like, ':s2' => $like, ':s3' => $like];
    }

    // Conteo filtrado.
    $stmtFiltered = $connect->prepare("SELECT COUNT(*) $baseFrom $whereSearch");
    $stmtFiltered->bindValue(':estado', $estado, PDO::PARAM_STR);
    foreach ($searchParams as $k => $v) {
        $stmtFiltered->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmtFiltered->execute();
    $recordsFiltered = (int) $stmtFiltered->fetchColumn();

    // Ordenamiento (whitelist por indice de columna de DataTables).
    $orderable = [
        0 => 't.source_table',
        1 => 't.tipo_empleado',
        2 => 't.num_empleado',
        3 => 't.identificacion',
        4 => 't.nombres',
        5 => 't.apellidos',
        6 => 't.sexo',
        9 => 't.salario',
        10 => 't.cuenta_bac',
        11 => 't.fecha_ingreso',
        12 => 't.telefono',
        14 => 't.id_biometrico',
        15 => 't.num_locker',
    ];
    $orderColIdx = intval($_GET['order'][0]['column'] ?? 4);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
    $orderBy = $orderable[$orderColIdx] ?? 't.nombres';
    $orderClause = " ORDER BY $orderBy $orderDir, t.nombres ASC";

    $dataQuery = "SELECT * $baseFrom $whereSearch $orderClause LIMIT :start, :length";
    $stmt = $connect->prepare($dataQuery);
    $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
    foreach ($searchParams as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $data[] = [
            'source_table' => $r['source_table'],
            'id' => (int) $r['id'],
            'num_empleado' => $r['num_empleado'],
            'identificacion' => $r['identificacion'],
            'nombres' => $r['nombres'],
            'apellidos' => $r['apellidos'],
            'sexo' => $r['sexo'],
            'tipo_empleado' => $r['tipo_empleado'],
            'id_departamento' => $r['id_departamento'] !== null ? (int) $r['id_departamento'] : null,
            'id_salary_level' => $r['id_salary_level'] !== null ? (int) $r['id_salary_level'] : null,
            'salario' => $r['salario'],
            'cuenta_bac' => $r['cuenta_bac'],
            'fecha_ingreso' => $r['fecha_ingreso'],
            'telefono' => $r['telefono'],
            'correo_personal' => $r['correo_personal'],
            'correo_institucional' => $r['correo_institucional'],
            'fecha_nacimiento' => $r['fecha_nacimiento'],
            'especialidad' => $r['especialidad'],
            'id_biometrico' => $r['id_biometrico'],
            'num_locker' => $r['num_locker'],
            'tiene_contrato' => (int) $r['tiene_contrato'],
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
    error_log('Error en get_colaboradores.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al obtener los datos',
    ], JSON_UNESCAPED_UNICODE);
}
