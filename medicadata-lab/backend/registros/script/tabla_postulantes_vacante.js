document.addEventListener('DOMContentLoaded', function() {
    const ROWS_PER_PAGE = 8;
    const gridContainer = document.getElementById('postulantes-grid');
    const idVacante = document.getElementById('id_vacante_hidden').value;
    const paginationEl = document.getElementById('postulantes-pagination');
    const prevBtn = paginationEl ? paginationEl.querySelector('.rrhh-page-prev') : null;
    const nextBtn = paginationEl ? paginationEl.querySelector('.rrhh-page-next') : null;
    const pageInfoEl = paginationEl ? paginationEl.querySelector('.rrhh-page-info') : null;

    let currentPage = 1;
    let allPostulantes = [];
    let activeSearchTerm = '';

    function statusBadgeClass(status) {
        var value = String(status || '').trim();
        if (value === 'Contratado') return 'candidate-status-badge--contratado';
        if (value === 'Descartado') return 'candidate-status-badge--descartado';
        if (value === 'En Espera') return 'candidate-status-badge--espera';
        if (value === 'Entrevista' || value === 'Agendado' || value === 'Pruebas Psicometricas') {
            return 'candidate-status-badge--proceso';
        }
        return 'candidate-status-badge--default';
    }

    function getTotalPages() {
        return Math.max(1, Math.ceil(allPostulantes.length / ROWS_PER_PAGE));
    }

    function getPageData() {
        const start = (currentPage - 1) * ROWS_PER_PAGE;
        return allPostulantes.slice(start, start + ROWS_PER_PAGE);
    }

    function renderEmptyMessage(message, isError) {
        if (!gridContainer) {
            return;
        }
        gridContainer.innerHTML = `
            <div class="alert${isError ? '-danger' : ''}" style="grid-column: 1 / -1;">
                <strong>${isError ? 'Error' : 'Alerta'}:</strong> ${escapeHtml(message)}
            </div>
        `;
        if (paginationEl) {
            paginationEl.style.display = 'none';
        }
    }

    function renderPagination() {
        if (!paginationEl) {
            return;
        }

        const totalPages = getTotalPages();
        const hasMultiplePages = allPostulantes.length > ROWS_PER_PAGE;

        paginationEl.style.display = hasMultiplePages ? 'flex' : 'none';

        if (pageInfoEl) {
            pageInfoEl.textContent = 'Página ' + currentPage + ' de ' + totalPages +
                ' (' + allPostulantes.length + ' candidatos)';
        }

        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
            prevBtn.classList.toggle('disabled', currentPage <= 1);
        }

        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
            nextBtn.classList.toggle('disabled', currentPage >= totalPages);
        }
    }

    function renderCards(pageData) {
        if (!gridContainer) {
            return;
        }
        gridContainer.innerHTML = '';

        if (!allPostulantes.length) {
            renderEmptyMessage(
                activeSearchTerm
                    ? 'No hay candidatos que coincidan con la búsqueda.'
                    : 'No hay candidatos en esta vacante.',
                false
            );
            return;
        }

        pageData.forEach(function (d) {
            const card = document.createElement('div');
            card.className = 'mgmt-card';
            card.style.borderLeft = '5px solid var(--dark-blue, #035c67)';

            card.innerHTML = `
                <div class="card-header">
                    <h4 class="card-title">${escapeHtml(d.fullname)}</h4>
                    <span class="candidate-status-badge ${statusBadgeClass(d.status)}" title="${escapeHtml(d.status)}">
                        ${escapeHtml(d.status)}
                    </span>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <i class="fa fa-id-card"></i>
                        <strong>DNI:</strong>&nbsp;<span>${escapeHtml(d.dni)}</span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-phone"></i>
                        <strong>Tel:</strong>&nbsp;<span>${escapeHtml(d.phonenumber)}</span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-envelope"></i>
                        <strong>Email:</strong>&nbsp;<span>${escapeHtml(d.email)}</span>
                    </div>
                    <div class="info-item" style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee;">
                        <i class="fa fa-calendar-check"></i>
                        <strong>Aplicó el:</strong>&nbsp;<span>${formatDate(d.created_at)}</span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-star"></i>
                        <strong>Puntaje:</strong>&nbsp;<span style="font-weight: bold; color: ${d.overall_score ? 'var(--dark-blue)' : '#999'}">${d.overall_score ? d.overall_score + '%' : 'Pendiente'}</span>
                    </div>
                </div>
                <div class="card-footer" style="justify-content: center;">
                    <button class="action-btn" title="Ver Perfil Detallado" onclick="verPerfil(${d.id})" style="background: var(--blue, #06adbf); color: white; border: none; padding: 8px 15px; border-radius: 5px; font-size: 0.9rem; font-weight: 600; width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px; transition: background 0.3s;">
                        <i class="fa fa-user-circle" style="background: transparent; padding: 0;"></i> Ver Perfil
                    </button>
                </div>
            `;
            gridContainer.appendChild(card);
        });

        renderPagination();
    }

    function renderView() {
        renderCards(getPageData());
    }

    function goToPage(page) {
        const totalPages = getTotalPages();
        const nextPage = Math.min(Math.max(1, page), totalPages);
        if (nextPage === currentPage) {
            return;
        }
        currentPage = nextPage;
        renderView();
        if (gridContainer && typeof gridContainer.scrollIntoView === 'function') {
            gridContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function fetchData(searchTerm) {
        activeSearchTerm = String(searchTerm || '').trim();
        const url = '../../backend/registros/tabla_postulantes_vacante.php?id_vacante=' + encodeURIComponent(idVacante) +
            (activeSearchTerm ? '&search=' + encodeURIComponent(activeSearchTerm) : '');

        fetch(url)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.error) {
                    console.error(data.error);
                    allPostulantes = [];
                    currentPage = 1;
                    renderEmptyMessage(data.error, true);
                    return;
                }
                allPostulantes = data.data || [];
                currentPage = 1;
                renderView();
            })
            .catch(function (error) {
                console.error('Error fetching postulantes:', error);
                allPostulantes = [];
                currentPage = 1;
                renderEmptyMessage('No se pudo cargar el listado de candidatos.', true);
            });
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    window.verPerfil = function(id) {
        const usr = window.location.pathname.includes('_usr');
        window.location = (usr ? 'detalle_postulante_usr.php' : 'detalle_postulante.php') + '?id=' + id;
    };

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            goToPage(currentPage - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            goToPage(currentPage + 1);
        });
    }

    fetchData();
    window.filterPostulantes = fetchData;
});
