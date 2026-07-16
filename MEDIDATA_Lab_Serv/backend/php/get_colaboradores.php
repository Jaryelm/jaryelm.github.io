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
require_once __DIR__ . '/staff_areas_lib.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    $tipoAllow = medidata_staff_area_keys();
    $tipo = trim((string) ($_GET['tipo'] ?? ''));
    $tipoFilter = in_array($tipo, $tipoAllow, true) ? " AND t.source_table = '" . $tipo . "'" : '';

    // Exclusión opcional (ej. la lista de colaboradores excluye médicos y medifarma).
    $excluirRaw = trim((string) ($_GET['excluir'] ?? ''));
    $excluirList = array_filter(array_map('trim', explode(',', $excluirRaw)));
    $excluirFilter = '';
    foreach ($excluirList as $ex) {
        if (in_array($ex, $tipoAllow, true)) {
            $excluirFilter .= " AND t.source_table <> '" . $ex . "'";
        }
    }

    // UNION de las 4 tablas de personal con columnas normalizadas.
    $union = "
        SELECT 'staff_administrative' AS source_table, idadm AS id,
               num_empleado, numide AS identificacion, nomadm AS nombres, apeadm AS apellidos, sexadm AS sexo,
               tipo_empleado, id_departamento, id_cargo, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacadm AS fecha_nacimiento, NULL AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM staff_administrative
        UNION ALL
        SELECT 'doctor' AS source_table, idodc AS id,
               num_empleado, ceddoc AS identificacion, nodoc AS nombres, apdoc AS apellidos, sexd AS sexo,
               tipo_empleado, id_departamento, id_cargo, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacd AS fecha_nacimiento, nomesp AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM doctor
        UNION ALL
        SELECT 'nurse' AS source_table, idnur AS id,
               num_empleado, numide AS identificacion, nomnur AS nombres, apenur AS apellidos, sexnur AS sexo,
               tipo_empleado, id_departamento, id_cargo, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacinur AS fecha_nacimiento, NULL AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM nurse
        UNION ALL
        SELECT 'staff_general_services' AS source_table, idsg AS id,
               num_empleado, numide AS identificacion, nomsg AS nombres, apesg AS apellidos, sexsg AS sexo,
               tipo_empleado, id_departamento, id_cargo, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacsg AS fecha_nacimiento, NULL AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM staff_general_services
        UNION ALL
        SELECT 'staff_medifarma' AS source_table, idmf AS id,
               num_empleado, numide AS identificacion, nommf AS nombres, apemf AS apellidos, sexmf AS sexo,
               tipo_empleado, id_departamento, id_cargo, id_salary_level, salario, cuenta_bac, fecha_ingreso, telefono,
               correo_personal, correo_institucional, nacmf AS fecha_nacimiento, NULL AS especialidad, id_biometrico, num_locker,
               (url_contrato IS NOT NULL) AS tiene_contrato, state
        FROM staff_medifarma

    ";

    $baseFrom = " FROM ( $union ) AS t WHERE t.state = :estado" . $tipoFilter . $excluirFilter;

    // Total sin filtro de busqueda (solo estado).
    $stmtTotal = $connect->prepare("SELECT COUNT(*) $baseFrom");
    $stmtTotal->bindValue(':estado', $estado, PDO::PARAM_STR);
    $stmtTotal->execute();
    $recordsTotal = (int) $stmtTotal->fetchColumn();

    // Filtro de busqueda sobre campos de texto y IDs.
    $whereSearch = '';
    $searchParams = [];
    if ($searchValue !== '') {
        // Mapeo de IDs (Departamentos, Cargos, Niveles Salariales)
        $depto_ids = [];
        $cargo_ids = [];
        $sl_ids = [];

        try {
            global $connect_rrhh;
            if (isset($connect_rrhh) && $connect_rrhh instanceof PDO) {
                $stmt = $connect_rrhh->query("SELECT id, name FROM departaments");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (stripos($row['name'], $searchValue) !== false) {
                        $depto_ids[] = $row['id'];
                    }
                }
                $stmt = $connect_rrhh->query("SELECT id, level_name, position_category FROM salary_levels");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (stripos($row['level_name'] . ' ' . $row['position_category'], $searchValue) !== false) {
                        $sl_ids[] = $row['id'];
                    }
                }
            }

            $stmt = $connect->query("SELECT id, name FROM positions");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (stripos($row['name'], $searchValue) !== false) {
                    $cargo_ids[] = $row['id'];
                }
            }
        } catch (Exception $e) {}

        // Tipos de empleado (mapeo inverso para source_table)
        $source_tables = [];
        if (stripos('Médico', $searchValue) !== false || stripos('Medico', $searchValue) !== false) $source_tables[] = 'doctor';
        if (stripos('Enfermería', $searchValue) !== false || stripos('Enfermeria', $searchValue) !== false) $source_tables[] = 'nurse';
        if (stripos('Administrativo', $searchValue) !== false) $source_tables[] = 'staff_administrative';
        if (stripos('Servicios Generales', $searchValue) !== false) $source_tables[] = 'staff_general_services';
        if (stripos('Medifarma', $searchValue) !== false) $source_tables[] = 'staff_medifarma';

        $whereSearch = " AND (t.nombres LIKE :s0 
                            OR t.apellidos LIKE :s1 
                            OR t.identificacion LIKE :s2 
                            OR t.num_empleado LIKE :s3
                            OR t.tipo_empleado LIKE :s4
                            OR t.sexo LIKE :s5
                            OR t.telefono LIKE :s6
                            OR t.cuenta_bac LIKE :s7
                            OR t.correo_personal LIKE :s8
                            OR t.correo_institucional LIKE :s9
                            OR t.num_locker LIKE :s10
                            OR t.especialidad LIKE :s11";

        $like = '%' . $searchValue . '%';
        $searchParams = [
            ':s0' => $like, ':s1' => $like, ':s2' => $like, ':s3' => $like,
            ':s4' => $like, ':s5' => $like, ':s6' => $like, ':s7' => $like,
            ':s8' => $like, ':s9' => $like, ':s10' => $like, ':s11' => $like
        ];

        if (count($depto_ids) > 0) {
            $whereSearch .= " OR t.id_departamento IN (" . implode(',', array_map('intval', $depto_ids)) . ")";
        }
        if (count($cargo_ids) > 0) {
            $whereSearch .= " OR t.id_cargo IN (" . implode(',', array_map('intval', $cargo_ids)) . ")";
        }
        if (count($sl_ids) > 0) {
            $whereSearch .= " OR t.id_salary_level IN (" . implode(',', array_map('intval', $sl_ids)) . ")";
        }
        if (count($source_tables) > 0) {
            $source_tables_quoted = array_map(function($st) use ($connect) { return $connect->quote($st); }, $source_tables);
            $whereSearch .= " OR t.source_table IN (" . implode(',', $source_tables_quoted) . ")";
        }

        $whereSearch .= ")";
    }

    // Conteo filtrado.
    $stmtFiltered = $connect->prepare("SELECT COUNT(*) $baseFrom $whereSearch");
    $stmtFiltered->bindValue(':estado', $estado, PDO::PARAM_STR);
    foreach ($searchParams as $k => $v) {
        $stmtFiltered->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmtFiltered->execute();
    $recordsFiltered = (int) $stmtFiltered->fetchColumn();

    // Ordenamiento (índices alineados con columnas DataTables en tabla_colaboradores.js).
    $orderable = [
        1 => 't.source_table',
        2 => 't.tipo_empleado',
        3 => 't.identificacion',
        4 => 't.nombres',
        5 => 't.apellidos',
        6 => 't.sexo',
        10 => 't.salario',
        11 => 't.cuenta_bac',
        12 => 't.fecha_ingreso',
        13 => 't.telefono',
        17 => 't.id_biometrico',
        18 => 't.num_locker',
    ];
    $orderColIdx = intval($_GET['order'][0]['column'] ?? 5);
    $orderDir = strtoupper((string) ($_GET['order'][0]['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
    $orderField = $orderable[$orderColIdx] ?? 't.apellidos';

    if ($orderField === 't.apellidos') {
        $orderClause = " ORDER BY UPPER(t.apellidos) $orderDir, UPPER(t.nombres) ASC";
    } elseif ($orderField === 't.nombres') {
        $orderClause = " ORDER BY UPPER(t.nombres) $orderDir, UPPER(t.apellidos) ASC";
    } elseif (in_array($orderField, ['t.source_table', 't.tipo_empleado', 't.identificacion', 't.sexo'], true)) {
        $orderClause = " ORDER BY UPPER($orderField) $orderDir, UPPER(t.apellidos) ASC, UPPER(t.nombres) ASC";
    } else {
        $orderClause = " ORDER BY $orderField $orderDir, UPPER(t.apellidos) ASC, UPPER(t.nombres) ASC";
    }

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
            'id_cargo' => isset($r['id_cargo']) && $r['id_cargo'] !== null ? (int) $r['id_cargo'] : null,
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
