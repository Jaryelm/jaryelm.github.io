document.getElementById('registroarticuloform').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Formulario enviado');

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
        swal("Error", "Por favor, complete todos los campos obligatorios.", "error");
        return;
    }

    // Normalización de la fecha antes de enviar el formulario
    let fechaVenceInput = document.getElementById('fecha_vence');
    let fechaVence = fechaVenceInput.value; // Obtener el valor actual

    // Si la fecha viene en otro formato, puedes procesarla aquí
    if (fechaVence) {
        let dateObj = new Date(fechaVence);
        let normalizedDate = dateObj.toISOString().split('T')[0]; // Normalizar a formato YYYY-MM-DD
        fechaVenceInput.value = normalizedDate; // Asignar el valor normalizado al campo
    }

    let formData = new FormData(this);

    fetch('../../backend/registros/reg_articulo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta del servidor:', data);
        if (data.success) {
            swal("¡Éxito!", data.message, "success");
            this.reset(); // Restablece el formulario

            this.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
                $(select).val(null).trigger('change');
            });

        } else {
            swal("Error", data.message, "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        swal("Error", "Ocurrió un error inesperado.", "error");
    });    
});

