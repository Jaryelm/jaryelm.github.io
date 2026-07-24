/**
 * DataTable server-side: marcaciones biométricas.
 * Requiere window.MEDIDATA_RELOJBIO = { ajaxUrl, dbOk }
 *
 * Los botones de exportación piden TODOS los registros del filtro actual
 * (no solo la página visible).
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

        /**
         * Exporta con serverSide: recarga temporalmente todos los filtrados,
         * ejecuta el botón nativo y restaura la paginación.
         */
        function exportFilteredAll(e, dt, button, config, originalAction) {
            var info = dt.page.info();
            var totalFiltered = info.recordsDisplay || 0;
            var oldStart = dt.settings()[0]._iDisplayStart || 0;
            var oldLength = dt.page.len() || 10;

            if (totalFiltered <= 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Aviso', 'No hay registros para exportar con el filtro actual.', 'info');
                }
                return;
            }

            // Ya están todos los filtrados en la página visible.
            if (info.start === 0 && info.end >= totalFiltered) {
                originalAction.call(this, e, dt, button, config);
                return;
            }

            var self = this;
            var $processing = $(dt.table().container()).find('.dataTables_processing');
            $processing.show();

            dt.one('preXhr.dt.rbexport', function (ev, settings, data) {
                data.start = 0;
                data.length = totalFiltered;
                data.export_all = 1;
            });

            dt.one('draw.dt.rbexport', function () {
                dt.off('preXhr.dt.rbexport');
                try {
                    originalAction.call(self, e, dt, button, config);
                } finally {
                    dt.one('preXhr.dt.rbrestore', function (ev, settings, data) {
                        data.start = oldStart;
                        data.length = oldLength;
                        data.export_all = 0;
                    });
                    dt.one('draw.dt.rbrestore', function () {
                        dt.off('preXhr.dt.rbrestore');
                        $processing.hide();
                    });
                    dt.ajax.reload(null, false);
                }
            });

            dt.ajax.reload();
        }

        function makeExportButton(extendKey, actionKey) {
            var original = $.fn.dataTable.ext.buttons[actionKey]
                || $.fn.dataTable.ext.buttons[extendKey];
            if (!original || typeof original.action !== 'function') {
                return extendKey;
            }
            return {
                extend: extendKey,
                title: 'MEDIDATA',
                action: function (e, dt, button, config) {
                    exportFilteredAll.call(this, e, dt, button, config, original.action);
                },
            };
        }

        var dt = $table.DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[6, 'desc']],
            responsive: true,
            autoWidth: false,
            dom: 'Blfrtip',
            buttons: [
                makeExportButton('copy', 'copyHtml5'),
                makeExportButton('csv', 'csvHtml5'),
                makeExportButton('excel', 'excelHtml5'),
                makeExportButton('pdf', 'pdfHtml5'),
                makeExportButton('print', 'print'),
            ],
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
                { data: 'dia_semana', defaultContent: '—' },
                { data: 'fecha', defaultContent: '—' },
                { data: 'hora_entrada', defaultContent: '—' },
                { data: 'hora_salida', defaultContent: '—' },
                { data: 'justificacion', defaultContent: '—' },
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
