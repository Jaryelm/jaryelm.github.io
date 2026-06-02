document.getElementById('eliminar-form').addEventListener('submit', function(e) {
    e.preventDefault();  // Prevenir el envío del formulario
    // Obtener el valor de la cuenta a eliminar
    let cuentaEliminar = document.getElementById('cuenta-eliminar').value;
    // Crear un objeto FormData
    let formData = new FormData();
    formData.append('cuenta-eliminar', cuentaEliminar);
    
    // Enviar la solicitud AJAX usando fetch
    fetch('../../backend/registros/eliminar_catalogo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Mostrar la alerta de acuerdo a la respuesta
        if (data.success) {
            Swal.fire({ title: "¡Éxito!", text: data.message || "Operación completada", icon: "success", confirmButtonText: "OK" });
            // Limpiar el campo del formulario
            document.getElementById('eliminar-form').reset();
            // Opcional: Actualizar la lista de cuentas
            // Puedes llamar a la función que carga los registros nuevamente
        } else {
            Swal.fire({ title: "Error", text: data.message || "No se pudo completar la operación", icon: "error", confirmButtonText: "OK" });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({ title: "Error", text: "Ocurrió un error inesperado.", icon: "error", confirmButtonText: "OK" });
    });
});