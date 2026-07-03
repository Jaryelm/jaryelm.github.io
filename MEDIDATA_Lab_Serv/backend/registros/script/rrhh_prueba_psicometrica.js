(function ($) {
    'use strict';

    $(function () {
        var cfg = window.MEDIDATA_PSICO || {};
        var $form = $('#rrhh-psico-form');
        if (!$form.length || !cfg.saveUrl) {
            return;
        }

        $form.on('submit', function (e) {
            e.preventDefault();
            var fd = new FormData($form[0]);

            $.ajax({
                url: cfg.saveUrl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (res) {
                if (res.success) {
                    Swal.fire('Guardado', res.message, 'success').then(function () {
                        if (cfg.volverUrl) {
                            window.location.href = cfg.volverUrl;
                        }
                    });
                } else {
                    Swal.fire('Aviso', res.message || 'No se pudo guardar.', 'warning');
                }
            }).fail(function () {
                Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
            });
        });
    });
})(jQuery);
