(function(window, $) {
    'use strict';

    var idiomaSS = {
        processing: 'Cargando...',
        zeroRecords: 'No se encontraron resultados',
        emptyTable: 'No hay registros disponibles.',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros totales)',
        search: 'Buscar:',
        paginate: {
            first: 'Primero',
            previous: 'Anterior',
            next: 'Siguiente',
            last: 'Último'
        }
    };

    function esc(text) {
        if (text === null || text === undefined || text === '') {
            return '';
        }
        return $('<div>').text(text).html();
    }

    function lps(valor) {
        var n = parseFloat(valor);
        if (isNaN(n)) {
            n = 0;
        }
        return 'LPS ' + n.toLocaleString('es-HN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function renderMetodos(valor) {
        if (!valor) {
            return '—';
        }
        var obj;
        try {
            obj = JSON.parse(valor);
        } catch (e) {
            return esc(valor);
        }
        if (!obj || typeof obj !== 'object') {
            return '—';
        }
        return Object.keys(obj).map(function(key) {
            return esc(key) + ': ' + lps(obj[key]);
        }).join('<br>') || '—';
    }

    function columnasCierres(showNombre) {
        var cols = [
            { data: 'fecha_cierre', render: function(d) { return esc(d) || '—'; } },
            { data: 'total_ventas', className: 'dt-right', render: function(d) { return lps(d); } },
            { data: 'total_facturas', render: function(d) { return esc(d) || '0'; } },
            { data: 'facturas_cobradas', render: function(d) { return esc(d) || '0'; } },
            { data: 'facturas_pendientes', render: function(d) { return esc(d) || '0'; } },
            {
                data: 'total_por_metodo',
                orderable: false,
                searchable: false,
                render: function(d) { return renderMetodos(d); }
            },
            { data: 'usuario_cierre', render: function(d) { return esc(d) || '—'; } }
        ];
        if (showNombre) {
            cols.push({ data: 'nombre_completo', render: function(d) { return esc(d) || '—'; } });
        }
        return cols;
    }

    window.medidataCierresCajaDT = {
        tabla: null,

        /**
         * @param {string} selector ej. '#tabla_cierres'
         * @param {{scope?: string, showNombre?: boolean}} opciones
         */
        init: function(selector, opciones) {
            opciones = opciones || {};
            var scope = opciones.scope || '';
            var showNombre = !!opciones.showNombre;

            this.tabla = $(selector).DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: false,
                dom: 'Bfrtip',
                order: [[0, 'desc']],
                ajax: {
                    url: '../../backend/registros/get_cierres_caja.php',
                    type: 'GET',
                    data: function(d) {
                        if (scope) {
                            d.scope = scope;
                        }
                    }
                },
                columns: columnasCierres(showNombre),
                buttons: [
                    'copy',
                    { extend: 'csv', title: 'cierres_de_caja' },
                    { extend: 'excel', title: 'cierres_de_caja' },
                    'print'
                ],
                language: idiomaSS
            });

            return this.tabla;
        },

        recargar: function() {
            if (this.tabla) {
                this.tabla.ajax.reload();
            }
        }
    };
})(window, jQuery);
