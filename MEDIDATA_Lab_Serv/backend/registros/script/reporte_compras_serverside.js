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

    function detallePagoExportFilters() {
        var search = '';
        if (window.medidataReporteComprasSS && medidataReporteComprasSS.tabla) {
            search = medidataReporteComprasSS.tabla.search() || '';
        }
        return {
            fechaDesde: ($('#fechaDesde').val() || '').trim(),
            fechaHasta: ($('#fechaHasta').val() || '').trim(),
            search: search.trim()
        };
    }

    function swalAlert(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({ icon: icon, title: title, text: text });
        }
        window.alert(text || title);
        return Promise.resolve();
    }

    function swalConfirm(title, text) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonColor: '#035c67',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, exportar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                return result.isConfirmed;
            });
        }
        return Promise.resolve(window.confirm((title ? title + '\n\n' : '') + text));
    }

    function runDetallePagoExport(format, f) {
        var params = new URLSearchParams();
        params.set('format', format);
        params.set('fechaDesde', f.fechaDesde);
        params.set('fechaHasta', f.fechaHasta);
        if (f.search) {
            params.set('search', f.search);
        }
        params.set('_ts', String(Date.now()));

        var exportUrl = (window.MEDIDATA_DETALLE_PAGO_EXPORT && window.MEDIDATA_DETALLE_PAGO_EXPORT.url)
            ? window.MEDIDATA_DETALLE_PAGO_EXPORT.url
            : 'get_reporte_detalle_pago_export.php';
        var url = exportUrl + '?' + params.toString();
        var fetchOpts = {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        };

        if (format === 'print' || format === 'pdf') {
            window.open(url, '_blank', 'width=1200,height=800');
            return;
        }

        if (format === 'csv' || format === 'excel') {
            window.location.href = url;
            return;
        }

        if (format === 'copy') {
            fetch(url, fetchOpts).then(function(r) {
                if (!r.ok) {
                    return r.text().then(function(body) {
                        var msg = 'HTTP ' + r.status;
                        try {
                            var j = JSON.parse(body);
                            if (j.error || j.message) {
                                msg = j.error || j.message;
                            }
                        } catch (ignore) {}
                        throw new Error(msg);
                    });
                }
                return r.text();
            }).then(function(text) {
                var lines = text.split('\n').length;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text).then(function() {
                        swalAlert('success', 'Copiado', 'Datos copiados al portapapeles (' + lines + ' filas).');
                    });
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Copiar datos',
                        html: '<textarea readonly style="width:100%;height:220px;font-size:12px;">' +
                            $('<div>').text(text).html() + '</textarea>',
                        confirmButtonText: 'Cerrar'
                    });
                } else {
                    window.prompt('Copie los datos (Ctrl+C):', text);
                }
            }).catch(function(err) {
                swalAlert('error', 'Error', err && err.message ? err.message : 'Error al obtener los datos para copiar.');
            });
        }
    }

    function exportDetallePago(format) {
        var f = detallePagoExportFilters();
        var tieneFiltroFecha = f.fechaDesde !== '' || f.fechaHasta !== '';

        if (tieneFiltroFecha && (!f.fechaDesde || !f.fechaHasta)) {
            swalAlert('warning', 'Aviso', 'Para exportar un mes, indique fecha Desde y Hasta.');
            return;
        }

        if (!tieneFiltroFecha && !f.search) {
            swalConfirm(
                'Sin filtros activos',
                'No hay filtros activos. ¿Exportar todos los registros de detalle de pago?'
            ).then(function(ok) {
                if (ok) {
                    runDetallePagoExport(format, f);
                }
            });
            return;
        }

        runDetallePagoExport(format, f);
    }

    function detallePagoExportButtons() {
        return [
            { extend: 'copy', text: 'Copy', action: function() { exportDetallePago('copy'); } },
            { extend: 'csv', text: 'CSV', action: function() { exportDetallePago('csv'); } },
            { extend: 'excel', text: 'Excel', action: function() { exportDetallePago('excel'); } },
            { extend: 'pdf', text: 'PDF', action: function() { exportDetallePago('pdf'); } },
            { extend: 'print', text: 'Print', action: function() { exportDetallePago('print'); } }
        ];
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
            cfg.buttons = detallePagoExportButtons();
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
