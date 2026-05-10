$(function() {
    // Llenar la lista de proveedores
    $.post('../../frontend/funciones/proveedores.php').done(function(respuesta) {
        $('#prov_datos').html(respuesta);
    });

    // Acción al cambiar la opción seleccionada
    $('#prov_datos').change(function() {
        var proveedorSeleccionado = $(this).val();
        // Aquí puedes realizar alguna acción adicional si es necesario
    });
});
