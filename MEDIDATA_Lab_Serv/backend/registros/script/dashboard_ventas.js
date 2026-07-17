(function (window, $) {
    'use strict';

    var idiomaSS = {
        processing: 'Procesando...',
        zeroRecords: 'No se encontraron resultados',
        emptyTable: 'No hay ventas cobradas en este período',
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

    var periodoActual = 'mes';
    var tablaDetalle = null;

    function fmtVar(pct) {
        if (pct === null || pct === undefined || isNaN(pct)) {
            return 'Sin período anterior comparable';
        }
        var sign = pct >= 0 ? '+' : '';
        var cls = pct >= 0 ? 'var-up' : 'var-down';
        return '<span class="' + cls + '">' + sign + pct.toFixed(1) + '% vs período anterior</span>';
    }

    function renderRankList($el, items) {
        $el.empty();
        if (!items || !items.length) {
            $el.append('<li><span class="rank-name">Sin datos en el período</span></li>');
            return;
        }
        items.forEach(function (item, idx) {
            $el.append(
                '<li><span class="rank-n">' + (idx + 1) + '.</span>' +
                '<span class="rank-name">' + $('<div>').text(item.nombre).html() +
                ' <small>(' + (item.tipo === 'servicio' ? 'Servicio' : 'Producto') + ')</small></span>' +
                '<span class="rank-amt">' + item.ingresos + '</span></li>'
            );
        });
    }

    function renderMix(data) {
        var html = '';
        if (data.mix_tipo && data.mix_tipo.length) {
            html += '<p><strong>Por tipo:</strong> ';
            html += data.mix_tipo.map(function (m) {
                var lbl = m.tipo === 'servicio' ? 'Servicios' : 'Productos';
                return lbl + ': ' + Number(m.unidades).toLocaleString('es-HN') + ' uds · L. ' +
                    Number(m.ingresos).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }).join(' &nbsp;|&nbsp; ');
            html += '</p>';
        }
        if (data.mix_pago && data.mix_pago.length) {
            html += '<p><strong>Forma de pago:</strong> ';
            html += data.mix_pago.map(function (m) {
                return m.metodo + ': ' + m.cantidad + ' fact. · L. ' +
                    Number(m.ingresos).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }).join(' &nbsp;|&nbsp; ');
            html += '</p>';
        }
        if (!html) {
            html = '<p>Sin datos de mix en el período.</p>';
        }
        $('#dvMix').html(html);
    }

    function renderInsights(items) {
        var $box = $('#dvInsights');
        $box.empty();
        if (!items || !items.length) {
            return;
        }
        items.forEach(function (ins) {
            var cls = ins.type ? ' dv-insight ' + ins.type : ' dv-insight';
            $box.append(
                '<div class="' + cls.trim() + '"><strong>' + $('<div>').text(ins.title).html() +
                '</strong><p>' + $('<div>').text(ins.text).html() + '</p></div>'
            );
        });
    }

    function cargarResumen() {
        return $.getJSON('../../backend/registros/get_dashboard_ventas_resumen.php', { periodo: periodoActual })
            .done(function (res) {
                if (!res.success) {
                    Swal.fire('Error', res.message || 'No se pudo cargar el resumen.', 'error');
                    return;
                }
                var c = res.cards || {};
                $('#dvPeriodoLabel').text(res.periodo.label || '—');
                $('#dvActualizado').text(' · Actualizado: ' + (res.actualizado || ''));
                $('#dvIngresos').text(c.ingresos || '—');
                $('#dvVarIngresos').html(fmtVar(c.var_ingresos));
                $('#dvTicket').text(c.ticket || '—');
                $('#dvTransacciones').text((c.transacciones || 0) + ' facturas cobradas');
                $('#dvUnidades').text(c.unidades || '—');
                $('#dvProductosCount').text((c.productos_count || 0) + ' productos/servicios distintos');
                $('#dvIsv').text(c.isv || '—');
                $('#dvDescuentos').text('Descuentos: ' + (c.descuentos || '—'));

                var $cards = $('#dvCards .dv-card').first();
                $cards.removeClass('var-up var-down');
                if (c.var_ingresos !== null && c.var_ingresos !== undefined) {
                    $cards.addClass(c.var_ingresos >= 0 ? 'var-up' : 'var-down');
                }

                renderRankList($('#dvTopMas'), res.top_mas);
                renderRankList($('#dvTopMenos'), res.top_menos);
                renderMix(res);
                renderInsights(res.insights);
            })
            .fail(function () {
                Swal.fire('Error', 'No se pudo conectar con el servidor del dashboard.', 'error');
            });
    }

    function initTablaDetalle() {
        if (tablaDetalle) {
            tablaDetalle.destroy();
            tablaDetalle = null;
        }

        tablaDetalle = $('#tablaDashboardVentas').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: false,
            autoWidth: false,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            order: [[6, 'desc']],
            ajax: {
                url: '../../backend/registros/get_dashboard_ventas_detalle.php',
                type: 'GET',
                data: function (d) {
                    d.periodo = periodoActual;
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo cargar el detalle por producto/servicio.', 'error');
                }
            },
            columns: [
                { data: 'num', orderable: false, searchable: false, width: '2.5rem' },
                { data: 'codigo', width: '6.5rem' },
                { data: 'nombre' },
                { data: 'tipo', width: '5.5rem' },
                { data: 'linea', width: '9rem' },
                { data: 'unidades', className: 'dt-right', width: '4.5rem' },
                { data: 'ingresos', className: 'dt-right', width: '6.5rem' },
                { data: 'pct_ingresos', className: 'dt-right', width: '5rem' },
                { data: 'ticket_linea', className: 'dt-right', width: '6.5rem' },
                { data: 'facturas', className: 'dt-right', width: '4.5rem' },
                { data: 'stock', className: 'dt-right', width: '4rem' },
                {
                    data: 'tendencia',
                    orderable: false,
                    searchable: false,
                    render: function (d, type, row) {
                        if (type !== 'display') {
                            return d;
                        }
                        var cls = row.tendencia_class || '';
                        return '<span class="' + cls + '">' + $('<div>').text(d || '').html() + '</span>';
                    }
                },
                { data: 'recomendacion', orderable: false, className: 'dv-rec-cell' }
            ],
            language: idiomaSS,
            initComplete: function () {
                $('#tablaDashboardVentas').closest('.dataTables_wrapper').addClass('dt-ready');
                $('#page-loading-overlay').hide();
            }
        });
    }

    function recargarTodo() {
        var promesa = cargarResumen();
        if (tablaDetalle) {
            tablaDetalle.ajax.reload();
        }
        return promesa;
    }

    window.medidataDashboardVentas = {
        init: function () {
            if (!$('#tablaDashboardVentas').length) {
                return;
            }

            initTablaDetalle();

            $('.dv-period-btn').on('click', function () {
                var btn = $(this);
                periodoActual = btn.data('periodo') || 'mes';
                $('.dv-period-btn').removeClass('active');
                btn.addClass('active');
                recargarTodo();
            });

            cargarResumen();
        }
    };

    $(function () {
        medidataDashboardVentas.init();
    });
})(window, jQuery);
