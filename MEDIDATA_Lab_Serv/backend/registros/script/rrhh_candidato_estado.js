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
                    Swal.fire('¡Actualizado!', res.message, 'success').then(function () {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message || 'No se pudo actualizar', 'error');
                }
            }).fail(function () {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            });
        });

        $('.rrhh-estado-rapido').on('click', function () {
            var status = $(this).data('status');
            if (!status) {
                return;
            }
            $form.find('[name="status"]').val(status).trigger('change');
            if (typeof $.fn.select2 === 'function' && $form.find('[name="status"]').hasClass('select2')) {
                $form.find('[name="status"]').trigger('change.select2');
            }
        });
    });
})(jQuery);
