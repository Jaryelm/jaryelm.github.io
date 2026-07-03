/**
 * Columnas extendidas + edición inline + contrato (PDF) para las listas de
 * colaboradores y médicos del módulo RRHH.
 *
 * Las columnas "extra" se editan dando clic en la celda y se guardan en
 * rrhh_colaborador_extra (tipo + ref_id). El contrato se sube como PDF y se
 * visualiza en un modal tipo "ojo".
 */
(function (window, $) {
    'use strict';

    var BASE = '../../backend/registros/recursos_humanos/';
    var MAX_PDF_MB = 15; // Debe coincidir con los límites del servidor (nginx + PHP).

    // Definición de columnas en el orden solicitado por el cliente.
    var COLS = [
        { key: 'num', label: 'N°', kind: 'num' },
        { key: 'tipo', label: 'Tipo Empleado', kind: 'fixed', src: 'Tipo_Empleado' },
        { key: 'fecha_ingreso', label: 'Fecha de Ingreso', kind: 'extra', date: true, src: 'FechaIngreso' },
        { key: 'cuenta_bac', label: 'N° Cuenta BAC', kind: 'extra' },
        { key: 'nombres', label: 'Nombres', kind: 'fixed', src: 'Nombres' },
        { key: 'apellidos', label: 'Apellidos', kind: 'fixed', src: 'Apellidos' },
        { key: 'sexo', label: 'Sexo', kind: 'fixed', src: 'Sexo' },
        { key: 'dni', label: 'DNI', kind: 'fixed', src: 'Cedula' },
        { key: 'depto', label: 'Depto', kind: 'extra' },
        { key: 'cargo', label: 'Cargo', kind: 'extra', src: 'Especialidad' },
        { key: 'horario', label: 'Horario', kind: 'extra' },
        { key: 'salario', label: 'Salario', kind: 'extra' },
        { key: 'nivel_salarial', label: 'Nivel Salarial', kind: 'extra' },
        { key: 'telefono', label: 'Contacto / Teléfono', kind: 'extra', src: 'Telefono' },
        { key: 'correo_personal', label: 'Correo Personal', kind: 'extra', src: 'CorreoPersonal' },
        { key: 'correo_institucional', label: 'Correo Institucional', kind: 'extra' },
        { key: 'fecha_nacimiento', label: 'Fecha de Nacimiento', kind: 'extra', date: true, src: 'FechaNac' },
        { key: 'locker', label: 'Locker', kind: 'extra' },
        { key: 'codigo_empleado', label: 'Código Empleado', kind: 'extra' },
        { key: 'contrato', label: 'Contrato', kind: 'contrato' },
        { key: 'estado', label: 'Estado', kind: 'estado' }
    ];

    function esc(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function fmtFecha(iso) {
        if (!iso) return '';
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso));
        return m ? (m[3] + '/' + m[2] + '/' + m[1]) : String(iso);
    }

    // Valor a mostrar/editar de una columna extra (extra ?? source).
    function valueOf(c, col) {
        var x = c['x_' + col.key];
        if (x === null || x === undefined || x === '') {
            if (col.src && c[col.src] != null && c[col.src] !== '') {
                return String(c[col.src]);
            }
            return '';
        }
        return String(x);
    }

    // Columnas según el modo (readonly = vista de excolaboradores: sin toggle de estado).
    function getCols(opts) {
        opts = opts || {};
        if (opts.readonly) {
            return COLS.filter(function (col) { return col.kind !== 'estado'; });
        }
        return COLS;
    }

    function headRow(opts) {
        return getCols(opts).map(function (col) {
            return '<th scope="col">' + esc(col.label) + '</th>';
        }).join('');
    }

    function readonlyExtraCell(c, col) {
        var raw = valueOf(c, col);
        var display = col.date ? fmtFecha(raw) : raw;
        return '<td data-title="' + esc(col.label) + '">'
            + (display !== '' ? esc(display) : '<span class="rrhh-empty">—</span>') + '</td>';
    }

    function readonlyContratoCell(c) {
        var has = String(c.x_has_contrato) === '1';
        return '<td class="rrhh-contrato-cell">' + contratoButtons(c.Tipo_Empleado, c.RealID, has, false) + '</td>';
    }

    function fixedValue(c, col) {
        var v = (col.src && c[col.src] != null) ? c[col.src] : '';
        if (v === '' || v === null) v = 'N/A';
        return esc(v);
    }

    function editableCell(c, col) {
        var raw = valueOf(c, col);
        var display = col.date ? fmtFecha(raw) : raw;
        var inner = display !== '' ? esc(display) : '<span class="rrhh-empty">—</span>';
        return '<td class="rrhh-edit-cell" data-field="' + esc(col.key) + '"'
            + ' data-date="' + (col.date ? '1' : '0') + '"'
            + ' data-value="' + esc(raw) + '"'
            + ' data-tipo="' + esc(c.Tipo_Empleado) + '"'
            + ' data-ref="' + esc(c.RealID) + '"'
            + ' title="Clic para editar">' + inner + '</td>';
    }

    function contratoUrl(tipo, ref) {
        return BASE + 'ver_colaborador_contrato.php?tipo=' + encodeURIComponent(tipo) + '&ref_id=' + encodeURIComponent(ref);
    }

    function contratoButtons(tipo, ref, has, withUpload) {
        var html = '';
        if (has) {
            html += '<button type="button" class="rrhh-btn-mini rrhh-ver-contrato" data-url="' + esc(contratoUrl(tipo, ref)) + '" title="Ver contrato">'
                + '<i class="bx bx-show"></i> Ver</button>';
            if (withUpload) {
                html += '<button type="button" class="rrhh-btn-mini rrhh-subir-contrato" title="Reemplazar contrato"><i class="bx bx-upload"></i></button>';
            }
        } else if (withUpload) {
            html += '<button type="button" class="rrhh-btn-mini rrhh-subir-contrato" title="Subir contrato (PDF)"><i class="bx bx-upload"></i> Subir</button>';
        } else {
            html += '<span class="rrhh-empty">—</span>';
        }
        return html;
    }

    function contratoCell(c) {
        var has = String(c.x_has_contrato) === '1';
        var attrs = 'data-tipo="' + esc(c.Tipo_Empleado) + '" data-ref="' + esc(c.RealID) + '"';
        return '<td class="rrhh-contrato-cell" ' + attrs + '>' + contratoButtons(c.Tipo_Empleado, c.RealID, has, true) + '</td>';
    }

    function estadoCell(c) {
        var checked = (String(c.Estado) === '1') ? 'checked' : '';
        return '<td data-title="Estado"><label class="switch">'
            + '<input type="checkbox" class="toggle-state" data-id="' + esc(c.RealID) + '" data-type="' + esc(c.Tipo_Empleado) + '" ' + checked + '>'
            + '<span class="slider round"></span></label></td>';
    }

    function bodyRow(c, num, opts) {
        opts = opts || {};
        var ro = !!opts.readonly;
        var cells = getCols(opts).map(function (col) {
            if (col.kind === 'num') return '<td data-title="N°">' + num + '</td>';
            if (col.kind === 'fixed') return '<td data-title="' + esc(col.label) + '">' + fixedValue(c, col) + '</td>';
            if (col.kind === 'extra') return ro ? readonlyExtraCell(c, col) : editableCell(c, col);
            if (col.kind === 'contrato') return ro ? readonlyContratoCell(c) : contratoCell(c);
            if (col.kind === 'estado') return estadoCell(c);
            return '<td></td>';
        });
        return '<tr>' + cells.join('') + '</tr>';
    }

    function colCount(opts) { return getCols(opts).length; }

    // ---- Edición inline ----
    function beginEdit($cell) {
        if ($cell.hasClass('rrhh-editing')) return;
        $cell.addClass('rrhh-editing');
        var isDate = $cell.data('date') === 1 || $cell.data('date') === '1';
        var current = $cell.attr('data-value') || '';
        var $input = $('<input>', {
            type: isDate ? 'date' : 'text',
            value: current,
            class: 'rrhh-edit-input'
        });
        $cell.html('').append($input);
        $input.focus();

        var done = false;
        function commit() {
            if (done) return; done = true;
            saveCell($cell, $input.val());
        }
        function cancel() {
            if (done) return; done = true;
            renderCellValue($cell, $cell.attr('data-value'));
        }
        $input.on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); commit(); }
            else if (e.key === 'Escape') { e.preventDefault(); cancel(); }
        });
        $input.on('blur', commit);
    }

    function renderCellValue($cell, raw) {
        $cell.removeClass('rrhh-editing');
        var isDate = $cell.data('date') === 1 || $cell.data('date') === '1';
        var display = isDate ? fmtFecha(raw) : raw;
        $cell.html(display !== '' && display != null ? esc(display) : '<span class="rrhh-empty">—</span>');
    }

    function saveCell($cell, newVal) {
        var field = $cell.attr('data-field');
        var tipo = $cell.attr('data-tipo');
        var ref = $cell.attr('data-ref');
        var prev = $cell.attr('data-value') || '';
        newVal = (newVal == null ? '' : String(newVal).trim());

        if (newVal === prev) { renderCellValue($cell, prev); return; }

        renderCellValue($cell, newVal); // optimista
        $.ajax({
            url: BASE + 'save_colaborador_extra.php',
            type: 'POST',
            dataType: 'json',
            data: { tipo: tipo, ref_id: ref, field: field, value: newVal },
            success: function (resp) {
                if (resp && resp.success) {
                    $cell.attr('data-value', newVal);
                } else {
                    $cell.attr('data-value', prev);
                    renderCellValue($cell, prev);
                    if (window.Swal) Swal.fire('Error', (resp && resp.message) || 'No se pudo guardar.', 'error');
                }
            },
            error: function () {
                $cell.attr('data-value', prev);
                renderCellValue($cell, prev);
                if (window.Swal) Swal.fire('Error', 'Error de comunicación al guardar.', 'error');
            }
        });
    }

    // ---- Contrato (subir / ver) ----
    function hiddenFileInput() {
        var el = document.getElementById('rrhhContratoInput');
        if (!el) {
            el = document.createElement('input');
            el.type = 'file';
            el.accept = 'application/pdf';
            el.id = 'rrhhContratoInput';
            el.style.display = 'none';
            document.body.appendChild(el);
            $(el).on('change', function () {
                if (!this.files || !this.files.length) return;
                var f = this.files[0];
                this.value = '';
                var ext = (f.name.split('.').pop() || '').toLowerCase();
                if (ext !== 'pdf' || (f.type && f.type !== 'application/pdf')) {
                    if (window.Swal) Swal.fire('Archivo no válido', 'El contrato debe ser un archivo PDF.', 'warning');
                    return;
                }
                if (f.size > MAX_PDF_MB * 1024 * 1024) {
                    if (window.Swal) Swal.fire('Archivo muy grande', 'El PDF no puede superar ' + MAX_PDF_MB + ' MB.', 'warning');
                    return;
                }
                uploadContrato(f);
            });
        }
        return el;
    }

    function uploadContrato(file) {
        var t = window.__rrhhContratoTarget;
        if (!t) return;
        var fd = new FormData();
        fd.append('tipo', t.tipo);
        fd.append('ref_id', t.ref);
        fd.append('contrato', file);
        var $cell = t.$cell;
        $cell.css('opacity', 0.5);
        $.ajax({
            url: BASE + 'upload_colaborador_contrato.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                $cell.css('opacity', 1);
                if (resp && resp.success) {
                    $cell.html(contratoButtons(t.tipo, t.ref, true, true));
                    if (window.Swal) Swal.fire({ title: 'Listo', text: 'Contrato subido.', icon: 'success', timer: 1300, showConfirmButton: false });
                } else {
                    if (window.Swal) Swal.fire('Error', (resp && resp.message) || 'No se pudo subir.', 'error');
                }
            },
            error: function (xhr) {
                $cell.css('opacity', 1);
                var msg = 'Error al subir el contrato.';
                if (xhr && xhr.status === 413) {
                    msg = 'El servidor rechazó el archivo por ser muy grande (límite de subida). '
                        + 'Use un PDF más liviano o solicite a TI ampliar el límite.';
                } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (window.Swal) Swal.fire('Error', msg, 'error');
            }
        });
    }

    function verPDF(url) {
        var sep = url.indexOf('?') >= 0 ? '&' : '?';
        $('#rrhhPdfFrame').attr('src', url + sep + 'view=inline#zoom=100');
        $('#rrhhPdfModal').css('display', 'flex');
        document.body.style.overflow = 'hidden';
        window.__rrhhPdfUrl = url;
    }
    function cerrarPDF() {
        $('#rrhhPdfModal').css('display', 'none');
        $('#rrhhPdfFrame').attr('src', '');
        document.body.style.overflow = 'auto';
        window.__rrhhPdfUrl = '';
    }

    function bindGlobal() {
        $(document)
            .off('click.rrhhExtra')
            .on('click.rrhhExtra', '.rrhh-edit-cell', function () { beginEdit($(this)); })
            .on('click.rrhhExtra', '.rrhh-subir-contrato', function () {
                var $cell = $(this).closest('.rrhh-contrato-cell');
                window.__rrhhContratoTarget = { tipo: $cell.attr('data-tipo'), ref: $cell.attr('data-ref'), $cell: $cell };
                hiddenFileInput().click();
            })
            .on('click.rrhhExtra', '.rrhh-ver-contrato', function () { verPDF($(this).attr('data-url')); })
            .on('click.rrhhExtra', '#rrhhPdfClose', cerrarPDF)
            .on('click.rrhhExtra', '#rrhhPdfDownload', function () {
                if (window.__rrhhPdfUrl) {
                    var u = window.__rrhhPdfUrl;
                    u += (u.indexOf('?') >= 0 ? '&' : '?') + 'dl=1';
                    window.open(u, '_blank');
                }
            });
    }

    window.RRHH_COLAB = {
        endpoint: BASE + 'fetch_collaborators_rrhh.php',
        headRow: headRow,
        bodyRow: bodyRow,
        colCount: colCount,
        bindGlobal: bindGlobal
    };

    $(function () { bindGlobal(); });

})(window, jQuery);
