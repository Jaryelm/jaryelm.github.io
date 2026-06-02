document.addEventListener('DOMContentLoaded', function() {
    const rowsPerPage = 10;
    let currentPage = 1;
    let tableData = [];

    function updateTable(data) {
        const tableBody = document.querySelector('#directorio-table tbody');
        tableBody.innerHTML = ''; // Limpiar el contenido existente
        
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.nombre_proveedor}</td>
                <td>${row.especialidad}</td>
                <td>${row.identidad}</td>
                <td>${row.colegiado}</td>
                <td>${row.rtn}</td>
                <td>${row.celular}</td>
                <td>${row.correo}</td>
                <td>${row.cuenta_bac}</td>
                <td>${row.cuenta_si}</td>
                <td>${row.cuenta_no}</td>
                <td>${row.tipo_cuenta}</td>
                <td>${row.constancia_pagos}</td>
                <td>${row.solicitud_constancia}</td>
                <td>${row.constancia_vigente}</td>
                <td>${row.fecha_registro}</td>
                <td>
                    <a class="download-btn" href="javascript:descargarArchivoProveedor(${row.id})">
                        <svg class="download-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Descargar
                    </a>
                </td>
                <td><button onclick="generarPDFProveedor('${row.nombre_proveedor}')">Generar PDF</button></td>
            `;
            tableBody.appendChild(tr);
        });
    }

    function paginateData(data) {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        return data.slice(start, end);
    }

    function renderPagination(totalRows) {
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        const paginationDiv = document.getElementById('pagination');
        paginationDiv.innerHTML = '';

        // Botón "Prev"
        const prevButton = document.createElement('button');
        prevButton.className = 'pagination-button' + (currentPage === 1 ? ' disabled' : '');
        prevButton.textContent = 'Prev';
        prevButton.disabled = currentPage === 1;
        prevButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            }
        });
        paginationDiv.appendChild(prevButton);

        // Botones de página
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.className = 'pagination-button' + (i === currentPage ? ' active' : '');
            pageButton.textContent = i;
            pageButton.addEventListener('click', () => {
                currentPage = i;
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            });
            paginationDiv.appendChild(pageButton);
        }

        // Botón "Next"
        const nextButton = document.createElement('button');
        nextButton.className = 'pagination-button' + (currentPage === totalPages ? ' disabled' : '');
        nextButton.textContent = 'Next';
        nextButton.disabled = currentPage === totalPages;
        nextButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            }
        });
        paginationDiv.appendChild(nextButton);
    }

    function filterData(searchTerm) {
        fetch(`../../backend/registros/tabla_directorio.php?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                tableData = data;
                currentPage = 1;
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            })
            .catch(error => console.error('Error al actualizar la tabla:', error));
    }

    function fetchData() {
        fetch('../../backend/registros/tabla_directorio.php')
            .then(response => response.json())
            .then(data => {
                tableData = data;
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            })
            .catch(error => console.error('Error al actualizar la tabla:', error));
    }

    fetchData();

    document.getElementById('search-button').addEventListener('click', function() {
        const searchTerm = document.getElementById('search-input').value;
        filterData(searchTerm);
    });
});

// Función para llamar el archivo admin.css para aplicar los estilos del boton Descargar

// Crear una nueva etiqueta <link>
const linkElement = document.createElement('link');

// Establecer los atributos del <link>
linkElement.rel = 'stylesheet';
linkElement.href = '../../backend/css/admin.css'; // Ruta a tu archivo CSS

// Añadir la etiqueta <link> al <head> del documento
document.head.appendChild(linkElement);

// Función para llamar el archivo Descargar

function descargarArchivoProveedor(id) {
    if (id) {
        const url = `../../backend/registros/descargar_proveedor_data.php?id=${encodeURIComponent(id)}`;
        window.open(url, '_blank');
    } else {
        alert('No hay archivo disponible para descargar.');
    }
}