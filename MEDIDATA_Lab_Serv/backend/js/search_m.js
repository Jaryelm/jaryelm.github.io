    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput_medicos');
        const table = document.getElementById('search_m');
        
        // Verificar que ambos elementos existan antes de continuar
        if (!searchInput || !table) {
            return; // Salir si no existen los elementos
        }
        
        // Verificar que la tabla tenga tbody antes de buscar filas
        const tbody = table.querySelector('tbody');
        if (!tbody) {
            return; // Salir si no hay tbody
        }
        
        const tableRows = tbody.querySelectorAll('tr');
        if (tableRows.length === 0) {
            return; // Salir si no hay filas
        }

        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let match = false;

                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchTerm)) {
                        match = true;
                    }
                });

                if (match) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });