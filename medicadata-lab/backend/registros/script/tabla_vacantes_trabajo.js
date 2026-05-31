document.addEventListener('DOMContentLoaded', function() {
    
    function renderGroupedCards(data) {
        const loadingState = document.getElementById('loading-state');
        const noResults = document.getElementById('no-results-message');
        
        if (loadingState) loadingState.style.display = 'none';
        
        // Define groups
        const groups = {
            'Urgente': { grid: document.getElementById('grid-urgente'), section: document.getElementById('section-urgente'), countId: 'count-urgente' },
            'Alta': { grid: document.getElementById('grid-alta'), section: document.getElementById('section-alta'), countId: 'count-alta' },
            'Media': { grid: document.getElementById('grid-media'), section: document.getElementById('section-media'), countId: 'count-media' },
            'Baja': { grid: document.getElementById('grid-baja'), section: document.getElementById('section-baja'), countId: 'count-baja' }
        };

        // Reset all sections and counts
        const counts = { 'Urgente': 0, 'Alta': 0, 'Media': 0, 'Baja': 0 };

        Object.keys(groups).forEach(key => {
            const g = groups[key];
            if (g.grid) g.grid.innerHTML = '';
            if (g.section) g.section.style.display = 'none';
            const badge = document.getElementById(g.countId);
            if (badge) badge.textContent = '0';
        });

        if (!data || data.length === 0) {
            if (noResults) noResults.style.display = 'block';
            return;
        } else {
            if (noResults) noResults.style.display = 'none';
        }

        data.forEach(d => {
            // Find appropriate group (handling potential casing issues)
            const groupKey = Object.keys(groups).find(k => k.toLowerCase() === (d.priority || '').trim().toLowerCase());
            if (!groupKey) return;

            const group = groups[groupKey];
            if (!group || !group.grid) return;

            counts[groupKey]++;
            group.section.style.display = 'block';
            
            const card = document.createElement('div');
            card.className = 'mgmt-card card-vacante';
            card.innerHTML = `
                <div class="card-header">
                    <h4 class="card-title">
                        ${escapeHtml(d.vacant_name)}
                    </h4>
                    <span class="priority-badge priority-${groupKey.toLowerCase()}">
                        ${escapeHtml(d.priority)}
                    </span>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <i class="fa fa-briefcase"></i>
                        <strong>Puesto:</strong>&nbsp;<span>${escapeHtml(d.position_name || 'N/A')}</span>
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
                        <strong>Motivo:</strong> ${escapeHtml(d.reason)}
                    </div>
                </div>
                <div class="card-footer" style="flex-wrap: wrap; gap: 10px;">
                    <div class="status-container">
                        <label class="switch">
                            <input type="checkbox" class="status-toggle" data-id="${d.id}" ${d.deleted == '0' ? 'checked' : ''}/> 
                            <span class="slider"></span>
                        </label>
                        <span style="margin-left: 10px; font-size: 0.8rem; color: #777;">
                            ${d.deleted == '0' ? 'Abierta' : 'Cerrada'}
                        </span>
                    </div>
                    <div class="card-actions" style="display: flex; gap: 10px; align-items: center;">
                        <a href="postulantes_vacante.php?id_vacante=${d.id}" class="action-btn" style="background: var(--blue, #06adbf); color: white; padding: 5px 12px; border-radius: 5px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 5px; text-decoration: none;">
                            <i class="fa fa-users" style="color: white; background: transparent; padding: 0;"></i> Postulantes
                        </a>
                        <a title="Ver detalles y Editar" href="${window.location.pathname.includes('_usr') ? 'registrar_vacantes_trabajo_usr.php' : 'registrar_vacantes_trabajo.php'}?id=${d.id}" class="action-btn">
                            <i class="fa fa-edit" style="color: #06adbf; background: none; padding: 0;"></i>
                        </a>
                    </div>
                </div>
            `;
            group.grid.appendChild(card);
        });

        // Update counts in badges with final values
        Object.keys(counts).forEach(key => {
            const badge = document.getElementById(groups[key].countId);
            if (badge) {
                badge.textContent = counts[key];
            }
        });

        attachStatusToggles();
    }

    function fetchData(searchTerm = '') {
        const loadingState = document.getElementById('loading-state');
        if (loadingState) loadingState.style.display = 'block';

        const url = `../../backend/registros/tabla_vacantes_trabajo.php${searchTerm ? '?search=' + encodeURIComponent(searchTerm) : ''}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                } else {
                    renderGroupedCards(data);
                }
            })
            .catch(error => {
                console.error('Error fetching vacantes:', error);
                if (loadingState) loadingState.style.display = 'none';
            });
    }

    function attachStatusToggles() {
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const id = this.dataset.id;
                const status = this.checked ? 0 : 1;
                const label = this.parentElement.nextElementSibling;
                label.textContent = status == 0 ? 'Abierta' : 'Cerrada';
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

    fetchData();

    window.filterVacantes = fetchData;
});
