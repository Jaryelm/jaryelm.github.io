/**

 * Cambio de estado del candidato en detalle (admin + RRHH).

 */

(function ($) {

    'use strict';



    function isDetallePostulanteUrl(url) {

        return /detalle_postulante(_usr)?\.php/i.test(url || '');

    }



    function resolveReturnUrl(cfg) {

        var candidateId = cfg.candidateId || 0;

        var fallback = cfg.returnUrl || 'postulantes_usr.php';

        var storageKey = 'medidata_rrhh_return_' + candidateId;

        var stored = '';



        try {

            stored = sessionStorage.getItem(storageKey) || '';

        } catch (e) {

            stored = '';

        }



        if (stored && !isDetallePostulanteUrl(stored)) {

            return stored;

        }



        var referrer = document.referrer || '';

        if (referrer && !isDetallePostulanteUrl(referrer)) {

            try {

                sessionStorage.setItem(storageKey, referrer);

            } catch (e2) {

                /* ignore */

            }

            return referrer;

        }



        try {

            sessionStorage.setItem(storageKey, fallback);

        } catch (e3) {

            /* ignore */

        }

        return fallback;

    }



    function pageSuffix(cfg) {

        return cfg.isUsr ? '_usr' : '';

    }



    $(function () {

        var cfg = window.MEDIDATA_CANDIDATO_ESTADO || {};

        var $btn = $('#btn-volver-candidato');

        var $form = $('#rrhh-candidato-estado-form');

        var fallback = $btn.data('returnUrl') || cfg.returnUrl || 'postulantes_usr.php';

        var returnUrl = resolveReturnUrl({

            candidateId: cfg.candidateId || 0,

            returnUrl: fallback,

        });



        $btn.on('click', function () {

            window.location.href = returnUrl;

        });



        if (!$form.length || !cfg.candidateId) {

            return;

        }



        var apiUrl = cfg.estadoUrl || '../../backend/php/rrhh_candidato_estado.php';

        var formularioApiUrl = cfg.formularioUrl || '../../backend/php/rrhh_formulario_empleado_link.php';

        var expedienteApiUrl = cfg.expedienteUrl || '../../backend/php/rrhh_expediente_link.php';

        var suffix = pageSuffix(cfg);



        function medidataCopiarTexto(text) {

            if (navigator.clipboard && navigator.clipboard.writeText) {

                return navigator.clipboard.writeText(text);

            }

            var $tmp = $('<textarea>').css({ position: 'fixed', left: '-9999px' }).val(text).appendTo('body');

            $tmp[0].select();

            document.execCommand('copy');

            $tmp.remove();

            return $.Deferred().resolve().promise();

        }



        function medidataModalEnlace(titulo, url, email, onCloseReload) {

            var emailNote = email

                ? '<p style="text-align:left;font-size:13px;color:#555;margin:8px 0 0;">Correo: <strong>' + $('<div>').text(email).html() + '</strong></p>'

                : '<p style="text-align:left;font-size:13px;color:#856404;margin:8px 0 0;">Sin correo válido — copie el enlace manualmente.</p>';



            var html = '<p style="text-align:left;margin:0 0 10px;">Comparta este enlace con el candidato:</p>'

                + '<input type="text" readonly class="swal2-input" style="width:100%;font-size:13px;" value="' + $('<div>').text(url).html() + '">'

                + emailNote;



            Swal.fire({

                title: titulo,

                html: html,

                width: 620,

                showCancelButton: true,

                showDenyButton: !!email,

                confirmButtonText: 'Copiar enlace',

                denyButtonText: 'Enviar por correo',

                cancelButtonText: 'Cerrar',

                reverseButtons: true,

                focusConfirm: false

            }).then(function (result) {

                if (result.isConfirmed) {

                    medidataCopiarTexto(url).then(function () {

                        Swal.fire({ icon: 'success', title: 'Copiado', timer: 1200, showConfirmButton: false });

                    });

                } else if (result.isDenied) {

                    return null;

                } else if (onCloseReload && result.dismiss === Swal.DismissReason.cancel) {

                    window.location.reload();

                }

            });

        }



        function medidataMostrarEnlaceFormulario(url, email) {

            Swal.fire({

                title: 'Formulario de empleado',

                html: '<p style="text-align:left;margin:0 0 10px;">Comparta este enlace con el candidato para que complete el formulario de empleado:</p>'

                    + '<input id="swal-form-url" type="text" readonly class="swal2-input" style="width:100%;font-size:13px;" value="' + $('<div>').text(url).html() + '">'

                    + (email ? '<p style="text-align:left;font-size:13px;color:#555;margin:8px 0 0;">Correo: <strong>' + $('<div>').text(email).html() + '</strong></p>'

                        : '<p style="text-align:left;font-size:13px;color:#856404;margin:8px 0 0;">Sin correo válido — copie el enlace manualmente.</p>'),

                width: 620,

                showCancelButton: true,

                showDenyButton: !!email,

                confirmButtonText: 'Copiar enlace',

                denyButtonText: 'Enviar por correo',

                cancelButtonText: 'Cerrar',

                reverseButtons: true,

                focusConfirm: false

            }).then(function (result) {

                if (result.isConfirmed) {

                    medidataCopiarTexto(url).then(function () {

                        Swal.fire({ icon: 'success', title: 'Copiado', timer: 1200, showConfirmButton: false });

                    });

                } else if (result.isDenied) {

                    $.ajax({

                        type: 'POST',

                        url: formularioApiUrl,

                        data: { candidate_id: cfg.candidateId, action: 'send_email' },

                        dataType: 'json'

                    }).done(function (res) {

                        if (res.success) {

                            Swal.fire('Correo enviado', res.message, 'success').then(function () {

                                window.location.reload();

                            });

                        } else {

                            Swal.fire('Aviso', res.message || 'No se pudo enviar.', 'warning');

                        }

                    }).fail(function () {

                        Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');

                    });

                } else if (result.dismiss === Swal.DismissReason.cancel) {

                    window.location.reload();

                }

            });

        }



        function medidataAbrirFormularioEmpleado() {

            $.ajax({

                type: 'POST',

                url: formularioApiUrl,

                data: { candidate_id: cfg.candidateId, action: 'link' },

                dataType: 'json'

            }).done(function (res) {

                if (res.success && res.url) {

                    $form.find('[name="status"]').val('Formulario Empleados').trigger('change');

                    medidataMostrarEnlaceFormulario(res.url, res.email || cfg.candidateEmail || '');

                } else {

                    Swal.fire('Error', res.message || 'No se pudo generar el enlace.', 'error');

                }

            }).fail(function () {

                Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');

            });

        }



        function medidataMostrarEnlaceExpediente(url, email, estado) {

            var faltantes = (estado && estado.missing) ? estado.missing.length : 0;

            var faltantesHtml = '';

            if (estado && estado.missing && estado.missing.length) {

                faltantesHtml = '<ul style="text-align:left;font-size:13px;color:#555;margin:8px 0;padding-left:18px;">';

                estado.missing.forEach(function (item) {

                    faltantesHtml += '<li>' + $('<div>').text(item.label).html() + '</li>';

                });

                faltantesHtml += '</ul>';

            }



            Swal.fire({

                title: 'Expediente de contratación',

                html: '<p style="text-align:left;margin:0 0 10px;">Enlace para subir documentos (solo PDF):</p>'

                    + '<input type="text" readonly class="swal2-input" style="width:100%;font-size:13px;" value="' + $('<div>').text(url).html() + '">'

                    + '<p style="text-align:left;font-size:13px;color:#555;margin:8px 0 4px;"><strong>Faltan ' + faltantes + ' documento(s):</strong></p>'

                    + faltantesHtml

                    + (email ? '<p style="text-align:left;font-size:13px;color:#555;">Correo: <strong>' + $('<div>').text(email).html() + '</strong></p>'

                        : '<p style="text-align:left;font-size:13px;color:#856404;">Sin correo válido — copie el enlace manualmente.</p>'),

                width: 640,

                showCancelButton: true,

                showDenyButton: !!email,

                confirmButtonText: 'Copiar enlace',

                denyButtonText: 'Enviar por correo',

                cancelButtonText: 'Ver detalle expediente',

                reverseButtons: true

            }).then(function (result) {

                if (result.isConfirmed) {

                    medidataCopiarTexto(url).then(function () {

                        Swal.fire({ icon: 'success', title: 'Copiado', timer: 1200, showConfirmButton: false });

                    });

                } else if (result.isDenied) {

                    $.ajax({

                        type: 'POST',

                        url: expedienteApiUrl,

                        data: { candidate_id: cfg.candidateId, action: 'send_email' },

                        dataType: 'json'

                    }).done(function (res) {

                        if (res.success) {

                            Swal.fire('Correo enviado', res.message, 'success');

                        } else {

                            Swal.fire('Aviso', res.message || 'No se pudo enviar.', 'warning');

                        }

                    }).fail(function () {

                        Swal.fire('Error', 'Error de comunicación.', 'error');

                    });

                } else if (result.dismiss === Swal.DismissReason.cancel) {

                    window.location.href = 'expediente_estado' + suffix + '.php?id=' + cfg.candidateId;

                }

            });

        }



        function medidataAbrirExpediente() {

            $.ajax({

                type: 'POST',

                url: expedienteApiUrl,

                data: { candidate_id: cfg.candidateId, action: 'link' },

                dataType: 'json'

            }).done(function (res) {

                if (res.success && res.url) {

                    $form.find('[name="status"]').val('Llenando Expediente').trigger('change');

                    medidataMostrarEnlaceExpediente(res.url, res.email || cfg.candidateEmail || '', res.estado);

                } else {

                    Swal.fire('Error', res.message || 'No se pudo generar el enlace.', 'error');

                }

            }).fail(function () {

                Swal.fire('Error', 'Error de comunicación.', 'error');

            });

        }



        function medidataIrEntrevista() {

            window.location.href = 'formulario_entrevista' + suffix + '.php?id=' + cfg.candidateId;

        }



        function medidataIrPsicometricas() {

            window.location.href = 'prueba_psicometrica_candidato' + suffix + '.php?id=' + cfg.candidateId;

        }



        function medidataMarcarContratado() {

            Swal.fire({

                title: '¿Marcar como contratado?',

                text: 'Se registrará al candidato como contratado y se abrirá la lista de colaboradores.',

                icon: 'question',

                showCancelButton: true,

                confirmButtonText: 'Sí, contratar',

                cancelButtonText: 'Cancelar'

            }).then(function (result) {

                if (!result.isConfirmed) {

                    return;

                }

                $.ajax({

                    type: 'POST',

                    url: apiUrl,

                    data: {

                        candidate_id: cfg.candidateId,

                        status: 'Contratado',

                        observaciones: 'Contratación confirmada desde avance rápido.'

                    },

                    dataType: 'json'

                }).done(function (res) {

                    if (res.success) {

                        var dest = res.redirect_url || ('lista_colaboradores' + suffix + '.php');

                        Swal.fire('¡Contratado!', res.message, 'success').then(function () {

                            window.location.href = dest;

                        });

                    } else {

                        Swal.fire('Error', res.message || 'No se pudo actualizar', 'error');

                    }

                }).fail(function () {

                    Swal.fire('Error', 'Error de comunicación con el servidor', 'error');

                });

            });

        }



        function syncSelect2($sel) {

            if (typeof $.fn.select2 === 'function' && $sel.hasClass('select2')) {

                $sel.trigger('change.select2');

            }

        }



        $form.on('submit', function (e) {

            e.preventDefault();

            var status = ($form.find('[name="status"]').val() || '').trim();

            var obs = ($form.find('[name="observaciones"]').val() || '').trim();

            if (!status) {

                Swal.fire('Atención', 'Seleccione un estado.', 'warning');

                return;

            }



            $.ajax({

                type: 'POST',

                url: apiUrl,

                data: {

                    candidate_id: cfg.candidateId,

                    status: status,

                    observaciones: obs,

                },

                dataType: 'json',

            }).done(function (res) {

                if (res.success) {

                    if (res.redirect_url) {

                        Swal.fire('¡Actualizado!', res.message, 'success').then(function () {

                            window.location.href = res.redirect_url;

                        });

                    } else {

                        Swal.fire('¡Actualizado!', res.message, 'success').then(function () {

                            window.location.reload();

                        });

                    }

                } else {

                    Swal.fire('Error', res.message || 'No se pudo actualizar', 'error');

                }

            }).fail(function () {

                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');

            });

        });



        $('.rrhh-estado-rapido').on('click', function () {

            var status = $(this).data('status');

            var action = $(this).data('action');

            if (!status) {

                return;

            }



            if (action === 'formulario') {

                medidataAbrirFormularioEmpleado();

                return;

            }

            if (action === 'entrevista') {

                medidataIrEntrevista();

                return;

            }

            if (action === 'psicometricas') {

                medidataIrPsicometricas();

                return;

            }

            if (action === 'expediente') {

                medidataAbrirExpediente();

                return;

            }

            if (action === 'contratado') {

                medidataMarcarContratado();

                return;

            }



            $form.find('[name="status"]').val(status).trigger('change');

            syncSelect2($form.find('[name="status"]'));

        });

    });

})(jQuery);

