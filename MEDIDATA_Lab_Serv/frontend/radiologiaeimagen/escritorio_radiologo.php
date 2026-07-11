<?php
include_once '../../backend/registros/session_check.php';
if (($_SESSION['rol'] ?? '') !== 'Radiologo') {
    header('Location: escritorio.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/backend/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <title>MEDIDATA</title>
    <style>
        .rx-dash-intro {
            color: #555;
            margin: -8px 0 24px;
            font-size: 1rem;
        }
        .rx-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .rx-stat {
            background: #fff;
            border-radius: 10px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #06adbf;
        }
        .rx-stat h3 {
            margin: 0 0 8px;
            font-size: 0.85rem;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .rx-stat p {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: #035c67;
        }
        .rx-section {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .rx-section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .rx-section-head h2 {
            margin: 0;
            font-size: 1.15rem;
            color: #035c67;
        }
        .rx-table-wrap {
            overflow-x: auto;
        }
        .rx-table {
            width: 100%;
            border-collapse: collapse;
        }
        .rx-table th,
        .rx-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e8e8e8;
            font-size: 0.92rem;
        }
        .rx-table th {
            background: #06adbf;
            color: #fff;
            font-weight: 600;
        }
        .rx-table tr:nth-child(even) {
            background: #f9fbfc;
        }
        .rx-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .rx-badge-pending { background: #fff3cd; color: #856404; }
        .rx-badge-draft { background: #e2e3e5; color: #383d41; }
        .rx-badge-final { background: #d4edda; color: #155724; }
        .rx-empty {
            text-align: center;
            color: #666;
            padding: 28px 12px;
        }
        .rx-actions-top {
            margin-bottom: 20px;
        }
        .rx-actions-top a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
<?php include_once __DIR__ . '/menu.php'; ?>

<section id="content">
    <nav>
        <i class="bx bx-menu toggle-sidebar"></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once __DIR__ . '/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora = (int) date('H');
        if ($hora >= 6 && $hora < 12) {
            $saludo = 'Buenos Días';
        } elseif ($hora >= 12 && $hora < 18) {
            $saludo = 'Buenas Tardes';
        } else {
            $saludo = 'Buenas Noches';
        }
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
        <p class="rx-dash-intro">Panel de estudios radiológicos asignados a usted.</p>

        <div class="rx-stats">
            <div class="rx-stat">
                <h3>Estudios asignados</h3>
                <p id="stat-listados">—</p>
            </div>
            <div class="rx-stat">
                <h3>Pendientes de interpretación</h3>
                <p id="stat-pending">—</p>
            </div>
            <div class="rx-stat">
                <h3>En transcripción</h3>
                <p id="stat-in-transcription">—</p>
            </div>
            <div class="rx-stat">
                <h3>Interpretados hoy</h3>
                <p id="stat-today">—</p>
            </div>
            <div class="rx-stat">
                <h3>Completados</h3>
                <p id="stat-completed">—</p>
            </div>
            <div class="rx-stat">
                <h3>Hallazgos críticos</h3>
                <p id="stat-critical">—</p>
            </div>
        </div>

        <div class="rx-section">
            <div class="rx-section-head">
                <h2>Estudios pendientes de interpretación</h2>
                <a href="lista_estudios_medico.php" class="button">Ver todos</a>
            </div>
            <div class="rx-table-wrap">
                <table class="rx-table">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Modalidad</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbl-pending"></tbody>
                </table>
                <div id="empty-pending" class="rx-empty" style="display:none;">No tiene estudios pendientes en este momento.</div>
            </div>
        </div>

        <div class="rx-section">
            <div class="rx-section-head">
                <h2>Enviados a transcripción</h2>
                <a href="lista_estudios_medico.php" class="button">Ver todos</a>
            </div>
            <div class="rx-table-wrap">
                <table class="rx-table">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Modalidad</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbl-transcription"></tbody>
                </table>
                <div id="empty-transcription" class="rx-empty" style="display:none;">No tiene estudios enviados a transcripción.</div>
            </div>
        </div>

        <div class="rx-section">
            <div class="rx-section-head">
                <h2>Estudios completados</h2>
                <a href="lista_estudios_medico.php" class="button">Ver todos</a>
            </div>
            <div class="rx-table-wrap">
                <table class="rx-table">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Modalidad</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Completado</th>
                        </tr>
                    </thead>
                    <tbody id="tbl-completed"></tbody>
                </table>
                <div id="empty-completed" class="rx-empty" style="display:none;">Aún no tiene estudios completados registrados.</div>
            </div>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script>
(function () {
    function esc(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatDateTime(value) {
        if (!value) return '—';
        const d = new Date(value);
        if (isNaN(d.getTime())) return esc(value);
        return d.toLocaleString('es-HN', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function statusBadge(status) {
        const map = {
            pending: { label: 'Pendiente', cls: 'rx-badge-pending' },
            draft: { label: 'Borrador', cls: 'rx-badge-draft' },
            final: { label: 'Completado', cls: 'rx-badge-final' }
        };
        const item = map[status] || { label: status || '—', cls: 'rx-badge-draft' };
        return '<span class="rx-badge ' + item.cls + '">' + esc(item.label) + '</span>';
    }

    function renderRows(tbodyId, emptyId, rows, completedView) {
        const tbody = document.getElementById(tbodyId);
        const empty = document.getElementById(emptyId);
        tbody.innerHTML = '';

        if (!rows.length) {
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';

        rows.slice(0, 10).forEach(function (study) {
            const tr = document.createElement('tr');
            const dateCol = completedView
                ? formatDateTime(study.updated_at || study.study_date)
                : formatDateTime(study.study_date);
            tr.innerHTML =
                '<td>' + esc(study.patient_name || '—') + '</td>' +
                '<td>' + esc(study.modality || '—') + '</td>' +
                '<td>' + esc(study.study_description || 'Sin descripción') + '</td>' +
                '<td>' + formatDateTime(study.study_date) + '</td>' +
                '<td>' + (completedView ? dateCol : statusBadge(study.status)) + '</td>';
            tbody.appendChild(tr);
        });
    }

    function loadStats() {
        fetch('get_stats.php')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                document.getElementById('stat-listados').textContent = data.listados ?? 0;
                document.getElementById('stat-pending').textContent = data.pending ?? 0;
                document.getElementById('stat-in-transcription').textContent = data.in_transcription ?? 0;
                document.getElementById('stat-today').textContent = data.today ?? 0;
                document.getElementById('stat-completed').textContent = data.completed_global ?? 0;
                document.getElementById('stat-critical').textContent = data.critical ?? 0;
            })
            .catch(function () {
                ['stat-listados', 'stat-pending', 'stat-in-transcription', 'stat-today', 'stat-completed', 'stat-critical']
                    .forEach(function (id) { document.getElementById(id).textContent = '0'; });
            });
    }

    function loadStudies() {
        fetch('get_completed_studies.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const studies = Array.isArray(data) ? data : (data.data || []);
                const pending = studies.filter(function (s) {
                    return s.status === 'pending' || s.status === 'draft';
                });
                const inTranscription = studies.filter(function (s) {
                    return s.status === 'pending_transcription';
                });
                const completed = studies.filter(function (s) {
                    return s.status === 'final' || s.status === 'transcribed' || s.status === 'reviewed';
                });
                renderRows('tbl-pending', 'empty-pending', pending, false);
                renderRows('tbl-transcription', 'empty-transcription', inTranscription, false);
                renderRows('tbl-completed', 'empty-completed', completed, true);
            })
            .catch(function () {
                renderRows('tbl-pending', 'empty-pending', [], false);
                renderRows('tbl-transcription', 'empty-transcription', [], false);
                renderRows('tbl-completed', 'empty-completed', [], true);
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadStats();
        loadStudies();
    });
})();
</script>
</body>
</html>
