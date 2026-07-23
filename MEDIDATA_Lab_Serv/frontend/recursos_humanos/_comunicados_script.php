<script>
(function ($) {
    'use strict';

    var manualEmails = {};

    function esc(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function selectedUserIds() {
        var ids = [];
        $('.rrhh-com-user-cb:checked').each(function () {
            ids.push(parseInt($(this).val(), 10));
        });
        return ids;
    }

    function updateCounts() {
        var nUsers = selectedUserIds().length;
        var nManual = Object.keys(manualEmails).length;
        $('#rrhhComSelectedCount').text(nUsers);
        $('#rrhhComReadyCount').text((nUsers + nManual) + ' destinatario(s) listos (pueden deduplicarse al enviar)');
    }

    function renderChips() {
        var $box = $('#rrhhComManualChips').empty();
        var keys = Object.keys(manualEmails).sort();
        if (!keys.length) {
            $box.append('<p class="rrhh-comunicados-empty">Ningún correo manual agregado.</p>');
            updateCounts();
            return;
        }
        keys.forEach(function (email) {
            var $chip = $('<span class="rrhh-comunicados-chip"/>')
                .append($('<span/>').text(email))
                .append(
                    $('<button type="button" aria-label="Quitar"/>').text('×').on('click', function () {
                        delete manualEmails[email];
                        renderChips();
                    })
                );
            $box.append($chip);
        });
        updateCounts();
    }

    function addManualEmail(raw) {
        var email = String(raw || '').trim().toLowerCase();
        if (!email) return false;
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!re.test(email)) {
            Swal.fire({ icon: 'warning', title: 'Correo no válido', text: email });
            return false;
        }
        manualEmails[email] = true;
        return true;
    }

    $('#rrhhComUserFilter').on('input', function () {
        var q = String($(this).val() || '').toLowerCase().trim();
        $('#rrhhComUserList .rrhh-comunicados-user').each(function () {
            var hay = String($(this).data('search') || '');
            $(this).toggle(!q || hay.indexOf(q) !== -1);
        });
    });

    $('#rrhhComSelectAll').on('click', function () {
        $('.rrhh-com-user-cb:not(:disabled)').prop('checked', true);
        updateCounts();
    });

    $('#rrhhComClearUsers').on('click', function () {
        $('.rrhh-com-user-cb').prop('checked', false);
        updateCounts();
    });

    $(document).on('change', '.rrhh-com-user-cb', updateCounts);

    $('#rrhhComManualAdd').on('click', function () {
        if (addManualEmail($('#rrhhComManualInput').val())) {
            $('#rrhhComManualInput').val('');
            renderChips();
        }
    });

    $('#rrhhComManualInput').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#rrhhComManualAdd').click();
        }
    });

    $('#rrhhComManualImport').on('click', function () {
        var raw = String($('#rrhhComManualBulk').val() || '');
        var parts = raw.split(/[\s,;]+/);
        var added = 0;
        parts.forEach(function (p) {
            if (addManualEmail(p)) added++;
        });
        if (added) {
            $('#rrhhComManualBulk').val('');
            renderChips();
        } else {
            Swal.fire({ icon: 'info', title: 'Sin correos válidos', text: 'Revise el formato de los correos.' });
        }
    });

    $('#rrhhComunicadosForm').on('submit', function (e) {
        e.preventDefault();
        var userIds = selectedUserIds();
        var manuals = Object.keys(manualEmails);
        var subject = String($('#rrhhComSubject').val() || '').trim();
        var message = String($('#rrhhComMessage').val() || '').trim();

        if (!subject || !message) {
            Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Asunto y mensaje son obligatorios.' });
            return;
        }
        if (!userIds.length && !manuals.length) {
            Swal.fire({ icon: 'warning', title: 'Sin destinatarios', text: 'Seleccione usuarios o agregue correos manuales.' });
            return;
        }

        var totalApprox = userIds.length + manuals.length;
        Swal.fire({
            title: '¿Enviar comunicado?',
            html: 'Se enviará a aproximadamente <strong>' + totalApprox + '</strong> destinatario(s).<br>Los correos duplicados se unifican automáticamente.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#035c67'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Enviando…',
                html: 'Por favor espere. El envío puede tardar según la cantidad de correos.',
                allowOutsideClick: false,
                didOpen: function () { Swal.showLoading(); }
            });

            $.ajax({
                url: '../../backend/php/rrhh_comunicados_enviar.php',
                method: 'POST',
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                data: JSON.stringify({
                    subject: subject,
                    message: message,
                    user_ids: userIds,
                    manual_emails: manuals
                })
            }).done(function (res) {
                if (!res) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Respuesta vacía del servidor.' });
                    return;
                }
                var details = Array.isArray(res.details) ? res.details : [];
                var failList = details.filter(function (d) { return !d.ok; })
                    .slice(0, 8)
                    .map(function (d) { return esc(d.email) + ': ' + esc(d.message); })
                    .join('<br>');
                var skipped = Array.isArray(res.skipped_no_email) ? res.skipped_no_email : [];
                var skipHtml = skipped.length
                    ? '<p style="margin-top:10px;"><strong>Sin correo en sistema:</strong> '
                        + skipped.slice(0, 6).map(function (s) { return esc(s.name); }).join(', ')
                        + (skipped.length > 6 ? '…' : '') + '</p>'
                    : '';
                var invalid = Array.isArray(res.invalid_manual) ? res.invalid_manual : [];
                var invalidHtml = invalid.length
                    ? '<p style="margin-top:8px;"><strong>Omitidos (formato inválido):</strong> ' + esc(invalid.join(', ')) + '</p>'
                    : '';

                Swal.fire({
                    icon: res.failed === 0 ? 'success' : (res.sent > 0 ? 'warning' : 'error'),
                    title: res.failed === 0 ? 'Envío completado' : 'Envío con observaciones',
                    html: '<p>' + esc(res.message || '') + '</p>'
                        + (failList ? '<p style="margin-top:10px;text-align:left;font-size:13px;">' + failList + '</p>' : '')
                        + skipHtml
                        + invalidHtml
                });
            }).fail(function (xhr) {
                var msg = 'No se pudo completar el envío.';
                try {
                    var j = JSON.parse(xhr.responseText || '{}');
                    if (j && j.message) msg = j.message;
                } catch (err) {}
                Swal.fire({ icon: 'error', title: 'Error de comunicación', text: msg });
            });
        });
    });

    renderChips();
    updateCounts();
})(jQuery);
</script>
