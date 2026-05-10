$(document).ready(function () {
    let cuentasOptions = '';

    // Cargar las opciones de cuenta solo una vez y reutilizarlas
    $.post('../../frontend/funciones/cat_cuentas.php').done(function(respuesta) {
        cuentasOptions = respuesta;
        $('select[name="cat_cuenta[]"]').html(cuentasOptions);
        $('.select2').select2();
    });

    // Función para agregar un nuevo producto a la tabla
    function addItemRow() {
        const table = document.getElementById('items_table').getElementsByTagName('tbody')[0];
        const newRow = table.insertRow();

        newRow.innerHTML = `
            <td>
                <select name="cat_cuenta[]" class="select2" style="width: 200px;">
                    ${cuentasOptions}
                </select>
            </td>
            <td><input type="text" name="codigo_producto[]" required placeholder="Código"></td>
            <td><input type="number" name="cantidad[]" min="1" required placeholder="Cantidad" oninput="calcularTotales(this)"></td>
            <td><input type="text" name="unidad[]" required placeholder="Unidad"></td>
            <td><input type="text" name="descripcion[]" required placeholder="Descripción"></td>
            <td><input type="number" name="precio_unitario[]" min="0" step="0.01" required placeholder="Precio Unitario" oninput="calcularTotales(this)"></td>
            <td><input type="checkbox" name="exento[]" onclick="toggleExentoGravado(this)" value="exento"></td>
            <td><input type="checkbox" name="gravado[]" onclick="toggleExentoGravado(this)" value="gravado"></td>
            <td><input type="number" name="isv[]" min="0" step="0.01" required readonly placeholder="ISV"></td>
            <td><input type="number" name="subtotal[]" min="0" step="0.01" required readonly placeholder="Subtotal"></td>
            <td><input type="number" name="descuento_porcentaje[]" min="0" max="100" step="0.01" placeholder="% Descuento" oninput="calcularTotales(this)"></td>
            <td><input type="number" name="total_item[]" min="0" step="0.01" required readonly placeholder="Total por Item"></td>
            <td><button type="button" class="item-table-button" onclick="removeItem(this)">Eliminar</button></td>
        `;

        $(newRow).find('.select2').select2(); // Inicializar select2 en el nuevo campo de cuenta
        calcularTotalesGenerales();
    }

    // Asignar la función al botón de agregar producto
    $('.item-table-button').on('click', function() {
        addItemRow();
    });
});
