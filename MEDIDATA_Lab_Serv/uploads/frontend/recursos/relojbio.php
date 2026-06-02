<?php

include_once '../../backend/registros/session_check.php';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <title>MEDIDATA</title>
    <style>
        .rb-card { background: #fff; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .rb-alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1rem; }
        .rb-alert.error { background: #fde8e8; border: 1px solid #f5b5b5; color: #8a1f1f; }
        .rb-alert.ok { background: #e6f7f4; border: 1px solid #7fcdbe; color: #0d4f44; }
        .rb-meta { font-size: 0.9rem; color: #555; margin-bottom: 0.5rem; }
        .rb-dash { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
        .rb-stat { background: linear-gradient(135deg, #0d6f7e 0%, #0a5561 100%); color: #fff; border-radius: 8px; padding: 1rem 1.1rem; }
        .rb-stat .rb-stat-label { font-size: 0.8rem; opacity: 0.9; margin-bottom: 0.35rem; }
        .rb-stat .rb-stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .rb-stat.rb-stat-muted { background: #f4f8f9; color: #0d4f44; border: 1px solid #cde8e4; }
        .rb-stat.rb-stat-muted .rb-stat-value { font-size: 1rem; font-weight: 600; }
        .rb-table-wrap { overflow-x: auto; }
        table.rb-table { width: 100%; border-collapse: collapse; }
        table.rb-table th, table.rb-table td { border: 1px solid #ddd; padding: 0.5rem 0.75rem; text-align: left; }
        table.rb-table th { background: #0d6f7e; color: #fff; }
        table.rb-table tbody tr:nth-child(even) { background: #f9f9f9; }
        #table_reloj_marcas_wrapper .dataTables_length,
        #table_reloj_marcas_wrapper .dataTables_filter { margin-bottom: 0.75rem; }
        #table_reloj_marcas_wrapper .dataTables_info,
        #table_reloj_marcas_wrapper .dataTables_paginate { margin-top: 0.75rem; }
        .rb-filter-panel {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.75rem 1rem;
            background: #f7f9fb;
            border: 1px solid #dfe6eb;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .rb-filter-field label {
            display: block;
            font-size: 0.8rem;
            color: #0a5561;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .rb-filter-field {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .rb-filter-field input[type="date"] {
            min-width: 150px;
            padding: 0.35rem 0.45rem;
            border: 1px solid #c9d5df;
            border-radius: 4px;
            background: #fff;
            margin: 0 !important; /* pisa admin.css global input[type=date] */
            height: 34px;
            line-height: 34px;
        }
        .rb-btn {
            border: 0;
            border-radius: 4px;
            padding: 0.48rem 0.8rem;
            font-size: 0.85rem;
            cursor: pointer;
            color: #fff;
            margin: 0 !important;
            height: 34px;
            display: inline-flex;
            align-items: center;
        }
        .rb-filter-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-start;
            justify-content: flex-end;
            min-height: 57px;
            align-self: flex-end;
        }
        .rb-filter-actions > div {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .rb-btn-search { background: #0d6f7e; }
        .rb-btn-clear { background: #6b7280; }
        .rb-table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            margin-bottom: 0.45rem;
            flex-wrap: wrap;
        }
        .rb-table-toolbar-left,
        .rb-table-toolbar-right {
            display: flex;
            align-items: flex-end;
            gap: 0.65rem;
            flex-wrap: wrap;
        }
        .rb-table-toolbar-left {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.45rem;
        }
        .rb-table-toolbar-right {
            margin-left: auto;
        }
        #table_reloj_marcas_wrapper .dt-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 0;
        }
        #table_reloj_marcas_wrapper .dt-button {
            background: #f3f4f6 !important;
            border: 1px solid #d1d5db !important;
            border-radius: 4px !important;
            box-shadow: none !important;
            color: #111827 !important;
            padding: 0.3rem 0.65rem !important;
            font-size: 0.8rem !important;
        }
        #table_reloj_marcas_wrapper .dataTables_length {
            margin-bottom: 0;
            width: auto;
            display: inline-flex;
            align-items: center;
        }
        #table_reloj_marcas_wrapper .dataTables_length select {
            width: auto !important;
            min-width: 64px !important;
            max-width: 78px !important;
            padding: 0.15rem 0.25rem !important;
            display: inline-block !important;
            flex: 0 0 auto !important;
            margin: 0 0.35rem !important;
        }
        #table_reloj_marcas_wrapper .dataTables_filter {
            margin-bottom: 0;
            margin-left: auto;
        }
        #table_reloj_marcas_wrapper .dataTables_length label,
        #table_reloj_marcas_wrapper .dataTables_filter label {
            margin-bottom: 0;
            white-space: nowrap;
        }
        #table_reloj_marcas_wrapper .dataTables_length label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0 !important;
        }
        #table_reloj_marcas_wrapper .dataTables_length,
        #table_reloj_marcas_wrapper .dataTables_filter {
            float: none !important;
            text-align: left !important;
        }
    </style>
</head>
<body>

<?php include_once '../admin/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        if ($hora_actual >= 6 && $hora_actual < 12) {
            $saludo = 'Buenos Días';
        } elseif ($hora_actual >= 12 && $hora_actual < 18) {
            $saludo = 'Buenas Tardes';
        } else {
            $saludo = 'Buenas Noches';
        }
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? 'Usuario') . '</strong>'; ?></h1>

        <div class="rb-card">
            <?php
            require_once __DIR__ . '/../../backend/php/biometric_agent_secret_bootstrap.php';
            medidata_lab_opt_env_from_local_file();

            require_once __DIR__ . '/../../backend/php/biometric_marcas_db.php';

            $siteCode = medidata_biometric_resolve_site_code();
            $dbError = null;
            $stats = ['total' => 0, 'ultima' => null, 'hoy' => 0];
            $dbOk = isset($connect) && $connect instanceof PDO;

            if (!$dbOk) {
                $dbError = 'No hay conexión a la base de datos.';
            } else {
                $stats = medidata_biometric_fetch_marcas_stats_db($connect, $siteCode);
            }
            ?>

            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:1rem;">
                <h2 style="margin:0;color:#000;">Marcaciones biométricas</h2>
                <button type="button" class="register-btn" onclick="location.reload(true);">Actualizar</button>
            </div>

            <p class="rb-meta">
                Registros sincronizados desde la sede al reloj <strong>MB360</strong> (agente → <code>biometric_marcas</code>).
                Sitio: <strong><?php echo htmlspecialchars($siteCode, ENT_QUOTES, 'UTF-8'); ?></strong>.
            </p>

            <?php if ($dbError !== null): ?>
                <div class="rb-alert error"><strong>Error</strong><br><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php elseif ((int) $stats['total'] === 0): ?>
                <div class="rb-alert ok">Sin marcas. El agente en sede las enviará en la próxima sincronización.</div>
            <?php else: ?>
                <div class="rb-alert ok">
                    <strong><?php echo (int) $stats['total']; ?></strong> marca(s) en base.
                    La tabla carga por páginas (10 registros por solicitud).
                </div>
            <?php endif; ?>

            <div class="rb-dash">
                <div class="rb-stat">
                    <div class="rb-stat-label">Total en base</div>
                    <div class="rb-stat-value"><?php echo (int) $stats['total']; ?></div>
                </div>
                <div class="rb-stat">
                    <div class="rb-stat-label">Marcas hoy</div>
                    <div class="rb-stat-value"><?php echo (int) $stats['hoy']; ?></div>
                </div>
                <div class="rb-stat rb-stat-muted">
                    <div class="rb-stat-label">Última marca</div>
                    <div class="rb-stat-value"><?php
                        echo $stats['ultima'] !== null && $stats['ultima'] !== ''
                            ? htmlspecialchars((string) $stats['ultima'], ENT_QUOTES, 'UTF-8')
                            : '—';
                    ?></div>
                </div>
                <div class="rb-stat rb-stat-muted">
                    <div class="rb-stat-label">Sitio</div>
                    <div class="rb-stat-value"><?php echo htmlspecialchars($siteCode, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <?php if ($dbOk && (int) $stats['total'] > 0): ?>
                <p class="rb-meta">Tabla: <strong>10</strong> registros por página (buscador, fechas y paginación abajo).</p>
            <?php endif; ?>

            <?php if ($dbOk): ?>
            <div class="rb-filter-panel">
                <div class="rb-filter-field">
                    <label for="rb-date-from">Desde:</label>
                    <input type="date" id="rb-date-from">
                </div>
                <div class="rb-filter-field">
                    <label for="rb-date-to">Hasta:</label>
                    <input type="date" id="rb-date-to">
                </div>
                <div class="rb-filter-actions">
                    <div>
                        <button type="button" id="rb-apply-dates" class="rb-btn rb-btn-search">Buscar</button>
                        <button type="button" id="rb-clear-dates" class="rb-btn rb-btn-clear">Limpiar</button>
                    </div>
                </div>
            </div>

            <div class="rb-table-wrap">
                <table id="table_reloj_marcas" class="rb-table responsive-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Empleado</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>ID reloj</th>
                            <th>Fecha entrada</th>
                            <th>Fecha salida</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script type="text/javascript" src="../../backend/js/datatable.js"></script>
<script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
<script type="text/javascript" src="../../backend/js/jszip.js"></script>
<script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
<script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
<script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
<script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
<script>
window.MEDIDATA_RELOJBIO = {
    ajaxUrl: '../../backend/registros/fetch_biometric_marcas.php',
    dbOk: <?php echo !empty($dbOk) ? 'true' : 'false'; ?>
};
</script>
<script src="../../backend/registros/script/tabla_relojbio.js?v=20260531b"></script>

</body>
</html>
