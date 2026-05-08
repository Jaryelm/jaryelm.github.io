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
    
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    
    <title>MEDIDATA</title>
</head>
<body>

<?php include_once '../admin/menu.php'; ?>

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
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : 
                 (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <!-- Botones de navegación -->
        <button class="button" onclick="cambiarColor(this, 'compra_unificada.php')">Compra e inventario</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_compras.php')">Compras Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'categoria_new.php')">Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'categoria.php')">Lista de Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'nuevo_servicio.php')">Registrar Servicio</button>
        <button class="button" onclick="cambiarColor(this, 'lista_servicios.php')">Lista de Servicios</button>
        <button class="button" onclick="cambiarColor(this, 'reorden.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_solicitud_reorden_admin.php')">Autorización Compras Almacen</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones.php')">Requisiciones</button>

        <!-- Formulario de Requisición -->
        <form id="requisicionForm" method="POST" autocomplete="off">
            <div class="containerss">
                <h1>DETALLES DE REQUISICIÓN</h1>
                <br>
                <div class="alert-danger">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                    <strong>Importante!</strong> Es necesario rellenar los campos con &nbsp;<span class="badge-warning">*</span>
                </div>
                <hr>
                <br>

                <!-- Tipo de Requisición -->
                <label for="tipo_requisicion"><b>Tipo de Requisición</b></label><span class="badge-warning">*</span>
                <select id="tipo_requisicion" name="tipo_requisicion" class="select2" required>
                    <option value="interna">Interna (entre departamentos)</option>
                    <option value="externa">Externa (para pacientes)</option>
                </select>

                <!-- Paciente (solo para externas) -->
                <div id="pacienteSection" style="display:none;">
                    <label for="paciente"><b>Paciente</b></label><span class="badge-warning">*</span>
                    <select id="paciente" name="paciente" class="select2">
                        <option value="">Seleccione un paciente</option>
                        <?php
                        $stmt = $connect->query("SELECT idpa, nompa, apepa FROM patients ORDER BY nompa, apepa");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $nombreCompleto = $row['nompa'] . ' ' . $row['apepa'];
                            echo '<option value="' . htmlspecialchars($nombreCompleto) . '">' . htmlspecialchars($nombreCompleto) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Solicitante y Fecha -->
                <label for="solicitante"><b>Solicitante</b></label><span class="badge-warning">*</span>
                <select id="solicitante" name="solicitante" class="select2" required>
                    <option value="">Seleccione...</option>
                    <?php
                    $stmt = $connect->query("SELECT * FROM users WHERE rol IS NOT NULL");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
                    }
                    ?>
                </select>

                <label for="fecha"><b>Fecha</b></label>
                <input type="text" id="fecha" name="fecha" value="<?php echo date('d-m-Y'); ?>" readonly>

                <label for="justificacion"><b>Justificación</b></label><span class="badge-warning">*</span>
                <textarea id="justificacion" name="justificacion" rows="4" required></textarea>

                <hr>

                <!-- Sección de DESCARGO -->
                <label for="descargo"><b>DESCARGO</b></label>
                <div class="sub-form">
                    <label for="sucursal_descargo"><b>Sucursal</b></label>
                    <select id="sucursal_descargo" name="sucursal_descargo" class="select2" required>
                        <option value="SUCURSAL 1">SUCURSAL 1</option>
                    </select>

                    <label for="bodega_descargo"><b>Bodega</b></label>
                    <select id="bodega_descargo" name="bodega_descargo" class="select2" required>
                        <option value="">SELECCIONE</option>
                        <option value="Bodega General">Bodega General</option>
                        <option value="Almacen Hospitalario">Almacen Hospitalario</option>
                        <option value="Quirofano">Quirofano</option>
                        <option value="Emergencia">Emergencia</option>
                        <option value="Area Hospitalizacion">Area Hospitalización</option>
                        <option value="Atencion al Cliente">Atención al Cliente</option>
                        <option value="Procedimiento">Procedimiento</option>
                        <option value="Odontologia">Odontología</option>
                        <option value="Radiología e Imagen">Radiología e Imagen</option>
                        <option value="Radiodiagnóstico Dental">Radiodiagnóstico Dental</option>
                        <option value="Tomografia">Tomografía</option>
                        <option value="Ultrasonido">Ultrasonido</option>
                        <option value="Unidad Digestiva">Unidad Digestiva</option>
                        <option value="Mamografia">Mamografía</option>
                        <option value="UCI">UCI</option>
                    </select>
                </div>

                <!-- Sección de CARGO -->
                <label for="cargo"><b>CARGO</b></label>
                <div class="sub-form">
                    <label for="sucursal_cargo"><b>Sucursal</b></label>
                    <select id="sucursal_cargo" name="sucursal_cargo" class="select2" required>
                        <option value="SUCURSAL 1">SUCURSAL 1</option>
                    </select>

                    <label for="bodega_cargo"><b>Bodega</b></label>
                    <select id="bodega_cargo" name="bodega_cargo" class="select2" required>
                        <option value="">SELECCIONE</option>
                        <option value="Bodega General">Bodega General</option>
                        <option value="Almacen Hospitalario">Almacen Hospitalario</option>
                        <option value="Quirofano">Quirofano</option>
                        <option value="Emergencia">Emergencia</option>
                        <option value="Area Hospitalizacion">Area Hospitalización</option>
                        <option value="Atencion al Cliente">Atención al Cliente</option>
                        <option value="Procedimiento">Procedimiento</option>
                        <option value="Odontologia">Odontología</option>
                        <option value="Radiologia">Radiología</option>
                        <option value="Radiodiagnostico">Radiodiagnóstico</option>
                        <option value="Tomografia">Tomografía</option>
                        <option value="Ultrasonido">Ultrasonido</option>
                        <option value="Unidad Digestiva">Unidad Digestiva</option>
                        <option value="Mamografia">Mamografía</option>
                        <option value="UCI">UCI</option>
                    </select>
                </div>

                <hr>

                <!-- Tabla de Artículos -->
                <label><b>AGREGAR ARTÍCULOS A LA REQUISICIÓN</b></label>
                <table id="detallesTable" class="items-table">
                    <thead>
                        <tr>
                            <th>Cantidad</th>
                            <th>Artículo</th>
                            <th>Existencia</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="number" id="cantidad" min="1" class="form-control"></td>
                            <td>
                                <select id="articulo" class="select2 form-control">
                                    <option value="">Seleccione un artículo</option>
                                    <?php
                                    $stmt = $connect->query("SELECT * FROM product WHERE state='1'");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="'.$row['idprcd'].'" data-stock="'.$row['stock'].'" data-precio="'.$row['preprd'].'">'
                                            .$row['nompro'].' (Stock: '.$row['stock'].')</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="text" id="existencia" readonly class="form-control"></td>
                            <td><input type="text" id="costo" readonly class="form-control"></td>
                            <td>
                                <button type="button" id="agregarArticulo" class="item-table-button">
                                    Agregar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div id="articulosAgregados">
                    <table id="tablaArticulos" class="items-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Artículo</th>
                                <th>Cantidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Contenedor oculto para almacenar los artículos -->
                <div id="articulosData"></div>

                <hr>
                <button type="submit" class="registerbtn">Enviar Requisición</button>
            </div>
        </form>
    </main>
</section>

<!-- Scripts -->
<script src="../../backend/js/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

<script>
$(document).ready(function() {
    $('.select2').select2();
    
    // Actualizar existencia y costo al seleccionar artículo
    $('#articulo').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        $('#existencia').val(selectedOption.data('stock'));
        $('#costo').val(selectedOption.data('precio'));
    });

    // Agregar artículo a la tabla
    $('#agregarArticulo').on('click', function() {
        const cantidad = $('#cantidad').val();
        const articulo = $('#articulo option:selected');
        const codigo = articulo.val();
        const nombre = articulo.text().split('(')[0].trim();
        
        if (!cantidad || !codigo) {
            swal('Error', 'Por favor complete todos los campos', 'error');
            return;
        }

        // Verificar si el artículo ya existe en la tabla
        if ($(`#tablaArticulos tr[data-codigo="${codigo}"]`).length > 0) {
            swal('Error', 'Este artículo ya ha sido agregado', 'error');
            return;
        }

        // Agregar fila a la tabla
        $('#tablaArticulos tbody').append(`
            <tr data-codigo="${codigo}" data-cantidad="${cantidad}">
                <td>${codigo}</td>
                <td>${nombre}</td>
                <td>${cantidad}</td>
                <td>
                    <button type="button" class="btn-eliminar" onclick="eliminarArticulo(this)">
                        <i class='bx bxs-trash'></i>
                    </button>
                </td>
            </tr>
        `);

        // Agregar campo oculto con los datos del artículo
        actualizarCamposOcultos();

        // Limpiar campos
        $('#cantidad').val('');
        $('#articulo').val('').trigger('change');
        $('#existencia').val('');
        $('#costo').val('');
    });

    // Enviar formulario
    $('#requisicionForm').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#tablaArticulos tbody tr').length === 0) {
            swal('Error', 'Debe agregar al menos un artículo', 'error');
            return;
        }

        // Enviar datos al servidor usando AJAX
        $.ajax({
            url: '../../backend/php/guardar_requisicion.php',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                const res = JSON.parse(response);
                if (res.success) {
                    swal('Éxito', res.message, 'success')
                    .then(() => {
                        window.location.href = 'lista_requisiciones.php';
                    });
                } else {
                    swal('Error', res.message, 'error');
                }
            },
            error: function() {
                swal('Error', 'Hubo un problema al guardar la requisición', 'error');
            }
        });
    });

    $('#tipo_requisicion').on('change', function() {
        if ($(this).val() === 'externa') {
            $('#pacienteSection').show();
            $('#paciente').prop('required', true);
        } else {
            $('#pacienteSection').hide();
            $('#paciente').val('').trigger('change');
            $('#paciente').prop('required', false);
        }
    });
});

function eliminarArticulo(btn) {
    $(btn).closest('tr').remove();
    actualizarCamposOcultos();
}

function actualizarCamposOcultos() {
    // Limpiar contenedor de datos
    $('#articulosData').empty();
    
    // Crear campos ocultos para cada artículo
    $('#tablaArticulos tbody tr').each(function(index) {
        const codigo = $(this).data('codigo');
        const cantidad = $(this).data('cantidad');
        
        $('#articulosData').append(`
            <input type="hidden" name="articulos[${index}][codigo]" value="${codigo}">
            <input type="hidden" name="articulos[${index}][cantidad]" value="${cantidad}">
        `);
    });
}
</script>

<style>
/* Estilos generales del contenedor */
.containerss {
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin: 20px;
}

/* Estilos para los campos de formulario */
.containerss input[type=text],
.containerss input[type=number],
.containerss select,
.containerss textarea {
    width: 100%;
    padding: 12px;
    margin: 5px 0 22px 0;
    display: inline-block;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

/* Estilos para las etiquetas */
.containerss label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
}



.closebtn {
    color: #721c24;
    font-weight: bold;
    float: right;
    font-size: 22px;
    line-height: 20px;
    cursor: pointer;
}

.badge-warning {
    color: #ff0000;
    margin-left: 3px;
}

/* Estilos para el sub-formulario */
.sub-form {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin: 10px 0 22px 0;
}

/* Estilos para la tabla */
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #f9f9f9;
}

.items-table th {
    background-color: #06adbf;
    color: white;
    padding: 12px;
    text-align: left;
    font-weight: bold;
}

.items-table td {
    padding: 8px;
    border-bottom: 1px solid #ddd;
}

.items-table tr:nth-child(even) {
    background-color: #f2f2f2;
}

/* Estilos para los botones */
.item-table-button {
    background-color: #035c67;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.item-table-button:hover {
    background-color: #06adbf;
}



/* Estilos para Select2 */
.select2-container {
    width: 100% !important;
    margin-bottom: 22px;
}

.select2-container--default .select2-selection--single {
    height: 42px;
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
}

/* Responsive */
@media screen and (max-width: 768px) {
    .containerss {
        margin: 10px;
        padding: 15px;
    }

    .items-table {
        display: block;
        overflow-x: auto;
    }
}
</style>

</body>
</html> 