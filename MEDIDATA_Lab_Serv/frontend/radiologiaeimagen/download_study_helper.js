/**
 * Confirmación de descarga DICOM con peso estimado (fase 1 rendimiento MH-PACS).
 *
 * Uso:
 *   downloadStudyWithConfirm(studyId, { downloadUrl, openInNewTab, infoUrl })
 */
(function (global) {
    'use strict';

    function ensureSwal() {
        return typeof global.Swal !== 'undefined' && global.Swal && typeof global.Swal.fire === 'function';
    }

    function alertMsg(title, text, icon) {
        if (ensureSwal()) {
            return global.Swal.fire(title, text, icon || 'info');
        }
        window.alert(title + (text ? '\n\n' + text : ''));
        return Promise.resolve({ isConfirmed: true });
    }

    /**
     * @param {string} studyId
     * @param {{ downloadUrl?: string, openInNewTab?: boolean, infoUrl?: string }} [options]
     */
    async function downloadStudyWithConfirm(studyId, options) {
        options = options || {};
        if (!studyId || studyId === 'N/A') {
            await alertMsg('Información', 'No hay ID de estudio válido para descargar.', 'info');
            return;
        }

        var infoUrl = options.infoUrl || 'get_study_download_info.php';
        var downloadUrl = options.downloadUrl
            || ('https://medicloud.medicasa.hn/orthanc/studies/' + encodeURIComponent(studyId) + '/archive');
        var openInNewTab = options.openInNewTab !== false;

        if (ensureSwal()) {
            global.Swal.fire({
                title: 'Consultando tamaño…',
                text: 'Obteniendo peso del estudio antes de descargar.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () {
                    global.Swal.showLoading();
                }
            });
        }

        var info = null;
        try {
            var resp = await fetch(infoUrl + '?study_id=' + encodeURIComponent(studyId), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            info = await resp.json();
            if (!resp.ok || !info || !info.success) {
                throw new Error((info && info.error) || ('Error HTTP ' + resp.status));
            }
        } catch (err) {
            if (ensureSwal()) {
                var fail = await global.Swal.fire({
                    icon: 'warning',
                    title: 'No se pudo estimar el tamaño',
                    html: 'No fue posible consultar el peso del estudio'
                        + (err && err.message ? ' (' + String(err.message) + ')' : '')
                        + '.<br><br>¿Desea descargar de todos modos?',
                    showCancelButton: true,
                    confirmButtonText: 'Descargar',
                    cancelButtonText: 'Cancelar'
                });
                if (!fail.isConfirmed) {
                    return;
                }
                startDownload(downloadUrl, openInNewTab);
                return;
            }
            if (!window.confirm('No se pudo estimar el tamaño. ¿Descargar de todos modos?')) {
                return;
            }
            startDownload(downloadUrl, openInNewTab);
            return;
        }

        var sizeLabel = info.size_label || (info.size_mb ? (info.size_mb + ' MB') : 'desconocido');
        var remoteEta = info.eta_remote_label || 'variable';
        var lanEta = info.eta_lan_label || 'unos segundos';
        var series = info.count_series != null ? info.count_series : '—';
        var instances = info.count_instances != null ? info.count_instances : '—';
        var largeNote = info.is_large
            ? '<p style="margin-top:10px;color:#b45309;"><strong>Estudio grande:</strong> la descarga puede demorar o interrumpirse en conexiones lentas. Prefiera red local de la clínica si es posible.</p>'
            : '';

        var html = ''
            + '<div style="text-align:left;line-height:1.45;">'
            + '<p><strong>Peso aproximado:</strong> ' + sizeLabel + '</p>'
            + '<p><strong>Series / imágenes:</strong> ' + series + ' / ' + instances + '</p>'
            + '<p><strong>Tiempo orientativo (internet ~50 KB/s):</strong> ' + remoteEta + '</p>'
            + '<p><strong>En red local de la clínica (~8 MB/s):</strong> ' + lanEta + '</p>'
            + largeNote
            + '<p style="margin-top:12px;font-size:0.92em;opacity:0.85;">El ZIP se descarga directo desde Orthanc. No cierre el navegador hasta que termine.</p>'
            + '</div>';

        if (ensureSwal()) {
            var result = await global.Swal.fire({
                icon: info.is_large ? 'warning' : 'question',
                title: 'Descargar estudio DICOM',
                html: html,
                showCancelButton: true,
                confirmButtonText: 'Descargar ZIP',
                cancelButtonText: 'Cancelar',
                width: 520,
                heightAuto: true,
                scrollbarPadding: false,
                target: 'body'
            });
            if (!result.isConfirmed) {
                return;
            }
        } else {
            var plain = 'Peso aproximado: ' + sizeLabel
                + '\nTiempo orientativo (internet): ' + remoteEta
                + '\nEn red local: ' + lanEta
                + '\n\n¿Descargar ZIP?';
            if (!window.confirm(plain)) {
                return;
            }
        }

        startDownload(downloadUrl, openInNewTab);
    }

    function startDownload(url, openInNewTab) {
        if (openInNewTab) {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }

    global.downloadStudyWithConfirm = downloadStudyWithConfirm;
})(typeof window !== 'undefined' ? window : this);
