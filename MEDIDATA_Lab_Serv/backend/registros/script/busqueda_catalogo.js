document.addEventListener('DOMContentLoaded', function() {
    cargarRegistros();

    document.getElementById('search-form').addEventListener('submit', function(e) {
        e.preventDefault();  // Prevenir el envío del formulario
        const searchValue = document.getElementById('search').value;
        cargarRegistros(searchValue);
    });
});

function cargarRegistros(searchValue = '') {
    const url = searchValue ? 
        `../../backend/registros/busqueda_catalogo.php?search=${encodeURIComponent(searchValue)}` : 
        '../../backend/registros/busqueda_catalogo.php';

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const lista = Array.isArray(data) ? data : [];
            actualizarTabla(lista);
            actualizarConteo(lista.length, searchValue);  // Actualizar conteo de registros
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Ocurrió un error al cargar los registros.");
        });
}

function actualizarTabla(data) {
    const resultsTableBody = document.querySelector('#results-table tbody');
    resultsTableBody.innerHTML = '';  // Limpiar contenido previo

    if (data.length > 0) {
        data.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.tipo_cuenta}</td>
                <td>${item.cuenta}</td>
                <td>${item.nombre}</td>
            `;
            resultsTableBody.appendChild(tr);
        });
    } else {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="3">No se encontraron registros.</td>';
        resultsTableBody.appendChild(tr);
    }
}

// Función para actualizar el conteo de registros
function actualizarConteo(count, searchValue) {
    const countDisplay = document.getElementById('count-display');
    if (searchValue) {
        countDisplay.textContent = `Resultados filtrados: ${count}`;
    } else {
        countDisplay.textContent = `Total de registros: ${count}`;
    }
}
