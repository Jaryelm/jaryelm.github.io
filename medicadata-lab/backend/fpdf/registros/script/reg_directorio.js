document.getElementById('proveedor-form').addEventListener('submit', function(e) {
    e.preventDefault();  // Prevenir el envío del formulario

    // Obtener los valores de los campos
    let nombreProveedor = document.getElementById('nombre-proveedor');
    let especialidad = document.getElementById('especialidad');
    let identidad = document.getElementById('identidad');
    let colegiado = document.getElementById('colegiado');
    let rtn = document.getElementById('rtn');
    let celular = document.getElementById('celular');
    let correo = document.getElementById('correo');

    // Validar campos requeridos
    if (nombreProveedor.value.trim() === "") {
        Swal.fire("Error", "El campo Nombre Completo Proveedor es obligatorio.", "error").then(() => {
            nombreProveedor.focus();
        });
        return;
    }

    if (especialidad.value.trim() === "") {
        Swal.fire("Error", "El campo Especialidad es obligatorio.", "error").then(() => {
            especialidad.focus();
        });
        return;
    }

    if (identidad.value.trim() === "") {
        Swal.fire("Error", "El campo Número de Identidad es obligatorio.", "error").then(() => {
            identidad.focus();
        });
        return;
    }

    if (colegiado.value.trim() === "") {
        Swal.fire("Error", "El campo Número Colegiado es obligatorio.", "error").then(() => {
            colegiado.focus();
        });
        return;
    }

    if (rtn.value.trim() === "") {
        Swal.fire("Error", "El campo RTN es obligatorio.", "error").then(() => {
            rtn.focus();
        });
        return;
    }

    if (celular.value.trim() === "" || celular.value.trim() === "+504 " || celular.value.trim().length <= 5) {
        Swal.fire("Error", "Por favor, ingrese un número de whats app luego de +504.", "error").then(() => {
            celular.focus();
        });
        return;
    }

    if (correo.value.trim() === "") {
        Swal.fire("Error", "El campo Correo Electrónico es obligatorio.", "error").then(() => {
            correo.focus();
        });
        return;
    }

    // Crear un FormData con los datos del formulario
    let formData = new FormData(this);

    // Enviar la solicitud AJAX usando fetch
    fetch('../../backend/registros/reg_directorio.php', { // Actualiza el path según la ubicación real del archivo PHP
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Mostrar la alerta de acuerdo a la respuesta
        if (data.success) {
            Swal.fire("¡Éxito!", data.message, "success");
            // Limpiar los campos del formulario
            document.getElementById('proveedor-form').reset();
            // Restablecer el valor del campo celular
            document.getElementById('celular').value = "+504 ";
        } else {
            Swal.fire("Error", data.message, "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire("Error", "Ocurrió un error inesperado.", "error");
    });
});