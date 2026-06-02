$(document).ready(function() {
    // Cargar opciones de cuenta desde el archivo PHP
    $.ajax({
        url: '../../frontend/funciones/cat_cuentas.php',
        type: 'GET',
        success: function(data) {
            const select = $('#cat_cuenta');
            select.empty(); // Limpiar cualquier opción previa
            select.append(data); // Añadir las opciones desde el PHP directamente

            // Inicializar Select2 en el campo de cuentas
            select.select2({
                placeholder: 'Seleccione o busque una cuenta',
                allowClear: true
            });
        },
        error: function() {
            console.error('Error al cargar las cuentas.');
        }
    });
});
