$(function() {
    // Llenar la lista de productos
    $.post('../../frontend/funciones/prod.php').done(function(respuesta) {
        $('#prod_ingreso').html(respuesta);
    });

    // Acción al cambiar la opción seleccionada
    $('#prod_ingreso').change(function() {
        var productoSeleccionado = $(this).val();
        // Aquí puedes realizar alguna acción adicional si es necesario
    });
});
