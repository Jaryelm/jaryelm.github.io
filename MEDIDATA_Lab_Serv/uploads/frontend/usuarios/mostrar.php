<?php
include_once '../../backend/registros/session_check.php';

// Eliminar usuario ANTES de cualquier HTML (header() falla si ya se envió salida).
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $idUsuario = (int) $_GET['eliminar'];

    try {
        if ($idUsuario === (int) ($_SESSION['id'] ?? 0)) {
            $_SESSION['errorMsg'] = 'No puedes eliminar tu propia cuenta.';
        } else {
            $stmtCheck = $connect->prepare('SELECT username FROM users WHERE id = ?');
            $stmtCheck->execute([$idUsuario]);
            $usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $stmtDelete = $connect->prepare('DELETE FROM users WHERE id = ?');
                if ($stmtDelete->execute([$idUsuario])) {
                    $_SESSION['successMsg'] = "Usuario '{$usuario['username']}' eliminado correctamente.";
                } else {
                    $_SESSION['errorMsg'] = 'Error al eliminar el usuario.';
                }
            } else {
                $_SESSION['errorMsg'] = 'Usuario no encontrado.';
            }
        }
    } catch (Exception $e) {
        $_SESSION['errorMsg'] = 'Error: ' . $e->getMessage();
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
include_once '../admin/menu.php';
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

    <button class="button" onclick="cambiarColor(this, '../usuarios/crear_user.php')">Crear Usuarios</button>
    <button class="button" onclick="cambiarColor(this, '../usuarios/mostrar.php')">Lista de Usuarios</button>


    <?php
    $sentencia = $connect->prepare("SELECT * FROM users ORDER BY id DESC;");
    $sentencia->execute();
    $data = array();
    if($sentencia){
        while($r = $sentencia->fetchObject()){
            $data[] = $r;
        }
    }
    ?>

    <div class="data">
        <div class="content-data">
            <div class="head">
                <h3>Usuarios Registrados</h3>
            </div>
            <div class="table-responsive" style="overflow-x:auto;">
                <?php if(count($data) > 0): ?>
                    <table id="example" class="responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">Usuario</th>
                                <th scope="col">Nombre Completo</th>
                                <th scope="col">Correo Electrónico</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Fecha Creación</th>
                                <th scope="col">Ultima Actividad</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data as $d): ?>
                                <tr>
                                    <td><?php echo $d->username; ?></td>
                                    <td><?php echo $d->name; ?></td>
                                    <td><?php echo $d->email; ?></td>
                                    <td><?php echo $d->rol; ?></td>
                                    <td><?php echo $d->created_at; ?></td>
                                    <td><?php echo $d->last_activity; ?></td>
                                    <td>
                                        <a title="Editar Usuario" href="editar_user.php?id=<?php echo $d->id; ?>" class="btn-action btn-edit-perfil">
                                            <i class="fas fa-user-edit"></i>
                                        </a>
                                        <a title="Cambiar Contraseña" href="password.php?id=<?php echo $d->id; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-key"></i>
                                        </a>
                                        <?php if ($d->id != $_SESSION['id']): ?>
                                            <a title="Eliminar Usuario" href="javascript:void(0);" onclick="confirmarEliminacion(<?php echo $d->id; ?>, '<?php echo addslashes($d->username); ?>')" class="btn-action btn-delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table> 
                <?php else: ?>
                    <div class="alert">
                        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                        <strong>Danger!</strong> No hay datos.
                    </div>
                <?php endif; ?>
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
$(document).ready(function() {
    $('#example').DataTable({
        pageLength: 10, // Establece explícitamente 10 registros por página
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
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
        },
        order: [[4, 'desc']] // Ordenar por la columna de "Fecha de Creación" (índice 4) de forma descendente
    });
});

// Función para confirmar eliminación de usuario
function confirmarEliminacion(id, username) {
    Swal.fire({
        title: 'Confirmar eliminación',
        html: `Se eliminará el usuario <span class="medidata-alert-user">${username}</span>.<br>Esta acción no se puede deshacer.`,
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
