$(function(){
    // Cargar los niveles salariales mediante el patrón cat
    function loadSalaryLevels($selectElement) {
        $.post('../../frontend/funciones/cat_salary_levels.php').done(function(respuesta) {
            var currentValue = $selectElement.val();
            $selectElement.html(respuesta);
            if (currentValue) {
                $selectElement.val(currentValue).trigger('change');
            }
            if ($selectElement.hasClass('select2-hidden-accessible')) {
                $selectElement.select2();
            }
        });
    }

    if ($('#id_salary_level').length) {
        loadSalaryLevels($('#id_salary_level'));
    }
});