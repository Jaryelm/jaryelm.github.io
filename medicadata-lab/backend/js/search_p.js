document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput_pacientes');
    const table = document.getElementById('search_p');
    
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
            const cell = row.querySelector('td'); // Solo un <td> por fila
            if (cell && cell.textContent.toLowerCase().includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});