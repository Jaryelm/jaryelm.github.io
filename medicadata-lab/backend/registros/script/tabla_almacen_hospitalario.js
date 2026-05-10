/**
 * Lista inventario almacén hospitalario: JSON + DataTables (misma UX que el resto del sistema).
 * Requiere: jQuery, DataTables y plugins cargados antes de este script.
 */
(function ($) {
    'use strict';

    var DATA_URL = '../../backend/registros/tabla_almacen_hospitalario.php';

    function esc(s) {
        if (s == null || s === '') {
            return '';
        }
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;');
    }

    function resolveFotoUrl(safePath) {
        if (!safePath) {
            return '';
        }
        var p = String(safePath).replace(/^\//, '');
        if (/^https?:\/\//i.test(p)) {
            return p;
        }
        var a = document.createElement('a');
        a.href = '../../' + p;
        return a.href;
    }

    function categoriaCell(row) {
        if (row.nomcat != null && String(row.nomcat) !== '') {
            return esc(row.nomcat);
        }
        return esc(row.idcat);
    }

    function buildRow(row) {
        var safePath = row.adj_foto
            ? String(row.adj_foto).replace(/^\//, '').replace(/[^a-zA-Z0-9._\-\/]/g, '')
            : '';
        var imageUrl = resolveFotoUrl(safePath);
        var urlAttr = imageUrl ? escAttr(imageUrl) : '';
        var fotoTd = imageUrl
            ? '<img class="foto-thumb" src="' +
              urlAttr +
              '" data-full-src="' +
              urlAttr +
              '" alt="Foto" loading="lazy" width="60" height="50" style="width:60px;height:50px;object-fit:cover;object-position:center;border-radius:5px;">'
            : 'No disponible';
        return (
            '<tr>' +
            '<td>' + esc(row.codpro) + '</td>' +
            '<td>' + esc(row.codbars) + '</td>' +
            '<td>' + esc(row.linea) + '</td>' +
            '<td>' + esc(row.sub_linea) + '</td>' +
            '<td>' + esc(row.presentacion) + '</td>' +
            '<td>' + esc(row.forma_farmaceutica) + '</td>' +
            '<td>' + esc(row.concentracion) + '</td>' +
            '<td>' + esc(row.via_administracion) + '</td>' +
            '<td>' + esc(row.nompro) + '</td>' +
            '<td>' + esc(row.principio_activo) + '</td>' +
            '<td>' + esc(row.preprd) + '</td>' +
            '<td>' + esc(row.precio_venta) + '</td>' +
            '<td>' + esc(row.margen_ganancia) + '</td>' +
            '<td>' + esc(row.impuesto) + '</td>' +
            '<td>' + categoriaCell(row) + '</td>' +
            '<td>' + esc(row.stock) + '</td>' +
            '<td>' + esc(row.fere) + '</td>' +
            '<td>' + esc(row.fecha_vencimiento) + '</td>' +
            '<td>' + fotoTd + '</td>' +
            '</tr>'
        );
    }

    var dtLang = {
        sProcessing: 'Procesando...',
        sLengthMenu: 'Mostrar _MENU_ registros',
        sZeroRecords: 'No se encontraron resultados',
        sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        sInfoEmpty: 'Mostrando 0 a 0 de 0 registros',
        sInfoFiltered: '(filtrado de _MAX_ registros totales)',
        sSearch: 'Buscar:',
        oPaginate: {
            sFirst: 'Primero',
            sLast: 'Último',
            sNext: 'Siguiente',
            sPrevious: 'Anterior'
        }
    };

    function initInventarioTable(data) {
        var $table = $('#directorio-table');
        if (!$table.length) {
            return;
        }
        if (!Array.isArray(data)) {
            console.error('tabla_almacen_hospitalario: respuesta no es un array', data);
            $table.closest('.medidata-dt-host').removeClass('medidata-dt-host--pending');
            return;
        }
        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().destroy();
        }
        var $body = $table.find('tbody');
        $body.empty();
        data.forEach(function (row) {
            $body.append(buildRow(row));
        });
        var dt = $table.DataTable({
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            order: [[16, 'desc']],
            language: dtLang,
            scrollX: true,
            scrollCollapse: true,
            initComplete: function () {
                var api = this.api();
                $table.closest('.medidata-dt-host').removeClass('medidata-dt-host--pending');
                api.columns.adjust();
                setTimeout(function () {
                    api.columns.adjust();
                }, 300);
            },
            drawCallback: function () {
                this.api().columns.adjust();
            }
        });
        $body.find('img').on('load', function () {
            dt.columns.adjust();
        });
        var resizeTimer;
        $(window)
            .off('resize.dtInventario')
            .on('resize.dtInventario', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    dt.columns.adjust();
                }, 200);
            });
    }

    (function initFotoModalOnce() {
        if (window.__medidataFotoModalInit) {
            return;
        }
        window.__medidataFotoModalInit = true;

        function ensureModal() {
            if (document.getElementById('medidata-foto-modal')) {
                return;
            }
            $('body').append(
                '<div id="medidata-foto-modal" class="medidata-foto-modal" aria-hidden="true">' +
                    '<div class="medidata-foto-modal__backdrop"></div>' +
                    '<div class="medidata-foto-modal__dialog" role="dialog" aria-modal="true" aria-label="Foto del producto">' +
                    '<button type="button" class="medidata-foto-modal__close" aria-label="Cerrar">&times;</button>' +
                    '<div class="medidata-foto-modal__imgwrap"><img src="" alt="Foto del producto"></div>' +
                    '</div></div>'
            );
        }

        function closeModal() {
            var $m = $('#medidata-foto-modal');
            $m.removeClass('is-open').attr('aria-hidden', 'true');
            $m.find('.medidata-foto-modal__imgwrap img').attr('src', '');
        }

        $(document).on('click', '#directorio-table_wrapper img.foto-thumb', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var src = $(this).attr('data-full-src') || $(this).attr('src');
            if (!src) {
                return;
            }
            ensureModal();
            var $m = $('#medidata-foto-modal');
            $m.find('.medidata-foto-modal__imgwrap img').attr('src', src);
            $m.addClass('is-open').attr('aria-hidden', 'false');
        });

        $(document).on(
            'click',
            '#medidata-foto-modal .medidata-foto-modal__backdrop, #medidata-foto-modal .medidata-foto-modal__close',
            function () {
                closeModal();
            }
        );

        $(document).on('keydown.medidataFotoModal', function (e) {
            if (e.key === 'Escape' && $('#medidata-foto-modal').hasClass('is-open')) {
                closeModal();
            }
        });
    })();

    $(function () {
        if (!$('#directorio-table').length) {
            return;
        }
        fetch(DATA_URL)
            .then(function (r) {
                return r.json();
            })
            .then(initInventarioTable)
            .catch(function (e) {
                console.error('tabla_almacen_hospitalario:', e);
                $('#directorio-table').closest('.medidata-dt-host').removeClass('medidata-dt-host--pending');
            });
    });
})(jQuery);
