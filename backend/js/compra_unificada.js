/**
 * Compra unificada: clonar filas detalle + maestro producto nuevo.
 * Rutas AJAX relativas al documento (frontend/almacen/*.php): ../funciones/…
 * No reinicializar todos los .select2 tras cargar cuentas (rompe el combo Modo / «Producto nuevo»).
 */
(function () {
    let cuentasOptions = '';
    let catMedicateOptions = '';
    let productOptionsHtml = '';

    var URL_CAT_CUENTAS = '../funciones/cat_cuentas.php';
    var URL_CAT_MEDICATE = '../funciones/cat.php';

    function dropdownParent() {
        return $('#content').length ? $('#content') : $(document.body);
    }

    function reiniciarSelect2Solo($selects) {
        var $dp = dropdownParent();
        $selects.each(function () {
            var $s = $(this);
            if ($s.data('select2')) {
                $s.select2('destroy');
            }
            $s.select2({ width: '100%', dropdownParent: $dp });
        });
    }

    function lineaToCodpro(lineaVal) {
        if (!lineaVal) return '';
        if (lineaVal.includes('OFTALMED') || lineaVal.includes('OFTALM')) {
            if (lineaVal.includes('INSUMO') || lineaVal.includes('MATERIAL') || lineaVal.includes('EQUIPO')) {
                return '110400104';
            }
            return '110400105';
        }
        if (lineaVal.includes('CONSUMIBLE') || lineaVal.includes('SUMINISTRO') ||
            lineaVal.includes('MATERIAL DESCARTABLE') || lineaVal.includes('PROMOCIONES') ||
            lineaVal.includes('ARRENDAMIENTO') || lineaVal.includes('ALQUILER')) {
            return '110400103';
        }
        return '110400102';
    }

    function getNextRowIndex() {
        return document.querySelectorAll('#items_tbody tr.line-main').length;
    }

    function reindexPair(mainEl, maestroEl, idx) {
        [mainEl, maestroEl].forEach(function (el) {
            el.querySelectorAll('[name]').forEach(function (node) {
                var n = node.getAttribute('name');
                if (n) {
                    node.setAttribute('name', n.replace(/\[0\]/g, '[' + idx + ']'));
                }
            });
        });
    }

    function clearRowInputs(mainEl, maestroEl) {
        mainEl.querySelectorAll('input:not([type=hidden]):not([type=radio]), textarea').forEach(function (i) {
            if (i.classList.contains('stock-actual') || i.classList.contains('stock-resultado')) return;
            i.value = '';
        });
        maestroEl.querySelectorAll('input:not([type=radio]), textarea, select').forEach(function (i) {
            if (i.type === 'file') {
                i.value = '';
            } else if (i.tagName === 'SELECT') {
                i.selectedIndex = 0;
            } else if (i.type === 'radio') {
                i.checked = false;
            } else {
                i.value = '';
            }
        });
        mainEl.querySelectorAll('select').forEach(function (s) {
            s.selectedIndex = 0;
        });
        var sm = mainEl.querySelector('.line-mode');
        if (sm) sm.value = 'existing';
    }

    function refreshStockForMainRow(trMain) {
        var mode = trMain.querySelector('.line-mode');
        if (!mode || mode.value !== 'existing') return;
        var sel = trMain.querySelector('.product-id-select');
        if (!sel || !sel.value) {
            var sa = trMain.querySelector('.stock-actual');
            var sr = trMain.querySelector('.stock-resultado');
            if (sa) sa.value = '';
            if (sr) sr.value = '';
            return;
        }
        $.post('../../backend/php/obtener_stock_disponible.php', { producto_id: sel.value }, function (r) {
            var act = 0;
            if (r.success) act = parseInt(r.stock_disponible, 10) || 0;
            var sa = trMain.querySelector('.stock-actual');
            var sr = trMain.querySelector('.stock-resultado');
            var qtyEl = trMain.querySelector('input[name^="cantidad"]');
            var qty = parseInt(qtyEl && qtyEl.value, 10) || 0;
            if (sa) sa.value = act;
            if (sr) sr.value = act + qty;
        }, 'json');
    }

    function toggleMaestroRow(trMain) {
        var mode = trMain.querySelector('.line-mode');
        var trM = trMain.nextElementSibling;
        if (!trM || !trM.classList.contains('line-maestro')) return;
        var prodSel = trMain.querySelector('.product-id-select');
        if (mode.value === 'new') {
            trM.style.display = '';
            if (prodSel) {
                prodSel.disabled = true;
                prodSel.value = '';
            }
            var sa = trMain.querySelector('.stock-actual');
            var sr = trMain.querySelector('.stock-resultado');
            if (sa) sa.value = '0';
            var qtyEl = trMain.querySelector('input[name^="cantidad"]');
            var qty = parseInt(qtyEl && qtyEl.value, 10) || 0;
            if (sr) sr.value = qty;
        } else {
            trM.style.display = 'none';
            if (prodSel) prodSel.disabled = false;
            refreshStockForMainRow(trMain);
        }
    }

    window.addItemRow = function () {
        var tbody = document.getElementById('items_tbody');
        var firstMain = tbody.querySelector('tr.line-main');
        var firstMaestro = firstMain && firstMain.nextElementSibling;
        if (!firstMain || !firstMaestro || !firstMaestro.classList.contains('line-maestro')) return;

        var idx = getNextRowIndex();
        var newM = firstMain.cloneNode(true);
        var newA = firstMaestro.cloneNode(true);
        reindexPair(newM, newA, idx);
        clearRowInputs(newM, newA);

        $(newM).find('select.select2').each(function () {
            if ($(this).data('select2')) $(this).select2('destroy');
        });
        $(newA).find('select.select2').each(function () {
            if ($(this).data('select2')) $(this).select2('destroy');
        });

        $(newM).find('select.cat_cuenta_sel').html(cuentasOptions);
        $(newM).find('select.product-id-select').html(productOptionsHtml);
        $(newA).find('select.np_medicate').html(catMedicateOptions);

        tbody.appendChild(newM);
        tbody.appendChild(newA);

        var $dp = dropdownParent();
        $(newM).find('.select2').select2({ width: '100%', dropdownParent: $dp });
        $(newA).find('.select2').select2({ width: '100%', dropdownParent: $dp });

        newM.querySelector('.line-mode').value = 'existing';
        newA.style.display = 'none';
        toggleMaestroRow(newM);
    };

    window.removeItemPair = function (btn) {
        var tr = btn.closest('tr.line-main');
        if (document.querySelectorAll('#items_tbody tr.line-main').length <= 1) {
            alert('Debe existir al menos una línea de detalle.');
            return;
        }
        var next = tr.nextElementSibling;
        tr.remove();
        if (next && next.classList.contains('line-maestro')) next.remove();
        if (typeof calcularTotalesGenerales === 'function') calcularTotalesGenerales();
    };

    window.toggleLineMode = function (sel) {
        toggleMaestroRow(sel.closest('tr.line-main'));
    };

    window.updateCodproLinea = function (sel) {
        var trM = sel.closest('tr.line-maestro');
        if (!trM) return;
        var inp = trM.querySelector('.np_codpro_field');
        if (inp) inp.value = lineaToCodpro(sel.value || '');
    };

    $(document).ready(function () {
        $.post(URL_CAT_CUENTAS)
            .done(function (resp) {
                cuentasOptions = typeof resp === 'string' ? resp : '';
                if (!cuentasOptions.trim()) {
                    cuentasOptions = '<option value="">Seleccione cuenta</option>';
                }
                var $cuentas = $('select.cat_cuenta_sel');
                $cuentas.html(cuentasOptions);
                reiniciarSelect2Solo($cuentas);
            })
            .fail(function (xhr, status, err) {
                console.error(
                    '[compra_unificada] No se cargó cat_cuentas.php (' + URL_CAT_CUENTAS + ').',
                    status,
                    err,
                    xhr.status,
                    xhr.responseText ? xhr.responseText.substring(0, 300) : ''
                );
            });

        $.post(URL_CAT_MEDICATE)
            .done(function (resp) {
                catMedicateOptions = typeof resp === 'string' ? resp : '';
                var $med = $('select.np_medicate');
                if ($med.length) {
                    $med.html(catMedicateOptions);
                    reiniciarSelect2Solo($med);
                }
            })
            .fail(function (xhr, status, err) {
                console.error(
                    '[compra_unificada] No se cargó cat.php (' + URL_CAT_MEDICATE + ').',
                    status,
                    err,
                    xhr.status,
                    xhr.responseText ? xhr.responseText.substring(0, 300) : ''
                );
            });
        if (typeof window.__PRODUCT_OPTIONS_HTML__ === 'string') {
            productOptionsHtml = window.__PRODUCT_OPTIONS_HTML__;
            $('select.product-id-select').html(productOptionsHtml);
        }

        $(document).on('change', '.line-mode', function () {
            toggleLineMode(this);
        });
        $(document).on('change', '.product-id-select', function () {
            refreshStockForMainRow($(this).closest('tr.line-main')[0]);
        });
        $(document).on('input change', 'tr.line-main input[name^="cantidad"]', function () {
            var tr = $(this).closest('tr.line-main')[0];
            var mode = tr.querySelector('.line-mode');
            if (mode && mode.value === 'existing') {
                refreshStockForMainRow(tr);
            } else {
                var sa = tr.querySelector('.stock-actual');
                var sr = tr.querySelector('.stock-resultado');
                var qty = parseInt(this.value, 10) || 0;
                if (sa) sa.value = '0';
                if (sr) sr.value = qty;
            }
        });
        $(document).on('change', '.np_linea_select', function () {
            updateCodproLinea(this);
        });
    });
})();
