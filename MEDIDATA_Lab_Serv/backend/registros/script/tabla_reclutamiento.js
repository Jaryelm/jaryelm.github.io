/**
 * DataTable server-side: postulantes website (aplica) + incorporar al proceso RRHH.
 * Requiere window.MEDIDATA_RECLUTAMIENTO
 */
var medidataPdfUrlActual = '';

function medidataVerPDF(url, titulo) {
    var separator = url.indexOf('?') >= 0 ? '&' : '?';
    var urlConView = url + separator + 'view=inline#zoom=100';
    medidataPdfUrlActual = url + separator + 'download=1';
    var modal = document.getElementById('pdfModal');
    var frame = document.getElementById('pdfFrame');
    var title = document.getElementById('pdfModalTitle');
    if (!modal || !frame || !title) {
        window.open(urlConView, '_blank');
        return;
    }
    title.textContent = titulo || 'Visualizar documento';
    frame.src = urlConView;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function medidataCerrarPDFModal() {
    var modal = document.getElementById('pdfModal');
    var frame = document.getElementById('pdfFrame');
    if (!modal || !frame) {
        return;
    }
    modal.style.display = 'none';
    frame.src = '';
    medidataPdfUrlActual = '';
    document.body.style.overflow = 'auto';
}

function medidataDescargarPDFActual() {
    if (medidataPdfUrlActual) {
        window.open(medidataPdfUrlActual, '_blank');
    }
}

window.addEventListener('click', function (event) {
    var pdfModal = document.getElementById('pdfModal');
    if (pdfModal && event.target === pdfModal) {
        medidataCerrarPDFModal();
    }
});

(function ($) {
    'use strict';

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderEllipsis(text, type) {
        var val = String(text || '');
        if (type !== 'display') {
            return val;
        }
        if (!val) {
            return '—';
        }
        return '<span class="rrhh-aplica-ellipsis" title="' + escapeHtml(val) + '">' + escapeHtml(val) + '</span>';
    }

    function badgeEstado(estado) {
        var map = {
            Pendiente: 'badge-rrhh-pending',
            Incorporado: 'badge-rrhh-yes',
            Descartado: 'badge-rrhh-no',
        };
        var cls = map[estado] || 'badge-rrhh-pending';
        var label = estado || 'Pendiente';
        return '<span class="badge-rrhh ' + cls + '" title="' + escapeHtml(label) + '">' + escapeHtml(label) + '</span>';
    }

    function loadVacantes(cfg) {
        return $.getJSON(cfg.vacantesUrl || '../../backend/registros/fetch_rrhh_vacantes_abiertas.php');
    }

    function pickVacante(cfg, row, title, confirmLabel) {
        return loadVacantes(cfg).then(function (res) {
            if (!res.success || !res.data || !res.data.length) {
                Swal.fire('Sin vacantes', 'No hay vacantes abiertas disponibles. Registre una vacante en RRHH.', 'warning');
                return null;
            }

            var suggested = parseInt(row.vacante_sugerida_id, 10) || 0;
            var inputOptions = {};
            res.data.forEach(function (v) {
                inputOptions[v.id] = v.label;
            });

            return Swal.fire({
                title: title,
                width: '32em',
                html: '<div class="medidata-swal-rrhh-meta"><strong>' + escapeHtml(row.nombre_completo) + '</strong><br>' +
                    'Puesto: ' + escapeHtml(row.puesto_aspirado) + '<br>' +
                    'Vacante actual: ' + escapeHtml(row.vacante_sugerida || 'N/A') + '</div>',
                input: 'select',
                inputOptions: inputOptions,
                inputValue: suggested > 0 ? String(suggested) : '',
                inputPlaceholder: 'Seleccione vacante',
                showCancelButton: true,
                confirmButtonText: confirmLabel,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#06adbf',
                customClass: {
                    popup: 'medidata-swal-rrhh medidata-swal-rrhh--pick',
                },
            }).then(function (result) {
                if (!result.isConfirmed || !result.value) {
                    return null;
                }
                return parseInt(result.value, 10);
            });
        });
    }

    function swalLimpiarInputsSwal() {
        var popup = Swal.getPopup();
        if (!popup) {
            return;
        }
        popup.querySelectorAll('.swal2-input, .swal2-select, select.swal2-select').forEach(function (el) {
            el.remove();
        });
    }

    function confirmIncorporar(row) {
        var idVacante = parseInt(row.vacante_sugerida_id, 10) || 0;
        if (idVacante <= 0) {
            return Swal.fire({
                title: 'Sin vacante',
                html: '<div class="medidata-swal-rrhh-meta">No hay una vacante abierta para <strong>' +
                    escapeHtml(row.puesto_aspirado || 'este puesto') +
                    '</strong>. Registre o abra una vacante en RRHH antes de incorporar.</div>',
                icon: 'warning',
                confirmButtonColor: '#06adbf',
            }).then(function () {
                return null;
            });
        }

        return Swal.fire({
            title: 'Incorporar al proceso RRHH',
            width: '32em',
            html: '<div class="medidata-swal-rrhh-meta"><strong>' + escapeHtml(row.nombre_completo) + '</strong><br>' +
                'Puesto: ' + escapeHtml(row.puesto_aspirado) + '<br>' +
                'Vacante: ' + escapeHtml(row.vacante_sugerida || 'N/A') + '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Incorporar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#06adbf',
            customClass: {
                popup: 'medidata-swal-rrhh',
            },
            didOpen: swalLimpiarInputsSwal,
        }).then(function (result) {
            if (!result.isConfirmed) {
                return null;
            }
            return idVacante;
        });
    }

    function postAction(cfg, url, data) {
        return $.ajax({
            type: 'POST',
            url: url,
            data: data,
            dataType: 'json',
        });
    }

    function reloadTable(table) {
        table.ajax.reload(null, false);
    }

    function swalOk(message) {
        return Swal.fire({
            title: '¡Listo!',
            text: message,
            icon: 'success',
            confirmButtonColor: '#06adbf',
        });
    }

    function swalError(message) {
        return Swal.fire({
            title: 'Error',
            text: message,
            icon: 'error',
            confirmButtonColor: '#06adbf',
        });
    }

    $(function () {
        var cfg = window.MEDIDATA_RECLUTAMIENTO || {};
        var $table = $('#rrhh-aplica-table');
        if (!$table.length || !cfg.dbOk || !cfg.ajaxUrl) {
            return;
        }

        var cvBase = cfg.cvBaseUrl || '../../backend/php/download_cv.php';
        var apiIncorporar = cfg.incorporarUrl || '../../backend/php/rrhh_aplica_incorporar.php';
        var apiDescartar = cfg.descartarUrl || '../../backend/php/rrhh_aplica_descartar.php';
        var apiReasignar = cfg.reasignarUrl || '../../backend/php/rrhh_aplica_reasignar.php';
        var detalleBase = cfg.detalleCandidatoUrl || '../recursos_humanos/detalle_postulante_usr.php';

        var dt = $table.DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            order: [[7, 'desc']],
            ajax: {
                url: cfg.ajaxUrl,
                type: 'GET',
            },
            columnDefs: [
                { targets: 0, width: '96px', className: 'rrhh-aplica-col-id' },
                { targets: 1, width: '14%', className: 'rrhh-aplica-col-clip' },
                { targets: 2, width: '11%', className: 'rrhh-aplica-col-clip' },
                { targets: 3, width: '15%', className: 'rrhh-aplica-col-vacante' },
                { targets: 4, width: '92px', className: 'rrhh-aplica-col-estado' },
                { targets: 5, width: '88px', className: 'rrhh-aplica-col-clip' },
                { targets: 6, width: '13%', className: 'rrhh-aplica-col-clip' },
                { targets: 7, width: '108px', className: 'rrhh-aplica-col-fecha' },
                { targets: 8, width: '118px', orderable: false, searchable: false, className: 'rrhh-aplica-col-actions rrhh-web-actions' },
            ],
            columns: [
                {
                    data: 'numero_id',
                    defaultContent: '',
                    render: renderEllipsis,
                },
                {
                    data: 'nombre_completo',
                    defaultContent: '',
                    render: renderEllipsis,
                },
                {
                    data: 'puesto_aspirado',
                    defaultContent: '',
                    render: renderEllipsis,
                },
                {
                    data: 'vacante_sugerida',
                    orderable: false,
                    defaultContent: '',
                    render: function (data, type, row) {
                        var val = String(data || 'Sin match');
                        if (type !== 'display') {
                            return val;
                        }
                        var hint = row.match_type === 'ambiguous'
                            ? ' <i class="fa fa-info-circle" title="Varias vacantes posibles"></i>'
                            : '';
                        return '<span class="rrhh-aplica-ellipsis rrhh-vacante-sugerida" title="' + escapeHtml(val) + '">' +
                            escapeHtml(val) + hint + '</span>';
                    },
                },
                {
                    data: 'estado_rrhh',
                    defaultContent: 'Pendiente',
                    render: function (data, type) {
                        if (type !== 'display') {
                            return data || 'Pendiente';
                        }
                        return badgeEstado(data || 'Pendiente');
                    },
                },
                {
                    data: 'whatsapp',
                    defaultContent: '',
                    render: renderEllipsis,
                },
                {
                    data: 'correo',
                    defaultContent: '',
                    render: renderEllipsis,
                },
                {
                    data: 'fecha_registro',
                    defaultContent: '',
                    render: renderEllipsis,
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        if (type !== 'display') {
                            return '';
                        }

                        var parts = [];
                        var estado = row.estado_rrhh || 'Pendiente';

                        if (row.cv_disponible) {
                            parts.push(
                                '<i class="bx bx-show rrhh-cv-view" title="Ver hoja de vida" data-aplica-id="' +
                                parseInt(row.id, 10) + '" data-cv-title="' + escapeHtml(row.nombre_completo || '') + '"></i>'
                            );
                        }

                        if (estado === 'Pendiente') {
                            parts.push(
                                '<button type="button" class="rrhh-action-btn rrhh-btn-incorporar" data-id="' + row.id + '" title="Incorporar al proceso RRHH">' +
                                '<i class="fa fa-user-plus"></i></button>'
                            );
                            parts.push(
                                '<button type="button" class="rrhh-action-btn rrhh-btn-descartar" data-id="' + row.id + '" title="Descartar postulación">' +
                                '<i class="fa fa-times"></i></button>'
                            );
                        }

                        if (estado === 'Incorporado' && row.id_candidate_rrhh) {
                            parts.push(
                                '<a title="Ver candidato RRHH" class="rrhh-action-icon" href="' +
                                detalleBase + '?id=' + parseInt(row.id_candidate_rrhh, 10) + '">' +
                                '<i class="fa fa-eye"></i></a>'
                            );
                            parts.push(
                                '<button type="button" class="rrhh-action-btn rrhh-btn-reasignar" data-id="' + row.id + '" title="Reasignar vacante">' +
                                '<i class="fa fa-exchange"></i></button>'
                            );
                        }

                        if (estado === 'Descartado' && row.motivo_descarte) {
                            parts.push(
                                '<span class="rrhh-action-icon rrhh-action-icon--muted" title="' + escapeHtml(row.motivo_descarte) + '">' +
                                '<i class="fa fa-comment-o"></i></span>'
                            );
                        }

                        if (!parts.length) {
                            return '—';
                        }

                        return '<div class="rrhh-actions-cell">' + parts.join('') + '</div>';
                    },
                },
            ],
            language: {
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
                    sPrevious: 'Anterior',
                },
            },
        });

        $table.on('click', '.rrhh-cv-view', function () {
            var id = parseInt($(this).data('aplica-id'), 10);
            var nombre = String($(this).data('cv-title') || '').trim();
            if (!id) {
                return;
            }
            var titulo = nombre ? ('Hoja de vida — ' + nombre) : 'Hoja de vida';
            medidataVerPDF(cvBase + '?id=' + id, titulo);
        });

        $table.on('click', '.rrhh-btn-incorporar', function () {
            var row = dt.row($(this).closest('tr')).data();
            if (!row) {
                return;
            }
            confirmIncorporar(row).then(function (idVacante) {
                if (!idVacante) {
                    return;
                }
                postAction(cfg, apiIncorporar, { id_aplica: row.id, id_vacante: idVacante }).done(function (res) {
                    if (res.success) {
                        swalOk(res.message);
                        reloadTable(dt);
                    } else {
                        swalError(res.message || 'No se pudo incorporar');
                    }
                }).fail(function () {
                    swalError('Error de comunicación con el servidor');
                });
            });
        });

        $table.on('click', '.rrhh-btn-reasignar', function () {
            var row = dt.row($(this).closest('tr')).data();
            if (!row) {
                return;
            }
            pickVacante(cfg, row, 'Reasignar vacante', 'Reasignar').then(function (idVacante) {
                if (!idVacante) {
                    return;
                }
                postAction(cfg, apiReasignar, { id_aplica: row.id, id_vacante: idVacante }).done(function (res) {
                    if (res.success) {
                        swalOk(res.message);
                        reloadTable(dt);
                    } else {
                        swalError(res.message || 'No se pudo reasignar');
                    }
                }).fail(function () {
                    swalError('Error de comunicación con el servidor');
                });
            });
        });

        $table.on('click', '.rrhh-btn-descartar', function () {
            var row = dt.row($(this).closest('tr')).data();
            if (!row) {
                return;
            }
            Swal.fire({
                title: '¿Descartar postulación?',
                text: row.nombre_completo,
                input: 'textarea',
                inputPlaceholder: 'Motivo (opcional)',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Descartar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#FC3B56',
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                postAction(cfg, apiDescartar, { id_aplica: row.id, motivo: result.value || '' }).done(function (res) {
                    if (res.success) {
                        swalOk(res.message);
                        reloadTable(dt);
                    } else {
                        swalError(res.message || 'No se pudo descartar');
                    }
                });
            });
        });
    });
})(jQuery);
