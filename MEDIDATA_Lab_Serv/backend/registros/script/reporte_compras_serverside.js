(function(window, $) {
    'use strict';

    var idiomaSS = {
        processing: 'Procesando...',
        zeroRecords: 'No se encontraron resultados',
        emptyTable: 'No hay datos disponibles',
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

    function accionesCompraHtml(id) {
        return '<div class="acciones-wrap">' +
            '<button type="button" class="btn-editar-compra" data-id="' + id + '">Editar</button>' +
            '<button type="button" class="btn-eliminar-compra" data-id="' + id + '">Eliminar</button>' +
            '</div>';
    }

    function accionesIngresoHtml(id) {
        return '<div class="acciones-wrap">' +
            '<button type="button" class="btn-editar-ingreso" data-id="' + id + '">Editar</button>' +
            '<button type="button" class="btn-eliminar-ingreso" data-id="' + id + '">Revertir</button>' +
            '</div>';
    }

    function baseConfig(ajaxUrl) {
        return {
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: false,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            ajax: {
                url: ajaxUrl,
                type: 'GET',
                data: function(d) {
                    d.fechaDesde = $('#fechaDesde').val();
                    d.fechaHasta = $('#fechaHasta').val();
                }
            },
            language: idiomaSS,
            initComplete: function() {
                $('.dataTables_wrapper').addClass('dt-ready');
                $('#page-loading-overlay').hide();
            }
        };
    }

    window.medidataReporteComprasSS = {
        tabla: null,

        initIngresadas: function() {
            var cfg = baseConfig('../../backend/registros/get_reporte_compras_ingresadas.php');
            cfg.order = [[1, 'desc']];
            cfg.columns = [
                { data: 'numero_orden' },
                { data: 'fecha' },
                { data: 'proveedor' },
                { data: 'factura' },
                { data: 'impuesto' },
                { data: 'subtotal' },
                { data: 'total' },
                { data: 'partida' },
                {
                    data: 'id_compra',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return accionesCompraHtml(data);
                    }
                }
            ];
            this.tabla = $('#tablaReporteCompras').DataTable(cfg);
            return this.tabla;
        },

        initDetalladas: function() {
            var cfg = baseConfig('../../backend/registros/get_reporte_compras_detalladas.php');
            cfg.order = [[0, 'desc']];
            cfg.columns = [
                { data: 'fecha' },
                { data: 'proveedor' },
                { data: 'factura' },
                { data: 'impuesto' },
                { data: 'retencion' },
                { data: 'exenta' },
                { data: 'gravada' },
                { data: 'subtotal' },
                { data: 'total' },
                {
                    data: 'id_compra',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return accionesCompraHtml(data);
                    }
                }
            ];
            this.tabla = $('#tablaReporteCompras').DataTable(cfg);
            return this.tabla;
        },

        initDetalleFactura: function() {
            var cfg = baseConfig('../../backend/registros/get_reporte_detalle_factura.php');
            cfg.order = [[0, 'desc']];
            cfg.columns = [
                { data: 'fecha' },
                { data: 'num_orden' },
                { data: 'factura' },
                { data: 'metodo' },
                { data: 'usuario' },
                { data: 'total' },
                {
                    data: 'idord',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return accionesIngresoHtml(data);
                    }
                }
            ];
            this.tabla = $('#tablaReporteDetalleFactura').DataTable(cfg);
            return this.tabla;
        },

        initDetallePago: function() {
            var cfg = baseConfig('../../backend/registros/get_reporte_detalle_pago.php');
            cfg.order = [[0, 'desc']];
            cfg.columns = [
                { data: 'fecha' },
                { data: 'hora' },
                { data: 'forma_pago' },
                { data: 'estado' },
                { data: 'factura' },
                { data: 'detalle_examen' },
                { data: 'cliente' },
                { data: 'cliente_celular' },
                { data: 'tipo_descuento' },
                { data: 'subtotal', className: 'dt-right' },
                { data: 'descuento', className: 'dt-right' },
                { data: 'impuesto', className: 'dt-right' },
                { data: 'total', className: 'dt-right' }
            ];
            this.tabla = $('#tablaReporteDetallePago').DataTable(cfg);
            return this.tabla;
        },

        initCuadreCaja: function() {
            var cfg = baseConfig('../../backend/registros/get_reporte_cuadre_caja.php');
            cfg.order = [[0, 'desc']];
            cfg.columns = [
                { data: 'fecha' },
                { data: 'usuario' },
                { data: 'efectivo', className: 'dt-right' },
                { data: 'tarjeta', className: 'dt-right' },
                { data: 'otros', className: 'dt-right' }
            ];
            this.tabla = $('#tablaReporteCuadreCaja').DataTable(cfg);
            return this.tabla;
        },

        initDevoluciones: function() {
            var cfg = baseConfig('../../backend/registros/get_reporte_devoluciones_ventas.php');
            cfg.order = [[0, 'desc']];
            cfg.columns = [
                { data: 'fecha' },
                { data: 'tipo' },
                { data: 'num_orden' },
                { data: 'num_factura' },
                { data: 'cliente' },
                { data: 'tipo_item' },
                { data: 'descripcion' },
                { data: 'cantidad' },
                { data: 'motivo' },
                { data: 'procesado_por' },
                { data: 'valor', className: 'dt-right' }
            ];
            this.tabla = $('#tablaReporteDevoluciones').DataTable(cfg);
            return this.tabla;
        },

        recargar: function() {
            if (this.tabla) {
                this.tabla.ajax.reload();
            }
        }
    };
})(window, jQuery);
