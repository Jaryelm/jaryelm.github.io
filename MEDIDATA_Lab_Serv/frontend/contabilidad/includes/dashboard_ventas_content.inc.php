<?php
/** Contenido compartido dashboard ventas. Variables opcionales: $dvTabSuffix ('' o '_user') */
$dvTabSuffix = $dvTabSuffix ?? '';
?>
<style>
.catalog-container.dv-dashboard .catalog-header .catalog-title { margin-bottom: 6px; }
.catalog-container.dv-dashboard .catalog-lead {
    color: #666;
    margin: 0 0 20px;
    font-size: 0.95rem;
    line-height: 1.45;
}
.dv-period-bar {
    display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
    justify-content: space-between; margin-bottom: 18px;
}
.dv-period-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.dv-period-tabs button {
    border: 2px solid #06adbf; background: #fff; color: #035c67;
    padding: 8px 18px; border-radius: 6px; cursor: pointer; font-weight: 600;
}
.dv-period-tabs button.active, .dv-period-tabs button:hover {
    background: #06adbf; color: #fff;
}
.dv-period-label { color: #555; font-size: 0.95rem; }
.dv-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px; margin-bottom: 20px;
}
.dv-card {
    background: #fff; border-radius: 10px; padding: 16px 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08); border-left: 4px solid #06adbf;
}
.dv-card h4 { margin: 0 0 6px; font-size: 0.85rem; color: #666; font-weight: 600; text-transform: uppercase; }
.dv-card .dv-val { font-size: 1.45rem; font-weight: 700; color: #035c67; }
.dv-card .dv-sub { font-size: 0.8rem; color: #888; margin-top: 4px; }
.dv-card.var-up .dv-sub { color: #28a745; }
.dv-card.var-down .dv-sub { color: #c0392b; }
.dv-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
@media (max-width: 900px) { .dv-panels { grid-template-columns: 1fr; } }
.dv-panel {
    background: #fff; border-radius: 10px; padding: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.dv-panel h3 { margin: 0 0 12px; color: #06adbf; font-size: 1.05rem; }
.dv-rank-list { list-style: none; padding: 0; margin: 0; }
.dv-rank-list li {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 8px 0; border-bottom: 1px solid #eee; gap: 10px; font-size: 0.9rem;
}
.dv-rank-list li:last-child { border-bottom: none; }
.dv-rank-list .rank-n { color: #06adbf; font-weight: 700; min-width: 1.5em; }
.dv-rank-list .rank-name { flex: 1; }
.dv-rank-list .rank-amt { white-space: nowrap; font-weight: 600; color: #035c67; }
.dv-insights { margin-bottom: 20px; }
.dv-insight {
    background: #fff; border-radius: 8px; padding: 12px 14px; margin-bottom: 10px;
    border-left: 4px solid #06adbf; box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.dv-insight.warning { border-left-color: #e67e22; background: #fffbf5; }
.dv-insight.success { border-left-color: #28a745; background: #f6fff8; }
.dv-insight strong { display: block; color: #035c67; margin-bottom: 4px; }
.dv-insight p { margin: 0; color: #444; font-size: 0.9rem; line-height: 1.45; }
.dv-table-title { color: #06adbf; margin: 0 0 12px; font-size: 1.2rem; }
.tend-subida { color: #28a745; font-weight: 600; }
.tend-baja { color: #c0392b; font-weight: 600; }
.tend-estable { color: #888; }
.tend-nuevo { color: #06adbf; font-weight: 600; }
.dv-rec-cell { max-width: 280px; font-size: 0.82rem; color: #555; line-height: 1.35; }
#dvActualizado { font-size: 0.8rem; color: #999; }
/* Tabla detalle: mismo layout que reportes contabilidad (sin scrollX de DataTables) */
.dv-dashboard .table-container .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.dv-dashboard .table-container table.dt-reporte-compras-unificado {
    width: 100% !important;
    min-width: 1100px;
    table-layout: auto;
}
.dv-dashboard .dataTables_wrapper {
    width: 100%;
}
.dv-dashboard table.dt-reporte-compras-unificado th,
.dv-dashboard table.dt-reporte-compras-unificado td {
    white-space: nowrap;
}
.dv-dashboard table.dt-reporte-compras-unificado th:nth-child(3),
.dv-dashboard table.dt-reporte-compras-unificado td:nth-child(3),
.dv-dashboard table.dt-reporte-compras-unificado th:nth-child(13),
.dv-dashboard table.dt-reporte-compras-unificado td:nth-child(13) {
    white-space: normal;
    min-width: 160px;
    max-width: 280px;
}
</style>

<div class="catalog-container dv-dashboard">
    <div class="catalog-header">
        <h2 class="catalog-title">Dashboard de Ventas</h2>
        <p class="catalog-lead">Análisis de productos y servicios cobrados — se actualiza al cambiar período (día, semana o mes en curso).</p>
    </div>

    <div class="dv-period-bar">
        <div class="dv-period-tabs">
            <button type="button" class="dv-period-btn" data-periodo="dia">Hoy</button>
            <button type="button" class="dv-period-btn" data-periodo="semana">Esta semana</button>
            <button type="button" class="dv-period-btn active" data-periodo="mes">Este mes</button>
        </div>
        <div>
            <span class="dv-period-label" id="dvPeriodoLabel">—</span>
            <span id="dvActualizado"></span>
        </div>
    </div>

    <div class="dv-cards" id="dvCards">
        <div class="dv-card"><h4>Ingresos cobrados</h4><div class="dv-val" id="dvIngresos">—</div><div class="dv-sub" id="dvVarIngresos"></div></div>
        <div class="dv-card"><h4>Ticket promedio</h4><div class="dv-val" id="dvTicket">—</div><div class="dv-sub" id="dvTransacciones">—</div></div>
        <div class="dv-card"><h4>Unidades vendidas</h4><div class="dv-val" id="dvUnidades">—</div><div class="dv-sub" id="dvProductosCount">—</div></div>
        <div class="dv-card"><h4>ISV / Descuentos</h4><div class="dv-val" id="dvIsv">—</div><div class="dv-sub" id="dvDescuentos">—</div></div>
    </div>

    <div class="dv-insights" id="dvInsights"></div>

    <div class="dv-panels">
        <div class="dv-panel">
            <h3><i class='bx bx-trending-up'></i> Top 10 — Más vendido (ingresos)</h3>
            <ul class="dv-rank-list" id="dvTopMas"></ul>
        </div>
        <div class="dv-panel">
            <h3><i class='bx bx-trending-down'></i> Top 10 — Menor aporte</h3>
            <ul class="dv-rank-list" id="dvTopMenos"></ul>
        </div>
    </div>

    <div class="dv-panel" style="margin-bottom:16px;">
        <h3>Mix por tipo y forma de pago</h3>
        <div id="dvMix" style="font-size:0.9rem;color:#444;"></div>
    </div>

    <h3 class="dv-table-title">Detalle completo por producto / servicio</h3>
    <div class="table-container">
        <div class="table-responsive">
            <table id="tablaDashboardVentas" class="display dt-reporte-compras-unificado" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Producto / Servicio</th>
                    <th>Tipo</th>
                    <th>Línea</th>
                    <th>Unidades</th>
                    <th>Ingresos</th>
                    <th>% Ingresos</th>
                    <th>Ticket línea</th>
                    <th>Facturas</th>
                    <th>Stock</th>
                    <th>Tendencia</th>
                    <th>Recomendación</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        </div>
    </div>
</div>
