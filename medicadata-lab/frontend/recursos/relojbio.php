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

    <style>
        .sync-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-sync {
            background-color: #3C91E6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-sync:hover { background-color: #2a6db0; }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            display: inline-block;
            min-width: 140px;
            text-align: center;
        }
        .entrada { background-color: #28a745; }
        .salida { background-color: #dc3545; }
        .entrada_almuerzo { background-color: #17a2b8; }
        .salida_almuerzo { background-color: #ffc107; color: #333; }
        
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #dc3545;
            margin-top: 20px;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        th {
            background-color: #f4f7f6;
            text-align: left;
            padding: 15px 12px;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        tr:hover { background-color: #f9f9f9; }
    </style>

    <title>MEDIDATA - Monitor Biométrico</title>
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
            $hora = date('H');
            $saludo = ($hora >= 6 && $hora < 12) ? "Buenos Días" : (($hora >= 12 && $hora < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>

            <div class="sync-header">
                <h1 class="title"><?= $saludo ?>, <strong><?= $name ?></strong></h1>
                <button onclick="location.reload()" class="btn-sync">
                    <i class='bx bx-sync'></i> Sincronizar Ahora
                </button>
            </div>

            <p class="subtitle">Monitor de marcaciones en tiempo real desde ZKTeco</p>
            <br>

            <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/bd/Conexion.php';
            require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/ajax/sync_zkteco.php';

            $controller = new ZkTecoController('192.168.1.203', 4370, $connect);
            $attendance = $controller->fetchAttendance();
            ?>

             <?php if ($attendance !== false): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Código Empleado</th>
                                <th>Tipo de Marcación</th>
                                <th>Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendance)): 
                                usort($attendance, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));
                                foreach ($attendance as $log): 
                                    $statusClass = strtolower($log['mapped_type']); ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($log['id']) ?></strong></td>
                                        <td><span class="badge <?= $statusClass ?>"><?= str_replace('_', ' ', $log['mapped_type']) ?></span></td>
                                        <td><?= date('d/m/Y h:i:s A', strtotime($log['timestamp'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center;">No hay registros recientes en el dispositivo</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="error-msg">
                    <p><i class='bx bx-error'></i> Error de comunicación con el biométrico.</p>
                    <p>Verifique la conexión de red y la disponibilidad del dispositivo en 192.168.1.203.</p>
                </div>
            <?php endif; ?>
        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src='../../backend/js/submenu.js'></script>
</body>
</html>
