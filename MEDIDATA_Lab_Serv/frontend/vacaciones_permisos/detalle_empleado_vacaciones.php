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
    <?php include '../recursos_humanos/_rrhh_select2_head.php'; ?>
    <title>MEDIDATA - DETALLE DE EMPLEADO</title>
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
                <label for="empleado_select" class="vp-select-label">Seleccionar Colaborador:</label>
                <select id="empleado_select" style="width: 100%;">
                    <option value="">Seleccione un colaborador...</option>
                </select>
            </div>

            <div id="profile-content" style="display: none;">
                <div class="vp-profile-grid">
                    <!-- Información General -->
                    <div class="vp-panel">
                        <h3>Información General</h3>
                        <div class="vp-data-row"><span class="vp-data-label">Nombre Completo</span> <span class="vp-data-value" id="lbl_nombre"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Código de colaborador</span> <span class="vp-data-value" id="lbl_codigo"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Puesto</span> <span class="vp-data-value" id="lbl_puesto"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Departamento</span> <span class="vp-data-value" id="lbl_depto"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Jefe Inmediato</span> <span class="vp-data-value" id="lbl_jefe"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Fecha de Ingreso</span> <span class="vp-data-value" id="lbl_ingreso"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Antigüedad</span> <span class="vp-data-value" id="lbl_antiguedad"></span></div>
                    </div>

                    <!-- Información de Vacaciones -->
                    <div class="vp-panel">
                        <h3>Información de Vacaciones</h3>
                        <div class="vp-data-row"><span class="vp-data-label">Período vacacional</span> <span class="vp-data-value" id="lbl_periodo"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Días otorgados</span> <span class="vp-data-value" id="lbl_otorgados"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Días disfrutados</span> <span class="vp-data-value" id="lbl_disfrutados"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Días pendientes</span> <span class="vp-data-value vp-data-value--highlight" id="lbl_pendientes"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Días pagados</span> <span class="vp-data-value" id="lbl_pagados"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Fecha del último disfrute</span> <span class="vp-data-value" id="lbl_ultimo"></span></div>
                        <div class="vp-data-row"><span class="vp-data-label">Próxima fecha de generación</span> <span class="vp-data-value" id="lbl_proxima"></span></div>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <?php include '../recursos_humanos/_rrhh_select2_foot.php'; ?>

    <script>
    $(document).ready(function() {
        // Inicializar Select2 y cargar empleados
        $('#empleado_select').select2({
            placeholder: "Buscar colaborador...",
            ajax: {
                url: '../../backend/registros/vacaciones_permisos/fetch_empleados_vacaciones.php',
                dataType: 'json',
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.id_user,
                                text: item.text
                            }
                        })
                    };
                }
            }
        });

        // Evento al seleccionar un colaborador
        $('#empleado_select').on('change', function() {
            var userId = $(this).val();
            if(userId) {
                $.getJSON('../../backend/registros/vacaciones_permisos/fetch_vacation_profile.php', { user_id: userId }, function(res) {
                    if(res.error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.error });
                        return;
                    }

                    // Llenar info general
                    $('#lbl_nombre').text(res.info_general.nombre_completo);
                    $('#lbl_codigo').text(res.info_general.codigo_colaborador);
                    $('#lbl_puesto').text(res.info_general.puesto || 'N/A');
                    $('#lbl_depto').text(res.info_general.departamento || 'N/A');
                    $('#lbl_jefe').text(res.info_general.jefe_inmediato);
                    $('#lbl_ingreso').text(res.info_general.fecha_ingreso);
                    $('#lbl_antiguedad').text(res.info_general.antiguedad);

                    // Llenar info de vacaciones
                    $('#lbl_periodo').text(res.info_vacaciones.periodo_vacacional);
                    $('#lbl_otorgados').text(res.info_vacaciones.dias_otorgados);
                    $('#lbl_disfrutados').text(res.info_vacaciones.dias_disfrutados);
                    $('#lbl_pendientes').text(res.info_vacaciones.dias_pendientes);
                    $('#lbl_pagados').text(res.info_vacaciones.dias_pagados);
                    $('#lbl_ultimo').text(res.info_vacaciones.fecha_ultimo_disfrute);
                    $('#lbl_proxima').text(res.info_vacaciones.proxima_generacion);

                    $('#profile-content').fadeIn();
                });
            } else {
                $('#profile-content').hide();
            }
        });
    });
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
