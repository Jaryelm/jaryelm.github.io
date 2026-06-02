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
    <link rel="stylesheet" href="../../backend/css/cards.css">
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>


    <title>MEDIDATA</title>

</head>
<body>
    
<?php include_once '../admin/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>

        <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'puestos_trabajo.php')">Listar Puestos de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_puesto_trabajo.php')">Registrar Puesto de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head" style="margin-bottom: 20px;">
                    <h3>Gestión de Puestos de Trabajo</h3>
                </div>

                <!-- Integrated Search Bar -->
                <div class="search-container-inline">
                    <input type="text" id="inline-search-input" placeholder="Buscar por nombre, departamento, objetivo o jefe...">
                    <button class="search-btn" id="inline-search-button">
                        <i class="fa fa-search"></i> Buscar
                    </button>
                </div>

                <!-- Grid Container for AJAX rendering -->
                <div id="puestos-grid" class="grid-container">
                    <div style="grid-column: 1 / -1; width: 100%;">
                        <p class="alert alert-warning" style="margin: 0; display: block; width: 100%; border-radius: 5px; text-align: left;">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>  

        </main>
    </section>

    <script src="../../backend/js/script.js"></script>
    <script src='../../backend/js/submenu.js'></script>
    <script src="../../backend/registros/script/botones_color.js"></script>
    <script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/registros/script/tabla_puestos_trabajo.js"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        $('#inline-search-button').on('click', function() {
            if (window.filterPuestos) {
                window.filterPuestos($('#inline-search-input').val());
            }
        });
        $('#inline-search-input').on('keypress', function(e) {
            if (e.which == 13) {
                e.preventDefault();
                if (window.filterPuestos) {
                    window.filterPuestos($(this).val());
                }
            }
        });
    });
    </script>
</body>
</html>
