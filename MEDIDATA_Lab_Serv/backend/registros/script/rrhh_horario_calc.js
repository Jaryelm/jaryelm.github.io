(function ($) {
    'use strict';

    function timeToMinutes(value) {
        if (!value) {
            return 0;
        }
        var parts = String(value).substring(0, 5).split(':');
        return (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
    }

    function formatHours(hours) {
        var rounded = Math.round(hours * 100) / 100;
        if (Math.abs(rounded - Math.round(rounded)) < 0.001) {
            return Math.round(rounded) + ' hrs';
        }
        return rounded.toFixed(1) + ' hrs';
    }

    function formatMinutes(mins) {
        var h = Math.floor(mins / 60);
        var m = mins % 60;
        if (h > 0 && m > 0) {
            return h + ' h ' + m + ' min';
        }
        if (h > 0) {
            return h + ' h';
        }
        return m + ' min';
    }

    function syncBreakCheckboxes($row, dayActive) {
        var breakPerDay = parseInt($('#break_minutes').val(), 10) || 0;
        var $break = $row.find('.day-break-check');
        if (!dayActive || breakPerDay === 0) {
            $break.prop('disabled', true);
            return;
        }
        $break.prop('disabled', false);
    }

    function shiftMinutes(entry, exit) {
        if (entry === exit) {
            return 0;
        }
        if (exit > entry) {
            return exit - entry;
        }
        // Turno nocturno (cruza medianoche)
        return (1440 - entry) + exit;
    }

    function calcWeekly() {
        var breakPerDay = parseInt($('#break_minutes').val(), 10) || 0;
        var gross = 0;
        var activeDays = 0;
        var breakTotal = 0;

        $('.day-check:checked').each(function () {
            var $row = $(this).closest('tr');
            var entry = timeToMinutes($row.find('input[name*="[entry_time]"]').val());
            var exit = timeToMinutes($row.find('input[name*="[exit_time]"]').val());
            var shift = shiftMinutes(entry, exit);
            if (shift > 0) {
                gross += shift;
                activeDays++;
                if (breakPerDay > 0 && $row.find('.day-break-check').is(':checked')) {
                    breakTotal += breakPerDay;
                }
            }
        });

        var effective = Math.max(0, gross - breakTotal);

        $('#horario-gross').text(formatHours(gross / 60));
        $('#horario-break-total').text(breakPerDay === 0 ? 'No aplica' : formatMinutes(breakTotal));
        $('#horario-effective').text(formatHours(effective / 60));
        $('#horario-active-days').text(activeDays);
        $('#weekly_effective_hours').val((Math.round((effective / 60) * 100) / 100).toFixed(2));
    }

    $(function () {
        function refreshRowState($row) {
            var dayActive = $row.find('.day-check').is(':checked');
            $row.find('input[type="time"]').prop('disabled', !dayActive);
            syncBreakCheckboxes($row, dayActive);
        }

        $('.day-check').on('change', function () {
            var $row = $(this).closest('tr');
            if ($(this).is(':checked') && ! $row.find('.day-break-check').prop('disabled')) {
                $row.find('.day-break-check').prop('checked', true);
            }
            refreshRowState($row);
            calcWeekly();
        });

        $('.day-break-check').on('change', calcWeekly);

        $('#break_minutes').on('change', function () {
            $('tbody tr').each(function () {
                refreshRowState($(this));
            });
            calcWeekly();
        });

        $('input[type="time"]').on('change input', calcWeekly);

        $('tbody tr').each(function () {
            refreshRowState($(this));
        });
        calcWeekly();

        var cfg = window.MEDIDATA_HORARIO_CFG || {};
        var $form = $('#scheduleForm');

        $form.on('submit', function (e) {
            e.preventDefault();
            calcWeekly();

            if ($('.day-check:checked').length === 0) {
                Swal.fire('Aviso', 'Debe activar al menos un día para este horario.', 'warning');
                return;
            }

            var $btn = $('#btnGuardarHorario');
            var btnText = cfg.btnText || 'Guardar Horario';
            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                type: 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                dataType: 'json'
            }).done(function (response) {
                if (response.success) {
                    Swal.fire('¡Logrado!', response.message, 'success').then(function () {
                        window.location = cfg.listUrl || 'horarios_usr.php';
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                    $btn.prop('disabled', false).text(btnText);
                }
            }).fail(function () {
                Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
                $btn.prop('disabled', false).text(btnText);
            });
        });
    });
})(jQuery);
