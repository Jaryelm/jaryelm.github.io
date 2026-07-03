$(document).ready(function() {
    $(document).on('blur', '.editable-cell', function() {
        var $cell = $(this);
        var id = $cell.data('id');
        var field = $cell.data('field');
        var table = $cell.data('table');
        var idcol = $cell.data('idcol');
        var value = $cell.text().trim();
        
        if (value === '—' || value === '-') {
            value = '';
        }
        
        $.ajax({
            url: '../../backend/php/update_inline_staff.php',
            method: 'POST',
            data: {
                id: id,
                field: field,
                value: value,
                table: table,
                id_col: idcol
            },
            success: function(response) {
                try {
                    var res = JSON.parse(response);
                    if(res.status == 'success') {
                        $cell.css('background-color', '#d4edda');
                        setTimeout(function() { $cell.css('background-color', '#f9f9f9'); }, 1000);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                } catch(e) {
                    console.error("Error parseando JSON:", response);
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo conectar al servidor', 'error');
            }
        });
    });
    
    
    $(document).on('change', '.inline-select', function() {
        var $select = $(this);
        var id = $select.data('id');
        var field = $select.data('field');
        var table = $select.data('table');
        var idcol = $select.data('idcol');
        var value = $select.val();
        
        $.ajax({
            url: '../../backend/php/update_inline_staff.php',
            method: 'POST',
            data: {
                id: id,
                field: field,
                value: value,
                table: table,
                id_col: idcol
            },
            success: function(response) {
                try {
                    var res = JSON.parse(response);
                    if(res.status == 'success') {
                        $select.css('background-color', '#d4edda');
                        setTimeout(function() { $select.css('background-color', '#f9f9f9'); }, 1000);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                } catch(e) {}
            },
            error: function() {
                Swal.fire('Error', 'No se pudo conectar al servidor', 'error');
            }
        });
    });

    $(document).on('keypress', '.editable-cell', function(e) {
        if(e.which == 13) {
            e.preventDefault();
            $(this).blur();
        }
    });
});

function uploadContract(inputElement, id, table, idcol) {
    if (!inputElement.files || inputElement.files.length === 0) return;
    var file = inputElement.files[0];
    var formData = new FormData();
    formData.append('file', file);
    formData.append('id', id);
    formData.append('table', table);
    formData.append('idcol', idcol);
    
    // Mostramos un alert de carga
    Swal.fire({
        title: 'Subiendo contrato...',
        allowOutsideClick: false,
        onBeforeOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '../../backend/php/upload_inline_contract.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status == 'success') {
                    Swal.fire('Éxito', 'Contrato subido correctamente', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Respuesta no válida del servidor', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Fallo al conectar con el servidor', 'error');
        }
    });
}

function deleteContract(id, table, idcol) {
    Swal.fire({
        title: '¿Eliminar contrato?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                onBeforeOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '../../backend/php/upload_inline_contract.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: id,
                    table: table,
                    idcol: idcol
                },
                success: function(response) {
                    try {
                        var res = JSON.parse(response);
                        if (res.status == 'success') {
                            Swal.fire('Eliminado', 'Contrato eliminado correctamente', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    } catch(e) {
                        Swal.fire('Error', 'Respuesta no válida del servidor', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Fallo al conectar con el servidor', 'error');
                }
            });
        }
    });
}
