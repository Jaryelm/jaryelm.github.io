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

    function medidataSyncImmediateBoss($select) {
        var boss = $select.find('option:selected').data('boss');
        $('#immediate_boss').val(boss || '');
    }

    // Cargar los departamentos mediante el patrón cat
    $.post('../../frontend/funciones/cat_departaments.php').done(function (respuesta) {
        var $select = $('#id_departament');
        if (!$select.length) {
            return;
        }

        var currentValue = $select.val();

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.html(respuesta);

        if (currentValue) {
            $select.val(currentValue);
        }

        medidataReinitSelect2($select);

        if (currentValue) {
            $select.trigger('change');
        } else {
            medidataSyncImmediateBoss($select);
        }
    });

    // Manejar el cambio de departamento para auto-rellenar el jefe
    $('#id_departament').on('change', function () {
        medidataSyncImmediateBoss($(this));
    });
});