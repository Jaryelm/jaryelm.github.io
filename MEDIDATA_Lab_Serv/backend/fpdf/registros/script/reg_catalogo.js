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
        Swal.fire("Error", "Por favor, complete todos los campos obligatorios.", "error");
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
            Swal.fire("¡Éxito!", data.message, "success");
            // Limpiar los campos del formulario
            this.reset();
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery('#tipo-cuenta').length) {
                jQuery('#tipo-cuenta').trigger('change');
            }
        } else {
            Swal.fire("Error", data.message, "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire("Error", "Ocurrió un error inesperado.", "error");
    });
});