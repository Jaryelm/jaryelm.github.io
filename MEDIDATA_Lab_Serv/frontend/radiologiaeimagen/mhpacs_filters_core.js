/**
 * Filtros compartidos MH-PACS: payload, paginación, búsqueda con debounce.
 */
(function (global) {
    'use strict';

    function el(id) {
        return document.getElementById(id);
    }

    function val(id) {
        const node = el(id);
        return node ? String(node.value || '').trim() : '';
    }

    function getPayload(extra) {
        const dateFrom = val('dateFromFilter') || val('dateFilter');
        const dateTo = val('dateToFilter') || val('dateFilter');
        const payload = {
            search: val('searchBar'),
            modality: val('modalityFilter'),
            priority: val('priorityFilter'),
            status: val('statusFilter'),
            radiologist_id: val('radiologistFilter'),
            date_from: dateFrom,
            date_to: dateTo,
            date: dateFrom,
        };
        if (extra && typeof extra === 'object') {
            Object.assign(payload, extra);
        }
        return payload;
    }

    function clearFilters(fieldIds) {
        (fieldIds || []).forEach(function (id) {
            const node = el(id);
            if (!node) return;
            if (node.tagName === 'SELECT') {
                node.selectedIndex = 0;
            } else {
                node.value = '';
            }
        });
    }

    function renderPagination(containerId, meta, onPage, isLoading) {
        const container = el(containerId);
        if (!container) return;

        const total = meta.total || 0;
        const page = meta.page || 1;
        const totalPages = meta.totalPages || 1;

        container.innerHTML = '';
        container.className = 'mhpacs-pagination';

        const info = document.createElement('span');
        info.className = 'mhpacs-pagination__info';
        info.textContent =
            total > 0
                ? 'Página ' + page + ' de ' + totalPages + ' (' + total.toLocaleString() + ' registros)'
                : 'Sin resultados';
        container.appendChild(info);

        const prev = document.createElement('button');
        prev.type = 'button';
        prev.textContent = 'Anterior';
        prev.disabled = page <= 1 || isLoading;
        prev.onclick = function () {
            if (page > 1) onPage(page - 1);
        };
        container.appendChild(prev);

        const maxVisible = 5;
        let start = Math.max(1, page - Math.floor(maxVisible / 2));
        let end = Math.min(totalPages, start + maxVisible - 1);
        if (end - start + 1 < maxVisible) {
            start = Math.max(1, end - maxVisible + 1);
        }

        for (let i = start; i <= end; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = String(i);
            btn.classList.toggle('active', i === page);
            btn.disabled = !!isLoading;
            (function (p) {
                btn.onclick = function () {
                    onPage(p);
                };
            })(i);
            container.appendChild(btn);
        }

        const next = document.createElement('button');
        next.type = 'button';
        next.textContent = 'Siguiente';
        next.disabled = page >= totalPages || isLoading;
        next.onclick = function () {
            if (page < totalPages) onPage(page + 1);
        };
        container.appendChild(next);
    }

    function bindSearch(callback, delayMs) {
        const searchBar = el('searchBar');
        if (!searchBar || typeof callback !== 'function') return;
        let timer = null;
        searchBar.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(callback, delayMs || 400);
        });
        searchBar.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(timer);
                callback();
            }
        });
    }

    function bindClear(clearIds, reloadFn) {
        const btn = el('clearFiltersBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            clearFilters(clearIds);
            if (typeof reloadFn === 'function') reloadFn();
        });
    }

    global.MhpacsFilters = {
        getPayload: getPayload,
        clearFilters: clearFilters,
        renderPagination: renderPagination,
        bindSearch: bindSearch,
        bindClear: bindClear,
    };
})(window);
