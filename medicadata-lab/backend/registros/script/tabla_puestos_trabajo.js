document.addEventListener('DOMContentLoaded', function() {
    const gridContainer = document.getElementById('puestos-grid');
    
    function renderCards(data) {
        if (!gridContainer) return;
        gridContainer.innerHTML = '';
        
        if (data.length === 0) {
            gridContainer.innerHTML = `
                <div style="grid-column: 1 / -1; width: 100%;">
                    <p class="alert alert-warning" style="margin: 0; display: block; width: 100%; border-radius: 5px; text-align: left;">No hay datos</p>
                </div>
            `;
            return;
        }
        
        data.forEach(d => {
            const card = document.createElement('div');
            card.className = 'mgmt-card card-puesto';
            card.innerHTML = `
                <div class="card-header">
                    <h4 class="card-title">${escapeHtml(d.name)}</h4>
                    <div class="card-actions">
                        <a title="Ver detalles y Editar" href="${window.location.pathname.includes('_usr') ? 'registrar_puesto_trabajo_usr.php' : 'registrar_puesto_trabajo.php'}?id=${d.id}" class="action-btn">
                            <i class="fa fa-edit" style="color: #06adbf; background: none; padding: 0;"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <i class="fa fa-building"></i>
                        <span>${escapeHtml(d.department)}</span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-user-tie"></i>
                        <span>Jefe: ${escapeHtml(d.immediate_boss)}</span>
                    </div>
                    <div class="info-item">
                        <i class="fa fa-clock"></i>
                        <span>${escapeHtml(d.schedule)}</span>
                    </div>
                    <div class="card-description">
                        <strong>Objetivo:</strong> ${escapeHtml(d.objective)}
                    </div>
                </div>
                <div class="card-footer">
                    <div class="status-container">
                        <label class="switch">
                            <input type="checkbox" class="status-toggle" data-id="${d.id}" ${d.deleted == '0' ? 'checked' : ''}/> 
                            <span class="slider"></span>
                        </label>
                        <span style="margin-left: 10px; font-size: 0.8rem; color: #777;">
                            ${d.deleted == '0' ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                </div>
            `;
            gridContainer.appendChild(card);
            
            // Note: Modals are still handled by PHP include in the loop for now 
            // but we can move them to dynamic loading if needed.
            // For now, I'll keep the PHP includes in the view and just sync the data.
        });

        // Re-attach toggle events
        attachStatusToggles();
    }

    function fetchData(searchTerm = '') {
        const url = `../../backend/registros/tabla_puestos_trabajo.php${searchTerm ? '?search=' + encodeURIComponent(searchTerm) : ''}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                } else {
                    renderCards(data);
                }
            })
            .catch(error => console.error('Error fetching puestos:', error));
    }

    function attachStatusToggles() {
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const id = this.dataset.id;
                const status = this.checked ? 0 : 1;
                const label = this.parentElement.nextElementSibling;
                label.textContent = status == 0 ? 'Activo' : 'Inactivo';
                // Here we could add an AJAX call to update the status in the DB
            });
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial fetch
    fetchData();

    // Export for search button if it exists
    window.filterPuestos = fetchData;
});
