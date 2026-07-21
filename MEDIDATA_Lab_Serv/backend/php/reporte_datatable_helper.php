<?php
/**
 * Helpers compartidos para endpoints DataTables server-side de reportes.
 * Incluye lógica de Cuadre Caja, Detalle Pago y Devoluciones (un solo archivo para despliegue).
 */

if (!function_exists('medidata_reporte_fmt_lempiras')) {
    function medidata_reporte_fmt_lempiras($valor): string
    {
        return 'L. ' . number_format((float) ($valor ?? 0), 2, '.', ',');
    }
}

if (!function_exists('medidata_reporte_dt_like_params')) {
    /**
     * @param array<string, string> $params
     * @param array<int, string> $placeholderNames
     */
    function medidata_reporte_dt_like_params(array &$params, string $searchValue, array $placeholderNames): void
    {
        $like = '%' . $searchValue . '%';
        foreach ($placeholderNames as $name) {
            $params[$name] = $like;
        }
    }
}

if (!function_exists('medidata_reporte_dt_limit_sql')) {
    function medidata_reporte_dt_limit_sql(int $start, int $length): string
    {
        $start = max(0, $start);
        $length = max(1, min($length, 100));
        return ' LIMIT ' . $start . ', ' . $length;
    }
}

if (!function_exists('medidata_reporte_dt_json_error')) {
    function medidata_reporte_dt_json_error(int $draw, string $logContext, Throwable $e): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
        }
        error_log($logContext . ': ' . $e->getMessage());
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Error al cargar los datos del reporte.',
        ], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('medidata_reporte_tipo_descuento_predominante')) {
    function medidata_reporte_tipo_descuento_predominante(array $row): string
    {
        $categorias = [
            'Edad 30%' => (float) ($row['desc_edad_30'] ?? 0),
            'Edad 40%' => (float) ($row['desc_edad_40'] ?? 0),
            'Promoción' => (float) ($row['desc_promocion'] ?? 0),
            'Otros' => (float) ($row['desc_otros'] ?? 0),
            'Porcentaje' => (float) ($row['desc_porcentaje'] ?? 0),
        ];
        $maxNombre = '-';
        $maxValor = 0.0;
        foreach ($categorias as $nombre => $valor) {
            if ($valor > $maxValor) {
                $maxValor = $valor;
                $maxNombre = $nombre;
            }
        }
        return $maxValor > 0 ? $maxNombre : '-';
    }
}

if (!function_exists('medidata_reporte_detalle_pago_has_tel_column')) {
    function medidata_reporte_detalle_pago_has_tel_column(PDO $connect): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $cached = $connect->query("SHOW COLUMNS FROM orders LIKE 'telefono_paciente'")->rowCount() > 0;
        } catch (Throwable $e) {
            $cached = false;
        }
        return $cached;
    }
}

if (!function_exists('medidata_reporte_detalle_pago_build_where')) {
    /**
     * @param array<string, string> $params
     */
    function medidata_reporte_detalle_pago_build_where(string $desde, string $hasta, string $searchValue, array &$params): string
    {
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

        return $whereExtra;
    }
}

if (!function_exists('medidata_reporte_detalle_pago_format_row')) {
    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    function medidata_reporte_detalle_pago_format_row(array $row): array
    {
        $fhRaw = $row['placed_on'] ?? '';
        $celular = trim((string) ($row['telefono_paciente'] ?? ''));
        if ($celular === '') {
            $celular = trim((string) ($row['paciente_phon'] ?? ''));
        }
        if ($celular === '') {
            $celular = '-';
        }

        return [
            'fecha' => $fhRaw ? date('d-m-Y', strtotime($fhRaw)) : '-',
            'fecha_iso' => (string) $fhRaw,
            'hora' => $fhRaw ? date('H:i', strtotime($fhRaw)) : '-',
            'forma_pago' => (string) ($row['method'] ?? '-'),
            'estado' => (string) ($row['invoice_status'] ?? '-'),
            'factura' => !empty($row['invoice_number']) ? (string) $row['invoice_number'] : '-',
            'detalle_examen' => (string) ($row['detalle_examen'] ?? '-'),
            'cliente' => (string) ($row['nomcl'] ?? '-'),
            'cliente_celular' => $celular,
            'tipo_descuento' => medidata_reporte_tipo_descuento_predominante($row),
            'subtotal' => medidata_reporte_fmt_lempiras($row['price_without_discount'] ?? 0),
            'descuento' => medidata_reporte_fmt_lempiras($row['discount_amount'] ?? 0),
            'impuesto' => medidata_reporte_fmt_lempiras($row['tax_amount'] ?? 0),
            'total' => medidata_reporte_fmt_lempiras($row['total_price'] ?? 0),
        ];
    }
}

if (!function_exists('medidata_reporte_detalle_pago_export_headers')) {
    /** @return array<int, string> */
    function medidata_reporte_detalle_pago_export_headers(): array
    {
        return [
            'Fecha',
            'Hora',
            'Forma de Pago',
            'Estado',
            'Num. Factura',
            'Detalle Examen',
            'Cliente',
            'Cliente Celular',
            'Tipo de Descuento',
            'Sub Total',
            'Descuento',
            'Impuesto',
            'Total',
        ];
    }
}

if (!function_exists('medidata_reporte_detalle_pago_row_to_export')) {
    /**
     * @param array<string, string> $formatted
     * @return array<int, string>
     */
    function medidata_reporte_detalle_pago_row_to_export(array $formatted): array
    {
        return [
            $formatted['fecha'],
            $formatted['hora'],
            $formatted['forma_pago'],
            $formatted['estado'],
            $formatted['factura'],
            $formatted['detalle_examen'],
            $formatted['cliente'],
            $formatted['cliente_celular'],
            $formatted['tipo_descuento'],
            $formatted['subtotal'],
            $formatted['descuento'],
            $formatted['impuesto'],
            $formatted['total'],
        ];
    }
}

if (!function_exists('medidata_reporte_detalle_pago_prepare_export_stmt')) {
    /**
     * @param array<string, string> $params
     */
    function medidata_reporte_detalle_pago_prepare_export_stmt(
        PDO $connect,
        string $desde,
        string $hasta,
        string $searchValue,
        array &$params,
        int $orderColumn = 0,
        string $orderDir = 'DESC'
    ): PDOStatement {
        $baseFrom = medidata_reporte_detalle_pago_from_sql($connect);
        $selectSql = medidata_reporte_detalle_pago_select_sql($connect);

        $whereExtra = medidata_reporte_detalle_pago_build_where($desde, $hasta, $searchValue, $params);
        $fromWhere = $baseFrom . $whereExtra;

        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
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

        $query = $selectSql . $fromWhere . " ORDER BY {$orderBy} {$orderDir}, o.idord DESC";
        $stmt = $connect->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt;
    }
}

if (!function_exists('medidata_reporte_detalle_pago_stream_rows')) {
    /**
     * @return Generator<int, array<string, string>>
     */
    function medidata_reporte_detalle_pago_stream_rows(
        PDO $connect,
        string $desde,
        string $hasta,
        string $searchValue = '',
        int $orderColumn = 0,
        string $orderDir = 'DESC'
    ): Generator {
        $params = [];
        $stmt = medidata_reporte_detalle_pago_prepare_export_stmt(
            $connect,
            $desde,
            $hasta,
            $searchValue,
            $params,
            $orderColumn,
            $orderDir
        );

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield medidata_reporte_detalle_pago_format_row($row);
        }
    }
}

if (!function_exists('medidata_reporte_detalle_pago_fetch_rows')) {
    /**
     * @return array<int, array<string, string>>
     */
    function medidata_reporte_detalle_pago_fetch_rows(
        PDO $connect,
        string $desde,
        string $hasta,
        string $searchValue = '',
        int $orderColumn = 0,
        string $orderDir = 'DESC'
    ): array {
        $rows = [];
        foreach (medidata_reporte_detalle_pago_stream_rows($connect, $desde, $hasta, $searchValue, $orderColumn, $orderDir) as $row) {
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('medidata_reporte_detalle_pago_from_sql')) {
    function medidata_reporte_detalle_pago_from_sql(PDO $connect): string
    {
        return "
            FROM orders o
            LEFT JOIN patients p ON p.numhs = o.dni_paciente
            LEFT JOIN (
                SELECT
                    order_id,
                    GROUP_CONCAT(descripcion SEPARATOR ', ') AS detalle_examen,
                    SUM(COALESCE(age_discount_30, 0)) AS desc_edad_30,
                    SUM(COALESCE(age_discount_40, 0)) AS desc_edad_40,
                    SUM(COALESCE(promotion_discount, 0)) AS desc_promocion,
                    SUM(COALESCE(other_discount, 0)) AS desc_otros,
                    SUM(COALESCE(discount_percentage, 0)) AS desc_porcentaje
                FROM order_details
                GROUP BY order_id
            ) det ON det.order_id = o.idord
            WHERE 1=1
        ";
    }
}

if (!function_exists('medidata_reporte_detalle_pago_select_sql')) {
    function medidata_reporte_detalle_pago_select_sql(PDO $connect): string
    {
        $exprTel = medidata_reporte_detalle_pago_has_tel_column($connect)
            ? 'o.telefono_paciente'
            : 'NULL';

        return "
            SELECT
                o.idord,
                o.invoice_number,
                o.placed_on,
                o.method,
                o.invoice_status,
                o.nomcl,
                {$exprTel} AS telefono_paciente,
                o.price_without_discount,
                o.discount_amount,
                o.total_price,
                COALESCE(o.tax_amount, 0) AS tax_amount,
                p.phon AS paciente_phon,
                det.detalle_examen,
                det.desc_edad_30,
                det.desc_edad_40,
                det.desc_promocion,
                det.desc_otros,
                det.desc_porcentaje
        ";
    }
}

if (!function_exists('medidata_reporte_cuadre_caja_metodo_norm_sql')) {
    function medidata_reporte_cuadre_caja_metodo_norm_sql(): string
    {
        return "COALESCE(NULLIF(TRIM(o.method), ''), 'Efectivo')";
    }
}

if (!function_exists('medidata_reporte_cuadre_caja_base_where')) {
    /**
     * @param array<string, string> $params
     */
    function medidata_reporte_cuadre_caja_base_where(?string $desde, ?string $hasta, array &$params): string
    {
        $where = "
            FROM orders o
            WHERE o.invoice_status = 'Cobrada'
              AND o.invoice_number IS NOT NULL
              AND o.invoice_number <> ''
              AND o.processed_by IS NOT NULL
              AND o.processed_by <> ''
        ";

        if ($desde !== null && $desde !== '' && $hasta !== null && $hasta !== '') {
            $where .= ' AND DATE(o.placed_on) BETWEEN :desde AND :hasta';
            $params[':desde'] = $desde;
            $params[':hasta'] = $hasta;
        } elseif ($desde !== null && $desde !== '') {
            $where .= ' AND DATE(o.placed_on) >= :desde';
            $params[':desde'] = $desde;
        } elseif ($hasta !== null && $hasta !== '') {
            $where .= ' AND DATE(o.placed_on) <= :hasta';
            $params[':hasta'] = $hasta;
        }

        return $where;
    }
}

if (!function_exists('medidata_reporte_cuadre_caja_grouped_sql')) {
    function medidata_reporte_cuadre_caja_grouped_sql(): string
    {
        $metodo = medidata_reporte_cuadre_caja_metodo_norm_sql();

        return "
            SELECT
                DATE(o.placed_on) AS fecha,
                CONCAT(DATE(o.placed_on), ' 23:59:59') AS fecha_orden,
                o.processed_by AS nombre,
                SUM(CASE WHEN {$metodo} = 'Efectivo' THEN o.total_price ELSE 0 END) AS efectivo,
                SUM(CASE WHEN {$metodo} = 'Tarjeta' THEN o.total_price ELSE 0 END) AS tarjeta,
                SUM(CASE WHEN {$metodo} NOT IN ('Efectivo', 'Tarjeta') THEN o.total_price ELSE 0 END) AS otros
        ";
    }
}

if (!function_exists('medidata_reporte_cuadre_caja_search_where')) {
    /**
     * @param array<string, string> $params
     */
    function medidata_reporte_cuadre_caja_search_where(string $searchValue, array &$params): string
    {
        if ($searchValue === '') {
            return '';
        }

        $params[':searchNombre'] = '%' . $searchValue . '%';
        $params[':searchFecha'] = '%' . $searchValue . '%';

        return " AND (
            cuadre.nombre LIKE :searchNombre
            OR CAST(cuadre.fecha AS CHAR) LIKE :searchFecha
        )";
    }
}

if (!function_exists('medidata_reporte_devoluciones_fmt_motivo')) {
    function medidata_reporte_devoluciones_fmt_motivo($motivo): string
    {
        if ($motivo === null || $motivo === '') {
            return '-';
        }
        $motivo = (string) $motivo;
        if (strpos($motivo, '_') !== false) {
            return ucwords(str_replace('_', ' ', $motivo));
        }
        return $motivo;
    }
}

if (!function_exists('medidata_reporte_devoluciones_cols_anulacion')) {
    /**
     * @return array{expr_fecha: string, expr_anulpor: string, expr_obs: string}
     */
    function medidata_reporte_devoluciones_cols_anulacion(PDO $connect): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $colAnuladaAt = false;
        $colAnuladaPor = false;
        $colObs = false;
        try {
            $colAnuladaAt = $connect->query("SHOW COLUMNS FROM orders LIKE 'anulada_at'")->rowCount() > 0;
            $colAnuladaPor = $connect->query("SHOW COLUMNS FROM orders LIKE 'anulada_por'")->rowCount() > 0;
            $colObs = $connect->query("SHOW COLUMNS FROM orders LIKE 'observacion_anulacion'")->rowCount() > 0;
        } catch (Throwable $e) {
            // ignorar
        }

        $cache = [
            'expr_fecha' => $colAnuladaAt ? 'COALESCE(o.anulada_at, o.placed_on)' : 'o.placed_on',
            'expr_anulpor' => $colAnuladaPor ? 'o.anulada_por' : 'NULL',
            'expr_obs' => $colObs ? 'o.observacion_anulacion' : 'NULL',
        ];

        return $cache;
    }
}

if (!function_exists('medidata_reporte_devoluciones_union_sql')) {
    function medidata_reporte_devoluciones_union_sql(PDO $connect): string
    {
        $cols = medidata_reporte_devoluciones_cols_anulacion($connect);
        $exprFecha = $cols['expr_fecha'];
        $exprAnulpor = $cols['expr_anulpor'];
        $exprObs = $cols['expr_obs'];

        $sqlReturns = "
            SELECT
                r.return_date AS fecha,
                'Devolución' AS tipo_evento,
                o.idord,
                o.invoice_number,
                o.nomcl,
                r.product_id,
                r.quantity_returned AS cantidad,
                r.return_reason AS motivo,
                r.processed_by AS procesado_por,
                od.descripcion AS detalle,
                od.item_type,
                od.total_after_discount AS valor_item
            FROM returns r
            LEFT JOIN orders o ON o.idord = r.order_id
            LEFT JOIN order_details od
                ON od.order_id = r.order_id
                AND (
                    od.codpro = r.product_id
                    OR CAST(od.id AS CHAR) = r.product_id
                )
        ";

        $sqlAnuladas = "
            SELECT
                {$exprFecha} AS fecha,
                'Anulación' AS tipo_evento,
                o.idord,
                o.invoice_number,
                o.nomcl,
                NULL AS product_id,
                NULL AS cantidad,
                {$exprObs} AS motivo,
                {$exprAnulpor} AS procesado_por,
                NULL AS detalle,
                NULL AS item_type,
                o.total_price AS valor_item
            FROM orders o
            WHERE o.invoice_status = 'Anulada'
        ";

        return '(' . $sqlReturns . ') UNION ALL (' . $sqlAnuladas . ')';
    }
}

if (!function_exists('medidata_reporte_devoluciones_format_row')) {
    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    function medidata_reporte_devoluciones_format_row(array $row): array
    {
        $fechaRaw = (string) ($row['fecha'] ?? '');
        $tipoEvento = (string) ($row['tipo_evento'] ?? '-');

        if ($tipoEvento === 'Anulación') {
            $tipoItem = 'Toda la factura';
            $descripcion = 'Factura completa anulada';
            $cantidad = '-';
        } else {
            $it = strtolower((string) ($row['item_type'] ?? ''));
            if ($it === 'producto') {
                $tipoItem = 'Producto';
            } elseif ($it === 'servicio') {
                $tipoItem = 'Servicio';
            } else {
                $tipoItem = '-';
            }
            $descripcion = (string) ($row['detalle'] ?? ($row['product_id'] ?? '-'));
            $cantidad = (string) ($row['cantidad'] ?? '-');
        }

        return [
            'fecha' => $fechaRaw ? date('d-m-Y H:i', strtotime($fechaRaw)) : '-',
            'fecha_iso' => $fechaRaw,
            'tipo' => $tipoEvento,
            'num_orden' => (string) ($row['idord'] ?? '-'),
            'num_factura' => (string) ($row['invoice_number'] ?? '-'),
            'cliente' => (string) ($row['nomcl'] ?? '-'),
            'tipo_item' => $tipoItem,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'motivo' => medidata_reporte_devoluciones_fmt_motivo($row['motivo'] ?? null),
            'procesado_por' => (string) ($row['procesado_por'] ?? '-'),
            'valor' => medidata_reporte_fmt_lempiras($row['valor_item'] ?? 0),
        ];
    }
}

if (!function_exists('medidata_reporte_compras_tiene_npc')) {
    function medidata_reporte_compras_tiene_npc(PDO $connect): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $cached = (bool) $connect->query("SHOW COLUMNS FROM compras LIKE 'numero_partida_contable'")->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $cached = false;
        }
        return $cached;
    }
}

if (!function_exists('medidata_reporte_compras_ingresadas_build_where')) {
    /**
     * @param array<string, string> $params
     */
    function medidata_reporte_compras_ingresadas_build_where(
        string $desde,
        string $hasta,
        string $searchValue,
        PDO $connect,
        array &$params
    ): string {
        $fromWhere = ' FROM compras c WHERE DATE(c.fecha_emision) BETWEEN :desde AND :hasta';
        $params[':desde'] = $desde;
        $params[':hasta'] = $hasta;

        if ($searchValue !== '') {
            $searchFields = [':searchId', ':searchProv', ':searchFac'];
            $fromWhere .= ' AND (CAST(c.id_compra AS CHAR) LIKE :searchId OR c.prov_datos LIKE :searchProv OR c.dato_fac LIKE :searchFac';
            if (medidata_reporte_compras_tiene_npc($connect)) {
                $fromWhere .= ' OR c.numero_partida_contable LIKE :searchNpc';
                $searchFields[] = ':searchNpc';
            }
            $fromWhere .= ')';
            medidata_reporte_dt_like_params($params, $searchValue, $searchFields);
        }

        return $fromWhere;
    }
}

if (!function_exists('medidata_reporte_compras_ingresadas_format_row')) {
    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    function medidata_reporte_compras_ingresadas_format_row(array $row): array
    {
        $fechaRaw = $row['fecha_emision'] ?? '';

        return [
            (string) ($row['id_compra'] ?? ''),
            $fechaRaw ? date('d-m-Y', strtotime((string) $fechaRaw)) : '-',
            (string) ($row['prov_datos'] ?? '-'),
            (string) ($row['dato_fac'] ?? '-'),
            medidata_reporte_fmt_lempiras($row['isv_global'] ?? 0),
            medidata_reporte_fmt_lempiras($row['sub_total'] ?? 0),
            medidata_reporte_fmt_lempiras($row['total'] ?? 0),
            (string) ($row['numero_partida_contable'] ?? ''),
        ];
    }
}

if (!function_exists('medidata_reporte_compras_ingresadas_export_headers')) {
    /** @return array<int, string> */
    function medidata_reporte_compras_ingresadas_export_headers(): array
    {
        return [
            'Numero de Orden',
            'Fecha',
            'Proveedor',
            'Num. Factura',
            'Impuesto',
            'SubTotal',
            'Total',
            'Partida contable',
        ];
    }
}

if (!function_exists('medidata_reporte_compras_ingresadas_stream_rows')) {
    /**
     * @return Generator<int, array<int, string>>
     */
    function medidata_reporte_compras_ingresadas_stream_rows(
        PDO $connect,
        string $desde,
        string $hasta,
        string $searchValue = ''
    ): Generator {
        $params = [];
        $fromWhere = medidata_reporte_compras_ingresadas_build_where($desde, $hasta, $searchValue, $connect, $params);
        $sqlNpc = medidata_reporte_compras_tiene_npc($connect)
            ? 'c.numero_partida_contable'
            : 'NULL AS numero_partida_contable';

        $query = "SELECT c.id_compra, c.fecha_emision, c.prov_datos, c.dato_fac, c.isv_global, c.sub_total, c.total, {$sqlNpc}"
            . $fromWhere
            . ' ORDER BY c.fecha_emision DESC, c.id_compra DESC';

        $stmt = $connect->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield medidata_reporte_compras_ingresadas_format_row($row);
        }
    }
}

if (!function_exists('medidata_reporte_compras_detalladas_build_where')) {
    /**
     * @param array<string, string> $params
     */
    function medidata_reporte_compras_detalladas_build_where(
        string $desde,
        string $hasta,
        string $searchValue,
        array &$params
    ): string {
        $fromJoin = " FROM compras c
            LEFT JOIN (
                SELECT id_compra,
                    SUM(CASE WHEN COALESCE(exento,0) = 1 THEN COALESCE(subtotal,0) ELSE 0 END) AS sum_exenta,
                    SUM(CASE WHEN COALESCE(gravado,0) = 1
                        OR (COALESCE(exento,0) = 0 AND COALESCE(gravado,0) = 0)
                        THEN COALESCE(subtotal,0) ELSE 0 END) AS sum_gravada
                FROM detalle_compras
                GROUP BY id_compra
            ) d ON d.id_compra = c.id_compra
            WHERE DATE(c.fecha_emision) BETWEEN :desde AND :hasta";

        $params[':desde'] = $desde;
        $params[':hasta'] = $hasta;

        if ($searchValue !== '') {
            $fromJoin .= ' AND (CAST(c.id_compra AS CHAR) LIKE :searchId OR c.prov_datos LIKE :searchProv OR c.dato_fac LIKE :searchFac)';
            medidata_reporte_dt_like_params($params, $searchValue, [':searchId', ':searchProv', ':searchFac']);
        }

        return $fromJoin;
    }
}

if (!function_exists('medidata_reporte_compras_detalladas_format_row')) {
    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    function medidata_reporte_compras_detalladas_format_row(array $row): array
    {
        $fechaRaw = $row['fecha_emision'] ?? '';

        return [
            $fechaRaw ? date('d-m-Y', strtotime((string) $fechaRaw)) : '-',
            (string) ($row['prov_datos'] ?? '-'),
            (string) ($row['dato_fac'] ?? '-'),
            medidata_reporte_fmt_lempiras($row['isv_global'] ?? 0),
            'L. -',
            medidata_reporte_fmt_lempiras($row['sum_exenta'] ?? 0),
            medidata_reporte_fmt_lempiras($row['sum_gravada'] ?? 0),
            medidata_reporte_fmt_lempiras($row['sub_total'] ?? 0),
            medidata_reporte_fmt_lempiras($row['total'] ?? 0),
        ];
    }
}

if (!function_exists('medidata_reporte_compras_detalladas_export_headers')) {
    /** @return array<int, string> */
    function medidata_reporte_compras_detalladas_export_headers(): array
    {
        return [
            'Fecha',
            'Proveedor',
            'Num. Factura',
            'Impuesto',
            'Retencion',
            'Exenta',
            'Gravada',
            'SubTotal',
            'Total',
        ];
    }
}

if (!function_exists('medidata_reporte_compras_detalladas_stream_rows')) {
    /**
     * @return Generator<int, array<int, string>>
     */
    function medidata_reporte_compras_detalladas_stream_rows(
        PDO $connect,
        string $desde,
        string $hasta,
        string $searchValue = ''
    ): Generator {
        $params = [];
        $fromJoin = medidata_reporte_compras_detalladas_build_where($desde, $hasta, $searchValue, $params);

        $query = "SELECT c.id_compra, c.fecha_emision, c.prov_datos, c.dato_fac, c.isv_global, c.sub_total, c.total,
            COALESCE(d.sum_exenta, 0) AS sum_exenta,
            COALESCE(d.sum_gravada, 0) AS sum_gravada"
            . $fromJoin
            . ' ORDER BY c.fecha_emision DESC, c.id_compra DESC';

        $stmt = $connect->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield medidata_reporte_compras_detalladas_format_row($row);
        }
    }
}

if (!function_exists('medidata_reporte_compras_export_stream')) {
    /**
     * @return Generator<int, array<int, string>>
     */
    function medidata_reporte_compras_export_stream(
        PDO $connect,
        string $report,
        string $desde,
        string $hasta,
        string $searchValue = ''
    ): Generator {
        if ($report === 'detalladas') {
            yield from medidata_reporte_compras_detalladas_stream_rows($connect, $desde, $hasta, $searchValue);
            return;
        }
        yield from medidata_reporte_compras_ingresadas_stream_rows($connect, $desde, $hasta, $searchValue);
    }
}

if (!function_exists('medidata_reporte_compras_export_headers')) {
    /** @return array<int, string> */
    function medidata_reporte_compras_export_headers(string $report): array
    {
        return $report === 'detalladas'
            ? medidata_reporte_compras_detalladas_export_headers()
            : medidata_reporte_compras_ingresadas_export_headers();
    }
}

if (!function_exists('medidata_reporte_compras_export_fetch_rows')) {
    /**
     * @return array<int, array<int, string>>
     */
    function medidata_reporte_compras_export_fetch_rows(
        PDO $connect,
        string $report,
        string $desde,
        string $hasta,
        string $searchValue = ''
    ): array {
        $rows = [];
        foreach (medidata_reporte_compras_export_stream($connect, $report, $desde, $hasta, $searchValue) as $row) {
            $rows[] = $row;
        }
        return $rows;
    }
}
