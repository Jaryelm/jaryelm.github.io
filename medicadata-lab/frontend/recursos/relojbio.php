<?php

include_once '../../backend/registros/session_check.php';

// Libera el candado del archivo de sesión antes de llamadas largas UDP al MB360
// (evita que otras pestañas queden esperando session_start hasta max_execution_time).
if (PHP_SESSION_ACTIVE === session_status()) {
    session_write_close();
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>

    <link rel="stylesheet" href="../../backend/css/admin.css">

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <title>MEDIDATA — Reloj biométrico</title>

    <style>

        .rb-card { background: #fff; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); }

        .rb-alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1rem; }

        .rb-alert.error { background: #fde8e8; border: 1px solid #f5b5b5; color: #8a1f1f; }

        .rb-alert.ok { background: #e6f7f4; border: 1px solid #7fcdbe; color: #0d4f44; }

        .rb-alert.info { background: #f0f7ff; border: 1px solid #9ec5f7; color: #1a4480; }

        .rb-meta { font-size: 0.9rem; color: #555; margin-bottom: 0.5rem; }

        .rb-table-wrap { overflow-x: auto; }

        table.rb-table { width: 100%; border-collapse: collapse; }

        table.rb-table th, table.rb-table td { border: 1px solid #ddd; padding: 0.5rem 0.75rem; text-align: left; }

        table.rb-table th { background: #0d6f7e; color: #fff; }

        table.rb-table tbody tr:nth-child(even) { background: #f9f9f9; }

        .rb-diag-wrap { margin-top: 1rem; }
        details.rb-details { border: 1px solid #cde; border-radius: 8px; padding: 0.65rem 1rem; background: #fafcfd; margin-bottom: 1rem; }
        details.rb-details summary { cursor: pointer; font-weight: 600; color: #135; }
        dl.rb-diag { margin: 0.75rem 0 0; font-size: 0.92rem; }
        dl.rb-diag dt { clear: left; font-weight: 600; color: #333; margin-top: 0.35rem; }
        dl.rb-diag dd { margin: 0 0 0.35rem 0.25rem; }

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

            <h2 style="margin-top:0;color:#0d6f7e;">Reloj biométrico — lectura de marcas</h2>

            <?php

            require_once __DIR__ . '/../../backend/php/reloj_biometrico_config.php';

            require_once __DIR__ . '/../../backend/php/biometric_agent_secret_bootstrap.php';
            medidata_lab_opt_env_from_local_file();

            require_once __DIR__ . '/../../backend/php/reloj_biometrico_mb360.php';

            $cfg = medidata_reloj_biometrico_config();

            $rbTimeout = (int) ($cfg['connect_timeout_sec'] ?? 12);

            $pull = medidata_mb360_pull_attendance($cfg);

            $diag = isset($pull['diag']) && is_array($pull['diag'])
                ? $pull['diag']
                : medidata_mb360_diagnostic_skeleton($cfg);

            $zkErr = $pull['error'] ?? null;

            $attendance = $pull['records'] ?? [];

            $recordsFromAgentDb = [];
            $siteReljoDb = getenv('MEDIDATA_RELJO_DB_SITE');
            $siteReljoDb = is_string($siteReljoDb) ? trim($siteReljoDb) : '';
            if ($siteReljoDb !== '' && isset($connect) && $connect instanceof PDO) {
                $recordsFromAgentDb = medidata_biometric_fetch_marcas_agent_db($connect, $siteReljoDb, 2000);
            }

            $usedAgentTableView = ($attendance === [] && $recordsFromAgentDb !== []);
            $displayRows = !$usedAgentTableView ? $attendance : $recordsFromAgentDb;


            $tcpOk = !empty($diag['tcp_ok']);

            $diagErrNo = (isset($diag['socket_errno']) && $diag['socket_errno'] !== null)
                ? (int) $diag['socket_errno']
                : null;
            $diagErrHint = medidata_mb360_socket_errno_hint_es($diagErrNo);

            ?>

            <p class="rb-meta">

                Equipo <strong>ZKTeco MB360</strong>: las <strong>marcas</strong> se obtienen por <strong>protocolo ZK (UDP)</strong> al puerto <strong><?php echo (int) $cfg['port']; ?></strong>

                sobre <strong><?php echo htmlspecialchars($cfg['ip']); ?></strong>.

                Una prueba opcional por <strong>TCP</strong> al mismo puerto sirve sólo como <strong>test de alcance</strong>; la lectura de marcas usa <strong>UDP</strong> (protocolo ZK). TCP OK pero UDP fallido suele indicar parámetros del equipo o modo de comunicación más que sólo filtros externos.

                <strong>Importante:</strong> el comando <code>ping</code> usa ICMP y no garantiza comportamiento del puerto UDP 4370 (ZK).

                Ajustes: <code>backend/php/reloj_biometrico_config.php</code> (IP/puerto) e integración

                <code>backend/php/reloj_biometrico_mb360.php</code>; variables de entorno

                <code>MEDIDATA_RELOJ_IP</code>, <code>MEDIDATA_RELOJ_PORT</code>, <code>MEDIDATA_ZK_RECV_SEC</code> (segundos de espera por respuesta UDP, 1–30).

            </p>

            <p class="rb-meta" style="margin-bottom:12px;">
                Los datos muestran el estado al cargar esta página.

                <button type="button" class="register-btn" style="margin-left:10px;display:inline-block;" onclick="location.reload(true);">Actualizar marcas desde el equipo</button>
            </p>

            <?php

            $errorMsg = null;

            $okMsg = null;

            $infoMsg = null;

            if ($usedAgentTableView) {

                $errorMsg = null;

                $okMsg = 'Mostrando <strong>' . count($displayRows) . '</strong> marca(s) desde '
                    . '<code>biometric_marcas</code> (sitio <strong>'
                    . htmlspecialchars($siteReljoDb, ENT_QUOTES, 'UTF-8')
                    . '</strong>), cargadas por el agente desde la sede.';

                if ($zkErr !== null && $zkErr !== '') {

                    $infoMsg = '<strong>Vista combinada:</strong> no hubo handshake ZK/UDP desde el servidor donde corre esta página '
                        . '(VPN, segmento distinto o MB360 solo accesible vía sede). Las filas mostradas llegaron por ingesta desde el agente. '
                        . 'Opcional local: <code>MEDIDATA_RELJO_DB_SITE</code> en '
                        . '<code>backend/php/biometric_ingest_secret.local.env</code>.';

                }

                if ($tcpOk) {

                    if ($infoMsg === null) {

                        $infoMsg = 'Prueba TCP al reloj: <strong>OK</strong>; el cuadro muestra datos de base (sin pull UDP en esta página).';

                    } else {

                        $infoMsg .= ' Prueba TCP: <strong>OK</strong>.';

                    }

                }

            } elseif ($zkErr !== null && $zkErr !== '') {

                $errorMsg = htmlspecialchars($zkErr, ENT_QUOTES, 'UTF-8');

            } elseif (count($attendance) > 0) {

                $okMsg = 'Marcas cargadas desde el reloj: <strong>' . count($attendance) . '</strong> registro(s).';

                if ($tcpOk) {

                    $infoMsg = 'Prueba TCP al puerto configurado: <strong>OK</strong> (' . htmlspecialchars($cfg['ip']) . ':' . (int) $cfg['port'] . ').';

                } else {

                    $infoMsg = 'Nota: la prueba TCP a ' . htmlspecialchars($cfg['ip']) . ':' . (int) $cfg['port'] . ' no respondió (timeout ' . $rbTimeout . ' s); el protocolo ZK por UDP igualmente puede funcionar.';

                }

            } else {

                $infoMsg = 'Sin marcas para mostrar: el equipo no devolvió registros o la lista está vacía. Pulse «Actualizar marcas desde el equipo».';

                if ($tcpOk) {

                    $infoMsg .= ' Prueba TCP: <strong>OK</strong>.';

                }

                if (($siteReljoDb === '') && ($zkErr === null || $zkErr === '')) {

                    $infoMsg .= ' Si usás agente en sede, definí <code>MEDIDATA_RELJO_DB_SITE</code> para ver aquí '
                        . '<code>biometric_marcas</code>.';

                }

            }



            ?>



            <?php

            if ($errorMsg !== null) {

                echo '<div class="rb-alert error"><strong>Error al leer el reloj</strong><br>' . $errorMsg . '</div>';

            }



            if ($okMsg !== null) {

                echo '<div class="rb-alert ok">' . $okMsg . '</div>';

            }



            if ($infoMsg !== null) {

                echo '<div class="rb-alert info">' . $infoMsg . '</div>';

            }

            $diagSockets = !empty($diag['sockets_extension']);

            $diagUdpOk = !empty($diag['zk_udp_connect']);

            $diagOpen = (($zkErr !== null && $zkErr !== '') || !$diagUdpOk);

            $detailsAttrs = $diagOpen ? ' open' : '';

            echo '<details class="rb-details"' . $detailsAttrs . '>';

            echo '<summary>Diagnóstico técnico — ZK UDP / sockets / TCP (lo mismo que muestra PRUEBA desde consola)</summary>';

            echo '<div class="rb-diag-wrap">';

            echo '<dl class="rb-diag">';

            echo '<dt>Destino configurado</dt><dd><code>'
                . htmlspecialchars((string) $cfg['ip'], ENT_QUOTES, 'UTF-8') . ':' . (int) $cfg['port']
                . '</code></dd>';

            echo '<dt>Extensión PHP «sockets»</dt><dd>' . ($diagSockets ? '<strong>sí cargada</strong>' : '<strong style="color:#a11;">no cargada</strong> — habilitar en php.ini') . '</dd>';

            echo '<dt>Prueba TCP (solo ruta/red al puerto)</dt><dd>' . ($tcpOk ? '<strong style="color:#0a7;">OK</strong> — el puerto aceptó conexión TCP' : '<strong style="color:#a60;">Sin respuesta TCP</strong> (timeout configurado '
                . (int) ($cfg['connect_timeout_sec'] ?? 12) . ' s). La lectura real usa UDP.') . '</dd>';

            echo '<dt>Conexión ZK por UDP (<code>ZKTeco::connect()</code>)</dt><dd>' . ($diagUdpOk ? '<strong style="color:#0a7;">OK</strong> — sesión inicial con el equipo' : '<strong style="color:#a11;">falló</strong> — no hubo handshake ZK válido') . '</dd>';

            echo '<dt>socket_last_error (Windows / errno después del intento)</dt><dd><code>';

            echo ($diagErrNo !== null ? htmlspecialchars((string) $diagErrNo, ENT_QUOTES, 'UTF-8') : '— sin código (extensión o sin socket)');

            echo '</code>';

            $strErr = isset($diag['socket_strerror']) ? trim((string) $diag['socket_strerror']) : '';

            if ($strErr !== '') {

                echo ' — <span>' . htmlspecialchars($strErr, ENT_QUOTES, 'UTF-8') . '</span>';

            }

            echo '</dd>';

            echo '<dt>Octetos recibidos en buffer (_data_recv)</dt><dd><code>' . (int) ($diag['recv_buffer_bytes'] ?? 0)
                . '</code> bytes</dd>';

            if ($diagErrHint !== '') {

                echo '<dt>Código errno (detalle técnico)</dt><dd>' . htmlspecialchars($diagErrHint, ENT_QUOTES, 'UTF-8') . '</dd>';

            }

            $itUdpNote = !$diagUdpOk ? medidata_mb360_diagnostic_it_note_es($diag) : '';

            if ($itUdpNote !== '') {

                echo '<dt>Interpretación para IT Medicasa</dt><dd>' . htmlspecialchars($itUdpNote, ENT_QUOTES, 'UTF-8') . '</dd>';

            }

            echo '</dl>';

            echo '<p class="rb-meta" style="margin-bottom:0;">Script CLI (misma lógica):<br>'
                . '<code>C:\\xampp\\php\\php.exe backend\\php\\zk_mb360_connect_diagnose.php</code> · '
                . '<code>php backend/php/zk_mb360_connect_diagnose.php</code></p>';

            echo '</div></details>';

            ?>



            <p class="rb-meta">

                <strong>Destino ZK/TCP configurado:</strong> <?php echo htmlspecialchars($cfg['ip']); ?>:<?php echo (int) $cfg['port']; ?>

            </p>



            <div class="rb-table-wrap">

                <table class="rb-table">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Usuario (UID texto)</th>

                            <th>ID numérico</th>

                            <th>Estado</th>

                            <th>Fecha y hora</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php

                        if (count($displayRows) === 0 && ($zkErr === null || $zkErr === '') && !$usedAgentTableView) {

                            echo '<tr><td colspan="5">No hay marcas en esta lectura.</td></tr>';

                        } elseif (($zkErr !== null && $zkErr !== '') && count($displayRows) === 0) {

                            echo '<tr><td colspan="5">Corrija el error arriba o configure el agente + <code>MEDIDATA_RELJO_DB_SITE</code> '
                                . 'en <code>biometric_ingest_secret.local.env</code> para ver marcas desde la base.</td></tr>';

                        } else {

                            $n = 0;

                            foreach ($displayRows as $record) {

                                if (!is_array($record) || count($record) < 4) {

                                    continue;

                                }

                                $n++;

                                $uidTxt = isset($record[0]) ? (string) $record[0] : '';

                                $idNum = isset($record[1]) ? (string) $record[1] : '';

                                $state = isset($record[2]) ? (string) $record[2] : '';

                                $ts = isset($record[3]) ? (string) $record[3] : '';

                                echo '<tr>';

                                echo '<td>' . $n . '</td>';

                                echo '<td>' . htmlspecialchars($uidTxt) . '</td>';

                                echo '<td>' . htmlspecialchars($idNum) . '</td>';

                                echo '<td>' . htmlspecialchars($state) . '</td>';

                                echo '<td>' . htmlspecialchars($ts) . '</td>';

                                echo '</tr>';

                            }

                            if ($n === 0) {

                                echo '<tr><td colspan="5">Los datos mostrados no tienen el formato esperado.</td></tr>';

                            }

                        }

                        ?>

                    </tbody>

                </table>

            </div>

        </div>



    </main>

</section>



<script src="../../backend/js/jquery.min.js"></script>

<script src="../../backend/js/script.js"></script>

<script src="../../backend/js/submenu.js"></script>

</body>

</html>

