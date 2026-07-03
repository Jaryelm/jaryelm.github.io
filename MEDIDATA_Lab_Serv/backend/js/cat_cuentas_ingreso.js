$(document).ready(function() {
    // Cargar servicios filtrados dinámicamente en el campo de selección
    $.post('../../frontend/funciones/cat_cuentas_ingreso.php', function(respuesta) {
        $('#service_name').html(respuesta); // Cargar opciones en service_name
    });

    // Al seleccionar un nombre, actualizar el código de servicio con el valor de "data-cuenta"
    $('#service_name').on('change', function() {
        var cuentaSeleccionada = $(this).find(':selected').data('cuenta'); // Obtener "cuenta" del atributo data-cuenta
        $('#service_code').val(cuentaSeleccionada); // Asignar valor a "Código de Servicio"
    });
});
