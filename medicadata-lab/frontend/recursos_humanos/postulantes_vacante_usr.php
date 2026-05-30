<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/backend/registros/session_check.php';

$id_vacante = isset($_GET['id_vacante']) ? (int)$_GET['id_vacante'] : 0;
$vacant_name = "Desconocida";

if ($id_vacante > 0) {
    try {
        $stmt = $connect_rrhh->prepare("SELECT vacant_name FROM vacant_positions WHERE id = :id AND deleted = 0");
        $stmt->bindParam(':id', $id_vacante, PDO::PARAM_INT);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vacant_name = $row['vacant_name'];
        }
    } catch (Exception $e) {}
}
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

    <title>MEDIDATA - Postulantes</title>
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
        <h1 class="title">Postulantes: <strong><?php echo htmlspecialchars($vacant_name); ?></strong></h1>

        <button class="button" onclick="window.location='vacantes_trabajo_usr.php'">Volver a Vacantes</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Listado de Candidatos</h3>
                </div>

                <!-- Integrated Search Bar -->
                <div class="search-container-inline" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; gap: 15px; align-items: center;">
                    <input type="text" id="inline-search-input" placeholder="Buscar por nombre, DNI o email..." style="flex-grow: 1; margin: 0 !important; border: 1px solid #ddd !important; background: #fdfdfd !important; padding: 10px;">
                    <button class="search-btn" id="inline-search-button" style="background: var(--dark-blue, #035c67); color: var(--light, #fff); border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: 600; transition: background 0.3s; display: flex; align-items: center; gap: 8px;">
                        <i class="fa fa-search" style="background-color: transparent !important; padding: 0 !important;"></i> Buscar
                    </button>
                </div>

                <input type="hidden" id="id_vacante_hidden" value="<?php echo $id_vacante; ?>">

                <!-- Grid Container for AJAX rendering -->
                <div id="postulantes-grid" class="grid-container">
                    <div style="grid-column: 1 / -1;">
                        <p class="alert alert-warning">Cargando...</p>
                    </div>
                </div>

            </div>
        </div>  

        </main>
    </section>

    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
    <script src="../../backend/registros/script/tabla_postulantes_vacante.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#inline-search-button').on('click', function() {
            if (window.filterPostulantes) {
                window.filterPostulantes($('#inline-search-input').val());
            }
        });
        $('#inline-search-input').on('keypress', function(e) {
            if (e.which == 13) {
                e.preventDefault();
                if (window.filterPostulantes) {
                    window.filterPostulantes($(this).val());
                }
            }
        });
    });
    </script>
</body>
</html>
