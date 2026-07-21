<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos Humanos'])) {
    header('Location: detalle_empleado_vacaciones_usr.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Vacaciones por Colaborador - MEDIDATA</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .info-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .info-section h3 {
            border-bottom: 2px solid var(--blue);
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: var(--dark-blue);
        }
        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .data-row span:first-child {
            font-weight: bold;
            color: #555;
        }
        .data-row span:last-child {
            color: #333;
        }
        .select-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
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
        <div class="header">
            <h2>Ficha de Vacaciones por Colaborador</h2>
        </div>
        
        <div class="select-container">
            <label for="empleado_select" style="font-weight: bold; margin-bottom:10px; display:block;">Seleccionar Colaborador:</label>
            <select id="empleado_select" style="width: 100%;">
                <option value="">Seleccione un colaborador...</option>
            </select>
        </div>

        <div id="profile-content" style="display: none;">
            <div class="profile-grid">
                <!-- Información General -->
                <div class="info-section">
                    <h3>Información General</h3>
                    <div class="data-row"><span>Nombre Completo</span> <span id="lbl_nombre"></span></div>
                    <div class="data-row"><span>Código de colaborador</span> <span id="lbl_codigo"></span></div>
                    <div class="data-row"><span>Puesto</span> <span id="lbl_puesto"></span></div>
                    <div class="data-row"><span>Departamento</span> <span id="lbl_depto"></span></div>
                    <div class="data-row"><span>Jefe Inmediato</span> <span id="lbl_jefe"></span></div>
                    <div class="data-row"><span>Fecha de Ingreso</span> <span id="lbl_ingreso"></span></div>
                    <div class="data-row"><span>Antigüedad</span> <span id="lbl_antiguedad"></span></div>
                </div>

                <!-- Información de Vacaciones -->
                <div class="info-section">
                    <h3>Información de Vacaciones</h3>
                    <div class="data-row"><span>Período vacacional</span> <span id="lbl_periodo"></span></div>
                    <div class="data-row"><span>Días otorgados</span> <span id="lbl_otorgados"></span></div>
                    <div class="data-row"><span>Días disfrutados</span> <span id="lbl_disfrutados"></span></div>
                    <div class="data-row"><span>Días pendientes</span> <span id="lbl_pendientes" style="font-weight:bold; color:var(--blue);"></span></div>
                    <div class="data-row"><span>Días pagados</span> <span id="lbl_pagados"></span></div>
                    <div class="data-row"><span>Fecha del último disfrute</span> <span id="lbl_ultimo"></span></div>
                    <div class="data-row"><span>Próxima fecha de generación</span> <span id="lbl_proxima"></span></div>
                </div>
            </div>
        </div>
        </div>
        </main>
    </section>

    <script src="../../backend/js/script.js"></script>

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
                        alert(res.error);
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




