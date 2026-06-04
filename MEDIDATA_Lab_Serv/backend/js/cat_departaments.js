$(function(){
    // Cargar los departamentos mediante el patrón cat
    $.post('../../frontend/funciones/cat_departaments.php').done(function(respuesta) {
        var $select = $('#id_departament');
        var currentValue = $select.val(); // Preservar si ya tiene valor (ej: modo edición)
        
        $select.html(respuesta);
        
        if (currentValue) {
            $select.val(currentValue).trigger('change');
        }
        
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2();
        }
    });

    // Manejar el cambio de departamento para auto-rellenar el jefe
    $('#id_departament').on('change', function() {
        var boss = $(this).find('option:selected').data('boss');
        $('#immediate_boss').val(boss || '');
    });
});