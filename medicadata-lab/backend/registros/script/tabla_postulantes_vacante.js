document.addEventListener('DOMContentLoaded', function() {
    const gridContainer = document.getElementById('postulantes-grid');
    const idVacante = document.getElementById('id_vacante_hidden').value;
    
    function renderCards(data) {
        if (!gridContainer) return;
        gridContainer.innerHTML = '';
        
        if (data.length === 0) {
            gridContainer.innerHTML = `
                <div class="alert" style="grid-column: 1 / -1;">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                    <strong>Alerta!</strong> No hay datos disponibles.
                </div>
            `;
            return;
        }
        
        data.forEach(d => {
            const card = document.createElement('div');
            card.className = 'mgmt-card';
            card.style.borderLeft = '5px solid var(--dark-blue, #035c67)';
            
            // Determinar color del badge de estado
            let statusColor = '#6c757d'; // Default gris
            if(d.status === 'Contratado') statusColor = 'var(--green, #81D43A)';
            else if(d.status === 'Descartado') statusColor = 'var(--red, #FC3B56)';
            else if(d.status === 'En Espera') statusColor = 'var(--blue, #06adbf)';
            else if(d.status === 'Entrevista' || d.status === 'Agendado' || d.status === 'Pruebas Psicometricas') statusColor = 'var(--dark-blue, #035c67)';

            card.innerHTML = `
                <div class="card-header" style="align-items: center;">
                    <h4 class="card-title" style="margin: 0;">${escapeHtml(d.fullname)}</h4>
                    <span style="background-color: ${statusColor}; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
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
    }

    function fetchData(searchTerm = '') {
        const url = `../../backend/registros/tabla_postulantes_vacante.php?id_vacante=${idVacante}${searchTerm ? '&search=' + encodeURIComponent(searchTerm) : ''}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                } else {
                    // El endpoint devuelve {"data": [...]}, lo ajustamos:
                    const candidates = data.data || [];
                    
                    // Si implementamos búsqueda en el backend luego, esto ya funciona.
                    // Si no, filtramos en el frontend:
                    if (searchTerm) {
                        const term = searchTerm.toLowerCase();
                        const filtered = candidates.filter(c => 
                            c.fullname.toLowerCase().includes(term) || 
                            c.dni.includes(term) || 
                            c.email.toLowerCase().includes(term)
                        );
                        renderCards(filtered);
                    } else {
                        renderCards(candidates);
                    }
                }
            })
            .catch(error => console.error('Error fetching postulantes:', error));
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

    // Export function to global scope for button click
    window.verPerfil = function(id) {
        swal("Próximamente", "La vista detallada del perfil del candidato (ID: " + id + ") está en construcción.", "info");
        // Futuro redireccionamiento: window.location = 'perfil_candidato.php?id=' + id;
    };

    fetchData();

    window.filterPostulantes = fetchData;
});