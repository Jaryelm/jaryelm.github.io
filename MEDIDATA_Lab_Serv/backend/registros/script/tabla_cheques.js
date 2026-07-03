document.addEventListener('DOMContentLoaded', function() {
    const rowsPerPage = 10;
    let currentPage = 1;
    let tableData = [];

    function updateTable(data) {
        const tableBody = document.querySelector('#cheques-table tbody');
        tableBody.innerHTML = ''; // Limpiar el contenido existente
        
        data.forEach(row => {
            const tr = document.createElement('tr');
            const fueraBalanceClass = row.fuera_balance !== '0.00' ? 'error' : ''; // Aplica la clase de error si hay desbalance
            tr.innerHTML = `
                <td>${row.cuenta}</td>
                <td>${row.balance}</td>
                <td>${row.impuestos}</td>
                <td>${row.proveedor_RTN}</td>
                <td>${row.cheque_no}</td>
                <td>${row.pagar}</td>
                <td>${row.fecha}</td>
                <td>${row.cantidad}</td>
                <td>${row.concepto}</td>
                <td>${row.asignar_monto}</td>
                <td>${row.monto}</td>
                <td>${row.proyecto}</td>
                <td>${row.imp_ventas}</td>
                <td>${row.total_asignado}</td>
                <td>${row.impuesto}</td>
                <td class="${fueraBalanceClass}">${row.fuera_balance}</td>
                <td>${row.total_pagado}</td>
                <td><button onclick="generarPDF('${row.cheque_no}')">Generar PDF</button></td> <!-- Agregar botón de PDF -->
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

        // Previous Button
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

        // Page Buttons
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

        // Next Button
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
        console.log(`Buscando: ${searchTerm}`); // Verificar el término de búsqueda
        fetch(`../../backend/registros/tabla_cheques.php?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Datos recibidos:', data); // Verificar los datos recibidos
                tableData = data;
                currentPage = 1; // Resetear la página actual a 1
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            })
            .catch(error => console.error('Error al actualizar la tabla:', error));
    }

    // Función para obtener y procesar los datos iniciales
    function fetchData() {
        fetch('../../backend/registros/tabla_cheques.php')
            .then(response => response.json())
            .then(data => {
                tableData = data;
                updateTable(paginateData(tableData));
                renderPagination(tableData.length);
            })
            .catch(error => console.error('Error al actualizar la tabla:', error));
    }
    
    // Inicializar la tabla con los datos actuales al cargar la página
    fetchData();

    // Manejar el filtro de búsqueda con el botón
    document.getElementById('search-button').addEventListener('click', function() {
        const searchTerm = document.getElementById('search-input').value;
        filterData(searchTerm);
    });
});