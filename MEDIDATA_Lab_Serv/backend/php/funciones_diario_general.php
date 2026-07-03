<?php
/**
 * Funciones auxiliares para el Diario General
 * Sistema: MEDIDATA
 */

// Incluir Conexion.php solo si no está ya incluido
if (!isset($connect)) {
    // Usar ruta absoluta basada en la ubicación del archivo
    $conexionPath = __DIR__ . '/../bd/Conexion.php';
    if (file_exists($conexionPath)) {
        require_once $conexionPath;
    } else {
        // Intentar ruta alternativa desde frontend
        $conexionPathAlt = __DIR__ . '/../../backend/bd/Conexion.php';
        if (file_exists($conexionPathAlt)) {
            require_once $conexionPathAlt;
        }
    }
}

/**
 * Normaliza fechas de filtro (input type=date) a inicio/fin de día.
 */
function medidata_diario_normalizar_fecha_desde(?string $fecha): ?string
{
    $fecha = ($fecha !== null && $fecha !== '') ? trim($fecha) : null;
    if ($fecha === null) {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha . ' 00:00:00';
    }

    return $fecha;
}

function medidata_diario_normalizar_fecha_hasta(?string $fecha): ?string
{
    $fecha = ($fecha !== null && $fecha !== '') ? trim($fecha) : null;
    if ($fecha === null) {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha . ' 23:59:59';
    }

    return $fecha;
}

/**
 * Fecha Y-m-d para filtro por fecha de ocurrencia (columna DATE).
 */
function medidata_diario_fecha_filtro_solo_dia(?string $fecha): ?string
{
    if ($fecha === null || trim($fecha) === '') {
        return null;
    }
    $fecha = trim($fecha);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $fecha, $m)) {
        return $m[1];
    }

    return null;
}

/**
 * SQL Desde/Hasta para Diario General: rango por fecha de ocurrencia.
 *
 * @param string $suffix Sufijo único por placeholder PDO (p. ej. _in, _out)
 * @return array{sql: string, params: array<string, string>, types: array<string, int>}
 */
function medidata_diario_sql_filtro_rango_fechas(
    ?string $fechaDesde,
    ?string $fechaHasta,
    string $alias = 'dg',
    string $suffix = ''
): array {
    $fechaDesde = medidata_diario_fecha_filtro_solo_dia($fechaDesde);
    $fechaHasta = medidata_diario_fecha_filtro_solo_dia($fechaHasta);

    if ($fechaDesde === null && $fechaHasta === null) {
        return ['sql' => '', 'params' => [], 'types' => []];
    }

    $fr = ($alias !== '' ? $alias . '.' : '') . 'fecha_ocurrencia';
    $params = [];
    $types = [];

    if ($fechaDesde !== null && $fechaHasta !== null) {
        $pDesde = ':fechaDesde' . $suffix;
        $pHasta = ':fechaHasta' . $suffix;
        $sql = " AND ($fr >= $pDesde AND $fr <= $pHasta)";
        $params[$pDesde] = $fechaDesde;
        $params[$pHasta] = $fechaHasta;
    } elseif ($fechaDesde !== null) {
        $pDesde = ':fechaDesde' . $suffix;
        $sql = " AND ($fr >= $pDesde)";
        $params[$pDesde] = $fechaDesde;
    } else {
        $pHasta = ':fechaHasta' . $suffix;
        $sql = " AND ($fr <= $pHasta)";
        $params[$pHasta] = $fechaHasta;
    }

    foreach (array_keys($params) as $key) {
        $types[$key] = PDO::PARAM_STR;
    }

    return ['sql' => $sql, 'params' => $params, 'types' => $types];
}

/**
 * Normaliza parámetros de filtro (GET / export / DataTables).
 *
 * @param array<string, mixed> $request
 * @return array{fechaDesde: ?string, fechaHasta: ?string, numeroPartida: ?string, cuenta: ?string, tipoTransaccion: string, searchValue: string}
 */
function medidata_diario_normalizar_filtros_request(array $request): array
{
    $fechaDesde = isset($request['fechaDesde']) ? trim((string) $request['fechaDesde']) : '';
    $fechaHasta = isset($request['fechaHasta']) ? trim((string) $request['fechaHasta']) : '';
    if ($fechaDesde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
        $fechaDesde = '';
    }
    if ($fechaHasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
        $fechaHasta = '';
    }

    $searchValue = isset($request['searchValue']) ? trim((string) $request['searchValue']) : '';
    if ($searchValue === '' && isset($request['search'])) {
        if (is_array($request['search'])) {
            $searchValue = trim((string) ($request['search']['value'] ?? ''));
        } else {
            $searchValue = trim((string) $request['search']);
        }
    }

    $numeroPartida = isset($request['numeroPartida']) ? trim((string) $request['numeroPartida']) : '';
    $cuenta = isset($request['cuenta']) ? trim((string) $request['cuenta']) : '';

    return [
        'fechaDesde' => $fechaDesde !== '' ? $fechaDesde : null,
        'fechaHasta' => $fechaHasta !== '' ? $fechaHasta : null,
        'numeroPartida' => $numeroPartida !== '' ? $numeroPartida : null,
        'cuenta' => $cuenta !== '' ? $cuenta : null,
        'tipoTransaccion' => isset($request['tipoTransaccion']) ? trim((string) $request['tipoTransaccion']) : '',
        'searchValue' => $searchValue,
    ];
}

/**
 * FROM + JOIN del listado Diario General (misma lógica que DataTables server-side).
 *
 * @param array<string, mixed> $filtros
 * @return array{fromJoin: string, params: array<string, mixed>, types: array<string, int>, selectFields: string}
 */
function medidata_diario_build_listado_from_join(array $filtros): array
{
    $fechaDesde = $filtros['fechaDesde'] ?? null;
    $fechaHasta = $filtros['fechaHasta'] ?? null;
    $numeroPartida = $filtros['numeroPartida'] ?? null;
    $cuenta = $filtros['cuenta'] ?? null;
    $tipoTransaccion = isset($filtros['tipoTransaccion']) ? trim((string) $filtros['tipoTransaccion']) : '';
    $searchValue = isset($filtros['searchValue']) ? trim((string) $filtros['searchValue']) : '';

    $whereInner = ' WHERE 1=1 ';
    $whereOuter = ' WHERE 1=1 ';
    $params = [];
    $types = [];

    if ($fechaDesde || $fechaHasta) {
        $filtroInner = medidata_diario_sql_filtro_rango_fechas($fechaDesde, $fechaHasta, 'dg', '_in');
        $whereInner .= $filtroInner['sql'];
        $params = array_merge($params, $filtroInner['params']);
        $types = array_merge($types, $filtroInner['types']);

        $filtroOuter = medidata_diario_sql_filtro_rango_fechas($fechaDesde, $fechaHasta, 'd', '_out');
        $whereOuter .= $filtroOuter['sql'];
        $params = array_merge($params, $filtroOuter['params']);
        $types = array_merge($types, $filtroOuter['types']);
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
        $concatMatch = "CONCAT_WS(' ',
            IFNULL(numero_partida, ''),
            IFNULL(cuenta, ''),
            IFNULL(nombre_cuenta, ''),
            IFNULL(descripcion, ''),
            IFNULL(unidad_servicio, ''),
            IFNULL(usuario, ''),
            IFNULL(referencia, '')
        ) LIKE :search_partida_inner";
        $concatMatchOuter = str_replace(':search_partida_inner', ':search_partida_outer', $concatMatch);
        $partidasInner = "(
            SELECT DISTINCT numero_partida
            FROM diario_general_transacciones
            WHERE {$concatMatch}
        )";
        $partidasOuter = "(
            SELECT DISTINCT numero_partida
            FROM diario_general_transacciones
            WHERE {$concatMatchOuter}
        )";
        $whereInner .= ' AND dg.numero_partida IN ' . $partidasInner;
        $whereOuter .= ' AND d.numero_partida IN ' . $partidasOuter;
        $like = '%' . $searchValue . '%';
        $params[':search_partida_inner'] = $like;
        $params[':search_partida_outer'] = $like;
        $types[':search_partida_inner'] = PDO::PARAM_STR;
        $types[':search_partida_outer'] = PDO::PARAM_STR;
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

    return [
        'fromJoin' => $fromJoin,
        'params' => $params,
        'types' => $types,
        'selectFields' => $selectFields,
    ];
}

/**
 * WHERE compartido entre listado (DataTables) y exportación del Diario General.
 *
 * @param array<string, mixed> $filtros
 * @return array{sql: string, params: array<string, mixed>, types: array<string, int>}
 */
function medidata_diario_build_filtros_where(array $filtros, string $alias = '', string $suffix = ''): array
{
    $fechaDesde = $filtros['fechaDesde'] ?? null;
    $fechaHasta = $filtros['fechaHasta'] ?? null;
    $numeroPartida = $filtros['numeroPartida'] ?? null;
    $cuenta = $filtros['cuenta'] ?? null;
    $tipoTransaccion = isset($filtros['tipoTransaccion']) ? trim((string) $filtros['tipoTransaccion']) : '';
    $searchValue = isset($filtros['searchValue']) ? trim((string) $filtros['searchValue']) : '';

    $sql = '';
    $params = [];
    $types = [];
    $col = static function (string $field) use ($alias): string {
        return ($alias !== '' ? $alias . '.' : '') . $field;
    };

    if ($fechaDesde || $fechaHasta) {
        $filtroFechas = medidata_diario_sql_filtro_rango_fechas($fechaDesde, $fechaHasta, $alias, $suffix);
        $sql .= $filtroFechas['sql'];
        $params = array_merge($params, $filtroFechas['params']);
        $types = array_merge($types, $filtroFechas['types']);
    }

    if ($numeroPartida) {
        $pPartida = ':numeroPartida' . $suffix;
        $sql .= ' AND ' . $col('numero_partida') . " = $pPartida";
        $params[$pPartida] = $numeroPartida;
        $types[$pPartida] = PDO::PARAM_STR;
    }

    if ($cuenta) {
        $pCuenta = ':cuenta' . $suffix;
        $sql .= ' AND ' . $col('cuenta') . " = $pCuenta";
        $params[$pCuenta] = $cuenta;
        $types[$pCuenta] = PDO::PARAM_STR;
    }

    if ($tipoTransaccion !== '') {
        $pTipo = ':tipoTransaccion' . $suffix;
        $sql .= ' AND ' . $col('tipo_transaccion') . " = $pTipo";
        $params[$pTipo] = $tipoTransaccion;
        $types[$pTipo] = PDO::PARAM_STR;
    }

    if ($searchValue !== '') {
        $pSearch = ':search_partida' . $suffix;
        $concatMatch = "CONCAT_WS(' ',
            IFNULL(numero_partida, ''),
            IFNULL(cuenta, ''),
            IFNULL(nombre_cuenta, ''),
            IFNULL(descripcion, ''),
            IFNULL(unidad_servicio, ''),
            IFNULL(usuario, ''),
            IFNULL(referencia, '')
        ) LIKE $pSearch";
        $partidasSub = "(
            SELECT DISTINCT numero_partida
            FROM diario_general_transacciones
            WHERE {$concatMatch}
        )";
        $sql .= ' AND ' . $col('numero_partida') . " IN $partidasSub";
        $params[$pSearch] = '%' . $searchValue . '%';
        $types[$pSearch] = PDO::PARAM_STR;
    }

    return ['sql' => $sql, 'params' => $params, 'types' => $types];
}

/**
 * Filas del diario para exportación (mismos filtros que la grilla, sin paginación).
 *
 * @param array<string, mixed> $filtros
 * @return list<array<string, mixed>>
 */
function medidata_diario_fetch_filas_export(PDO $connect, array $filtros): array
{
    $filtros = medidata_diario_normalizar_filtros_request($filtros);
    $parts = medidata_diario_build_listado_from_join($filtros);
    $query = 'SELECT d.id, d.numero_partida, d.fecha_ocurrencia, d.fecha_registro, d.unidad_servicio,
                     d.cuenta, d.nombre_cuenta, d.descripcion, d.debe, d.haber, d.neto, d.turno, d.usuario,
                     d.referencia, d.tipo_transaccion
              ' . $parts['fromJoin'] . '
              ORDER BY d.numero_partida DESC, d.fecha_ocurrencia DESC, d.id DESC';

    $stmt = $connect->prepare($query);
    foreach ($parts['params'] as $key => $value) {
        $stmt->bindValue($key, $value, $parts['types'][$key] ?? PDO::PARAM_STR);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Genera o obtiene el número de partida para una fecha específica
 * Formato: YYYYMMDDNNN (año + mes + día + secuencial del día)
 * 
 * @param string $fecha Fecha en formato Y-m-d
 * @return string Número de partida generado
 */
function generarNumeroPartida($fecha) {
    global $connect;
    
    try {
        // Obtener el último número de partida del día usando SELECT FOR UPDATE para evitar condiciones de carrera
        $fechaFormato = date('Ymd', strtotime($fecha));
        
        // Si ya hay una transacción activa, usar SELECT FOR UPDATE directamente
        // Si no, hacer una consulta simple (el SELECT FOR UPDATE requiere transacción)
        $stmt = $connect->prepare("
            SELECT numero_partida 
            FROM diario_general_transacciones 
            WHERE numero_partida LIKE :patron 
            ORDER BY numero_partida DESC 
            LIMIT 1" . ($connect->inTransaction() ? " FOR UPDATE" : "")
        );
        
        $patron = $fechaFormato . '%';
        $stmt->bindParam(':patron', $patron);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            // Extraer el secuencial del último número
            $ultimoNumero = $resultado['numero_partida'];
            $secuencial = intval(substr($ultimoNumero, 8)); // Los últimos 3 dígitos
            $nuevoSecuencial = $secuencial + 1;
        } else {
            // Primera partida del día
            $nuevoSecuencial = 1;
        }
        
        // Formatear el secuencial con ceros a la izquierda (3 dígitos)
        $secuencialFormateado = str_pad($nuevoSecuencial, 3, '0', STR_PAD_LEFT);
        
        // Generar el número de partida completo
        $numeroPartida = $fechaFormato . $secuencialFormateado;
        
        return $numeroPartida;
        
    } catch (PDOException $e) {
        error_log("Error al generar número de partida: " . $e->getMessage());
        throw new Exception("Error al generar número de partida");
    }
}

/**
 * Registra una transacción contable individual
 * 
 * @param array $datos Array con los datos de la transacción:
 *   - numero_partida: Número de partida
 *   - fecha_ocurrencia: Fecha de ocurrencia (Y-m-d)
 *   - fecha_registro: Fecha de registro (Y-m-d H:i:s)
 *   - unidad_servicio: Unidad de servicio/sucursal
 *   - cuenta: Código de cuenta
 *   - nombre_cuenta: Nombre de la cuenta
 *   - descripcion: Descripción de la transacción
 *   - debe: Monto débito (decimal)
 *   - haber: Monto crédito (decimal)
 *   - turno: Turno (opcional)
 *   - usuario: Usuario que registra
 *   - tipo_transaccion: Tipo de transacción (opcional)
 *   - referencia: Referencia (opcional)
 * @return int ID de la transacción insertada
 */
function registrarTransaccionContable($datos) {
    global $connect;
    
    try {
        // Calcular el neto (debe - haber)
        $debe = floatval($datos['debe'] ?? 0);
        $haber = floatval($datos['haber'] ?? 0);
        $neto = $debe - $haber;
        
        $stmt = $connect->prepare("
            INSERT INTO diario_general_transacciones (
                numero_partida, fecha_ocurrencia, fecha_registro, unidad_servicio,
                cuenta, nombre_cuenta, descripcion, debe, haber, neto,
                turno, usuario, tipo_transaccion, referencia
            ) VALUES (
                :numero_partida, :fecha_ocurrencia, :fecha_registro, :unidad_servicio,
                :cuenta, :nombre_cuenta, :descripcion, :debe, :haber, :neto,
                :turno, :usuario, :tipo_transaccion, :referencia
            )
        ");
        
        $stmt->execute([
            ':numero_partida' => $datos['numero_partida'],
            ':fecha_ocurrencia' => $datos['fecha_ocurrencia'],
            ':fecha_registro' => $datos['fecha_registro'],
            ':unidad_servicio' => $datos['unidad_servicio'] ?? null,
            ':cuenta' => $datos['cuenta'],
            ':nombre_cuenta' => mb_convert_encoding($datos['nombre_cuenta'], 'UTF-8', 'UTF-8'),
            ':descripcion' => mb_convert_encoding($datos['descripcion'], 'UTF-8', 'UTF-8'),
            ':debe' => $debe,
            ':haber' => $haber,
            ':neto' => $neto,
            ':turno' => $datos['turno'] ?? null,
            ':usuario' => $datos['usuario'] ?? null,
            ':tipo_transaccion' => $datos['tipo_transaccion'] ?? null,
            ':referencia' => $datos['referencia'] ?? null
        ]);
        
        return $connect->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Error al registrar transacción contable: " . $e->getMessage());
        throw new Exception("Error al registrar transacción contable: " . $e->getMessage());
    }
}

/**
 * Verifica si existe una partida duplicada (mismo contenido, mismo usuario, en ventana de tiempo)
 * Previene doble envío por doble clic o latencia del sistema
 * 
 * @param string $referencia Referencia de la partida
 * @param string $fechaOcurrencia Fecha de ocurrencia (Y-m-d)
 * @param float $totalDebe Total débito
 * @param float $totalHaber Total crédito
 * @param string $usuario Usuario que registra
 * @param string $tipoTransaccion Tipo de transacción (ej: PARTIDA_MANUAL)
 * @param int $ventanaSegundos Ventana de tiempo en segundos (default 90)
 * @return bool true si existe duplicado
 */
function existePartidaDuplicada($referencia, $fechaOcurrencia, $totalDebe, $totalHaber, $usuario, $tipoTransaccion = 'PARTIDA_MANUAL', $ventanaSegundos = 90) {
    global $connect;
    try {
        $totalDebeR = round($totalDebe, 2);
        $totalHaberR = round($totalHaber, 2);
        $stmt = $connect->prepare("
            SELECT numero_partida FROM diario_general_transacciones
            WHERE tipo_transaccion = :tipo
              AND referencia = :ref
              AND fecha_ocurrencia = :fecha
              AND usuario = :usuario
              AND fecha_registro >= DATE_SUB(NOW(), INTERVAL :ventana SECOND)
            GROUP BY numero_partida
            HAVING ABS(SUM(debe) - :totalDebe) < 0.005 AND ABS(SUM(haber) - :totalHaber) < 0.005
            LIMIT 1
        ");
        $stmt->execute([
            ':tipo' => $tipoTransaccion,
            ':ref' => $referencia,
            ':fecha' => $fechaOcurrencia,
            ':usuario' => $usuario,
            ':ventana' => $ventanaSegundos,
            ':totalDebe' => $totalDebeR,
            ':totalHaber' => $totalHaberR
        ]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("existePartidaDuplicada: " . $e->getMessage());
        return false; // En caso de error, permitir continuar
    }
}

/**
 * Registra múltiples transacciones con el mismo número de partida
 * Útil para registrar partidas completas (ej: recepción de compra, cierre de venta)
 * 
 * @param array $transacciones Array de arrays, cada uno con los datos de una transacción
 * @param string $fecha Fecha de ocurrencia (Y-m-d)
 * @param string $fechaRegistro Fecha de registro (Y-m-d H:i:s)
 * @return string Número de partida generado
 */
function registrarPartidaCompleta($transacciones, $fecha, $fechaRegistro = null) {
    global $connect;
    
    // Verificar si ya hay una transacción activa
    $transaccionIniciada = false;
    if (!$connect->inTransaction()) {
        $connect->beginTransaction();
        $transaccionIniciada = true;
    }
    
    try {
        // Si no se proporciona fecha de registro, usar la actual
        if ($fechaRegistro === null) {
            date_default_timezone_set('America/Tegucigalpa');
            $fechaRegistro = date('Y-m-d H:i:s');
        }
        
        // VALIDACIÓN DE BALANCE: Verificar que DEBE = HABER antes de registrar
        $totalDebe = 0;
        $totalHaber = 0;
        
        foreach ($transacciones as $transaccion) {
            $debe = floatval($transaccion['debe'] ?? 0);
            $haber = floatval($transaccion['haber'] ?? 0);
            $totalDebe += $debe;
            $totalHaber += $haber;
        }
        
        // Calcular diferencia (usando precisión decimal para evitar errores de punto flotante)
        $diferencia = abs($totalDebe - $totalHaber);
        $tolerancia = 0.01; // Tolerancia de 1 centavo para errores de redondeo
        
        if ($diferencia > $tolerancia) {
            // Revertir transacción si la iniciamos nosotros
            if ($transaccionIniciada && $connect->inTransaction()) {
                $connect->rollBack();
            }
            $errorMsg = "ERROR DE BALANCE: La partida no está balanceada. DEBE: " . number_format($totalDebe, 2) . " | HABER: " . number_format($totalHaber, 2) . " | Diferencia: " . number_format($diferencia, 2);
            error_log($errorMsg);
            throw new Exception($errorMsg);
        }
        
        // Generar el número de partida
        $numeroPartida = generarNumeroPartida($fecha);
        
        // Registrar cada transacción
        foreach ($transacciones as $transaccion) {
            $datos = array_merge($transaccion, [
                'numero_partida' => $numeroPartida,
                'fecha_ocurrencia' => $fecha,
                'fecha_registro' => $fechaRegistro
            ]);
            
            registrarTransaccionContable($datos);
        }

        // No usar error_log() para operaciones correctas: va al error_log del servidor
        // y confunde con fallos reales. Los desbalances ya se registran arriba y en catch.

        // Confirmar transacción solo si la iniciamos nosotros
        if ($transaccionIniciada) {
            $connect->commit();
        }
        
        return $numeroPartida;
        
    } catch (Exception $e) {
        // Revertir en caso de error solo si iniciamos la transacción
        if ($transaccionIniciada && $connect->inTransaction()) {
            $connect->rollBack();
        }
        error_log("Error al registrar partida completa: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Extrae solo el código numérico de catálogo cuando viene mezclado con texto
 * (ej. detalle_compras.cat_cuenta = "ACTIVOS 110400103 INVENTARIO…").
 * Alínea el diario con ventas/partidas manuales (columna Cuenta = solo número).
 */
function medidata_normalizar_codigo_cuenta_desde_cat(?string $raw): string
{
    $s = trim((string) $raw);
    if ($s === '') {
        return '';
    }
    if (preg_match('/^\d{6,12}$/', $s)) {
        return $s;
    }
    if (preg_match('/(\d{9})/', $s, $m)) {
        return $m[1];
    }
    if (preg_match('/(\d{6,8})/', $s, $m)) {
        return $m[1];
    }

    return $s;
}

/**
 * Carga en bloque los nombres del catálogo (una consulta por chunk) para listados paginados.
 *
 * @param string[] $codigosNorm Códigos de cuenta ya normalizados (6–12 dígitos)
 * @return array<string,string>
 */
function medidata_prefetch_nombres_cuentas_catalogo(PDO $connect, array $codigosNorm): array
{
    $codigosNorm = array_values(array_unique(array_filter(array_map('strval', $codigosNorm), static function ($v) {
        return preg_match('/^\d{6,12}$/', $v);
    })));
    if ($codigosNorm === []) {
        return [];
    }
    $out = [];
    foreach (array_chunk($codigosNorm, 500) as $chunk) {
        $ph = implode(',', array_fill(0, count($chunk), '?'));
        $st = $connect->prepare("SELECT cuenta, nombre FROM cuentas_catalogo WHERE cuenta IN ($ph)");
        $st->execute($chunk);
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($r['cuenta'])) {
                $out[(string) $r['cuenta']] = (string) ($r['nombre'] ?? '');
            }
        }
    }

    return $out;
}

/**
 * Para listados/exportación del diario: Cuenta solo código; Nombre desde catálogo si el guardado traía texto compuesto.
 *
 * @param array<string,string> $nombrePorCodigoCatalogo Resultado opcional de medidata_prefetch_nombres_cuentas_catalogo()
 *
 * @return array{cuenta: string, nombre_cuenta: string}
 */
function medidata_diario_columnas_cuenta(?string $cuentaDb, ?string $nombreDb, array $nombrePorCodigoCatalogo = []): array
{
    $c = trim((string) ($cuentaDb ?? ''));
    $n = (string) ($nombreDb ?? '');
    if ($c === '') {
        return ['cuenta' => '', 'nombre_cuenta' => $n];
    }
    $norm = medidata_normalizar_codigo_cuenta_desde_cat($c);
    if (($norm !== $c || !preg_match('/^\d{6,12}$/', $c)) && preg_match('/^\d{6,12}$/', $norm)) {
        if ($nombrePorCodigoCatalogo !== [] && isset($nombrePorCodigoCatalogo[$norm])) {
            return ['cuenta' => $norm, 'nombre_cuenta' => $nombrePorCodigoCatalogo[$norm]];
        }

        return ['cuenta' => $norm, 'nombre_cuenta' => obtenerNombreCuenta($norm)];
    }

    return ['cuenta' => $c, 'nombre_cuenta' => $n];
}

/**
 * Obtiene el nombre de la cuenta desde el catálogo de cuentas
 * 
 * @param string $codigoCuenta Código de la cuenta
 * @return string Nombre de la cuenta o el código si no se encuentra
 */
function obtenerNombreCuenta($codigoCuenta) {
    global $connect;
    
    try {
        $stmt = $connect->prepare("
            SELECT nombre 
            FROM cuentas_catalogo 
            WHERE cuenta = :cuenta 
            LIMIT 1
        ");
        
        $stmt->bindParam(':cuenta', $codigoCuenta);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            return $resultado['nombre'];
        }
        
        return $codigoCuenta; // Retornar el código si no se encuentra
        
    } catch (PDOException $e) {
        error_log("Error al obtener nombre de cuenta: " . $e->getMessage());
        return $codigoCuenta;
    }
}

/**
 * Obtiene la cuenta contable de tarjeta según el banco emisor
 * Ahora el banco_emisor contiene directamente el código de cuenta (110100401 o 110100402)
 * @param string $bancoEmisor Código de cuenta contable del banco emisor
 * @return string Código de cuenta contable (110100401 para BAC, 110100402 para Banpais)
 */
function obtenerCuentaTarjeta($bancoEmisor) {
    // Log para depuración
    error_log("DEBUG obtenerCuentaTarjeta: Código de cuenta recibido: '" . ($bancoEmisor ?? 'NULL') . "'");
    
    // Validar que sea un código de cuenta válido (110100401 o 110100402)
    if (empty($bancoEmisor)) {
        // Por defecto, usar Banpais si no se especifica
        error_log("DEBUG obtenerCuentaTarjeta: Código vacío, usando Banpais por defecto (110100402)");
        return '110100402';
    }
    
    // Si el valor recibido es un código de cuenta válido (empieza con 11010040), retornarlo directamente
    if (preg_match('/^11010040\d$/', $bancoEmisor)) {
        error_log("DEBUG obtenerCuentaTarjeta: Código de cuenta válido, retornando: $bancoEmisor");
        return $bancoEmisor;
    }
    
    // Si no es un código válido, intentar mapear por nombre (compatibilidad con datos antiguos)
    $bancoNormalizado = strtoupper(trim($bancoEmisor));
    if (strpos($bancoNormalizado, 'BAC') !== false) {
        error_log("DEBUG obtenerCuentaTarjeta: Detectado BAC en nombre antiguo, retornando 110100401");
        return '110100401';
    } elseif (strpos($bancoNormalizado, 'BANPAIS') !== false || strpos($bancoNormalizado, 'BANPAÍS') !== false) {
        error_log("DEBUG obtenerCuentaTarjeta: Detectado BANPAIS en nombre antiguo, retornando 110100402");
        return '110100402';
    }
    
    // Por defecto, usar Banpais
    error_log("DEBUG obtenerCuentaTarjeta: No se pudo determinar, usando Banpais por defecto (110100402)");
    return '110100402';
}

/**
 * Obtiene la cuenta contable de ingresos para productos según la línea/categoría
 * Los productos también generan ingresos además de inventario
 * 
 * @param string $linea Línea o categoría del producto
 * @return string Código de la cuenta de ingresos
 */
function obtenerCuentaIngresosProducto($linea) {
    global $connect;
    
    try {
        if (empty($linea)) {
            // Por defecto, usar cuenta de ingresos genérica
            return '410100103'; // Ingresos Por Ventas Ordinarias Emergencias (por defecto)
        }
        
        $lineaUpper = strtoupper(trim($linea));
        
        // Mapeo de líneas a cuentas de ingresos
        // Los productos generan ingresos según su categoría/uso
        if (stripos($lineaUpper, 'REACTIVO') !== false || 
            stripos($lineaUpper, 'LABORATORIO') !== false) {
            // Reactivos de laboratorio - usar cuenta de ingresos de laboratorio si existe
            return '410100103'; // Emergencias (por defecto, ajustar según necesidad)
        } elseif (stripos($lineaUpper, 'MEDICAMENTO') !== false) {
            // Medicamentos - usar cuenta de ingresos de emergencias o farmacia
            return '410100103'; // Emergencias (por defecto)
        } elseif (stripos($lineaUpper, 'INSUMO') !== false || 
                   stripos($lineaUpper, 'DESCARTABLE') !== false ||
                   stripos($lineaUpper, 'MATERIAL') !== false) {
            // Insumos descartables/material - usar cuenta de ingresos de emergencias
            return '410100103'; // Ingresos Por Ventas Ordinarias Emergencias
        } else {
            // Por defecto
            return '410100103'; // Ingresos Por Ventas Ordinarias Emergencias
        }
        
    } catch (PDOException $e) {
        error_log("Error al obtener cuenta de ingresos de producto: " . $e->getMessage());
        return '410100103'; // Por defecto
    }
}

/**
 * Obtiene la cuenta contable de inventario según la línea/categoría del producto
 * Mapea la línea del producto a su cuenta de inventario correspondiente
 * 
 * @param string $linea Línea o categoría del producto
 * @return string Código de la cuenta de inventario
 */
function obtenerCuentaInventario($linea) {
    global $connect;
    
    try {
        if (empty($linea)) {
            // Por defecto, usar Inventario Insumos si no hay línea
            return '110400102';
        }
        
        $lineaUpper = strtoupper(trim($linea));
        
        // Mapeo de líneas a cuentas de inventario
        if (stripos($lineaUpper, 'REACTIVO') !== false || 
            stripos($lineaUpper, 'LABORATORIO') !== false ||
            stripos($lineaUpper, 'REACTIVOS') !== false) {
            // Reactivos de laboratorio
            return '110400101'; // Inventario Reactivos (si existe, sino usar 110400102)
        } elseif (stripos($lineaUpper, 'MEDICAMENTO') !== false) {
            // Medicamentos - usar Inventario Insumos por defecto
            // Si existe una cuenta específica para medicamentos, usarla aquí
            return '110400102'; // Inventario Insumos Medicasa
        } else {
            // Por defecto, Insumos Descartables y otros
            return '110400102'; // Inventario Insumos Medicasa
        }
        
    } catch (PDOException $e) {
        error_log("Error al obtener cuenta de inventario: " . $e->getMessage());
        return '110400102'; // Por defecto
    }
}

/**
 * Obtiene la cuenta contable de descuentos según la cuenta de ingresos
 * Mapea la cuenta de ingresos a su cuenta de descuentos correspondiente
 * 
 * @param string $cuentaIngresos Código de la cuenta de ingresos
 * @return string Código de la cuenta de descuentos
 */
function obtenerCuentaDescuentos($cuentaIngresos) {
    global $connect;
    
    try {
        // Mapeo específico según el catálogo de cuentas
        $mapeoDescuentos = [
            '410100101' => '610300102', // Radiologia e Imagenes -> Rebajas y Descuentos sobre Ventas Radiologia
            '410100102' => '610300103', // Odontologia
            '410100103' => '610300104', // Emergencias
            '410100104' => '610300105', // Hospitalizacion
            '410100105' => '610300106', // Cirugias
            '410100106' => '610300107', // Cirugias Menores
            '410100107' => '610300113', // Ginecologia -> Maternidad
            '410100108' => '610300109', // Cardiologia
            '410100109' => '610300110', // Neurologia
            '410100110' => '610300111', // Unidad Digestiva
            '410100111' => '610300112', // Labor y Parto
            '410100112' => '610300114', // Otorrinolaringologia
            '410100113' => '610300115', // Urologia
            '410100114' => '610300116', // Hematologia
            '410100115' => '610300117', // Neumologia
            '410100116' => '610300118', // Podologia
            '410100117' => '610300121', // Ambulancia
            '410100118' => '610300113', // Maternidad
            '410100119' => '610300122', // Consultas
        ];
        
        // Verificar mapeo directo
        if (isset($mapeoDescuentos[$cuentaIngresos])) {
            $cuentaDescuentos = $mapeoDescuentos[$cuentaIngresos];
            
            // Verificar si existe en el catálogo
            $stmt = $connect->prepare("SELECT cuenta FROM cuentas_catalogo WHERE cuenta = :cuenta LIMIT 1");
            $stmt->bindParam(':cuenta', $cuentaDescuentos);
            $stmt->execute();
            if ($stmt->fetch()) {
                return $cuentaDescuentos;
            }
        }
        
        // Si no se encuentra en el mapeo, intentar patrón automático
        if (strlen($cuentaIngresos) >= 9) {
            $ultimosDos = substr($cuentaIngresos, -2);
            $cuentaDescuentos = '610300' . $ultimosDos;
            
            // Verificar si existe en el catálogo
            $stmt = $connect->prepare("SELECT cuenta FROM cuentas_catalogo WHERE cuenta = :cuenta LIMIT 1");
            $stmt->bindParam(':cuenta', $cuentaDescuentos);
            $stmt->execute();
            if ($stmt->fetch()) {
                return $cuentaDescuentos;
            }
        }
        
        // Si no se encuentra, usar la cuenta por defecto
        return '610300101'; // Rebajas y Descuentos sobre Ventas Oftalmed (por defecto)
        
    } catch (PDOException $e) {
        error_log("Error al obtener cuenta de descuentos: " . $e->getMessage());
        return '610300101'; // Por defecto
    }
}

/**
 * Obtiene la cuenta contable de costos según la cuenta de ingresos
 * Mapea la cuenta de ingresos a su cuenta de costos correspondiente
 * 
 * @param string $cuentaIngresos Código de la cuenta de ingresos
 * @return string Código de la cuenta de costos
 */
function obtenerCuentaCostos($cuentaIngresos) {
    global $connect;
    
    try {
        // Si la cuenta de ingresos es un código de producto (no numérico o formato incorrecto), usar cuenta por defecto
        if (!preg_match('/^\d{9}$/', $cuentaIngresos)) {
            error_log("WARNING obtenerCuentaCostos: Cuenta de ingresos no es numérica (probablemente código de producto): $cuentaIngresos, usando cuenta por defecto 510100101");
            return '510100101'; // Costo de Ventas por defecto
        }
        
        // Mapeo específico según el catálogo de cuentas
        $mapeoCostos = [
            '410100101' => '510100101', // Radiologia
            '410100102' => '510100102', // Odontologia
            '410100103' => '510100103', // Emergencias
            '410100104' => '510100104', // Hospitalizacion
            '410100105' => '510100105', // Cirugias
            '410100106' => '510100106', // Cirugias Menores
            '410100107' => '510100118', // Ginecologia
            '410100108' => '510100108', // Cardiologia
            '410100109' => '510100109', // Neurologia
            '410100110' => '510100110', // Unidad Digestiva
            '410100111' => '510100111', // Labor y Parto
            '410100112' => '510100112', // Otorrinolaringologia
            '410100113' => '510100113', // Urologia
            '410100114' => '510100114', // Hematologia
            '410100115' => '510100115', // Neumologia
            '410100116' => '510100116', // Podologia
            '410100117' => '510100117', // Ambulancia
            '410100118' => '510100119', // Maternidad
            '410100119' => '510100120', // Consultas
        ];
        
        // Verificar mapeo directo
        if (isset($mapeoCostos[$cuentaIngresos])) {
            $cuentaCostos = $mapeoCostos[$cuentaIngresos];
            
            // Verificar si existe en el catálogo
            $stmt = $connect->prepare("SELECT cuenta FROM cuentas_catalogo WHERE cuenta = :cuenta LIMIT 1");
            $stmt->bindParam(':cuenta', $cuentaCostos);
            $stmt->execute();
            if ($stmt->fetch()) {
                return $cuentaCostos;
            }
        }
        
        // Si no se encuentra en el mapeo, intentar patrón automático
        if (strlen($cuentaIngresos) >= 9) {
            $ultimosDos = substr($cuentaIngresos, -2);
            $cuentaCostos = '510100' . $ultimosDos;
            
            // Verificar si existe en el catálogo
            $stmt = $connect->prepare("SELECT cuenta FROM cuentas_catalogo WHERE cuenta = :cuenta LIMIT 1");
            $stmt->bindParam(':cuenta', $cuentaCostos);
            $stmt->execute();
            if ($stmt->fetch()) {
                return $cuentaCostos;
            }
        }
        
        // Si no se encuentra, buscar cualquier cuenta de costos que tenga los mismos últimos dígitos
        $stmt = $connect->prepare("SELECT cuenta FROM cuentas_catalogo WHERE cuenta LIKE '510100%' AND cuenta LIKE :patron LIMIT 1");
        $patron = '%' . substr($cuentaIngresos, -2);
        $stmt->bindParam(':patron', $patron);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resultado) {
            return $resultado['cuenta'];
        }
        
        // Si no se encuentra, usar la cuenta por defecto
        return '510100101'; // Costo de Ventas (por defecto)
        
    } catch (PDOException $e) {
        error_log("ERROR obtenerCuentaCostos: " . $e->getMessage());
        return '510100101'; // Por defecto
    }
}

/**
 * Obtiene la cuenta contable de costos para productos según la línea/categoría
 * Los productos usan una cuenta genérica de costos de ventas
 * 
 * @param string $linea Línea o categoría del producto
 * @return string Código de la cuenta de costos (510100101 por defecto)
 */
function obtenerCuentaCostosProducto($linea) {
    global $connect;
    
    try {
        // Los productos usan una cuenta genérica de costos de ventas
        // Por defecto, usar '510100101' (Costo de Ventas)
        $cuentaCostos = '510100101';
        
        // Verificar si existe en el catálogo
        $stmt = $connect->prepare("SELECT cuenta FROM cuentas_catalogo WHERE cuenta = :cuenta LIMIT 1");
        $stmt->bindParam(':cuenta', $cuentaCostos);
        $stmt->execute();
        if ($stmt->fetch()) {
            return $cuentaCostos;
        }
        
        // Si no existe, buscar cualquier cuenta de costos genérica
        $stmt = $connect->prepare("SELECT cuenta FROM cuentas_catalogo WHERE cuenta LIKE '510100%' ORDER BY cuenta LIMIT 1");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resultado) {
            return $resultado['cuenta'];
        }
        
        // Si no se encuentra ninguna, usar la cuenta por defecto
        return '510100101'; // Costo de Ventas (por defecto)
        
    } catch (PDOException $e) {
        error_log("ERROR obtenerCuentaCostosProducto: " . $e->getMessage());
        return '510100101'; // Por defecto
    }
}

/**
 * Indica si la factura ya tiene renglones en el diario (CIERRE_VENTA).
 */
function medidata_existe_partida_diario_factura(string $referencia): bool
{
    global $connect;
    $referencia = trim($referencia);
    if ($referencia === '') {
        return false;
    }
    $stmt = $connect->prepare(
        "SELECT 1 FROM diario_general_transacciones
         WHERE referencia = ? AND tipo_transaccion = 'CIERRE_VENTA'
         LIMIT 1"
    );
    $stmt->execute([$referencia]);

    return (bool) $stmt->fetchColumn();
}

/**
 * Registra la partida contable si la factura está cobrada y aún no existe en diario.
 *
 * @return array{ok: bool, skipped: bool, numero_partida: ?string, message: string}
 */
function medidata_asegurar_partida_diario_factura_cobrada(int $order_id): array
{
    global $connect;

    $stmt = $connect->prepare('SELECT idord, invoice_number, invoice_status FROM orders WHERE idord = ? LIMIT 1');
    $stmt->execute([$order_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$orden) {
        return ['ok' => false, 'skipped' => false, 'numero_partida' => null, 'message' => 'Orden no encontrada.'];
    }

    $referencia = trim((string) ($orden['invoice_number'] ?? ''));
    if ($referencia === '') {
        $referencia = (string) $order_id;
    }

    if (medidata_existe_partida_diario_factura($referencia)) {
        $stPartida = $connect->prepare(
            "SELECT numero_partida FROM diario_general_transacciones
             WHERE referencia = ? AND tipo_transaccion = 'CIERRE_VENTA'
             ORDER BY id DESC LIMIT 1"
        );
        $stPartida->execute([$referencia]);
        $numero = $stPartida->fetchColumn();

        return [
            'ok' => true,
            'skipped' => true,
            'numero_partida' => $numero ? (string) $numero : null,
            'message' => 'La partida ya estaba registrada en el diario.',
        ];
    }

    try {
        $numeroPartida = registrarTransaccionesFacturaCobrada($order_id);

        return [
            'ok' => true,
            'skipped' => false,
            'numero_partida' => $numeroPartida,
            'message' => 'Partida registrada en el diario: ' . $numeroPartida,
        ];
    } catch (Throwable $e) {
        error_log('medidata_asegurar_partida_diario_factura_cobrada: ' . $e->getMessage());

        return [
            'ok' => false,
            'skipped' => false,
            'numero_partida' => null,
            'message' => $e->getMessage(),
        ];
    }
}

if (!defined('MEDIDATA_CUENTA_HONORARIOS_MEDICOS')) {
    define('MEDIDATA_CUENTA_HONORARIOS_MEDICOS', '210200108');
}

/**
 * Proporción del cobro cubierta con tarjeta (1 = todo tarjeta, 0 = sin tarjeta).
 */
function medidata_diario_ratio_pago_tarjeta(
    string $metodoPago,
    float $totalVenta,
    float $montoTarjetaMixto = 0
): float {
    if ($metodoPago === 'Tarjeta') {
        return 1.0;
    }
    if ($metodoPago === 'Pago Mixto' && $totalVenta > 0 && $montoTarjetaMixto > 0) {
        return min(1.0, max(0.0, $montoTarjetaMixto / $totalVenta));
    }

    return 0.0;
}

/**
 * Servicios vinculados a proveedores médicos (honorarios_configuracion).
 *
 * @return array<int, true>
 */
function medidata_diario_cargar_servicios_honorarios_configuracion(PDO $connect): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    try {
        $stmt = $connect->query(
            'SELECT DISTINCT id_servicio FROM honorarios_configuracion WHERE id_servicio IS NOT NULL'
        );
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cache[(int) $row['id_servicio']] = true;
        }
    } catch (PDOException $e) {
        error_log('medidata_diario_cargar_servicios_honorarios_configuracion: ' . $e->getMessage());
    }

    return $cache;
}

function medidata_diario_texto_contiene_consulta_medica(string $texto): bool
{
    $texto = trim($texto);
    if ($texto === '') {
        return false;
    }
    if (preg_match('/\bCONSULTA\b/ui', $texto)) {
        return true;
    }
    if (stripos($texto, 'MEDICINA GENERAL') !== false) {
        return true;
    }
    if (stripos($texto, 'MEDICO POR LLAMADO') !== false) {
        return true;
    }

    return false;
}

/**
 * Consultas de médicos generales y especialistas (honorarios / proveedores médicos).
 */
function medidata_diario_es_consulta_medica_honorarios(PDO $connect, array $detalle): bool
{
    if (($detalle['item_type'] ?? '') !== 'servicio') {
        return false;
    }

    $serviceId = (int) ($detalle['service_id'] ?? 0);
    if ($serviceId > 0) {
        $honorariosServicios = medidata_diario_cargar_servicios_honorarios_configuracion($connect);
        if (!empty($honorariosServicios[$serviceId])) {
            return true;
        }
    }

    if (trim((string) ($detalle['cuenta_ingresos'] ?? '')) === '410100119') {
        return true;
    }

    $categoria = strtoupper(trim((string) ($detalle['categoria'] ?? '')));
    if (in_array($categoria, ['CONSULTAS', 'MEDICINA GENERAL', 'MEDICO POR LLAMADO'], true)) {
        return true;
    }

    $uso = strtoupper(trim((string) ($detalle['uso_servicio'] ?? '')));
    $textos = [
        (string) ($detalle['nombre_servicio'] ?? ''),
        (string) ($detalle['nomservicio'] ?? ''),
        (string) ($detalle['descripcion'] ?? ''),
    ];
    foreach ($textos as $texto) {
        if (!medidata_diario_texto_contiene_consulta_medica($texto)) {
            continue;
        }
        if ($uso === '' || in_array($uso, ['ATENCIÓN', 'ATENCION'], true)) {
            return true;
        }
    }

    return false;
}

function medidata_diario_acumular_haber_servicio(array &$ingresosPorCuenta, string $cuentaHaber, float $monto): void
{
    if ($monto <= 0 || $cuentaHaber === '') {
        return;
    }
    if (!isset($ingresosPorCuenta[$cuentaHaber])) {
        $ingresosPorCuenta[$cuentaHaber] = 0;
    }
    $ingresosPorCuenta[$cuentaHaber] += $monto;
}

/**
 * HABER de servicios: consultas cobradas con tarjeta → honorarios médicos (210200108).
 */
function medidata_diario_distribuir_haber_consulta_tarjeta(
    PDO $connect,
    array &$ingresosPorCuenta,
    array $detalle,
    string $cuentaIngresos,
    float $bruto,
    string $metodoPago,
    float $totalVenta,
    float $montoTarjetaMixto = 0
): void {
    $ratioTarjeta = medidata_diario_ratio_pago_tarjeta($metodoPago, $totalVenta, $montoTarjetaMixto);
    if ($ratioTarjeta > 0 && medidata_diario_es_consulta_medica_honorarios($connect, $detalle)) {
        $montoHonorarios = round($bruto * $ratioTarjeta, 2);
        $montoIngresos = $bruto - $montoHonorarios;
        medidata_diario_acumular_haber_servicio(
            $ingresosPorCuenta,
            MEDIDATA_CUENTA_HONORARIOS_MEDICOS,
            $montoHonorarios
        );
        medidata_diario_acumular_haber_servicio($ingresosPorCuenta, $cuentaIngresos, $montoIngresos);
    } else {
        medidata_diario_acumular_haber_servicio($ingresosPorCuenta, $cuentaIngresos, $bruto);
    }
}

/**
 * Registra las transacciones contables cuando se marca una factura como "Cobrada"
 * 
 * @param int $order_id ID de la orden/factura
 * @return string Número de partida generado
 */
function registrarTransaccionesFacturaCobrada($order_id) {
    global $connect;
    
    try {
        // Obtener información de la orden
        $stmt = $connect->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(DISTINCT od.codpro) as cuentas,
                   SUM(od.total_after_discount) as total_venta
            FROM orders o
            LEFT JOIN order_details od ON o.idord = od.order_id
            WHERE o.idord = :order_id
            GROUP BY o.idord
        ");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orden) {
            throw new Exception("Orden no encontrada");
        }

        $referenciaFactura = trim((string) ($orden['invoice_number'] ?? ''));
        if ($referenciaFactura === '') {
            $referenciaFactura = (string) $order_id;
        }
        if (medidata_existe_partida_diario_factura($referenciaFactura)) {
            $stPartida = $connect->prepare(
                "SELECT numero_partida FROM diario_general_transacciones
                 WHERE referencia = ? AND tipo_transaccion = 'CIERRE_VENTA'
                 ORDER BY id DESC LIMIT 1"
            );
            $stPartida->execute([$referenciaFactura]);
            $existente = $stPartida->fetchColumn();

            return $existente ? (string) $existente : generarNumeroPartida(date('Y-m-d'));
        }
        
        // Obtener detalles de la orden con sus cuentas contables y precios de costo
        $stmt = $connect->prepare("
            SELECT od.*, 
                   CASE 
                       WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN ah.linea
                       WHEN od.item_type = 'producto' THEN p.linea
                       WHEN od.item_type = 'servicio' THEN s.categoria_servicio
                   END as categoria,
                   -- Cuenta contable de ingresos (solo para servicios)
                   CASE 
                       WHEN od.item_type = 'servicio' THEN s.codigo_servicio
                       ELSE NULL
                   END as cuenta_ingresos,
                   -- Precio de costo
                   CASE 
                       WHEN od.item_type = 'servicio' THEN s.precio_costo
                       WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN ah.preprd
                       WHEN od.item_type = 'producto' THEN p.preprd
                       ELSE 0
                   END as precio_costo,
                   s.nombre_servicio,
                   s.nomservicio,
                   s.uso_servicio
            FROM order_details od
            LEFT JOIN product p ON p.idprcd = od.product_id AND od.item_type = 'producto'
            LEFT JOIN almacen_hospitalario ah ON ah.idprcd = od.hospitalario_id AND od.item_type = 'producto'
            LEFT JOIN servicios_hospital s ON s.id = od.service_id AND od.item_type = 'servicio'
            WHERE od.order_id = :order_id
        ");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Determinar unidad de servicio (por defecto)
        $unidadServicio = 'Hospital Medicasa'; // Puede ajustarse según configuración
        
        $fechaOcurrencia = date('Y-m-d', strtotime($orden['placed_on']));
        $fechaRegistro = $orden['updated_at'] ?? date('Y-m-d H:i:s');
        
        // Obtener el nombre completo del usuario desde la tabla users
        $username = $orden['updated_by'] ?? null;
        $usuario = 'Sistema';
        if ($username) {
            try {
                $stmtUsuario = $connect->prepare("SELECT name FROM users WHERE username = ? LIMIT 1");
                $stmtUsuario->execute([$username]);
                $usuarioData = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
                $usuario = $usuarioData ? $usuarioData['name'] : $username;
            } catch (Exception $e) {
                error_log("Error al obtener nombre de usuario: " . $e->getMessage());
                $usuario = $username; // Fallback al username si hay error
            }
        }
        
        // Calcular totales por método de pago
        $totalVenta = floatval($orden['total_price']);
        $metodoPago = $orden['method'];
        $bancoEmisor = !empty($orden['banco_emisor']) ? trim($orden['banco_emisor']) : null; // Limpiar espacios
        
        // Obtener datos de pago mixto si aplica
        $montoEfectivoMixto = !empty($orden['monto_efectivo_mixto']) ? floatval($orden['monto_efectivo_mixto']) : 0;
        $montoTarjetaMixto = !empty($orden['monto_tarjeta_mixto']) ? floatval($orden['monto_tarjeta_mixto']) : 0;
        $tipoPagoMixto = !empty($orden['tipo_pago_mixto']) ? trim($orden['tipo_pago_mixto']) : null;
        
        // Log detallado para depuración
        error_log("===== FACTURA COBRADA =====");
        error_log("Order ID: $order_id");
        error_log("Método de pago: $metodoPago");
        error_log("Banco emisor RAW: '" . var_export($bancoEmisor, true) . "'");
        error_log("Banco emisor tipo: " . gettype($bancoEmisor));
        if ($metodoPago === 'Pago Mixto') {
            error_log("Pago Mixto - Efectivo: $montoEfectivoMixto, Tarjeta: $montoTarjetaMixto, Tipo: " . var_export($tipoPagoMixto, true));
        }
        error_log("===========================");
        
        // Mapeo de métodos de pago a cuentas contables (solo los métodos básicos)
        $cuentasMetodoPago = [
            'Efectivo' => '110100101', // Cajas
            'Credito Colaborador' => '110300103', // Crédito Colaborador
            'Crédito Colaborador' => '110300103', // Crédito Colaborador (con acento)
            'Pago Mixto' => '110100101' // Pago Mixto -> Cajas (solo para referencia, se desglosará)
        ];
        
        // Para Tarjeta, mapear según banco emisor
        if ($metodoPago === 'Tarjeta') {
            error_log("ANTES de llamar obtenerCuentaTarjeta - Banco: '" . var_export($bancoEmisor, true) . "'");
            $cuentaMetodo = obtenerCuentaTarjeta($bancoEmisor);
            error_log("DESPUES de obtenerCuentaTarjeta - Cuenta obtenida: $cuentaMetodo");
        } else {
            // Para otros métodos, usar el mapeo estándar
            $cuentaMetodo = $cuentasMetodoPago[$metodoPago] ?? '110100101'; // Por defecto Cajas
        }
        
        // Obtener el turno del usuario que marca como cobrada
        // IMPORTANTE: 
        // 1. Para buscar el turno: usar username (la tabla turnos_iniciados guarda username en columna "usuario")
        // 2. Para guardar en transacciones: usar nombre completo ($usuario que ya contiene el name)
        // 3. Buscar el turno que estaba activo EN EL MOMENTO en que se marcó como cobrada
        //    El turno debe haber iniciado ANTES o EN el momento de marcar como cobrada
        //    y no debe haber sido cerrado ANTES de marcar como cobrada
        $turno = null;
        if ($username) {
            try {
                // Usar fecha y hora cuando se marca como cobrada
                $fechaHoraCobrada = $orden['updated_at'] ? $orden['updated_at'] : date('Y-m-d H:i:s');
                $fechaParaTurno = date('Y-m-d', strtotime($fechaHoraCobrada));
                
                // Buscar el turno que estaba activo en el momento en que se marcó como cobrada
                // El turno debe:
                // 1. Haber iniciado ANTES o EN el momento de marcar como cobrada (t.fecha_inicio <= fechaHoraCobrada)
                // 2. No haber sido cerrado ANTES de marcar como cobrada
                //    (si tiene cierre, el cierre debe ser DESPUÉS de marcar como cobrada)
                // IMPORTANTE: Buscar por username (columna "usuario" en turnos_iniciados guarda el username)
                $stmtTurno = $connect->prepare("
                    SELECT t.turno
                    FROM turnos_iniciados t
                    WHERE t.usuario = ?
                    AND DATE(t.fecha_inicio) = DATE(?)
                    AND t.fecha_inicio <= ?
                    AND (
                        -- El turno no tiene cierre (aún está activo)
                        NOT EXISTS (
                            SELECT 1 FROM cierre_caja c 
                            WHERE c.id_turno_iniciado = t.id
                        )
                        OR
                        -- O el turno fue cerrado DESPUÉS de marcar como cobrada
                        EXISTS (
                            SELECT 1 FROM cierre_caja c 
                            WHERE c.id_turno_iniciado = t.id
                            AND c.fecha_cierre >= ?
                        )
                    )
                    ORDER BY t.fecha_inicio DESC
                    LIMIT 1
                ");
                // Usar username para buscar el turno, fecha y hora cuando se marca como cobrada
                $stmtTurno->execute([$username, $fechaParaTurno, $fechaHoraCobrada, $fechaHoraCobrada]);
                $turnoData = $stmtTurno->fetch(PDO::FETCH_ASSOC);
                $turno = $turnoData['turno'] ?? null;
                
                if ($turno) {
                    error_log("DEBUG registrarTransaccionesFacturaCobrada: Turno obtenido: $turno para username: $username (nombre: $usuario) en fecha: $fechaParaTurno");
                } else {
                    // Si no se encuentra, buscar el último turno del día que inició antes o en el momento de marcar como cobrada
                    // Esto captura el turno incluso si ya fue cerrado
                    $stmtTurnoSimple = $connect->prepare("
                        SELECT turno
                        FROM turnos_iniciados t
                        WHERE t.usuario = ?
                        AND DATE(t.fecha_inicio) = DATE(?)
                        AND t.fecha_inicio <= ?
                        ORDER BY t.fecha_inicio DESC
                        LIMIT 1
                    ");
                    $stmtTurnoSimple->execute([$username, $fechaParaTurno, $fechaHoraCobrada]);
                    $turnoDataSimple = $stmtTurnoSimple->fetch(PDO::FETCH_ASSOC);
                    $turno = $turnoDataSimple['turno'] ?? null;
                    
                    if ($turno) {
                        error_log("DEBUG registrarTransaccionesFacturaCobrada: Turno obtenido (método alternativo): $turno para username: $username");
                    } else {
                        error_log("WARNING registrarTransaccionesFacturaCobrada: No se encontró turno para username: $username (nombre: $usuario) en fecha: $fechaParaTurno");
                    }
                }
            } catch (Exception $e) {
                error_log("Error al obtener turno para factura cobrada: " . $e->getMessage());
            }
        }
        
        // Agrupar detalles por cuenta contable de ingresos (servicios) o inventario (productos)
        $ingresosPorCuenta = [];
        $descuentosPorCuenta = []; // Descuentos normales por cuenta de ingresos
        $descuentosTerceraEdad = 0; // Descuentos de tercera edad (610300119)
        $descuentosCuartaEdad = 0; // Descuentos de cuarta edad (610300120)
        $costosPorCuenta = []; // Solo costos de productos (no servicios)
        $inventarioPorCuenta = []; // Agrupado por cuenta de inventario
        $otrosIngresosPorCuenta = []; // Otros Ingresos para balancear placa de rayos X
        
        // Primero, identificar servicios de radiología para asociar con placas
        $serviciosRadiologia = []; // Array de cuentas de ingresos de servicios de radiología
        foreach ($detalles as $detalle) {
            if ($detalle['item_type'] === 'servicio') {
                $cuentaIngresos = $detalle['cuenta_ingresos'] ?? null;
                // Servicios de radiología: 410100101 (Radiología e Imagenes) o 410100102 (Radiografía RX Dental)
                if ($cuentaIngresos === '410100101' || $cuentaIngresos === '410100102') {
                    if (!isset($serviciosRadiologia[$cuentaIngresos])) {
                        $serviciosRadiologia[$cuentaIngresos] = 0;
                    }
                }
            }
        }
        
        // Obtener la cuenta de ingresos de radiología para asociar con la placa (prioridad: 410100101, luego 410100102)
        $cuentaIngresosPlaca = null;
        if (isset($serviciosRadiologia['410100101'])) {
            $cuentaIngresosPlaca = '410100101';
        } elseif (isset($serviciosRadiologia['410100102'])) {
            $cuentaIngresosPlaca = '410100102';
        }
        
        // Variable para acumular el costo total de placas
        $costoTotalPlacas = 0;
        
        foreach ($detalles as $detalle) {
            $itemType = $detalle['item_type'] ?? '';
            $precioCosto = floatval($detalle['precio_costo'] ?? 0);
            $cantidad = intval($detalle['cantidad'] ?? 1);
            $totalItem = floatval($detalle['total_after_discount'] ?? 0);
            $descuentoItem = floatval($detalle['total_discount'] ?? 0);
            $categoria = $detalle['categoria'] ?? '';
            
            if ($itemType === 'servicio') {
                // Para servicios: ingresos u honorarios médicos (tarjeta + consulta)
                $cuentaIngresos = $detalle['cuenta_ingresos'] ?? null;
                if ($cuentaIngresos) {
                    $brutoServicio = $totalItem + $descuentoItem;
                    medidata_diario_distribuir_haber_consulta_tarjeta(
                        $connect,
                        $ingresosPorCuenta,
                        $detalle,
                        $cuentaIngresos,
                        $brutoServicio,
                        $metodoPago,
                        $totalVenta,
                        $montoTarjetaMixto
                    );
                    
                    // Separar descuentos: tercera edad, cuarta edad, y descuentos normales
                    $ageDiscount30 = floatval($detalle['age_discount_30'] ?? 0);
                    $ageDiscount40 = floatval($detalle['age_discount_40'] ?? 0);
                    $descuentoNormal = $descuentoItem - $ageDiscount30 - $ageDiscount40;
                    
                    // Descuentos de tercera edad
                    if ($ageDiscount30 > 0) {
                        $descuentosTerceraEdad += $ageDiscount30;
                    }
                    
                    // Descuentos de cuarta edad
                    if ($ageDiscount40 > 0) {
                        $descuentosCuartaEdad += $ageDiscount40;
                    }
                    
                    // Descuentos normales (no tercera/cuarta edad)
                    if ($descuentoNormal > 0) {
                        if (!isset($descuentosPorCuenta[$cuentaIngresos])) {
                            $descuentosPorCuenta[$cuentaIngresos] = 0;
                        }
                        $descuentosPorCuenta[$cuentaIngresos] += $descuentoNormal;
                    }
                    
                    // Los costos de servicios NO se registran en la partida individual
                    // Se registran solo en el cierre de caja para mantener el balance
                }
            } elseif ($itemType === 'producto') {
                // Para productos: generan ingresos + costos + salida de inventario (al costo)
                $cuentaInventario = obtenerCuentaInventario($categoria);
                $cuentaIngresosProducto = obtenerCuentaIngresosProducto($categoria);
                
                // Detectar si es PLACA DE RAYOS X
                $descripcion = $detalle['descripcion'] ?? '';
                $es_placa = (stripos($descripcion, 'PLACA') !== false && stripos($descripcion, 'RAYOS') !== false);
                
                if ($es_placa && $precioCosto > 0) {
                    // PLACA DE RAYOS X: Registrar al COSTO, no al precio de venta
                    $costoItem = $precioCosto * $cantidad;
                    $costoTotalPlacas += $costoItem;
                    
                    // Inventario al COSTO (no precio de venta)
                    if (!isset($inventarioPorCuenta[$cuentaInventario])) {
                        $inventarioPorCuenta[$cuentaInventario] = 0;
                    }
                    $inventarioPorCuenta[$cuentaInventario] += $costoItem; // COSTO, no precio de venta
                    
                    // Costo de la placa en cuenta específica
                    $cuentaCostosPlaca = '510100101'; // Costo de Ventas Radiologia e Imagenes
                    if (!isset($costosPorCuenta[$cuentaCostosPlaca])) {
                        $costosPorCuenta[$cuentaCostosPlaca] = 0;
                    }
                    $costosPorCuenta[$cuentaCostosPlaca] += $costoItem;
                    
                    // Otros Ingresos: usar la cuenta específica 710100101 (Otros Ingresos)
                    $cuentaOtrosIngresos = '710100101'; // Otros Ingresos
                    if (!isset($otrosIngresosPorCuenta[$cuentaOtrosIngresos])) {
                        $otrosIngresosPorCuenta[$cuentaOtrosIngresos] = 0;
                    }
                    $otrosIngresosPorCuenta[$cuentaOtrosIngresos] += $costoItem;
                } else {
                    // Ingreso de producto (HABER) por valor de venta antes de descuento.
                    if (!isset($ingresosPorCuenta[$cuentaIngresosProducto])) {
                        $ingresosPorCuenta[$cuentaIngresosProducto] = 0;
                    }
                    $ingresosPorCuenta[$cuentaIngresosProducto] += $totalItem + $descuentoItem;

                    // Descuento de producto (DEBE) en la cuenta de descuentos de esa línea.
                    if ($descuentoItem > 0) {
                        if (!isset($descuentosPorCuenta[$cuentaIngresosProducto])) {
                            $descuentosPorCuenta[$cuentaIngresosProducto] = 0;
                        }
                        $descuentosPorCuenta[$cuentaIngresosProducto] += $descuentoItem;
                    }

                    // Salida de inventario y costo de venta SIEMPRE al costo.
                    $costoItem = max(0, $precioCosto * $cantidad);
                    if ($costoItem > 0) {
                        if (!isset($inventarioPorCuenta[$cuentaInventario])) {
                            $inventarioPorCuenta[$cuentaInventario] = 0;
                        }
                        $inventarioPorCuenta[$cuentaInventario] += $costoItem;

                        $cuentaCostosProducto = obtenerCuentaCostosProducto($categoria);
                        if (!isset($costosPorCuenta[$cuentaCostosProducto])) {
                            $costosPorCuenta[$cuentaCostosProducto] = 0;
                        }
                        $costosPorCuenta[$cuentaCostosProducto] += $costoItem;
                    }
                }
            }
        }
        
        // Reducir ingresos del servicio de radiología en el costo de las placas
        if ($costoTotalPlacas > 0 && $cuentaIngresosPlaca && isset($ingresosPorCuenta[$cuentaIngresosPlaca])) {
            $ingresosPorCuenta[$cuentaIngresosPlaca] -= $costoTotalPlacas;
            // Asegurar que no sea negativo
            if ($ingresosPorCuenta[$cuentaIngresosPlaca] < 0) {
                $ingresosPorCuenta[$cuentaIngresosPlaca] = 0;
            }
        }
        
        // Preparar transacciones para la partida
        $transacciones = [];
        
        // 1. Método de pago (DEBE)
        // Si es Pago Mixto, crear DOS transacciones separadas: Efectivo y Tarjeta
        if ($metodoPago === 'Pago Mixto' && ($montoEfectivoMixto > 0 || $montoTarjetaMixto > 0)) {
            // Transacción de Efectivo (Cajas)
            if ($montoEfectivoMixto > 0) {
                $transacciones[] = [
                    'unidad_servicio' => $unidadServicio,
                    'cuenta' => '110100101', // Cajas
                    'nombre_cuenta' => obtenerNombreCuenta('110100101'),
                    'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                    'debe' => $montoEfectivoMixto,
                    'haber' => 0.00,
                    'turno' => $turno,
                    'usuario' => $usuario,
                    'tipo_transaccion' => 'CIERRE_VENTA',
                    'referencia' => $orden['invoice_number'] ?? $order_id
                ];
            }
            
            // Transacción de Tarjeta
            if ($montoTarjetaMixto > 0) {
                // Obtener banco emisor del pago mixto (puede estar en tipo_pago_mixto o banco_emisor)
                $bancoEmisorMixto = $tipoPagoMixto ?: $bancoEmisor;
                $cuentaTarjetaMixto = obtenerCuentaTarjeta($bancoEmisorMixto);
                
                $transacciones[] = [
                    'unidad_servicio' => $unidadServicio,
                    'cuenta' => $cuentaTarjetaMixto,
                    'nombre_cuenta' => obtenerNombreCuenta($cuentaTarjetaMixto),
                    'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                    'debe' => $montoTarjetaMixto,
                    'haber' => 0.00,
                    'turno' => $turno,
                    'usuario' => $usuario,
                    'tipo_transaccion' => 'CIERRE_VENTA',
                    'referencia' => $orden['invoice_number'] ?? $order_id
                ];
            }
        } else {
            // Para otros métodos de pago, crear una sola transacción
            $nombreCuentaMetodo = obtenerNombreCuenta($cuentaMetodo);
            
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaMetodo,
                'nombre_cuenta' => $nombreCuentaMetodo,
                'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                'debe' => $totalVenta,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuario,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => $orden['invoice_number'] ?? $order_id
            ];
        }
        
        // 2. Ingresos Por Ventas Ordinarias (HABER) - Solo servicios, agrupados por cuenta contable
        // Los ingresos se registran como el total sin descuento (para balancear con descuentos)
        foreach ($ingresosPorCuenta as $cuentaIngresos => $totalIngreso) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaIngresos,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaIngresos),
                'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                'debe' => 0.00,
                'haber' => $totalIngreso, // Total sin descuento
                'turno' => $turno,
                'usuario' => $usuario,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => $orden['invoice_number'] ?? $order_id
            ];
        }
        
        // 3. Rebajas Sobre Ventas (DEBE) - Descuentos normales agrupados por cuenta contable
        foreach ($descuentosPorCuenta as $cuentaIngresos => $totalDescuento) {
            $cuentaDescuentos = obtenerCuentaDescuentos($cuentaIngresos);
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaDescuentos,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaDescuentos),
                'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                'debe' => $totalDescuento,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuario,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => $orden['invoice_number'] ?? $order_id
            ];
        }
        
        // 3b. Descuentos Tercera Edad (DEBE)
        if ($descuentosTerceraEdad > 0) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => '610300119',
                'nombre_cuenta' => obtenerNombreCuenta('610300119'),
                'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                'debe' => $descuentosTerceraEdad,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuario,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => $orden['invoice_number'] ?? $order_id
            ];
        }
        
        // 3c. Descuentos Cuarta Edad (DEBE)
        if ($descuentosCuartaEdad > 0) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => '610300120',
                'nombre_cuenta' => obtenerNombreCuenta('610300120'),
                'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                'debe' => $descuentosCuartaEdad,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuario,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => $orden['invoice_number'] ?? $order_id
            ];
        }
        
        
        // 4. Inventario (HABER) - Agrupado por cuenta de inventario
        // Cuando se vende un producto, el inventario se reduce (HABER - crédito a inventario)
        // Se registra al precio de venta según lo requerido por el cliente
        foreach ($inventarioPorCuenta as $cuentaInventario => $totalInventario) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaInventario,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaInventario),
                'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                'debe' => 0.00,
                'haber' => $totalInventario,
                'turno' => $turno,
                'usuario' => $usuario,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => $orden['invoice_number'] ?? $order_id
            ];
        }
        
        // 5. Costos de Venta (DEBE) - Solo productos, agrupados por cuenta de costos
        // Los costos de productos van a una cuenta genérica
        foreach ($costosPorCuenta as $cuentaCostos => $totalCostoAgrupado) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaCostos,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaCostos),
                'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                'debe' => $totalCostoAgrupado,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuario,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => $orden['invoice_number'] ?? $order_id
            ];
        }
        
        // 6. Otros Ingresos (HABER) - Para balancear placa de rayos X
        // Cuando hay una placa asociada a un servicio de radiología, se registra como "Otros Ingresos"
        // para balancear correctamente la partida
        foreach ($otrosIngresosPorCuenta as $cuentaIngresos => $totalOtrosIngresos) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaIngresos,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaIngresos),
                'descripcion' => "Venta Factura: " . ($orden['invoice_number'] ?? $order_id),
                'debe' => 0.00,
                'haber' => $totalOtrosIngresos,
                'turno' => $turno,
                'usuario' => $usuario,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => $orden['invoice_number'] ?? $order_id
            ];
        }
        
        // Registrar la partida completa
        return registrarPartidaCompleta($transacciones, $fechaOcurrencia, $fechaRegistro);
        
    } catch (Exception $e) {
        error_log("Error al registrar transacciones de factura cobrada: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Registra la reversión contable cuando se anula una factura.
 * Busca las partidas individuales de la factura (CIERRE_VENTA) y crea entradas inversas (DEBE↔HABER).
 * 
 * @param string $invoiceNumber Número de factura (ej: 000-001-01-0000142)
 * @return string|null Número de partida generado, o null si no había partidas que reversar
 */
function registrarReversionAnulacionFactura($invoiceNumber) {
    global $connect;
    
    if (empty($invoiceNumber)) {
        return null;
    }
    
    try {
        // Buscar las partidas de la factura (solo las individuales, no las de cierre de caja)
        $stmt = $connect->prepare("
            SELECT id, numero_partida, fecha_ocurrencia, unidad_servicio, cuenta, nombre_cuenta, 
                   descripcion, debe, haber, turno, usuario
            FROM diario_general_transacciones 
            WHERE referencia = :referencia AND tipo_transaccion = 'CIERRE_VENTA'
            ORDER BY id
        ");
        $stmt->bindParam(':referencia', $invoiceNumber, PDO::PARAM_STR);
        $stmt->execute();
        $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($partidas)) {
            // La factura nunca fue cobrada o no tiene partidas contables
            error_log("registrarReversionAnulacionFactura: No se encontraron partidas para factura $invoiceNumber");
            return null;
        }
        
        $descripcionReversion = "Reversión Anulación Factura: " . $invoiceNumber;
        $transacciones = [];
        
        foreach ($partidas as $p) {
            $debeOriginal = floatval($p['debe'] ?? 0);
            $haberOriginal = floatval($p['haber'] ?? 0);
            
            // Reversión: intercambiar DEBE y HABER
            $transacciones[] = [
                'unidad_servicio' => $p['unidad_servicio'] ?? null,
                'cuenta' => $p['cuenta'],
                'nombre_cuenta' => $p['nombre_cuenta'],
                'descripcion' => $descripcionReversion,
                'debe' => $haberOriginal,
                'haber' => $debeOriginal,
                'turno' => $p['turno'] ?? null,
                'usuario' => $p['usuario'] ?? null,
                'tipo_transaccion' => 'REVERSION_ANULACION',
                'referencia' => $invoiceNumber
            ];
        }
        
        $fechaOcurrencia = date('Y-m-d');
        $fechaRegistro = date('Y-m-d H:i:s');
        
        $numeroPartida = registrarPartidaCompleta($transacciones, $fechaOcurrencia, $fechaRegistro);
        error_log("registrarReversionAnulacionFactura: Partida $numeroPartida creada para reversión de factura $invoiceNumber");
        
        return $numeroPartida;
        
    } catch (Exception $e) {
        error_log("Error al registrar reversión de anulación: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Registra las transacciones contables cuando se cierra la caja
 * Agrupa todas las ventas del día en una partida completa
 * 
 * @param string $fechaCierre Fecha del cierre (Y-m-d)
 * @param string $usuarioCierre Usuario que realiza el cierre
 * @param array $facturasCobradas Array de facturas cobradas del día
 * @param string|null $turno Turno (Turno A, Turno B, Turno C)
 * @return string Número de partida generado
 */
function registrarTransaccionesCierreCaja($fechaCierre, $usuarioCierre, $facturasCobradas, $turno = null) {
    global $connect;
    
    try {
        error_log("DEBUG registrarTransaccionesCierreCaja: Iniciando - Fecha: $fechaCierre, Usuario: $usuarioCierre, Facturas: " . count($facturasCobradas));
        
        if (empty($facturasCobradas)) {
            error_log("DEBUG registrarTransaccionesCierreCaja: No hay facturas cobradas, retornando null");
            return null; // No hay ventas, no se registra partida
        }
        
        // Agrupar ventas por método de pago (y banco emisor si es Tarjeta)
        // Estructura: ['Efectivo' => total, 'Tarjeta|BAC Credomatic' => total, ...]
        $totalesPorMetodo = [];
        $bancosPorMetodo = []; // Guardar banco emisor para cada método de pago
        $ingresosPorCuenta = []; // Solo para servicios
        $descuentosPorCuenta = []; // Descuentos normales por cuenta de ingresos
        $descuentosTerceraEdad = 0; // Descuentos de tercera edad (610300119)
        $descuentosCuartaEdad = 0; // Descuentos de cuarta edad (610300120)
        $costosPorCuenta = []; // Solo costos de productos (no servicios)
        $inventarioPorCuenta = []; // Agrupado por cuenta de inventario (productos)
        $otrosIngresosPorCuenta = []; // Otros Ingresos para balancear placa de rayos X
        $costoTotalPlacasPorCuenta = []; // Costo total de placas por cuenta de ingresos
        
        foreach ($facturasCobradas as $factura) {
            $order_id = $factura['idord'];
            
            // Obtener detalles de la orden con cuentas contables y precios de costo
            $stmt = $connect->prepare("
                SELECT od.*, o.method, o.discount_amount, o.monto_efectivo_mixto, o.monto_tarjeta_mixto, o.tipo_pago_mixto, o.banco_emisor,
                       CASE 
                           WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN ah.linea
                           WHEN od.item_type = 'producto' THEN p.linea
                           WHEN od.item_type = 'servicio' THEN s.categoria_servicio
                       END as categoria,
                       -- Cuenta contable de ingresos (solo para servicios)
                       -- Para servicios: usar codigo_servicio directamente (ya es una cuenta contable)
                       -- Para productos: NULL porque no tienen cuenta de ingresos
                       CASE 
                           WHEN od.item_type = 'servicio' THEN s.codigo_servicio
                           ELSE NULL
                       END as cuenta_ingresos,
                       -- Cuenta de inventario (solo para productos)
                       CASE 
                           WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN ah.codpro
                           WHEN od.item_type = 'producto' THEN p.codpro
                           ELSE NULL
                       END as cuenta_inventario,
                       -- Precio de costo
                       CASE 
                           WHEN od.item_type = 'servicio' THEN s.precio_costo
                           WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN ah.preprd
                           WHEN od.item_type = 'producto' THEN p.preprd
                           ELSE 0
                       END as precio_costo,
                       s.nombre_servicio,
                       s.nomservicio,
                       s.uso_servicio
                FROM order_details od
                JOIN orders o ON o.idord = od.order_id
                LEFT JOIN product p ON p.idprcd = od.product_id AND od.item_type = 'producto'
                LEFT JOIN almacen_hospitalario ah ON ah.idprcd = od.hospitalario_id AND od.item_type = 'producto'
                LEFT JOIN servicios_hospital s ON s.id = od.service_id AND od.item_type = 'servicio'
                WHERE od.order_id = :order_id
            ");
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $metodoPago = $factura['method'] ?? 'Efectivo';
            $bancoEmisor = !empty($factura['banco_emisor']) ? trim($factura['banco_emisor']) : null; // Limpiar espacios
            
            // Obtener datos de pago mixto si aplica
            $montoEfectivoMixto = !empty($factura['monto_efectivo_mixto']) ? floatval($factura['monto_efectivo_mixto']) : 0;
            $montoTarjetaMixto = !empty($factura['monto_tarjeta_mixto']) ? floatval($factura['monto_tarjeta_mixto']) : 0;
            $tipoPagoMixto = !empty($factura['tipo_pago_mixto']) ? trim($factura['tipo_pago_mixto']) : null;
            
            $totalVenta = floatval($factura['total_price']);
            
            // Log detallado para depuración
            error_log("===== CIERRE CAJA - FACTURA =====");
            error_log("Factura ID: {$order_id}");
            error_log("Método de pago: $metodoPago");
            error_log("Banco emisor RAW: '" . var_export($bancoEmisor, true) . "'");
            error_log("Banco emisor tipo: " . gettype($bancoEmisor));
            error_log("Banco emisor length: " . strlen($bancoEmisor ?? ''));
            if ($metodoPago === 'Pago Mixto') {
                error_log("Pago Mixto - Efectivo: $montoEfectivoMixto, Tarjeta: $montoTarjetaMixto, Tipo: " . var_export($tipoPagoMixto, true));
            }
            error_log("==================================");
            
            // Si es Pago Mixto, desglosar en Efectivo y Tarjeta
            if ($metodoPago === 'Pago Mixto' && ($montoEfectivoMixto > 0 || $montoTarjetaMixto > 0)) {
                // Agrupar efectivo
                if ($montoEfectivoMixto > 0) {
                    if (!isset($totalesPorMetodo['Efectivo'])) {
                        $totalesPorMetodo['Efectivo'] = 0;
                    }
                    $totalesPorMetodo['Efectivo'] += $montoEfectivoMixto;
                }
                
                // Agrupar tarjeta
                if ($montoTarjetaMixto > 0) {
                    // Obtener banco emisor del pago mixto
                    $bancoEmisorMixto = $tipoPagoMixto ?: $bancoEmisor;
                    if (!empty($bancoEmisorMixto)) {
                        $claveMetodo = 'Tarjeta|' . $bancoEmisorMixto;
                        if (!isset($totalesPorMetodo[$claveMetodo])) {
                            $totalesPorMetodo[$claveMetodo] = 0;
                            $bancosPorMetodo[$claveMetodo] = $bancoEmisorMixto;
                        }
                        $totalesPorMetodo[$claveMetodo] += $montoTarjetaMixto;
                        error_log("DEBUG registrarTransaccionesCierreCaja: Agrupando tarjeta de pago mixto con banco: '$bancoEmisorMixto', Clave: '$claveMetodo'");
                    } else {
                        // Si no hay banco, agrupar como Tarjeta genérica
                        if (!isset($totalesPorMetodo['Tarjeta'])) {
                            $totalesPorMetodo['Tarjeta'] = 0;
                        }
                        $totalesPorMetodo['Tarjeta'] += $montoTarjetaMixto;
                    }
                }
            } elseif ($metodoPago === 'Tarjeta' && !empty($bancoEmisor)) {
                // Para Tarjeta normal, agrupar por banco emisor
                $claveMetodo = $metodoPago . '|' . $bancoEmisor;
                if (!isset($totalesPorMetodo[$claveMetodo])) {
                    $totalesPorMetodo[$claveMetodo] = 0;
                    $bancosPorMetodo[$claveMetodo] = $bancoEmisor;
                }
                $totalesPorMetodo[$claveMetodo] += $totalVenta;
                error_log("DEBUG registrarTransaccionesCierreCaja: Agrupando tarjeta con banco: '$bancoEmisor', Clave: '$claveMetodo'");
            } else {
                // Para otros métodos, agrupar normalmente
                if (!isset($totalesPorMetodo[$metodoPago])) {
                    $totalesPorMetodo[$metodoPago] = 0;
                }
                $totalesPorMetodo[$metodoPago] += $totalVenta;
            }
            
            // Procesar cada detalle para agrupar por cuenta contable
            foreach ($detalles as $detalle) {
                $itemType = $detalle['item_type'] ?? '';
                $precioCosto = floatval($detalle['precio_costo'] ?? 0);
                $cantidad = intval($detalle['cantidad'] ?? 1);
                $totalItem = floatval($detalle['total_after_discount'] ?? 0);
                $descuentoItem = floatval($detalle['total_discount'] ?? 0);
                
                if ($itemType === 'servicio') {
                    // Para servicios: ingresos u honorarios médicos (tarjeta + consulta)
                    $cuentaIngresos = $detalle['cuenta_ingresos'] ?? null;
                    if ($cuentaIngresos) {
                        $brutoServicio = $totalItem + $descuentoItem;
                        medidata_diario_distribuir_haber_consulta_tarjeta(
                            $connect,
                            $ingresosPorCuenta,
                            $detalle,
                            $cuentaIngresos,
                            $brutoServicio,
                            $metodoPago,
                            $totalVenta,
                            $montoTarjetaMixto
                        );
                        
                        // Separar descuentos: tercera edad, cuarta edad, y descuentos normales
                        $ageDiscount30 = floatval($detalle['age_discount_30'] ?? 0);
                        $ageDiscount40 = floatval($detalle['age_discount_40'] ?? 0);
                        $descuentoNormal = $descuentoItem - $ageDiscount30 - $ageDiscount40;
                        
                        // Descuentos de tercera edad
                        if ($ageDiscount30 > 0) {
                            $descuentosTerceraEdad += $ageDiscount30;
                        }
                        
                        // Descuentos de cuarta edad
                        if ($ageDiscount40 > 0) {
                            $descuentosCuartaEdad += $ageDiscount40;
                        }
                        
                        // Descuentos normales (no tercera/cuarta edad)
                        if ($descuentoNormal > 0) {
                            if (!isset($descuentosPorCuenta[$cuentaIngresos])) {
                                $descuentosPorCuenta[$cuentaIngresos] = 0;
                            }
                            $descuentosPorCuenta[$cuentaIngresos] += $descuentoNormal;
                        }
                        
                        // Los costos de servicios SÍ se registran en el cierre de caja
                        $costoItem = $precioCosto * $cantidad;
                        if ($costoItem > 0) {
                            if (!isset($costosPorCuenta[$cuentaIngresos])) {
                                $costosPorCuenta[$cuentaIngresos] = 0;
                            }
                            $costosPorCuenta[$cuentaIngresos] += $costoItem;
                        }
                    }
                } elseif ($itemType === 'producto') {
                    // Para productos: generan ingresos + costos + salida de inventario (al costo)
                    $linea = $detalle['categoria'] ?? '';
                    $cuentaInventario = obtenerCuentaInventario($linea);
                    $cuentaIngresosProducto = obtenerCuentaIngresosProducto($linea);
                    
                    // Detectar si es PLACA DE RAYOS X
                    $descripcion = $detalle['descripcion'] ?? '';
                    $es_placa = (stripos($descripcion, 'PLACA') !== false && stripos($descripcion, 'RAYOS') !== false);
                    
                    if ($es_placa && $precioCosto > 0) {
                        // PLACA DE RAYOS X: Registrar al COSTO, no al precio de venta
                        $costoItem = $precioCosto * $cantidad;
                        
                        // Inventario al COSTO (no precio de venta)
                        if (!isset($inventarioPorCuenta[$cuentaInventario])) {
                            $inventarioPorCuenta[$cuentaInventario] = 0;
                        }
                        $inventarioPorCuenta[$cuentaInventario] += $costoItem; // COSTO, no precio de venta
                        
                        // Costo de la placa en cuenta específica
                        $cuentaCostosPlaca = '510100101'; // Costo de Ventas Radiologia e Imagenes
                        if (!isset($costosPorCuenta[$cuentaCostosPlaca])) {
                            $costosPorCuenta[$cuentaCostosPlaca] = 0;
                        }
                        $costosPorCuenta[$cuentaCostosPlaca] += $costoItem;
                        
                        // Buscar el servicio de radiología asociado en esta factura
                        // Prioridad: 410100101 (Radiología e Imagenes), luego 410100102 (Radiografía RX Dental)
                        $cuentaIngresosPlaca = null;
                        foreach ($detalles as $detalleServicio) {
                            if ($detalleServicio['item_type'] === 'servicio') {
                                $cuentaIngresosServicio = $detalleServicio['cuenta_ingresos'] ?? null;
                                if ($cuentaIngresosServicio === '410100101') {
                                    $cuentaIngresosPlaca = '410100101';
                                    break;
                                } elseif ($cuentaIngresosServicio === '410100102' && $cuentaIngresosPlaca !== '410100101') {
                                    $cuentaIngresosPlaca = '410100102';
                                }
                            }
                        }
                        
                        // Si se encontró servicio de radiología, acumular costo de placa y otros ingresos
                        if ($cuentaIngresosPlaca) {
                            if (!isset($costoTotalPlacasPorCuenta[$cuentaIngresosPlaca])) {
                                $costoTotalPlacasPorCuenta[$cuentaIngresosPlaca] = 0;
                            }
                            $costoTotalPlacasPorCuenta[$cuentaIngresosPlaca] += $costoItem;
                        }
                        
                        // Otros Ingresos: usar la cuenta específica 710100101 (Otros Ingresos)
                        $cuentaOtrosIngresos = '710100101'; // Otros Ingresos
                        if (!isset($otrosIngresosPorCuenta[$cuentaOtrosIngresos])) {
                            $otrosIngresosPorCuenta[$cuentaOtrosIngresos] = 0;
                        }
                        $otrosIngresosPorCuenta[$cuentaOtrosIngresos] += $costoItem;
                    } else {
                        // Ingreso de producto (HABER) por valor de venta antes de descuento.
                        if (!isset($ingresosPorCuenta[$cuentaIngresosProducto])) {
                            $ingresosPorCuenta[$cuentaIngresosProducto] = 0;
                        }
                        $ingresosPorCuenta[$cuentaIngresosProducto] += $totalItem + $descuentoItem;

                        // Descuento de producto (DEBE) en la cuenta de descuentos de esa línea.
                        if ($descuentoItem > 0) {
                            if (!isset($descuentosPorCuenta[$cuentaIngresosProducto])) {
                                $descuentosPorCuenta[$cuentaIngresosProducto] = 0;
                            }
                            $descuentosPorCuenta[$cuentaIngresosProducto] += $descuentoItem;
                        }

                        // Salida de inventario y costo de venta al costo.
                        $costoItem = max(0, $precioCosto * $cantidad);
                        if ($costoItem > 0) {
                            if (!isset($inventarioPorCuenta[$cuentaInventario])) {
                                $inventarioPorCuenta[$cuentaInventario] = 0;
                            }
                            $inventarioPorCuenta[$cuentaInventario] += $costoItem;
                        }

                        // Costos de otros productos
                        $cuentaCostosProducto = obtenerCuentaCostosProducto($linea);
                        if ($cuentaCostosProducto && $costoItem > 0) {
                            if (!isset($costosPorCuenta[$cuentaCostosProducto])) {
                                $costosPorCuenta[$cuentaCostosProducto] = 0;
                            }
                            $costosPorCuenta[$cuentaCostosProducto] += $costoItem;
                        }
                    }
                }
            }
        }
        
        // Reducir ingresos de servicios de radiología en el costo de las placas
        foreach ($costoTotalPlacasPorCuenta as $cuentaIngresos => $costoTotalPlacas) {
            if (isset($ingresosPorCuenta[$cuentaIngresos])) {
                $ingresosPorCuenta[$cuentaIngresos] -= $costoTotalPlacas;
                // Asegurar que no sea negativo
                if ($ingresosPorCuenta[$cuentaIngresos] < 0) {
                    $ingresosPorCuenta[$cuentaIngresos] = 0;
                }
            }
        }
        
        $unidadServicio = 'Hospital Medicasa';
        $fechaRegistro = date('Y-m-d H:i:s');
        
        error_log("DEBUG registrarTransaccionesCierreCaja: Ingresos por cuenta: " . json_encode($ingresosPorCuenta));
        error_log("DEBUG registrarTransaccionesCierreCaja: Descuentos por cuenta: " . json_encode($descuentosPorCuenta));
        error_log("DEBUG registrarTransaccionesCierreCaja: Costos por cuenta: " . json_encode($costosPorCuenta));
        error_log("DEBUG registrarTransaccionesCierreCaja: Inventario por cuenta: " . json_encode($inventarioPorCuenta));
        error_log("DEBUG registrarTransaccionesCierreCaja: Otros Ingresos por cuenta: " . json_encode($otrosIngresosPorCuenta));
        
        // Mapeo de métodos de pago a cuentas contables (solo los métodos básicos)
        $cuentasMetodoPago = [
            'Efectivo' => '110100101', // Cajas
            'Credito Colaborador' => '110300103', // Crédito Colaborador
            'Crédito Colaborador' => '110300103', // Crédito Colaborador (con acento)
            'Pago Mixto' => '110100101' // Pago Mixto -> Cajas
        ];
        
        $transacciones = [];
        
        // Registrar cada método de pago (DEBE)
        foreach ($totalesPorMetodo as $claveMetodo => $total) {
            // Separar método y banco si es Tarjeta
            if (strpos($claveMetodo, '|') !== false) {
                list($metodo, $bancoEmisor) = explode('|', $claveMetodo, 2);
                error_log("===== PROCESAR TRANSACCION =====");
                error_log("Clave método: $claveMetodo");
                error_log("Método separado: $metodo");
                error_log("Banco separado: '" . var_export($bancoEmisor, true) . "'");
                error_log("Banco tipo: " . gettype($bancoEmisor));
                error_log("Banco length: " . strlen($bancoEmisor));
                error_log("================================");
            } else {
                $metodo = $claveMetodo;
                $bancoEmisor = null;
            }
            
            // Para Tarjeta, mapear según banco emisor
            if ($metodo === 'Tarjeta') {
                error_log("ANTES de llamar obtenerCuentaTarjeta - Banco: '" . var_export($bancoEmisor, true) . "'");
                $cuentaMetodo = obtenerCuentaTarjeta($bancoEmisor);
                error_log("DESPUES de obtenerCuentaTarjeta - Cuenta retornada: $cuentaMetodo");
            } else {
                // Para otros métodos, usar el mapeo estándar
                $cuentaMetodo = $cuentasMetodoPago[$metodo] ?? '110100101';
            }
            
            // Obtener el nombre de la cuenta desde el catálogo
            // Si es Pago Mixto, usar "Pago Mixto" como nombre de cuenta, de lo contrario usar el nombre del catálogo
            $nombreCuenta = ($metodo === 'Pago Mixto') ? 'Pago Mixto' : obtenerNombreCuenta($cuentaMetodo);
            error_log("DEBUG registrarTransaccionesCierreCaja: Nombre de cuenta: '$nombreCuenta'");
            
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaMetodo,
                'nombre_cuenta' => $nombreCuenta,
                'descripcion' => "Cierre de Caja fecha: " . $fechaCierre,
                'debe' => $total,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuarioCierre,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => "Cierre " . $fechaCierre
            ];
        }
        
        // Ingresos Por Ventas Ordinarias (HABER) - Solo servicios, agrupados por cuenta contable
        // Los ingresos se registran como el total sin descuento (para balancear con descuentos)
        foreach ($ingresosPorCuenta as $cuentaIngresos => $totalIngreso) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaIngresos,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaIngresos),
                'descripcion' => "Cierre de Caja fecha: " . $fechaCierre,
                'debe' => 0.00,
                'haber' => $totalIngreso, // Total sin descuento
                'turno' => $turno,
                'usuario' => $usuarioCierre,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => "Cierre " . $fechaCierre
            ];
        }
        
        // Rebajas Sobre Ventas (DEBE) - Descuentos normales agrupados por cuenta contable
        foreach ($descuentosPorCuenta as $cuentaIngresos => $totalDescuento) {
            $cuentaDescuentos = obtenerCuentaDescuentos($cuentaIngresos);
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaDescuentos,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaDescuentos),
                'descripcion' => "Cierre de Caja fecha: " . $fechaCierre,
                'debe' => $totalDescuento,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuarioCierre,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => "Cierre " . $fechaCierre
            ];
        }
        
        // Descuentos Tercera Edad (DEBE)
        if ($descuentosTerceraEdad > 0) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => '610300119',
                'nombre_cuenta' => obtenerNombreCuenta('610300119'),
                'descripcion' => "Cierre de Caja fecha: " . $fechaCierre,
                'debe' => $descuentosTerceraEdad,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuarioCierre,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => "Cierre " . $fechaCierre
            ];
        }
        
        // Descuentos Cuarta Edad (DEBE)
        if ($descuentosCuartaEdad > 0) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => '610300120',
                'nombre_cuenta' => obtenerNombreCuenta('610300120'),
                'descripcion' => "Cierre de Caja fecha: " . $fechaCierre,
                'debe' => $descuentosCuartaEdad,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuarioCierre,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => "Cierre " . $fechaCierre
            ];
        }
        
        
        // Inventario (HABER) - Agrupado por cuenta de inventario
        // Cuando se vende un producto, el inventario se reduce (HABER - crédito a inventario)
        // Para la placa de rayos X se registra al COSTO, para otros productos al precio de venta
        foreach ($inventarioPorCuenta as $cuentaInventario => $totalInventario) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaInventario,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaInventario),
                'descripcion' => "Cierre de Caja fecha: " . $fechaCierre,
                'debe' => 0.00,
                'haber' => $totalInventario,
                'turno' => $turno,
                'usuario' => $usuarioCierre,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => "Cierre " . $fechaCierre
            ];
        }
        
        // Costos de Venta (DEBE) - Agrupados por cuenta de costos
        // Los costos de servicios se mapean a sus cuentas correspondientes
        // Los costos de productos van a cuenta genérica
        $costosAgrupadosPorCuenta = [];
        foreach ($costosPorCuenta as $cuentaOrigen => $totalCosto) {
            // Si la cuenta origen es una cuenta de ingresos (servicios), mapear a cuenta de costos
            // Si es 510100101 (genérica), es de productos y se queda igual
            if ($cuentaOrigen === '510100101') {
                // Es costo de productos, usar directamente
                $cuentaCostos = $cuentaOrigen;
            } else {
                // Es costo de servicios, mapear a cuenta de costos correspondiente
                $cuentaCostos = obtenerCuentaCostos($cuentaOrigen);
            }
            
            // Agrupar por cuenta de costos final para evitar duplicados
            if (!isset($costosAgrupadosPorCuenta[$cuentaCostos])) {
                $costosAgrupadosPorCuenta[$cuentaCostos] = 0;
            }
            $costosAgrupadosPorCuenta[$cuentaCostos] += $totalCosto;
        }
        
        // Registrar costos agrupados (una sola entrada por cuenta de costos)
        foreach ($costosAgrupadosPorCuenta as $cuentaCostos => $totalCostoAgrupado) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaCostos,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaCostos),
                'descripcion' => "Cierre de Caja fecha: " . $fechaCierre,
                'debe' => $totalCostoAgrupado,
                'haber' => 0.00,
                'turno' => $turno,
                'usuario' => $usuarioCierre,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => "Cierre " . $fechaCierre
            ];
        }
        
        // Otros Ingresos (HABER) - Para balancear placa de rayos X
        // Cuando hay una placa asociada a un servicio de radiología, se registra como "Otros Ingresos"
        // para balancear correctamente la partida
        foreach ($otrosIngresosPorCuenta as $cuentaIngresos => $totalOtrosIngresos) {
            $transacciones[] = [
                'unidad_servicio' => $unidadServicio,
                'cuenta' => $cuentaIngresos,
                'nombre_cuenta' => obtenerNombreCuenta($cuentaIngresos),
                'descripcion' => "Cierre de Caja fecha: " . $fechaCierre,
                'debe' => 0.00,
                'haber' => $totalOtrosIngresos,
                'turno' => $turno,
                'usuario' => $usuarioCierre,
                'tipo_transaccion' => 'CIERRE_VENTA',
                'referencia' => "Cierre " . $fechaCierre
            ];
        }
        
        error_log("DEBUG registrarTransaccionesCierreCaja: Total transacciones a registrar: " . count($transacciones));
        
        if (empty($transacciones)) {
            error_log("ERROR registrarTransaccionesCierreCaja: No se generaron transacciones");
            return null;
        }
        
        $numeroPartida = registrarPartidaCompleta($transacciones, $fechaCierre, $fechaRegistro);
        error_log("DEBUG registrarTransaccionesCierreCaja: Partida registrada exitosamente - Número: $numeroPartida");
        
        return $numeroPartida;
        
    } catch (Exception $e) {
        error_log("ERROR al registrar transacciones de cierre de caja: " . $e->getMessage());
        error_log("ERROR stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

