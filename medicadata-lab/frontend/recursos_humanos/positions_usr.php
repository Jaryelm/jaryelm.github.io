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
    
    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <title>MEDIDATA - Posiciones</title>
    
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-button.active { background-color: var(--dark-blue) !important; color: white !important; }
    </style>
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

        <!-- Botones de navegación (Tabs) -->
        <div style="margin-bottom: 20px;">
            <button class="button tab-button active" id="btn-list-tab" onclick="showTab('list-tab', this)">Listado de Posiciones</button>
            <button class="button tab-button" id="btn-new-tab" onclick="prepareNew()">Nueva Posición</button>
        </div>

        <!-- Tab Listado -->
        <div id="list-tab" class="tab-content active">
            <div class="data">
                <div class="content-data">
                    <div class="head" style="margin-bottom: 20px;">
                        <h3>Listado de Posiciones de Trabajo</h3>
                    </div>
                    <div class="table-responsive" style="overflow-x:auto;">
                        <?php 
                        try {
                            $stmt = $connect->prepare("SELECT * FROM positions ORDER BY name ASC");
                            $stmt->execute();
                            $data = $stmt->fetchAll(PDO::FETCH_OBJ);
                        } catch (Exception $e) {
                            $data = [];
                        }
                        ?>
                        <?php if(count($data) > 0): ?>
                            <table id="positionsTable" class="responsive-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Nombre de la Posición</th>
                                        <th scope="col">Estado</th>
                                        <th scope="col">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($data as $d): ?>
                                        <tr>
                                            <td data-title="Nombre"><?php echo htmlspecialchars($d->name) ?></td>
                                            <td data-title="Estado">
                                                <label class="switch">
                                                    <input type="checkbox" class="state-toggle" data-id="<?php echo $d->id; ?>" <?php echo (isset($d->state) && $d->state == 0) ? '' : 'checked'; ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <button title="Editar" onclick="editPosition(<?php echo $d->id; ?>, '<?php echo addslashes($d->name); ?>')" class="fa fa-edit" style="color:#06adbf; background:none; border:none; cursor:pointer; font-size: 1.2rem; margin-right: 10px;"></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table> 
                        <?php else: ?>
                            <div class="alert">
                                <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                                <strong>Aviso:</strong> No hay posiciones registradas.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Registro / Edición -->
        <div id="new-tab" class="tab-content">
            <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3 id="form-title">Registrar Nueva Posición</h3>
                    </div>
                    <form id="positionForm" method="POST" autocomplete="off">
                        <input type="hidden" name="id" id="pos_id">
                        <input type="hidden" name="add_position" id="is_add" value="1">
                        <input type="hidden" name="upd_position" id="is_upd" value="0">
                        
                        <div class="containerss">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="name_pos">Nombre de la Posición <span style="color:red;">*</span></label>
                                <input type="text" name="name_pos" id="name_pos" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <input type="hidden" name="created_by" value="<?php echo htmlspecialchars($name); ?>">
                            <input type="hidden" name="updated_by" value="<?php echo htmlspecialchars($name); ?>">
                            
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" class="registerbtn" id="submit-btn" style="flex: 1; margin: 0;">Guardar Posición</button>
                                <button type="button" class="pabtn" style="flex: 1; margin: 0;" onclick="showTab('list-tab', document.getElementById('btn-list-tab'))">Cancelar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src='../../backend/js/submenu.js'></script>
    
    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

    <script type="text/javascript">
    function showTab(tabId, btn) {
        $('.tab-content').removeClass('active');
        $('.tab-button').removeClass('active');
        $('#' + tabId).addClass('active');
        $(btn).addClass('active');
    }

    function prepareNew() {
        $('#form-title').text('Registrar Nueva Posición');
        $('#submit-btn').text('Guardar Posición');
        $('#pos_id').val('');
        $('#name_pos').val('');
        $('#is_add').val('1');
        $('#is_upd').val('0');
        showTab('new-tab', document.getElementById('btn-new-tab'));
    }

    function editPosition(id, name) {
        $('#form-title').text('Modificar Posición de Trabajo');
        $('#submit-btn').text('Actualizar Posición');
        $('#pos_id').val(id);
        $('#name_pos').val(name);
        $('#is_add').val('0');
        $('#is_upd').val('1');
        showTab('new-tab', document.getElementById('btn-new-tab'));
    }

    $(document).ready(function() {
        $('#positionsTable').DataTable({
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            }
        });

        $('#positionForm').on('submit', function(e) {
            e.preventDefault();
            const isUpdate = $('#is_upd').val() === '1';
            const targetUrl = isUpdate ? '../../backend/php/upd_position.php' : '../../backend/php/add_position.php';
            
            $.ajax({
                type: 'POST',
                url: targetUrl,
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        swal("¡Éxito!", response.message, "success").then(() => {
                            window.location.reload();
                        });
                    } else {
                        swal("Error", response.message, "error");
                    }
                },
                error: function() {
                    swal("Error", "Ocurrió un error en el servidor", "error");
                }
            });
        });

        $('.state-toggle').on('change', function() {
            const id = $(this).data('id');
            const state = this.checked ? 1 : 0;
            
            $.ajax({
                type: 'POST',
                url: '../../backend/php/toggle_position_state.php',
                data: { id: id, state: state },
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        swal("Error", response.message, "error");
                        window.location.reload();
                    }
                },
                error: function() {
                    swal("Error", "Ocurrió un error al cambiar el estado", "error");
                    window.location.reload();
                }
            });
        });
    });
    </script>
</body>
</html>
