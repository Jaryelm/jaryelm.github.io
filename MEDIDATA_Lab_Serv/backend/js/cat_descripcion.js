$(document).ready(function() {
    // Cargar lista de nombres de productos desde PHP
    $.ajax({
        url: '../../frontend/funciones/cat_descripcion.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#mediname').empty();
            $('#mediname').append(new Option('Seleccione o busque un producto', ''));
            data.forEach(function(descripcion) {
                $('#mediname').append(new Option(descripcion, descripcion));
            });
            $('#mediname').select2({
                placeholder: 'Seleccione o busque un producto',
                allowClear: true
            });
        },
        error: function() {
            console.error('Error al cargar las descripciones.');
        }
    });

    // Evento para obtener y mostrar el stock disponible y el precio costo al seleccionar un producto
    $('#mediname').on('change', function() {
        const descripcionSeleccionada = $(this).val();
        if (descripcionSeleccionada) {
            // Obtener stock disponible
            $.ajax({
                url: '../../backend/php/obtener_stock_disponible.php',
                type: 'POST',
                data: { producto_nombre: descripcionSeleccionada },
                dataType: 'json',
                success: function(stockResponse) {
                    console.log('Respuesta stock disponible:', stockResponse); // DEPURACIÓN
                    if (stockResponse.success) {
                        $('#stock_disponible').val(stockResponse.stock_disponible);
                    } else {
                        $('#stock_disponible').val('0');
                        console.warn('No se pudo obtener el stock disponible:', stockResponse.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener el stock disponible:', error);
                    $('#stock_disponible').val('0');
                }
            });

            // Obtener precio costo
            $.ajax({
                url: '../../frontend/funciones/obtener_precio.php',
                type: 'GET',
                data: { descripcion: descripcionSeleccionada },
                dataType: 'json',
                success: function(response) {
                    if (response.precio_unitario) {
                        $('#costo').val(response.precio_unitario);
                    } else {
                        $('#costo').val('');
                        console.warn('No se encontró precio para el producto.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener el precio:', error);
                    $('#costo').val('');
                }
            });
        } else {
            $('#stock_disponible').val('');
            $('#costo').val('');
        }
    });
});
