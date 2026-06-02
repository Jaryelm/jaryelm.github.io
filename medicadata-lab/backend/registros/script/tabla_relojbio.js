/**
 * DataTable server-side: marcaciones biométricas.
 * Requiere window.MEDIDATA_RELOJBIO = { ajaxUrl, dbOk }
 */
(function ($) {
    'use strict';

    $(function () {
        var cfg = window.MEDIDATA_RELOJBIO || {};
        var $table = $('#table_reloj_marcas');
        if (!$table.length || !cfg.dbOk || !cfg.ajaxUrl) {
            return;
        }

        var fromInput = document.getElementById('rb-date-from');
        var toInput = document.getElementById('rb-date-to');

        var dt = $table.DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[5, 'desc']],
            responsive: true,
            autoWidth: false,
            dom: 'Blfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            ajax: {
                url: cfg.ajaxUrl,
                type: 'GET',
                data: function (d) {
                    d.fechaDesde = fromInput && fromInput.value ? fromInput.value : '';
                    d.fechaHasta = toInput && toInput.value ? toInput.value : '';
                },
            },
            columns: [
                { data: 'row_num', orderable: false, searchable: false, defaultContent: '' },
                { data: 'empleado', defaultContent: '' },
                { data: 'email', defaultContent: '' },
                { data: 'rol', defaultContent: '' },
                { data: 'uid_reloj', defaultContent: '' },
                { data: 'fecha_entrada', defaultContent: '—' },
                { data: 'fecha_salida', defaultContent: '—' },
            ],
            language: {
                sProcessing: 'Procesando...',
                sLengthMenu: 'Mostrar _MENU_ registros',
                sZeroRecords: 'No se encontraron resultados',
                sEmptyTable: 'No hay marcas para mostrar',
                sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                sInfoEmpty: 'Mostrando 0 a 0 de 0 registros',
                sInfoFiltered: '(filtrado de _MAX_ registros totales)',
                sSearch: 'Buscar:',
                oPaginate: {
                    sFirst: 'Primero',
                    sLast: 'Último',
                    sNext: 'Siguiente',
                    sPrevious: 'Anterior',
                },
            },
        });

        var $wrapper = $('#table_reloj_marcas_wrapper');
        if ($wrapper.length) {
            var $toolbar = $(
                '<div class="rb-table-toolbar"><div class="rb-table-toolbar-left"></div><div class="rb-table-toolbar-right"></div></div>'
            );
            $toolbar
                .find('.rb-table-toolbar-left')
                .append($wrapper.find('.dt-buttons'))
                .append($wrapper.find('.dataTables_length'));
            $toolbar.find('.rb-table-toolbar-right').append($wrapper.find('.dataTables_filter'));
            $wrapper.prepend($toolbar);
        }

        $('#rb-apply-dates').on('click', function () {
            dt.ajax.reload();
        });
        $('#rb-clear-dates').on('click', function () {
            if (fromInput) {
                fromInput.value = '';
            }
            if (toInput) {
                toInput.value = '';
            }
            dt.ajax.reload();
        });
    });
})(jQuery);
