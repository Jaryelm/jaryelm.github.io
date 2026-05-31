<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../../backend/js/jquery.min.js"></script>

    <style>
        .priority-title {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
            padding: 10px 15px;
            border-radius: 5px;
            user-select: none;
        }
        .priority-title:hover {
            opacity: 0.9;
        }
        .priority-title .priority-meta {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .priority-title .vacantes-count-badge {
            background: #fff;
            color: #333;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 800;
            min-width: 30px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: inline-block;
        }
        .priority-title .chevron-icon {
            transition: transform 0.3s;
        }
        .priority-section.collapsed .chevron-icon {
            transform: rotate(-90deg);
        }
        .priority-section.collapsed .grid-container {
            display: none !important;
        }
    </style>

    <title>MEDIDATA - Vacantes de Trabajo</title>

</head>
<body>
    
<?php include_once './menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once './perfil.php'; ?>
        </nav>

        <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'vacantes_trabajo_usr.php')">Listar Vacantes de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_vacantes_trabajo_usr.php')">Registrar Vacante de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head" style="margin-bottom: 20px;">
                    <h3>Gestión de Vacantes de Trabajo</h3>
                </div>

                <!-- Integrated Search Bar -->
                <div class="search-container-inline">
                    <input type="text" id="inline-search-input" placeholder="Buscar por vacante, puesto, departamento o motivo...">
                    <button class="search-btn" id="inline-search-button">
                        <i class="fa fa-search"></i> Buscar
                    </button>
                </div>

                <!-- Priority Groups -->
                <div id="vacantes-grouped-container">
                    
                    <div class="priority-section collapsed" id="section-urgente" style="display:none;">
                        <div class="priority-title title-urgente" onclick="toggleSection('section-urgente')">
                            <span><i class="fa fa-fire"></i> Prioridad Urgente</span>
                            <div class="priority-meta">
                                <span class="vacantes-count-badge" id="count-urgente">0</span>
                                <i class="fa fa-chevron-down chevron-icon"></i>
                            </div>
                        </div>
                        <div id="grid-urgente" class="grid-container"></div>
                    </div>

                    <div class="priority-section collapsed" id="section-alta" style="display:none;">
                        <div class="priority-title title-alta" onclick="toggleSection('section-alta')">
                            <span><i class="fa fa-exclamation-circle"></i> Prioridad Alta</span>
                            <div class="priority-meta">
                                <span class="vacantes-count-badge" id="count-alta">0</span>
                                <i class="fa fa-chevron-down chevron-icon"></i>
                            </div>
                        </div>
                        <div id="grid-alta" class="grid-container"></div>
                    </div>

                    <div class="priority-section collapsed" id="section-media" style="display:none;">
                        <div class="priority-title title-media" onclick="toggleSection('section-media')">
                            <span><i class="fa fa-info-circle"></i> Prioridad Media</span>
                            <div class="priority-meta">
                                <span class="vacantes-count-badge" id="count-media">0</span>
                                <i class="fa fa-chevron-down chevron-icon"></i>
                            </div>
                        </div>
                        <div id="grid-media" class="grid-container"></div>
                    </div>

                    <div class="priority-section collapsed" id="section-baja" style="display:none;">
                        <div class="priority-title title-baja" onclick="toggleSection('section-baja')">
                            <span><i class="fa fa-check-circle"></i> Prioridad Baja</span>
                            <div class="priority-meta">
                                <span class="vacantes-count-badge" id="count-baja">0</span>
                                <i class="fa fa-chevron-down chevron-icon"></i>
                            </div>
                        </div>
                        <div id="grid-baja" class="grid-container"></div>
                    </div>

                    <div id="no-results-message" class="empty-state" style="display:none;">
                        <i class="fa fa-clipboard-list" style="font-size: 3rem; color: #ddd; margin-bottom: 15px; display: block;"></i>
                        <p>No se encontraron vacantes con los criterios de búsqueda.</p>
                    </div>

                    <div id="loading-state" class="empty-state">
                        <p>Cargando vacantes de trabajo...</p>
                    </div>

                </div>
            </div>
        </div>  

        </main>
    </section>

    <script src="../../backend/js/script.js"></script>
    <script src='../../backend/js/submenu.js'></script>
    <script src="../../backend/registros/script/botones_color.js"></script>
    <script src="../../backend/registros/script/tabla_vacantes_trabajo.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

    <script type="text/javascript">
    function toggleSection(id) {
        var section = document.getElementById(id);
        if (section) {
            section.classList.toggle('collapsed');
        }
    }

    $(document).ready(function() {
        $('#inline-search-button').on('click', function() {
            if (window.filterVacantes) {
                window.filterVacantes($('#inline-search-input').val());
            }
        });
        $('#inline-search-input').on('keypress', function(e) {
            if (e.which == 13) {
                e.preventDefault();
                if (window.filterVacantes) {
                    window.filterVacantes($(this).val());
                }
            }
        });
    });
    </script>
</body>
</html>
