/**
 * Lista de trabajo MH-PACS: paginación y filtros en servidor.
 */
(function (global) {
    'use strict';

    let currentPage = 1;
    let totalPages = 1;
    let totalRecords = 0;
    let currentPageStudies = [];
    let worklistLoading = false;
    let searchDebounceTimer = null;

    const studiesPerPage = 10;

    function getFiltersPayload(page) {
        return {
            page: page || currentPage,
            limit: studiesPerPage,
            modality: document.getElementById('modalityFilter')?.value || '',
            priority: document.getElementById('priorityFilter')?.value || '',
            status: document.getElementById('statusFilter')?.value || '',
            date: document.getElementById('dateFilter')?.value || '',
            search: (document.getElementById('searchBar')?.value || '').trim(),
        };
    }

    function formatAvgTime(minutes) {
        if (!minutes || isNaN(minutes)) return '0 min';
        minutes = Math.round(minutes);
        if (minutes < 60) return `${minutes} min`;
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return m > 0 ? `${h} h ${m} min` : `${h} h`;
    }

    async function loadStats() {
        const response = await fetch('get_technician_stats.php', { cache: 'no-store' });
        const data = await response.json();
        if (data.error) throw new Error(data.error);

        document.getElementById('pending-count').textContent = data.pending;
        document.getElementById('completed-today').textContent = data.completed_today;
        document.getElementById('completed-global').textContent = data.completed_global;
        document.getElementById('in-progress-count').textContent = data.in_progress;
        document.getElementById('cancelled-today').textContent = data.cancelled_today;
        document.getElementById('avg-time').textContent = formatAvgTime(data.avg_time);
        document.getElementById('quality-percentage').textContent = `${data.quality}%`;
    }

    function setTableLoading(isLoading) {
        const tbody = document.getElementById('worklistBody');
        if (!tbody) return;
        if (isLoading) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;">Cargando estudios...</td></tr>';
        }
    }

    function renderTableRows(studies) {
        const tbody = document.getElementById('worklistBody');
        if (!tbody) return;

        tbody.innerHTML = '';
        if (!studies || studies.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;">No se encontraron estudios con los filtros seleccionados</td></tr>';
            return;
        }

        studies.forEach((study) => {
            const row = document.createElement('tr');
            const seriesId = study.series_id || '';
            const studyId = study.id;
            const status = study.status || 'pending';

            row.innerHTML = `
                <td>${study.patient_id || 'N/A'}</td>
                <td>${study.patient_name || 'N/A'}</td>
                <td>${study.Modality || study.modality || 'N/A'}</td>
                <td>${study.description || 'Sin descripción'}</td>
                <td>${typeof formatStudyDate === 'function' ? formatStudyDate(study.study_date) : (study.study_date || 'N/A')}</td>
                <td>${typeof formatPriority === 'function' ? formatPriority(study.priority) : (study.priority || '')}</td>
                <td>${typeof formatStatus === 'function' ? formatStatus(study.status) : (study.status || '')}</td>
                <td>
                    <div class="action-buttons">
                        <button type="button" onclick="openDicomViewer('${seriesId}')" class="btn-view"><i class='bx bx-show'></i> Ver</button>
                        <button type="button" onclick="openQualityControl('${studyId}')" class="btn-quality"><i class='bx bx-check-circle'></i> Control</button>
                        <button type="button" onclick="openIncident('${studyId}')" class="btn-incident"><i class='bx bx-error'></i> Incidencia</button>
                        <button type="button" onclick="openRepeat('${studyId}')" class="btn-repeat"><i class='bx bx-refresh'></i> Repetir</button>
                        <button type="button" onclick="openDoseModal('${studyId}')" class="dose-btn"><i class='bx bx-radiation'></i> Dosis</button>
                        <button type="button" onclick="showAssignmentInfo('${studyId}')" class="btn-assignment"><i class='bx bx-user-check'></i> Asignado</button>
                        <button type="button" onclick="${status === 'cancelled' ? `showCancelDetail('${studyId}')` : `openCancelModal('${studyId}', '${status}')`}" class="btn-cancel"><i class='bx bx-x-circle'></i> ${status === 'cancelled' ? 'Ver motivo' : 'Cancelar'}</button>
                    </div>
                </td>
            `;
            row.dataset.studyId = studyId;
            row.dataset.status = status;
            row.dataset.seriesId = seriesId;
            tbody.appendChild(row);
            if (typeof updateActionButtons === 'function') {
                updateActionButtons(row);
            }
        });
    }

    function updatePaginationUi() {
        let paginationDiv = document.getElementById('pagination');
        if (!paginationDiv) {
            paginationDiv = document.createElement('div');
            paginationDiv.id = 'pagination';
            paginationDiv.className = 'pagination';
            document.querySelector('.table-container')?.appendChild(paginationDiv);
        }
        paginationDiv.innerHTML = '';

        const info = document.createElement('span');
        info.className = 'pagination-info';
        info.style.marginRight = '12px';
        info.textContent = totalRecords > 0
            ? `Mostrando página ${currentPage} de ${totalPages} (${totalRecords.toLocaleString()} estudios)`
            : 'Sin resultados';
        paginationDiv.appendChild(info);

        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        const prevButton = document.createElement('button');
        prevButton.type = 'button';
        prevButton.textContent = 'Anterior';
        prevButton.disabled = currentPage <= 1 || worklistLoading;
        prevButton.onclick = () => {
            if (currentPage > 1) loadWorklist(currentPage - 1);
        };
        paginationDiv.appendChild(prevButton);

        for (let i = startPage; i <= endPage; i++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = String(i);
            button.classList.toggle('active', i === currentPage);
            button.disabled = worklistLoading;
            button.onclick = () => loadWorklist(i);
            paginationDiv.appendChild(button);
        }

        const nextButton = document.createElement('button');
        nextButton.type = 'button';
        nextButton.textContent = 'Siguiente';
        nextButton.disabled = currentPage >= totalPages || worklistLoading;
        nextButton.onclick = () => {
            if (currentPage < totalPages) loadWorklist(currentPage + 1);
        };
        paginationDiv.appendChild(nextButton);
    }

    async function loadWorklist(page) {
        if (worklistLoading) return;
        worklistLoading = true;
        currentPage = page || 1;
        setTableLoading(true);

        try {
            const response = await fetch('get_worklist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                cache: 'no-store',
                body: JSON.stringify(getFiltersPayload(currentPage)),
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || `HTTP ${response.status}`);
            }

            currentPageStudies = data.data || [];
            totalRecords = data.total || 0;
            totalPages = data.totalPages || 1;
            currentPage = data.page || currentPage;

            renderTableRows(currentPageStudies);
            updatePaginationUi();
        } catch (error) {
            console.error('Error loading worklist:', error);
            if (typeof swal === 'function') {
                Swal.fire('Error', 'No se pudo cargar la lista de trabajo', 'error');
            }
            const tbody = document.getElementById('worklistBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;">Error al cargar datos.</td></tr>';
            }
        } finally {
            worklistLoading = false;
        }
    }

    function applyFilters() {
        loadWorklist(1);
    }

    function onSearchInput() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => loadWorklist(1), 400);
    }

    async function reloadWorklistPage() {
        await loadWorklist(currentPage);
    }

    async function refreshWorklistAndStats() {
        await Promise.all([loadStats(), reloadWorklistPage()]);
    }

    function getWorklistStudy(studyId) {
        return currentPageStudies.find((s) => String(s.id) === String(studyId));
    }

    function init(rolUsuario) {
        if (rolUsuario === 'Radiologo') {
            document.querySelector('.table-container').style.display = 'none';
            const pag = document.getElementById('pagination');
            if (pag) pag.style.display = 'none';
            document.querySelector('.filters').style.display = 'none';
            document.getElementById('noWorklistMsg').style.display = 'block';
            document.getElementById('noWorklistText').textContent =
                'No tienes permisos para ver este apartado. Solo los técnicos radiólogos pueden acceder.';
            return;
        }

        const searchBar = document.getElementById('searchBar');
        if (searchBar) {
            searchBar.removeAttribute('oninput');
            searchBar.addEventListener('input', onSearchInput);
        }

        loadStats().catch((err) => {
            console.error(err);
            if (typeof swal === 'function') Swal.fire('Error', 'No se pudieron cargar las estadísticas', 'error');
        });
        loadWorklist(1);
    }

    global.WorklistCore = {
        init,
        loadWorklist,
        loadStats,
        applyFilters,
        reloadWorklistPage,
        refreshWorklistAndStats,
        getWorklistStudy,
    };

    global.applyFilters = applyFilters;
    global.loadWorklist = reloadWorklistPage;
    global.loadStats = loadStats;
    global.refreshWorklistAndStats = refreshWorklistAndStats;
    global.getWorklistStudy = getWorklistStudy;
})(window);
