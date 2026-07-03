function obtenerFiltrosExportDiario() {
    var search = '';
    if (typeof tablaDiarioGeneral !== 'undefined' && tablaDiarioGeneral) {
        search = tablaDiarioGeneral.search() || '';
    }
    return {
        fechaDesde: ($('#fechaDesde').val() || '').trim(),
        fechaHasta: ($('#fechaHasta').val() || '').trim(),
        numeroPartida: ($('#numeroPartida').val() || '').trim(),
        cuenta: ($('#cuenta').val() || '').trim(),
        tipoTransaccion: ($('#filtroTipoTransaccion').val() || '').trim(),
        search: search.trim(),
    };
}

function exportarDiario(format) {
    var f = obtenerFiltrosExportDiario();
    var tieneFiltroFecha = f.fechaDesde !== '' || f.fechaHasta !== '';
    var tieneOtrosFiltros = f.numeroPartida !== '' || f.cuenta !== '' || f.tipoTransaccion !== '' || f.search !== '';

    if (tieneFiltroFecha && (!f.fechaDesde || !f.fechaHasta)) {
        alert('Para exportar un rango, indique fecha Desde y Hasta.');
        return;
    }

    if (!tieneFiltroFecha && !tieneOtrosFiltros) {
        if (!window.confirm('No hay filtros activos. ¿Exportar todos los registros del diario?')) {
            return;
        }
    }

    var params = new URLSearchParams();
    params.set('format', format);
    params.set('fechaDesde', f.fechaDesde);
    params.set('fechaHasta', f.fechaHasta);
    if (f.numeroPartida) {
        params.set('numeroPartida', f.numeroPartida);
    }
    if (f.cuenta) {
        params.set('cuenta', f.cuenta);
    }
    if (f.tipoTransaccion) {
        params.set('tipoTransaccion', f.tipoTransaccion);
    }
    if (f.search) {
        params.set('search', f.search);
    }
    params.set('_ts', String(Date.now()));

    var url = 'get_diariogeneral_export.php?' + params.toString();
    var fetchOpts = { credentials: 'same-origin', cache: 'no-store' };

    if (format === 'print') {
        window.open(url, '_blank', 'width=1200,height=800');
        return;
    }

    if (format === 'copy') {
        fetch(url, fetchOpts).then(function (r) { return r.text(); }).then(function (text) {
            navigator.clipboard.writeText(text).then(function () {
                alert('Datos copiados al portapapeles (' + text.split('\n').length + ' filas)');
            }).catch(function () {
                alert('No se pudo copiar. Use CSV para descargar.');
            });
        }).catch(function () {
            alert('Error al obtener los datos.');
        });
        return;
    }

    if (format === 'csv' || format === 'excel') {
        var ext = 'csv';
        var periodo = (f.fechaDesde && f.fechaHasta) ? (f.fechaDesde + '_' + f.fechaHasta) : 'completo';
        var filename = 'diario_general_' + periodo + '.' + ext;
        fetch(url, fetchOpts).then(function (r) {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status);
            }
            return r.blob();
        }).then(function (blob) {
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
        }).catch(function () {
            alert('Error al descargar. Verifique filtros e intente de nuevo.');
        });
        return;
    }

    window.location.href = url;
}
