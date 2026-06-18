document.getElementById('comercial-form').addEventListener('submit', function(e) {
    e.preventDefault();  // Prevenir el envío del formulario

    // Obtener los valores de los campos
    let nombreEmpresa = document.getElementById('empresa');
    let direccion = document.getElementById('direccion');
    let rtnComercial = document.getElementById('rtn_comercial');
    let telFijo = document.getElementById('tel_fijo');
    let correoComercial = document.getElementById('correo_comercial');
    let celWhatsApp = document.getElementById('cel_whatsapp');
    let nombreLegal = document.getElementById('nombre_legal');
    let dniComercial = document.getElementById('dni_comercial');
    let celComercial = document.getElementById('cel_comercial');
    let cuentaBACComercial = document.querySelector('input[name="cuenta_bac_comercial"]:checked');
    let cuentaBACSi = document.querySelector('input[name="cuenta_bac_si"]:checked');
    let cuentaBACNo = document.querySelector('input[name="cuenta_bac_no"]:checked');
    let tipoCuentaComercial = document.querySelector('input[name="tipo_cuenta_comercial"]:checked');
    let nomContacto = document.getElementById('nom_contacto');
    let archivoConstanciaComercial = document.getElementById('archivo-constancia-comercial');
    let firmaDigitalComercial = document.getElementById('firma_digital_comercial');
    let ref1BacComercial = document.getElementById('1_refbac_comercial');
    let ref1BacComercialTel = document.getElementById('1_refbac_comercial_tel');
    let ref2BacComercial = document.getElementById('2_refbac_comercial');
    let ref2BacComercialTel = document.getElementById('2_refbac_comercial_tel');
    let ref1BacContacto = document.getElementById('1_refbac_contacto');
    let ref1BacContactoTel = document.getElementById('1_refbac_contacto_tel');

    // Validar campos requeridos
    if (nombreEmpresa.value.trim() === "") {
        swal("Error", "El campo Nombre Empresa es obligatorio.", "error").then(() => {
            nombreEmpresa.focus();
        });
        return;
    }
    if (correoComercial.value.trim() === "") {
        swal("Error", "El campo Correo Electrónico Comercial es obligatorio.", "error").then(() => {
            correoComercial.focus();
        });
        return;
    }

    // Crear un FormData con los datos del formulario
    let formData = new FormData(this);

    // Enviar la solicitud AJAX usando fetch
    fetch('../../backend/registros/reg_directorio_comercial.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Mostrar la alerta de acuerdo a la respuesta
        if (data.success) {
            swal("¡Éxito!", data.message, "success");
            // Limpiar los campos del formulario
            document.getElementById('comercial-form').reset();
        } else {
            swal("Error", data.message, "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        swal("Error", "Ocurrió un error inesperado.", "error");
    });
});