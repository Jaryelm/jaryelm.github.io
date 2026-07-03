document.addEventListener('DOMContentLoaded', function() {
    const ROWS_PER_PAGE = 8;
    let currentPage = 1;
    let allVacantes = [];

    const groups = {
        'Urgente': { grid: document.getElementById('grid-urgente'), section: document.getElementById('section-urgente'), countId: 'count-urgente' },
        'Alta': { grid: document.getElementById('grid-alta'), section: document.getElementById('section-alta'), countId: 'count-alta' },
        'Media': { grid: document.getElementById('grid-media'), section: document.getElementById('section-media'), countId: 'count-media' },
        'Baja': { grid: document.getElementById('grid-baja'), section: document.getElementById('section-baja'), countId: 'count-baja' }
    };

    const paginationEl = document.getElementById('vacantes-pagination');
    const prevBtn = paginationEl ? paginationEl.querySelector('.rrhh-page-prev') : null;
    const nextBtn = paginationEl ? paginationEl.querySelector('.rrhh-page-next') : null;
    const pageInfoEl = paginationEl ? paginationEl.querySelector('.rrhh-page-info') : null;

    function getTotalPages() {
        return Math.max(1, Math.ceil(allVacantes.length / ROWS_PER_PAGE));
    }

    function getPageData() {
        const start = (currentPage - 1) * ROWS_PER_PAGE;
        return allVacantes.slice(start, start + ROWS_PER_PAGE);
    }

    function countByPriority(data) {
        const counts = { Urgente: 0, Alta: 0, Media: 0, Baja: 0 };
        data.forEach(function (d) {
            const groupKey = Object.keys(groups).find(function (k) {
                return k.toLowerCase() === String(d.priority || '').trim().toLowerCase();
            });
            if (groupKey) {
                counts[groupKey]++;
            }
        });
        return counts;
    }

    function resetGrids() {
        Object.keys(groups).forEach(function (key) {
            const g = groups[key];
            if (g.grid) {
                g.grid.innerHTML = '';
            }
            if (g.section) {
                g.section.style.display = 'none';
                g.section.classList.add('collapsed');
            }
            const badge = document.getElementById(g.countId);
            if (badge) {
                badge.textContent = '0';
            }
        });
    }

    function buildCardHtml(d, groupKey) {
        return `
            <div class="card-header">
                <h4 class="card-title">
                    ${escapeHtml(d.position_name || 'Sin Título')}
                </h4>
                <span class="priority-badge priority-${groupKey.toLowerCase()}">
                    ${escapeHtml(d.priority)}
                </span>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <i class="fa fa-building"></i>
                    <strong>Depto:</strong>&nbsp;<span>${escapeHtml(d.department_name || 'N/A')}</span>
                </div>
                <div class="info-item">
                    <i class="fa fa-user-tie"></i>
                    <strong>Jefe:</strong>&nbsp;<span>${escapeHtml(d.immediate_boss || 'N/A')}</span>
                </div>
                <div class="info-item">
                    <i class="fa fa-users"></i>
                    <strong>Plazas:</strong>&nbsp;<span class="slots-badge">${d.available_slots}</span>
                </div>
                <div class="info-item">
                    <i class="fa fa-user-check"></i>
                    <strong>Postulantes:</strong>&nbsp;<span class="applicants-badge">${d.total_applicants}</span>
                </div>
                <div class="info-item">
                    <i class="fa fa-calendar-alt"></i>
                    <span>${formatDate(d.init_date)} - ${formatDate(d.end_date)}</span>
                </div>
                <div class="card-description">
                    <strong>Descripción:</strong> ${escapeHtml(d.reason)}
                </div>
            </div>
            <div class="card-footer">
                <div class="status-container">
                    <label class="switch">
                        <input type="checkbox" class="status-toggle" data-id="${d.id}" ${d.deleted == '0' ? 'checked' : ''}/> 
                        <span class="slider"></span>
                    </label>
                    <span class="vacante-status-label">${d.deleted == '0' ? 'Abierta' : 'Cerrada'}</span>
                </div>
                <div class="card-actions">
                    <a href="${window.location.pathname.includes('_usr') ? 'postulantes_vacante_usr.php' : 'postulantes_vacante.php'}?id_vacante=${d.id}" class="btn-postulantes">
                        <i class="fa fa-users"></i> Postulantes
                    </a>
                    <a title="Ver detalles y Editar" href="${window.location.pathname.includes('_usr') ? 'registrar_vacantes_trabajo_usr.php' : 'registrar_vacantes_trabajo.php'}?id=${d.id}" class="action-btn-edit">
                        <i class="fa fa-edit"></i>
                    </a>
                </div>
            </div>
        `;
    }

    function renderGroupedCards(pageData) {
        const loadingState = document.getElementById('loading-state');
        const noResults = document.getElementById('no-results-message');

        if (loadingState) {
            loadingState.style.display = 'none';
        }

        resetGrids();

        if (!allVacantes.length) {
            if (noResults) {
                noResults.style.display = 'block';
            }
            if (paginationEl) {
                paginationEl.style.display = 'none';
            }
            return;
        }

        if (noResults) {
            noResults.style.display = 'none';
        }

        const totalCounts = countByPriority(allVacantes);
        Object.keys(totalCounts).forEach(function (key) {
            const badge = document.getElementById(groups[key].countId);
            if (badge) {
                badge.textContent = String(totalCounts[key]);
            }
        });

        pageData.forEach(function (d) {
            const groupKey = Object.keys(groups).find(function (k) {
                return k.toLowerCase() === String(d.priority || '').trim().toLowerCase();
            });
            if (!groupKey) {
                return;
            }

            const group = groups[groupKey];
            if (!group || !group.grid) {
                return;
            }

            group.section.style.display = 'block';
            group.section.classList.remove('collapsed');

            const card = document.createElement('div');
            card.className = 'mgmt-card card-vacante';
            card.innerHTML = buildCardHtml(d, groupKey);
            group.grid.appendChild(card);
        });

        attachStatusToggles();
        renderPagination();
    }

    function renderPagination() {
        if (!paginationEl) {
            return;
        }

        const totalPages = getTotalPages();
        const hasMultiplePages = allVacantes.length > ROWS_PER_PAGE;

        paginationEl.style.display = hasMultiplePages ? 'flex' : 'none';

        if (pageInfoEl) {
            pageInfoEl.textContent = 'Página ' + currentPage + ' de ' + totalPages +
                ' (' + allVacantes.length + ' vacantes)';
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

    function goToPage(page) {
        const totalPages = getTotalPages();
        const nextPage = Math.min(Math.max(1, page), totalPages);
        if (nextPage === currentPage) {
            return;
        }
        currentPage = nextPage;
        renderGroupedCards(getPageData());
        const container = document.getElementById('vacantes-grouped-container');
        if (container && typeof container.scrollIntoView === 'function') {
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function showFetchError(message) {
        const loadingState = document.getElementById('loading-state');
        const noResults = document.getElementById('no-results-message');
        resetGrids();
        allVacantes = [];
        currentPage = 1;
        if (loadingState) {
            loadingState.style.display = 'none';
        }
        if (paginationEl) {
            paginationEl.style.display = 'none';
        }
        if (noResults) {
            noResults.style.display = 'block';
            noResults.innerHTML =
                '<i class="fa fa-exclamation-triangle"></i>' +
                '<p>' + escapeHtml(message || 'Error al cargar vacantes de trabajo') + '</p>';
        }
    }

    function fetchData(searchTerm) {
        searchTerm = String(searchTerm || '').trim();
        const loadingState = document.getElementById('loading-state');
        const noResults = document.getElementById('no-results-message');
        if (loadingState) {
            loadingState.style.display = 'block';
        }
        if (noResults) {
            noResults.style.display = 'none';
            noResults.innerHTML =
                '<i class="fa fa-clipboard-list"></i>' +
                '<p>No se encontraron vacantes con los criterios de búsqueda.</p>';
        }

        const isInactivas = window.isVacantesInactivas ? '1' : '0';
        const url = '../../backend/registros/tabla_vacantes_trabajo.php' +
            '?inactivas=' + isInactivas +
            (searchTerm ? '&search=' + encodeURIComponent(searchTerm) : '');

        fetch(url, { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                if (!Array.isArray(data)) {
                    if (data && data.error) {
                        showFetchError(data.error);
                    } else {
                        showFetchError('No se pudo cargar el listado de vacantes.');
                    }
                    return;
                }
                allVacantes = data;
                currentPage = 1;
                renderGroupedCards(getPageData());
            })
            .catch(function (error) {
                console.error('Error fetching vacantes:', error);
                showFetchError(error && error.message ? error.message : 'Error al cargar vacantes de trabajo');
            });
    }

    function attachStatusToggles() {
        document.querySelectorAll('.status-toggle').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                const id = this.dataset.id;
                const deleted = this.checked ? 0 : 1;
                const label = this.closest('.status-container')?.querySelector('.vacante-status-label');
                const self = this;

                fetch('../../backend/php/toggle_vacante_trabajo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(id) + '&deleted=' + encodeURIComponent(deleted)
                })
                    .then(function (r) { return r.json(); })
                    .then(function (resp) {
                        if (!resp.success) {
                            self.checked = !self.checked;
                            Swal.fire('Error', resp.message || 'No se pudo actualizar el estado', 'error');
                            return;
                        }
                        if (label) {
                            label.textContent = deleted == 0 ? 'Abierta' : 'Cerrada';
                        }
                        const item = allVacantes.find(function (v) { return String(v.id) === String(id); });
                        if (item) {
                            item.deleted = deleted;
                        }
                    })
                    .catch(function () {
                        self.checked = !self.checked;
                        Swal.fire('Error', 'Error al actualizar el estado', 'error');
                    });
            });
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
    window.filterVacantes = fetchData;
});
