/**
 * Lista de transcripciones MH-PACS — filtros servidor + paginación.
 */
(function (global) {
    'use strict';

    let currentPage = 1;
    let totalPages = 1;
    let totalRecords = 0;
    let loading = false;
    const perPage = 10;
    let renderRowFn = null;
    let canLoadFn = null;

    function parseResponse(data) {
        if (Array.isArray(data)) {
            return { success: true, data: data, total: data.length, page: 1, totalPages: 1 };
        }
        return {
            success: data.success !== false && !data.error,
            data: data.data || [],
            total: data.total || 0,
            page: data.page || 1,
            totalPages: data.totalPages || 1,
            error: data.error || '',
        };
    }

    async function loadTranscriptions(page) {
        if (loading || (typeof canLoadFn === 'function' && !canLoadFn())) {
            return;
        }
        loading = true;
        currentPage = page || 1;

        const tbody = document.getElementById('transcriptionsBody');
        if (tbody) {
            tbody.innerHTML =
                '<tr><td colspan="8" style="text-align:center;padding:20px;">Cargando transcripciones...</td></tr>';
        }

        const payload =
            typeof MhpacsFilters !== 'undefined'
                ? MhpacsFilters.getPayload({ page: currentPage, limit: perPage })
                : { page: currentPage, limit: perPage };

        try {
            const response = await fetch('get_transcriptions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                cache: 'no-store',
                body: JSON.stringify(payload),
            });
            const raw = await response.json();
            const result = parseResponse(raw);

            if (!tbody) return;
            tbody.innerHTML = '';

            if (!result.success) {
                tbody.innerHTML =
                    '<tr><td colspan="8" style="text-align:center;color:red;padding:20px;">' +
                    (result.error || 'Error al cargar transcripciones') +
                    '</td></tr>';
                return;
            }

            totalRecords = result.total;
            totalPages = result.totalPages;
            currentPage = result.page;

            if (!result.data.length) {
                tbody.innerHTML =
                    '<tr><td colspan="8" style="text-align:center;padding:20px;">No hay transcripciones con los filtros seleccionados.</td></tr>';
            } else {
                result.data.forEach(function (row) {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        typeof renderRowFn === 'function'
                            ? renderRowFn(row)
                            : '<td colspan="8">Sin render</td>';
                    tbody.appendChild(tr);
                });
            }

        } catch (e) {
            console.error('TranscriptionsListCore:', e);
            if (tbody) {
                tbody.innerHTML =
                    '<tr><td colspan="8" style="text-align:center;color:red;padding:20px;">Error de comunicación.</td></tr>';
            }
        } finally {
            // Reactivar botones DESPUÉS de liberar loading; si se pinta con true quedan bloqueados.
            loading = false;
            if (typeof MhpacsFilters !== 'undefined') {
                MhpacsFilters.renderPagination(
                    'transcriptionsPagination',
                    { total: totalRecords, page: currentPage, totalPages: totalPages },
                    loadTranscriptions,
                    false
                );
            }
        }
    }

    function init(config) {
        renderRowFn = config.renderRow;
        canLoadFn = config.canLoad;

        global.applyFilters = function () {
            loadTranscriptions(1);
        };

        if (typeof MhpacsFilters !== 'undefined') {
            MhpacsFilters.bindSearch(function () {
                loadTranscriptions(1);
            });
            MhpacsFilters.bindClear(
                [
                    'searchBar',
                    'modalityFilter',
                    'priorityFilter',
                    'statusFilter',
                    'dateFromFilter',
                    'dateToFilter',
                ],
                function () {
                    loadTranscriptions(1);
                }
            );
        }

        if (!canLoadFn || canLoadFn()) {
            loadTranscriptions(1);
        }
    }

    global.TranscriptionsListCore = {
        init: init,
        reload: function () {
            return loadTranscriptions(currentPage);
        },
        load: loadTranscriptions,
    };

    global.loadTranscriptions = function (page) {
        return loadTranscriptions(page || currentPage);
    };
})(window);
