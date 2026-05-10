document.getElementById('emitir-cheque-btn').addEventListener('click', function(event) {
    event.preventDefault();  // Evitar el envío del formulario estándar
    // Crear objeto FormData para enviar todos los datos del formulario
    let formData = new FormData();
    let allFieldsFilled = true;

    // Validar que todos los campos del formulario estén llenos
    document.querySelectorAll('.form-container input, .form-container textarea, .form-container select').forEach(input => {
        if (input.value.trim() === '') {
            allFieldsFilled = false;
            swal("Error", `Por favor, completa el campo: ${input.previousElementSibling.textContent}`, "error");
            input.focus(); // Enfocar en el campo vacío
            return; // Salir del ciclo si algún campo está vacío
        }
        formData.append(input.name, input.value);
    });

    if (!allFieldsFilled) return;

    // Realizar la solicitud AJAX usando fetch
    fetch('../../backend/registros/cheques.php', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            swal("¡Éxito!", data.message, "success").then(() => {
                // Limpiar los campos del formulario
                document.querySelectorAll('.form-container input, .form-container textarea').forEach(input => {
                    input.value = ''; // Vaciar los campos
                });
                // Restablecer los campos <select>
                document.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0; // Seleccionar la primera opción
                });
            });
        } else {
            swal("Error", data.message, "error");
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        swal("Error", "Ocurrió un error inesperado.", "error").then(() => {
            // Restablecer los campos <select> en caso de error
            document.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0; // Seleccionar la primera opción
            });
        });
    });
});