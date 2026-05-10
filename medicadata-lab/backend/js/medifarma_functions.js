// Funciones específicas para el módulo de Medifarma

function toggleCampos(tipo) {
    const diasCreditoField = document.getElementById('dias_credito_field');
    const primaFields = document.getElementById('prima_fields');
    
    // Ocultar todos los campos primero
    diasCreditoField.style.display = 'none';
    primaFields.style.display = 'none';
    
    // Mostrar los campos correspondientes según el tipo seleccionado
    if (tipo === 'credito') {
        diasCreditoField.style.display = 'block';
    } else if (tipo === 'prima') {
        primaFields.style.display = 'block';
    }
    
    // Calcular fecha de vencimiento
    calcularFechaVencimiento();
}

function calcularFechaVencimiento() {
    const fechaEmision = document.getElementById('fecha_emision').value;
    const diasCredito = document.getElementById('dias_credito').value;
    const cuotasPendientes = document.getElementById('cuotas_pendientes').value;
    const fechVence = document.getElementById('fech_vence');
    
    if (fechaEmision) {
        let fechaVencimiento = new Date(fechaEmision);
        
        // Si es crédito y hay días de crédito
        if (diasCredito && diasCredito > 0) {
            fechaVencimiento.setDate(fechaVencimiento.getDate() + parseInt(diasCredito));
        }
        // Si es prima y hay cuotas pendientes
        else if (cuotasPendientes && cuotasPendientes > 0) {
            // Por defecto, 30 días por cuota
            fechaVencimiento.setDate(fechaVencimiento.getDate() + (parseInt(cuotasPendientes) * 30));
        }
        // Si es contado, la fecha de vencimiento es la misma que la de emisión
        else {
            // No hacer nada, mantener la fecha de emisión
        }
        
        // Formatear la fecha para el input
        const year = fechaVencimiento.getFullYear();
        const month = String(fechaVencimiento.getMonth() + 1).padStart(2, '0');
        const day = String(fechaVencimiento.getDate()).padStart(2, '0');
        
        fechVence.value = `${year}-${month}-${day}`;
    }
}

function addItemRow() {
    const tbody = document.querySelector('#items_table tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <select name="cat_cuenta[]" class="select2" style="width: 200px;">
                <option value="">Seleccione</option>
                <!-- Aquí se cargan las opciones de cuentas -->
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
    tbody.appendChild(newRow);
    
    // Reinicializar Select2 en la nueva fila
    $(newRow).find('.select2').select2();
}

function removeItem(button) {
    const row = button.parentElement.parentElement;
    row.remove();
    calcularTotalesGenerales();
}

function toggleExentoGravado(checkbox) {
    const row = checkbox.closest("tr");
    const exentoCheckbox = row.querySelector('input[name="exento[]"]');
    const gravadoCheckbox = row.querySelector('input[name="gravado[]"]');

    if (checkbox.value === "exento" && checkbox.checked) {
        gravadoCheckbox.checked = false;
    } else if (checkbox.value === "gravado" && checkbox.checked) {
        exentoCheckbox.checked = false;
    }
    
    calcularTotales(checkbox);
}

function calcularTotales(element) {
    const row = element.closest("tr");
    const cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
    const precioUnitario = parseFloat(row.querySelector('input[name="precio_unitario[]"]').value) || 0;
    const descuentoPorcentaje = parseFloat(row.querySelector('input[name="descuento_porcentaje[]"]').value) || 0;
    const isvField = row.querySelector('input[name="isv[]"]');
    const subtotalField = row.querySelector('input[name="subtotal[]"]');
    const totalItemField = row.querySelector('input[name="total_item[]"]');
    const gravado = row.querySelector('input[name="gravado[]"]').checked ? "Si" : "No";

    let isv = 0;
    let subtotal = cantidad * precioUnitario;

    if (gravado === "Si") {
        isv = subtotal * 0.15; // Aplicar ISV del 15%
    }

    isvField.value = isv.toFixed(2);
    subtotalField.value = subtotal.toFixed(2);

    const descuentoAplicado = subtotal * (descuentoPorcentaje / 100);
    const totalItem = subtotal + isv - descuentoAplicado;
    totalItemField.value = totalItem.toFixed(2);

    calcularTotalesGenerales();
}

function calcularTotalesGenerales() {
    const rows = document.querySelectorAll('#items_table tbody tr');
    let totalISV = 0, totalSubtotal = 0, totalGeneral = 0;

    rows.forEach(row => {
        const subtotal = parseFloat(row.querySelector('input[name="subtotal[]"]').value) || 0;
        const isv = parseFloat(row.querySelector('input[name="isv[]"]').value) || 0;
        const totalItem = parseFloat(row.querySelector('input[name="total_item[]"]').value) || 0;

        totalISV += isv;
        totalSubtotal += subtotal;
        totalGeneral += totalItem;
    });

    document.getElementById('isv_global').value = totalISV.toFixed(2);
    document.getElementById('sub_total').value = totalSubtotal.toFixed(2);
    document.getElementById('total').value = totalGeneral.toFixed(2);
} 