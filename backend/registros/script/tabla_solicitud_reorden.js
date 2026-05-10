document.addEventListener('DOMContentLoaded', function() {
    const rowsPerPage = 10;
    let currentPage = 1;
    let tableData = [];

    function updateTable(data) {
        const tableBody = document.querySelector('#directorio-table tbody');
        tableBody.innerHTML = ''; // Limpiar el contenido existente
    
        data.forEach(row => {
            // Construir la URL completa de la imagen
            const imageUrl = row.adj_foto ? `https://medidata.medicasa.hn/${row.adj_foto}` : '';
    
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.codpro}</td>
                <td>${row.codbars}</td>
                <td>${row.linea}</td>
                <td>${row.sub_linea}</td>
                <td>${row.presentacion}</td>
                <td>${row.forma_farmaceutica}</td>
                <td>${row.concentracion}</td>
                <td>${row.via_administracion}</td>
                <td>${row.nompro}</td>
                <td>${row.principio_activo}</td>
                <td>${row.preprd}</td>
                <td>${row.precio_venta}</td>
                <td>${row.margen_ganancia}</td>
                <td>${row.impuesto}</td>
                <td>${row.idcat}</td>
                <td>${row.stock}</td>
                <td>${row.fere}</td>
                <td>${row.fecha_vencimiento}</td>
<td>
    ${imageUrl ? `<img src="${imageUrl}" alt="Foto" style="width: 60px; height: 50px; object-fit: cover; object-position: center; border-radius: 5px;">` : 'No disponible'}
</td>
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
        fetch(`../../backend/registros/tabla_almacen.php?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data)) {
                    console.error("Los datos recibidos no son un array:", data);
                    return; // Si los datos no son un array, no continuar
                }
                tableData = data;
                currentPage = 1;
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            })
            .catch(error => console.error('Error al actualizar la tabla:', error));
    }
    
    function fetchData() {
        fetch('../../backend/registros/tabla_almacen.php')
            .then(response => response.json())
            .then(data => {
                console.log(data); // Asegúrate de que este es un array
                if (!Array.isArray(data)) {
                    console.error("Los datos recibidos no son un array:", data);
                    return; // Si los datos no son un array, no continuar
                }
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