<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">


    <title>MEDIDATA</title>
</head>

<body>

    <?php
    include_once '../admin/menu.php';
    // incuir el archivo menu principal
    ?>

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group">
                </div>
            </form>


            <span class="divider"></span>
            <?php
            include_once '../admin/perfil.php';
            // incuir el archivo menu principal
            ?>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <?php
            // Obtener la hora actual
            $hora_actual = date('H'); // Obtiene la hora en formato de 24 horas (0-23)

            if ($hora_actual >= 6 && $hora_actual < 12) {
                $saludo = "Buenos Días";
            } elseif ($hora_actual >= 12 && $hora_actual < 18) {
                $saludo = "Buenas Tardes";
            } else {
                $saludo = "Buenas Noches";
            }
            ?>

            <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

            <br>

            <?php
            require $_SERVER['DOCUMENT_ROOT'] . '/backend/sdk/zkteco/vendor/autoload.php';

            use Jmrashed\Zkteco\Lib\ZkTeco;

            $isConnected = false;
            // Desactivamos temporalmente los warnings para que los fallos de unpack() internos de la librería no ensucien la pantalla
            $old_error_reporting = error_reporting();
            error_reporting($old_error_reporting & ~E_WARNING);

            try{
                $zk = new ZkTeco(ip: '192.168.1.203', port: 4370);
                
                // Forzamos un timeout de 5 segundos en el socket
                if (isset($zk->_zkclient)) {
                    socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
                    socket_set_option($zk->_zkclient, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
                }

                $isConnected = $zk->connect();
                
                if ($isConnected) {
                    $attendance = $zk->getAttendance();    
                    $zk->disconnect();
                }
            }catch (Exception $ex) {}

            // Restauramos el nivel de reporte de errores
            error_reporting($old_error_reporting);
            ?>

            <!-- Mostrar los registros en la tabla -->
             <?php if($isConnected){ ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                        <tr>
                            <td>Prueba Data</td>
                            <td>Prueba Data</td>
                        </tr>
                </tbody>
            </table>
            <?php } else  { ?>
                <p>No se ha podido establecer comunicación con el dispositivo</p>
            <?php } ?>
        </main>
        <!-- MAIN -->
    </section>

    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>

    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
</body>

</html>