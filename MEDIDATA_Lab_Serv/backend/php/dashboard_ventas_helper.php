<?php
/**
 * Helpers compartidos — Dashboard de Ventas (contabilidad / admin).
 */

if (!function_exists('medidata_dv_fmt_lempiras')) {
    function medidata_dv_fmt_lempiras($valor): string
    {
        return 'L. ' . number_format((float) ($valor ?? 0), 2, '.', ',');
    }
}

if (!function_exists('medidata_dv_period_dates')) {
    /**
     * @return array{periodo:string,desde:string,hasta:string,prev_desde:string,prev_hasta:string,label:string}
     */
    function medidata_dv_period_dates(string $periodo): array
    {
        $periodo = in_array($periodo, ['dia', 'semana', 'mes'], true) ? $periodo : 'mes';
        $tz = new DateTimeZone('America/Tegucigalpa');
        $today = new DateTime('today', $tz);

        if ($periodo === 'dia') {
            $desde = clone $today;
            $hasta = clone $today;
            $label = 'Hoy — ' . $today->format('d/m/Y');
        } elseif ($periodo === 'semana') {
            $desde = clone $today;
            $desde->modify('monday this week');
            if ($desde > $today) {
                $desde->modify('-7 days');
            }
            $hasta = clone $today;
            $label = 'Semana del ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y');
        } else {
            $desde = new DateTime($today->format('Y-m-01'), $tz);
            $hasta = clone $today;
            $label = 'Mes en curso — ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y');
        }

        $days = (int) $desde->diff($hasta)->days + 1;
        $prevHasta = (clone $desde)->modify('-1 day');
        $prevDesde = (clone $prevHasta)->modify('-' . ($days - 1) . ' days');

        return [
            'periodo' => $periodo,
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
            'prev_desde' => $prevDesde->format('Y-m-d'),
            'prev_hasta' => $prevHasta->format('Y-m-d'),
            'label' => $label,
        ];
    }
}

if (!function_exists('medidata_dv_orders_filter_sql')) {
    function medidata_dv_orders_filter_sql(string $alias = 'o'): string
    {
        return "{$alias}.invoice_status = 'Cobrada'
            AND {$alias}.invoice_number IS NOT NULL
            AND TRIM({$alias}.invoice_number) <> ''";
    }
}

if (!function_exists('medidata_dv_agg_subquery')) {
    /**
     * Subconsulta agregada reutilizable (requiere PDO solo para fechas en bind externo).
     */
    function medidata_dv_agg_subquery(string $desdeParam = ':desde', string $hastaParam = ':hasta'): string
    {
        return "
            SELECT
                od.codpro AS codigo,
                od.descripcion AS nombre,
                od.item_type AS tipo,
                COALESCE(
                    NULLIF(TRIM(p.linea), ''),
                    NULLIF(TRIM(ah.linea), ''),
                    NULLIF(TRIM(s.categoria_servicio), ''),
                    '—'
                ) AS linea,
                SUM(od.cantidad) AS unidades,
                SUM(od.total_after_discount) AS ingresos,
                COUNT(DISTINCT od.order_id) AS facturas,
                MAX(
                    CASE
                        WHEN od.hospitalario_id IS NOT NULL THEN ah.stock
                        WHEN od.product_id IS NOT NULL THEN p.stock
                        ELSE NULL
                    END
                ) AS stock
            FROM order_details od
            INNER JOIN orders o ON o.idord = od.order_id
            LEFT JOIN product p ON p.idprcd = od.product_id AND od.hospitalario_id IS NULL
            LEFT JOIN almacen_hospitalario ah ON ah.idprcd = od.hospitalario_id
            LEFT JOIN servicios_hospital s ON s.id = od.service_id
            WHERE " . medidata_dv_orders_filter_sql('o') . "
              AND DATE(o.placed_on) BETWEEN {$desdeParam} AND {$hastaParam}
            GROUP BY od.codpro, od.descripcion, od.item_type,
                COALESCE(
                    NULLIF(TRIM(p.linea), ''),
                    NULLIF(TRIM(ah.linea), ''),
                    NULLIF(TRIM(s.categoria_servicio), ''),
                    '—'
                )
        ";
    }
}

if (!function_exists('medidata_dv_tendencia')) {
    function medidata_dv_tendencia(float $actual, float $anterior): array
    {
        if ($anterior <= 0 && $actual > 0) {
            return ['key' => 'nuevo', 'label' => 'Nuevo', 'class' => 'tend-nuevo'];
        }
        if ($anterior <= 0) {
            return ['key' => 'estable', 'label' => 'Sin ventas', 'class' => 'tend-estable'];
        }
        $pct = (($actual - $anterior) / $anterior) * 100;
        if ($pct >= 5) {
            return ['key' => 'subida', 'label' => '↑ ' . number_format($pct, 1) . '%', 'class' => 'tend-subida'];
        }
        if ($pct <= -5) {
            return ['key' => 'baja', 'label' => '↓ ' . number_format(abs($pct), 1) . '%', 'class' => 'tend-baja'];
        }
        return ['key' => 'estable', 'label' => 'Estable', 'class' => 'tend-estable'];
    }
}

if (!function_exists('medidata_dv_recomendacion')) {
    function medidata_dv_recomendacion(array $row, float $pctIngresos, array $tendencia): string
    {
        $stock = $row['stock'];
        $tipo = $row['tipo'] ?? '';

        if ($tendencia['key'] === 'baja' && $pctIngresos >= 5) {
            return 'Revisar precio, promoción o visibilidad; pierde participación.';
        }
        if ($tendencia['key'] === 'subida' && $pctIngresos >= 10) {
            return 'Mantener stock y destacar en mostrador; alta demanda.';
        }
        if ($tipo === 'producto' && $stock !== null && (float) $stock <= 5 && (float) $row['unidades'] > 0) {
            return 'Stock bajo: considerar reorden urgente.';
        }
        if ($tendencia['key'] === 'nuevo') {
            return 'Producto/servicio nuevo en el período; monitorear desempeño.';
        }
        if ($pctIngresos < 1 && (float) $row['ingresos'] > 0) {
            return 'Bajo aporte; evaluar si conviene mantener en catálogo.';
        }
        return 'Desempeño dentro de lo esperado.';
    }
}
