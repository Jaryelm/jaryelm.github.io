(function ($) {
    'use strict';

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

    function medidataMostrarEnlaceExpediente(url, email, estado) {
        var faltantes = (estado && estado.missing) ? estado.missing.length : 0;
        var html = '<p style="text-align:left;margin:0 0 10px;">Enlace para que el candidato suba documentos (solo PDF):</p>'
            + '<input id="swal-exp-url" type="text" readonly class="swal2-input" style="width:100%;font-size:13px;" value="' + $('<div>').text(url).html() + '">'
            + '<p style="text-align:left;font-size:13px;color:#555;margin:8px 0 0;">Documentos faltantes: <strong>' + faltantes + '</strong></p>'
            + (email ? '<p style="text-align:left;font-size:13px;color:#555;">Correo: <strong>' + $('<div>').text(email).html() + '</strong></p>' : '<p style="text-align:left;font-size:13px;color:#856404;">Sin correo válido — copie el enlace manualmente.</p>');

        Swal.fire({
            title: 'Expediente de contratación',
            html: html,
            width: 620,
            showCancelButton: true,
            showDenyButton: !!email,
            confirmButtonText: 'Copiar enlace',
            denyButtonText: 'Enviar por correo',
            cancelButtonText: 'Cerrar',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                medidataCopiarTexto(url).then(function () {
                    Swal.fire({ icon: 'success', title: 'Copiado', timer: 1200, showConfirmButton: false });
                });
            } else if (result.isDenied) {
                $.ajax({
                    type: 'POST',
                    url: window.MEDIDATA_EXPEDIENTE.apiUrl,
                    data: { candidate_id: window.MEDIDATA_EXPEDIENTE.candidateId, action: 'send_email' },
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
            }
        });
    }

    $(function () {
        var cfg = window.MEDIDATA_EXPEDIENTE || {};
        $('#btn-expediente-link').on('click', function () {
            $.ajax({
                type: 'POST',
                url: cfg.apiUrl,
                data: { candidate_id: cfg.candidateId, action: 'link' },
                dataType: 'json'
            }).done(function (res) {
                if (res.success && res.url) {
                    medidataMostrarEnlaceExpediente(res.url, res.email || cfg.candidateEmail || '', res.estado);
                } else {
                    Swal.fire('Error', res.message || 'No se pudo generar el enlace.', 'error');
                }
            }).fail(function () {
                Swal.fire('Error', 'Error de comunicación.', 'error');
            });
        });
    });
})(jQuery);
