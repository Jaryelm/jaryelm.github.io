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
