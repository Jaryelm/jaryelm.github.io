document.addEventListener('DOMContentLoaded', function() {
    // Hacer una solicitud AJAX para obtener las cuentas
    fetch('../../backend/registros/lista_catalogo.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selectCuentas = document.getElementById('cuenta');
                const selectAsignarMonto = document.getElementById('asignar_monto'); // Campo "Asignar Monto"

                // Limpiar el select de cuentas antes de agregar nuevas opciones
                selectCuentas.innerHTML = '<option value="">Seleccione una cuenta</option>';
                
                // Limpiar el select de asignar monto antes de agregar nuevas opciones
                selectAsignarMonto.innerHTML = '<option value="">Seleccione una cuenta</option>';

                // Verificar el número de registros recibidos
                console.log('Número de registros:', data.num_registros);

                // Iterar sobre las cuentas y agregarlas como opciones en ambos selects
                data.cuentas.forEach(cuenta => {
                    const optionCuenta = document.createElement('option');
                    optionCuenta.value = cuenta.cuenta;  // Usar el campo 'cuenta' como valor
                    optionCuenta.innerHTML = `<strong>${cuenta.tipo_cuenta}</strong> - ${cuenta.cuenta} - ${cuenta.nombre}`;
                    
                    // Añadir la opción al select de cuentas
                    selectCuentas.appendChild(optionCuenta);

                    // Crear una opción para el select de asignar monto
                    const optionAsignarMonto = document.createElement('option');
                    optionAsignarMonto.value = cuenta.cuenta;  // Usar el campo 'cuenta' como valor
                    optionAsignarMonto.innerHTML = `<strong>${cuenta.tipo_cuenta}</strong> - ${cuenta.cuenta} - ${cuenta.nombre}`;

                    // Añadir la opción al select de asignar monto
                    selectAsignarMonto.appendChild(optionAsignarMonto);
                });
            } else {
                console.error('Error al obtener los datos:', data.message);
            }
        })
        .catch(error => {
            console.error('Error en la solicitud AJAX:', error);
        });
});
