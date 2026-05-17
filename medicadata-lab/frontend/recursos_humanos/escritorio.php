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

    <!-- DataTables -->
    <link rel="stylesheet" href="../../backend/vendor/datatables/dataTables.bs4.css" />
    <link rel="stylesheet" href="../../backend/vendor/datatables/dataTables.bs4-custom.css" />
    <link href="../../backend/vendor/datatables/buttons.bs.css" rel="stylesheet" />

    <!-- FullCalendar -->
    <link href='../../backend/css/fullcalendar.css' rel='stylesheet' />
    <style>
        #calendar-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            width: 100%;
        }

        #calendar {
            flex: 1;
            max-width: 60%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background-color: #fff;
        }

        #notification-panel {
            flex: 1;
            max-width: 35%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
            overflow-y: auto;
            max-height: 75.5vh;
        }

        #notification-panel h4 {
            margin-bottom: 10px;
        }

        .notification-item {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
        }

        .notification-item.occupied {
            background-color: #ffdddd;
        }

        .notification-item.available {
            background-color: #ddffdd;
        }

        #weekly-status,
        #future-events,
        #past-events {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            #calendar-container {
                flex-direction: column;
                gap: 15px;
            }

            #calendar,
            #notification-panel {
                max-width: 100%;
                flex: none;
            }
        }
    </style>

    <title>MEDIDATA</title>
</head>

<body>

    <?php
    include_once './menu.php';
    // incuir el archivo menu principal
    ?>

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
                <div class="form-group">
                </div>


            <span class="divider"></span>

            <?php
            include_once './perfil.php';
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


            <!-- Dashboard Start -->

            <style>
                /* Estilo previo adaptado */

                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                    color: #000;
                }

                .dashboard-container {
                    width: 100%;
                    max-width: 1500px;
                    margin: 0 auto;
                    padding: 20px;
                }

                header {
                    text-align: center;
                    margin-bottom: 20px;
                }

                .dashboard {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 20px;
                }

                .card {
                    background-color: #06adbf;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    color: #fff;
                    transition: transform 0.3s ease;
                }

                .card h2 {
                    margin-top: 0;
                    color: #fff;
                }

                .card p {
                    color: #f4f4f4;
                }

                .card:hover {
                    transform: translateY(-5px);
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }

                table th,
                table td {
                    padding: 10px;
                    border: 1px solid #ddd;
                    text-align: left;
                }

                table th {
                    background-color: #06adbf;
                    color: #fff;
                }

                @media (max-width: 768px) {
                    .dashboard {
                        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    }
                }
            </style>
            <div class="dashboard-container">
                <header>
                    <h1>Recursos Humanos</h1>
                </header>
            </div>
        </main>
    </section>

    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>

</html>