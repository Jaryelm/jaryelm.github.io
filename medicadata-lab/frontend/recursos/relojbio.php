<?php

include_once '../../backend/registros/session_check.php';

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

            require_once __DIR__ . '/../../backend/php/reloj_biometrico_bt8lite.php';

            $cfg = medidata_reloj_biometrico_config();

            $rbTimeout = (int) ($cfg['connect_timeout_sec'] ?? 12);

            ?>

            <p class="rb-meta">

                Equipo <strong>BT-8LITE (OEM)</strong>: comunicación por <strong>TCP</strong>, no por protocolo ZK/UDP de ZKTeco puro.

                Destino configurado: <strong><?php echo htmlspecialchars($cfg['ip']); ?>:<?php echo (int) $cfg['port']; ?></strong>

                (timeout conexión <?php echo $rbTimeout; ?> s). Ajuste en <code>backend/php/reloj_biometrico_config.php</code> o

                <code>MEDIDATA_RELOJ_IP</code>, <code>MEDIDATA_RELOJ_PORT</code>, <code>MEDIDATA_RELOJ_TIMEOUT</code>.

                La lectura de marcas se implementará en <code>backend/php/reloj_biometrico_bt8lite.php</code> según el SDK/API del fabricante.

            </p>



            <?php

            $attendance = medidata_reloj_bt8lite_get_attendance($cfg);

            $errorMsg = null;

            $okMsg = null;



            $tcpOk = medidata_reloj_bt8lite_tcp_reachable($cfg);

            if ($tcpOk) {

                $okMsg = 'Conexión <strong>TCP</strong> al reloj correcta (' . htmlspecialchars($cfg['ip']) . ':' . (int) $cfg['port'] . '). '

                    . 'Las marcas aún no se obtienen hasta integrar el protocolo OEM en <code>reloj_biometrico_bt8lite.php</code>.';

            } else {

                $errorMsg = 'No se pudo abrir conexión <strong>TCP</strong> a ' . htmlspecialchars($cfg['ip']) . ':' . (int) $cfg['port']

                    . ' (timeout ' . $rbTimeout . ' s). Compruebe IP, puerto, VPN y firewall.';

            }

            ?>



            <?php

            if ($errorMsg !== null) {

                echo '<div class="rb-alert error"><strong>Atención</strong><br>' . $errorMsg . '</div>';

            }

            if ($okMsg !== null) {

                echo '<div class="rb-alert ok">' . $okMsg . '</div>';

            }

            ?>



            <p class="rb-meta"><strong>Equipo configurado:</strong> <?php echo htmlspecialchars($cfg['ip']); ?>:<?php echo (int) $cfg['port']; ?> (TCP)</p>



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

                        if (count($attendance) === 0) {

                            echo '<tr><td colspan="5">Sin registros: integración OEM pendiente en <code>reloj_biometrico_bt8lite.php</code>.</td></tr>';

                        } else {

                            $n = 0;

                            foreach ($attendance as $record) {

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

                                echo '<tr><td colspan="5">Los datos recibidos no tienen el formato esperado.</td></tr>';

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

