<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$vacantes = medidata_rrhh_vacantes_filtro();
$id_vacante = isset($_GET['id_vacante']) ? (int) $_GET['id_vacante'] : 0;
$rrhh_disponible = medidata_rrhh_pdo() !== null;
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

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">

    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
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
        <?php include __DIR__ . '/_rrhh_aviso.php'; ?>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Filtrar por Plaza Vacante</h3>
                </div>
                <form method="GET" action="" class="rrhh-filter-row">
                        <div class="form-group">
                            <label for="id_vacante">Seleccionar Vacante</label>
                            <select class="select2" name="id_vacante" id="id_vacante">
                                <option value="0">Todas las vacantes</option>
                                <?php foreach ($vacantes as $v): ?>
                                <option value="<?php echo $v['id']; ?>" <?php echo $id_vacante == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="rrhh-filter-actions">
                            <button type="submit" class="button rrhh-btn-inline">Filtrar</button>
                            <a href="postulantes_usr.php" class="pabtn rrhh-btn-inline">Limpiar</a>
                        </div>
                </form>
            </div>
        </div>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Listado de Postulantes (En Espera)</h3>
                    <?php if ($rrhh_disponible): ?>
                    <button type="button" class="button" id="btn-agregar-candidato-manual">Agregar candidato manual</button>
                    <?php endif; ?>
                </div>
                <div class="table-responsive" style="overflow-x:auto;">
                    <?php
                    $data = medidata_rrhh_fetch_postulantes("p.status = 'En Espera'", $id_vacante);
                    ?>
                    <?php if(count($data) > 0): ?>
                        <table id="example" class="responsive-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Vacante</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">DNI</th>
                                    <th scope="col">Teléfono</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Disponibilidad</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data as $d): ?>
                                    <tr>
                                        <th scope="row"><?php echo $d->id ?></th>
                                        <td data-title="Vacante"><?php echo htmlspecialchars($d->vacancy_name ?? 'N/A') ?></td>
                                        <td data-title="Nombre"><?php echo htmlspecialchars($d->fullname) ?></td>
                                        <td data-title="DNI"><?php echo htmlspecialchars($d->dni) ?></td>
                                        <td data-title="Teléfono"><?php echo htmlspecialchars($d->phonenumber) ?></td>
                                        <td data-title="Email"><?php echo htmlspecialchars($d->email) ?></td>
                                        <td data-title="Disponibilidad">
                                            <?php echo $d->isAvailability ? '<span class="badge-rrhh badge-rrhh-yes">Sí</span>' : '<span class="badge-rrhh badge-rrhh-no">No</span>'; ?>
                                        </td>
                                        <td>
                                            <a title="Ver detalle" href="detalle_postulante_usr.php?id=<?php echo $d->id ?>" class="fa fa-eye"></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table> 
                    <?php else: ?>
                        <div class="alert">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                            <strong>Aviso:</strong> No hay postulantes en espera registrados para esta selección.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>  

        </main>
    </section>

    <div id="modal-candidato-manual" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;padding:16px;">
        <div style="background:#fff;border-radius:10px;max-width:520px;width:100%;padding:24px;max-height:90vh;overflow-y:auto;">
            <h3 style="margin:0 0 16px;color:#035c67;">Registrar candidato (captación manual)</h3>
            <p style="font-size:.9rem;color:#555;margin:0 0 16px;">Para candidatos captados por correo, presencial u otro medio fuera del sitio web.</p>
            <form id="form-candidato-manual">
                <div class="form-group" style="margin-bottom:12px;">
                    <label for="cm_vacante"><b>Vacante *</b></label>
                    <select class="select2" name="id_vacante" id="cm_vacante" required style="width:100%;">
                        <option value="">Seleccione...</option>
                        <?php foreach ($vacantes as $v): ?>
                        <option value="<?php echo (int) $v['id']; ?>"><?php echo htmlspecialchars($v['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label for="cm_fullname"><b>Nombre completo *</b></label>
                    <input type="text" name="fullname" id="cm_fullname" required style="width:100%;padding:10px;box-sizing:border-box;">
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label for="cm_dni"><b>No. identidad *</b></label>
                    <input type="text" name="dni" id="cm_dni" required style="width:100%;padding:10px;box-sizing:border-box;">
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label for="cm_phone"><b>Teléfono *</b></label>
                    <input type="text" name="phonenumber" id="cm_phone" required style="width:100%;padding:10px;box-sizing:border-box;">
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label for="cm_email"><b>Correo *</b></label>
                    <input type="email" name="email" id="cm_email" required style="width:100%;padding:10px;box-sizing:border-box;">
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="cm_referral"><b>Origen / medio</b></label>
                    <input type="text" name="referral_source" id="cm_referral" value="Captación manual" style="width:100%;padding:10px;box-sizing:border-box;">
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="registerbtn">Guardar candidato</button>
                    <button type="button" class="pabtn" id="btn-cerrar-candidato-manual">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

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

    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script type="text/javascript">
    $(document).ready(function() {
        $('#example').DataTable({
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            }
        });

        var $modal = $('#modal-candidato-manual');
        $('#btn-agregar-candidato-manual').on('click', function () {
            $modal.css('display', 'flex');
        });
        $('#btn-cerrar-candidato-manual').on('click', function () {
            $modal.hide();
        });
        $('#form-candidato-manual').on('submit', function (e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: '../../backend/php/rrhh_candidato_crear.php',
                data: $(this).serialize(),
                dataType: 'json'
            }).done(function (res) {
                if (res.success) {
                    Swal.fire('Registrado', res.message, 'success').then(function () {
                        if (res.candidate_id) {
                            window.location.href = 'detalle_postulante_usr.php?id=' + res.candidate_id;
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire('Aviso', res.message || 'No se pudo registrar.', 'warning');
                    $btn.prop('disabled', false);
                }
            }).fail(function () {
                Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                $btn.prop('disabled', false);
            });
        });
    });
    </script>
</body>
</html>
