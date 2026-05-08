document.addEventListener('DOMContentLoaded', function() {
    const rowsPerPage = 10;
    let currentPage = 1;
    let tableData = [];

    function updateTable(data) {
        const tableBody = document.querySelector('#directorio-comercial-table tbody');
        tableBody.innerHTML = ''; // Limpiar el contenido existente
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.nombre_empresa}</td>
                <td>${row.direccion}</td>
                <td>${row.rtn_comercial}</td>
                <td>${row.tel_fijo || 'N/A'}</td>
                <td>${row.correo_comercial}</td>
                <td>${row.cel_whatsapp || 'N/A'}</td>
                <td>${row.nombre_legal}</td>
                <td>${row.dni_comercial}</td>
                <td>${row.cel_comercial || 'N/A'}</td>
                <td>${row.cuenta_bac_comercial || 'N/A'}</td>
                <td>${row.cuenta_bac_si || 'N/A'}</td>
                <td>${row.cuenta_bac_no || 'N/A'}</td>
                <td>${row.tipo_cuenta_comercial || 'N/A'}</td>
                <td>${row.nom_contacto || 'N/A'}</td>
                <td>${row['1_refbac_comercial'] || 'N/A'}</td>
                <td>${row['1_refbac_comercial_tel'] || 'N/A'}</td>
                <td>${row['2_refbac_comercial'] || 'N/A'}</td>
                <td>${row['2_refbac_comercial_tel'] || 'N/A'}</td>
                <td>${row['1_refbac_contacto'] || 'N/A'}</td>
                <td>${row['1_refbac_contacto_tel'] || 'N/A'}</td>
                <td>${row.firma_digital_comercial ? 'Sí' : 'No'}</td>
                <td>
                    <a class="download-btn" href="javascript:descargarArchivoComercial(${row.id})">
                        <svg class="download-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Descargar
                    </a>
                </td>
                <td><button onclick="generarPDFComercial('${row.nombre_empresa}')">Generar PDF</button></td>
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
        fetch(`../../backend/registros/tabla_directorio_comercial.php?search=${encodeURIComponent(searchTerm)}`)
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
        fetch('../../backend/registros/tabla_directorio_comercial.php')
            .then(response => response.json())
            .then(data => {
                tableData = data;
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            })
            .catch(error => console.error('Error al actualizar la tabla:', error));
    }

    function generarPDFComercial(nombreEmpresa) {
        // Lógica para generar el PDF
        window.open(`../../backend/registros/generar_pdf.php?nombre_empresa=${encodeURIComponent(nombreEmpresa)}`, '_blank');
    }

    fetchData();

    document.getElementById('search-button').addEventListener('click', function() {
        const searchTerm = document.getElementById('search-input').value;
        filterData(searchTerm);
    });
});

function descargarArchivoComercial(id) {
    if (id) {
        const url = `../../backend/registros/descargar_proveedor_comercial.php?id=${encodeURIComponent(id)}`;
        window.open(url, '_blank');
    } else {
        alert('No hay archivo disponible para descargar.');
    }
}
