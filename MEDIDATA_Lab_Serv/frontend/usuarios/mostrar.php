<?php
include_once '../../backend/registros/session_check.php';
require_once __DIR__ . '/../../backend/php/user_delete_cascade.php';

// Eliminar usuario ANTES de cualquier HTML (header() falla si ya se envió salida).
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $idUsuario = (int) $_GET['eliminar'];

    if ($idUsuario === (int) ($_SESSION['id'] ?? 0)) {
        $_SESSION['errorMsg'] = 'No puedes eliminar tu propia cuenta.';
    } else {
        $result = medidata_delete_user_cascade($connect, $idUsuario);
        if ($result['success']) {
            $_SESSION['successMsg'] = $result['message'];
        } else {
            $_SESSION['errorMsg'] = $result['message'];
        }
    }

    session_write_close();
    header('Location: mostrar.php', true, 303);
    exit;
}

// session_check cierra la sesión; reabrir solo para leer/borrar mensajes flash (una sola vez).
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$usuariosFlashSuccess = $_SESSION['successMsg'] ?? null;
$usuariosFlashError = $_SESSION['errorMsg'] ?? null;
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

if (function_exists('session_write_close')) {
    session_write_close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='../../backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    
    <!-- Estilos para botones de acción -->
    <style>
        .btn-action {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 3px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-edit {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #138496;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }
        
        .btn-edit-perfil {
            background-color: #28a745;
            color: white;
        }
        
        .btn-edit-perfil:hover {
            background-color: #218838;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        
        .btn-action i {
            font-size: 14px;
        }

        .medidata-alert-user {
            font-weight: 700;
            color: #111827;
        }
    </style>
    
    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once ((($_SESSION['rol'] ?? '') === 'IT') ? '../it/menu.php' : '../admin/menu.php');
// incluir el archivo menu principal
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
include_once ((($_SESSION['rol'] ?? '') === 'IT') ? '../it/perfil.php' : '../admin/perfil.php');
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

    <button class="button" onclick="cambiarColor(this, '../usuarios/crear_user.php')">Crear Usuarios</button>
    <button class="button" onclick="cambiarColor(this, '../usuarios/mostrar.php')">Lista de Usuarios</button>


    <div class="data">
        <div class="content-data">
            <div class="head">
                <h3>Usuarios Registrados</h3>
            </div>
            <div class="table-responsive" style="overflow-x:auto;">
                <table id="example" class="responsive-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th scope="col">Usuario</th>
                            <th scope="col">Nombre Completo</th>
                            <th scope="col">Cédula</th>
                            <th scope="col">Sexo</th>
                            <th scope="col">Correo Electrónico</th>
                            <th scope="col">Rol</th>
                            <th scope="col">Fecha Creación</th>
                            <th scope="col">Ultima Actividad</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    </main>
    <!-- MAIN -->
</section>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript" src="http://code.jquery.com/jquery-3.5.1.js"></script>
<script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php if ($usuariosFlashSuccess !== null): ?>
<script>
Swal.fire({
    title: '¡Éxito!',
    text: <?php echo json_encode($usuariosFlashSuccess, JSON_UNESCAPED_UNICODE); ?>,
    icon: 'success',
    confirmButtonText: 'Aceptar'
}).then(function () {
    if (window.location.href.includes('?eliminar=')) {
        window.location.href = 'mostrar.php';
    }
});
</script>
<?php endif; ?>
<?php if ($usuariosFlashError !== null): ?>
<script>
Swal.fire({
    title: 'Error',
    text: <?php echo json_encode($usuariosFlashError, JSON_UNESCAPED_UNICODE); ?>,
    icon: 'error',
    confirmButtonText: 'Aceptar'
}).then(function () {
    if (window.location.href.includes('?eliminar=')) {
        window.location.href = 'mostrar.php';
    }
});
</script>
<?php endif; ?>
<script src="../../backend/js/script.js"></script>
<!-- SubMenu -->
<script src='../../backend/js/submenu.js'></script>

<!-- Script para manejar el cambio de color en los botones -->
<script src="../../backend/registros/script/botones_color.js"></script>

<!-- Data Tables -->
<script type="text/javascript" src="../../backend/js/datatable.js"></script>
<script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
<script type="text/javascript" src="../../backend/js/jszip.js"></script>
<script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
<script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
<script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
<script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
<script type="text/javascript">
var MEDIDATA_CURRENT_USER_ID = <?php echo (int) ($_SESSION['id'] ?? 0); ?>;

function esc(text) {
    if (text === null || text === undefined || text === '') { return ''; }
    return $('<div>').text(text).html();
}

$(document).ready(function() {
    $('#example').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        pageLength: 10, // 10 registros por página (carga por página, no todo de golpe)
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'Bfrtip',
        ajax: {
            url: '../../backend/php/get_usuarios.php',
            type: 'GET'
        },
        columns: [
            { data: 'username', render: function (d) { return esc(d) || '—'; } },
            { data: 'name', render: function (d) { return esc(d) || '—'; } },
            { data: 'cedula', render: function (d) { return (d !== null && d !== undefined && String(d).trim() !== '') ? esc(d) : 'N/A'; } },
            { data: 'sexo', render: function (d) { return (d !== null && d !== undefined && String(d).trim() !== '') ? esc(d) : 'N/A'; } },
            { data: 'email', render: function (d) { return esc(d) || '—'; } },
            { data: 'rol', render: function (d) { return esc(d) || '—'; } },
            { data: 'created_at', render: function (d) { return esc(d) || '—'; } },
            { data: 'last_activity', render: function (d) { return esc(d) || '—'; } },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (row) {
                    var id = parseInt(row.id, 10);
                    var html = ''
                        + '<a title="Editar Usuario" href="editar_user.php?id=' + id + '" class="btn-action btn-edit-perfil"><i class="fas fa-user-edit"></i></a> '
                        + '<a title="Cambiar Contraseña" href="password.php?id=' + id + '" class="btn-action btn-edit"><i class="fas fa-key"></i></a>';
                    if (id !== MEDIDATA_CURRENT_USER_ID) {
                        var u = String(row.username || '').replace(/'/g, "\\'");
                        html += ' <a title="Eliminar Usuario" href="javascript:void(0);" onclick="confirmarEliminacion(' + id + ', \'' + esc(u) + '\')" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></a>';
                    }
                    return html;
                }
            }
        ],
        order: [[6, 'desc']], // Fecha de Creación, descendente
        buttons: ['copy', 'csv', 'excel', 'print'],
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
            "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
            "sSearch": "Buscar:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        }
    });
});

// Función para confirmar eliminación de usuario
function confirmarEliminacion(id, username) {
    Swal.fire({
        title: 'Confirmar eliminación',
        html: `Se eliminará el usuario <span class="medidata-alert-user">${username}</span> y, si aplica, su carrito y requisiciones de almacén vinculadas.<br>Esta acción no se puede deshacer.`,
        icon: 'question',
        iconColor: '#f59e0b',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: false,
        customClass: {
            popup: 'medidata-alert',
            title: 'medidata-alert-title',
            htmlContainer: 'medidata-alert-html',
            actions: 'medidata-alert-actions',
            confirmButton: 'btn-medidata-danger',
            cancelButton: 'btn-medidata-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar mensaje de procesamiento
            Swal.fire({
                title: 'Eliminando...',
                text: 'Procesando solicitud...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
            
            window.location.replace(`mostrar.php?eliminar=${id}`);
        }
    });
}
</script>
</body>
</html>
