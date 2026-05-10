document.getElementById('actualizar-form').addEventListener('submit', function(e) {
    e.preventDefault();  // Prevenir el envío del formulario
    
    // Obtener los valores de los campos del formulario
    let cuentaActualizar = document.getElementById('cuenta-actualizar').value;
    let nuevoTipoCuenta = document.getElementById('tipo-cuenta-actualizar').value;
    let nuevoNombre = document.getElementById('nuevo-nombre').value;
    
    // Crear un objeto FormData para enviar los datos al servidor
    let formData = new FormData();
    formData.append('cuenta-actualizar', cuentaActualizar);
    formData.append('nuevo-tipo-cuenta', nuevoTipoCuenta);
    formData.append('nuevo-nombre', nuevoNombre);
    
    // Enviar la solicitud AJAX usando fetch
    fetch('../../backend/registros/actualizar_catalogo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Mostrar la alerta de acuerdo a la respuesta
        if (data.success) {
            swal("¡Éxito!", data.message, "success");
            // Limpiar el formulario después de la actualización
            document.getElementById('actualizar-form').reset();
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery('#tipo-cuenta-actualizar').length) {
                jQuery('#tipo-cuenta-actualizar').trigger('change');
            }
        } else {
            swal("Error", data.message, "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        swal("Error", "Ocurrió un error inesperado.", "error");
    });
});