/**
 * Lista de estudios MH-PACS — filtros servidor + paginación.
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
    let onEmptyFn = null;

    function parseResponse(data) {
        if (Array.isArray(data)) {
            return { success: true, data: data, total: data.length, page: 1, totalPages: 1 };
        }
        return {
            success: data.success !== false,
            data: data.data || [],
            total: data.total || 0,
            page: data.page || 1,
            totalPages: data.totalPages || 1,
            error: data.error || '',
        };
    }

    async function loadStudies(page) {
        if (loading || typeof canLoadFn === 'function' && !canLoadFn()) {
            return;
        }
        loading = true;
        currentPage = page || 1;

        const tbody = document.getElementById('studiesBody');
        if (tbody) {
            tbody.innerHTML =
                '<tr><td colspan="7" style="text-align:center;padding:20px;">Cargando estudios...</td></tr>';
        }

        const payload =
            typeof MhpacsFilters !== 'undefined'
                ? MhpacsFilters.getPayload({ page: currentPage, limit: perPage })
                : { page: currentPage, limit: perPage };

        try {
            const response = await fetch('get_completed_studies.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                cache: 'no-store',
                body: JSON.stringify(payload),
            });
            const raw = await response.json();
            const result = parseResponse(raw);

            if (!tbody) return;

            tbody.innerHTML = '';
            const noMsg = document.getElementById('noStudiesMsg');
            const noText = document.getElementById('noStudiesText');

            if (!result.success) {
                if (noMsg && noText) {
                    noMsg.style.display = 'block';
                    noText.textContent = result.error || 'Error al cargar estudios.';
                }
                return;
            }

            totalRecords = result.total;
            totalPages = result.totalPages;
            currentPage = result.page;

            if (!result.data.length) {
                if (noMsg && noText && typeof onEmptyFn === 'function') {
                    noMsg.style.display = 'block';
                    noText.textContent = onEmptyFn();
                } else if (noMsg) {
                    noMsg.style.display = 'block';
                }
            } else {
                if (noMsg) noMsg.style.display = 'none';
                result.data.forEach(function (study) {
                    const row = document.createElement('tr');
                    row.innerHTML =
                        typeof renderRowFn === 'function'
                            ? renderRowFn(study)
                            : '<td colspan="7">Sin render</td>';
                    tbody.appendChild(row);
                });
            }

        } catch (e) {
            console.error('StudiesListCore:', e);
            if (tbody) {
                tbody.innerHTML =
                    '<tr><td colspan="7" style="text-align:center;padding:20px;color:#c00;">Error al cargar datos.</td></tr>';
            }
        } finally {
            // Pasar isLoading=false: si se renderiza con loading=true los botones quedan bloqueados.
            loading = false;
            if (typeof MhpacsFilters !== 'undefined') {
                MhpacsFilters.renderPagination(
                    'studiesPagination',
                    { total: totalRecords, page: currentPage, totalPages: totalPages },
                    loadStudies,
                    false
                );
            }
        }
    }

    function init(config) {
        renderRowFn = config.renderRow;
        canLoadFn = config.canLoad;
        onEmptyFn = config.onEmpty;

        global.applyFilters = function () {
            loadStudies(1);
        };

        if (typeof MhpacsFilters !== 'undefined') {
            MhpacsFilters.bindSearch(function () {
                loadStudies(1);
            });
            MhpacsFilters.bindClear(
                ['searchBar', 'modalityFilter', 'statusFilter', 'dateFromFilter', 'dateToFilter'],
                function () {
                    loadStudies(1);
                }
            );
        }

        if (!canLoadFn || canLoadFn()) {
            loadStudies(1);
        }
    }

    global.StudiesListCore = {
        init: init,
        reload: function () {
            return loadStudies(currentPage);
        },
        load: loadStudies,
    };
})(window);
