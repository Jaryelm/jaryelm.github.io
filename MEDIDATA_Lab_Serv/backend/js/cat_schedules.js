$(function(){
    // Cargar los horarios mediante el patrón cat
    function loadSchedules($selectElement) {
        $.post('../../frontend/funciones/cat_schedules.php').done(function(respuesta) {
            var currentValue = $selectElement.val();
            $selectElement.html(respuesta);
        $selectElement.trigger("change.select2");
            if (currentValue && currentValue != "0") {
                $selectElement.val(currentValue).trigger('change');
            }
            if ($selectElement.hasClass('select2-hidden-accessible')) {
                $selectElement.select2();
            }
        });
    }

    if ($('#id_schedule').length) {
        loadSchedules($('#id_schedule'));
    }
});