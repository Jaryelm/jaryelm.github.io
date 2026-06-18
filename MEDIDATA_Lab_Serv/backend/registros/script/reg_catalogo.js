document.getElementById('registro-form').addEventListener('submit', function(e) {
    e.preventDefault();  // Prevenir el envío del formulario
    let valid = true;
    const fields = this.querySelectorAll('input[required], select[required]');
    fields.forEach(field => {
        if (!field.value.trim()) {
            valid = false;
            field.classList.add('error');
        } else {
            field.classList.remove('error');
        }
    });

    if (!valid) {
        Swal.fire({ title: "Error", text: "Por favor, complete todos los campos obligatorios.", icon: "error", confirmButtonText: "OK" });
        return;
    }

    // Crear un FormData con los datos del formulario
    let formData = new FormData(this);

    // Enviar la solicitud AJAX usando fetch
    fetch('../../backend/registros/reg_catalogo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Mostrar la alerta de acuerdo a la respuesta
        if (data.success) {
            Swal.fire({ title: "¡Éxito!", text: data.message || "Operación completada", icon: "success", confirmButtonText: "OK" });
            // Limpiar los campos del formulario
            this.reset();
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery('#tipo-cuenta').length) {
                jQuery('#tipo-cuenta').trigger('change');
            }
        } else {
            Swal.fire({ title: "Error", text: data.message || "No se pudo completar la operación", icon: "error", confirmButtonText: "OK" });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({ title: "Error", text: "Ocurrió un error inesperado.", icon: "error", confirmButtonText: "OK" });
    });
});