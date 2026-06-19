<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$id_vacante = isset($_GET['id_vacante']) ? (int) $_GET['id_vacante'] : 0;
$vacant_name = 'Desconocida';
$pdoRrhh = medidata_rrhh_pdo();

if ($id_vacante > 0 && $pdoRrhh) {
    try {
        $stmt = $pdoRrhh->prepare("SELECT vacant_name FROM vacant_positions WHERE id = :id AND deleted = 0");
        $stmt->bindParam(':id', $id_vacante, PDO::PARAM_INT);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vacant_name = $row['vacant_name'];
        }
    } catch (Throwable $e) {}
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
        <h1 class="title">Postulantes: <strong><?php echo htmlspecialchars($vacant_name); ?></strong></h1>

        <button class="button" onclick="window.location='vacantes_trabajo.php'">Volver a Vacantes</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Listado de Candidatos</h3>
                </div>

                <!-- Integrated Search Bar -->
                <form class="search-container-inline">
                    <input type="text" id="inline-search-input" placeholder="Buscar por nombre, DNI o email...">
                    <button type="button" class="search-btn" id="inline-search-button">
                        <i class="fa fa-search"></i> Buscar
                    </button>
                </form>

                <input type="hidden" id="id_vacante_hidden" value="<?php echo $id_vacante; ?>">

                <!-- Grid Container for AJAX rendering -->
                <div id="postulantes-grid" class="grid-container">
                    <div style="grid-column: 1 / -1; width: 100%;">
                        <p class="alert alert-warning" style="margin: 0; display: block; width: 100%; border-radius: 5px; text-align: left;">Cargando...</p>
                    </div>
                </div>

                <div id="postulantes-pagination" class="rrhh-cards-pagination" style="display:none;" aria-label="Paginación de candidatos">
                    <button type="button" class="pagination-button rrhh-page-prev" disabled>Anterior</button>
                    <span class="rrhh-page-info">Página 1 de 1</span>
                    <button type="button" class="pagination-button rrhh-page-next" disabled>Siguiente</button>
                </div>

            </div>
        </div>  

        </main>
    </section>

    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
    <script src="../../backend/registros/script/tabla_postulantes_vacante.js?v=20260528j"></script>

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
