<?php
require_once '../../backend/registros/session_check.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    header('Location: mis_vacaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA - JEFES DE DEPARTAMENTO</title>
</head>
<body>
    <?php include 'menu_router.php'; ?>
    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? '') . '</strong>'; ?></h1>
            <div class="vp-panel">
                <p class="vp-muted">
                    Asigna el <strong>jefe inmediato</strong> de cada departamento. El jefe podrá aprobar o rechazar
                    las solicitudes de los colaboradores de su departamento y consultar su calendario.
                </p>
                <table id="tabla-jefes" class="responsive-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Departamento</th>
                            <th>Jefe asignado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script>
    var BASE = '../../backend/registros/vacaciones_permisos/';
    var tablaJefes, colaboradores = [];

    function vpEsc(v) {
        if (v === null || v === undefined) return '';
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function asignar(id, nombreDep, idJefeActual) {
        var opciones = '<option value="">— Sin jefe —</option>';
        colaboradores.forEach(function(c) {
            var sel = (parseInt(c.id_user, 10) === parseInt(idJefeActual, 10)) ? 'selected' : '';
            opciones += '<option value="' + c.id_user + '" ' + sel + '>' + vpEsc(c.text) + '</option>';
        });
        Swal.fire({
            title: 'Jefe de ' + vpEsc(nombreDep),
            html: '<select id="sel_jefe" class="form-control" style="width:100%">' + opciones + '</select>',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#035c67',
            preConfirm: function() { return { id_jefe: document.getElementById('sel_jefe').value }; }
        }).then(function(res) {
            if (res.isConfirmed) {
                $.post(BASE + 'save_departamento_jefe.php', { id: id, id_jefe: res.value.id_jefe }, function(r) {
                    if (r.success) {
                        Swal.fire('Guardado', r.message, 'success');
                        tablaJefes.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', r.message, 'error');
                    }
                }, 'json');
            }
        });
    }

    $(document).ready(function() {
        // Cargar colaboradores para el selector de jefe
        $.getJSON(BASE + 'fetch_empleados_vacaciones.php', function(data) {
            if (Array.isArray(data)) colaboradores = data;
        });

        tablaJefes = $('#tabla-jefes').DataTable({
            ajax: { url: BASE + 'fetch_departamentos_jefes.php', dataSrc: 'data' },
            columns: [
                { data: 'name' },
                { data: 'jefe_nombre', defaultContent: '', render: function(d) { return d ? vpEsc(d) : '<span class="status pending">Sin asignar</span>'; } },
                {
                    data: null, orderable: false, className: 'vp-col-center',
                    render: function(data, type, row) {
                        return '<button class="vp-icon-btn" onclick="asignar(' + row.id + ', \'' + vpEsc(row.name).replace(/'/g, "\\'") + '\', ' + (row.id_jefe || 0) + ')" title="Asignar jefe"><i class=\'bx bx-user-check\'></i> Asignar</button>';
                    }
                }
            ],
            order: [[0, 'asc']],
            language: {
                lengthMenu: 'Mostrar _MENU_ registros', zeroRecords: 'No se encontraron resultados',
                emptyTable: 'No hay departamentos.', info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros', infoFiltered: '(filtrado de _MAX_ registros totales)',
                search: 'Buscar:', paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
            }
        });
    });
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
