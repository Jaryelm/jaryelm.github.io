$(function () {
    function medidataReinitSelect2($select) {
        if (!$select.length || typeof $.fn.select2 !== 'function') {
            return;
        }
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        if (typeof window.medidataInitRrhhSelect2 === 'function') {
            window.medidataInitRrhhSelect2($select.parent());
        } else {
            $select.select2({ width: '100%' });
        }
    }

    function loadSalaryLevels($selectElement) {
        $.post('../../frontend/funciones/cat_salary_levels.php').done(function (respuesta) {
            var currentValue = $selectElement.val();

            if ($selectElement.hasClass('select2-hidden-accessible')) {
                $selectElement.select2('destroy');
            }

            $selectElement.html(respuesta);

            if (currentValue) {
                $selectElement.val(currentValue);
            }

            medidataReinitSelect2($selectElement);

            if (currentValue) {
                $selectElement.trigger('change');
            }
        });
    }

    if ($('#id_salary_level').length) {
        loadSalaryLevels($('#id_salary_level'));
    }
});