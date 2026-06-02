/**
 * Select2 en módulo Recursos Humanos (admin y _usr).
 * Marcar selects con class="select2" (y opcional data-placeholder).
 */
(function ($) {
    'use strict';

    function dropdownParent() {
        return $('#content').length ? $('#content') : $(document.body);
    }

    function initRrhhSelect2($root) {
        if (typeof $.fn.select2 !== 'function') {
            return;
        }
        var $scope = $root && $root.length ? $root : $(document);
        var $dp = dropdownParent();

        $scope.find('select.select2').each(function () {
            var $el = $(this);
            if ($el.data('select2')) {
                return;
            }
            var hasEmpty = $el.find('option[value=""]').length > 0;
            var placeholder = $el.attr('data-placeholder') || (hasEmpty ? 'Seleccione...' : null);

            $el.select2({
                width: '100%',
                dropdownParent: $dp,
                allowClear: !$el.prop('required') && hasEmpty,
                placeholder: placeholder,
                language: {
                    noResults: function () {
                        return 'Sin resultados';
                    },
                    searching: function () {
                        return 'Buscando...';
                    },
                },
            });
        });
    }

    $(function () {
        initRrhhSelect2($(document));
    });

    window.medidataInitRrhhSelect2 = initRrhhSelect2;
})(jQuery);
