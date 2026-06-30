document.addEventListener('DOMContentLoaded', function() {
    let signaturePadMedico, signaturePadComercial;

    function initializeSignaturePadMedico() {
        const canvasMedico = document.getElementById('firma_pad_medico');
        signaturePadMedico = new SignaturePad(canvasMedico, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)'
        });
        resizeCanvas(canvasMedico, signaturePadMedico);
    }

    function initializeSignaturePadComercial() {
        const canvasComercial = document.getElementById('firma_pad');
        signaturePadComercial = new SignaturePad(canvasComercial, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)'
        });
        resizeCanvas(canvasComercial, signaturePadComercial);
    }

    function resizeCanvas(canvas, signaturePad) {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        signaturePad.clear(); // Limpiar la firma al redimensionar
    }

    window.addEventListener('resize', function() {
        resizeCanvas(document.getElementById('firma_pad_medico'), signaturePadMedico);
        resizeCanvas(document.getElementById('firma_pad'), signaturePadComercial);
    });

    function showForm(formId) {
        const forms = document.querySelectorAll('.b_form-section');
        forms.forEach(form => {
            form.classList.remove('active');
        });
        const activeForm = document.getElementById(formId);
        activeForm.classList.add('active');
        if (formId === 'form1') {
            initializeSignaturePadMedico();
        } else if (formId === 'form2') {
            initializeSignaturePadComercial();
        }
    }

    showForm('form1');

    document.querySelector('.form-selector').addEventListener('click', function(e) {
        if (e.target.tagName === 'BUTTON') {
            const formId = e.target.getAttribute('onclick').match(/'([^']+)'/)[1];
            showForm(formId);
        }
    });

    window.clearSignatureMedico = function() {
        if (signaturePadMedico) {
            signaturePadMedico.clear();
        }
    }

    window.clearSignature = function() {
        if (signaturePadComercial) {
            signaturePadComercial.clear();
        }
    }

    document.getElementById('proveedor-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (signaturePadMedico && !signaturePadMedico.isEmpty()) {
            const dataURL = signaturePadMedico.toDataURL('image/png');
            const blob = dataURLToBlob(dataURL);
            const file = new File([blob], `${document.getElementById('nombre-proveedor').value}.png`, { type: 'image/png' });
            const formData = new FormData(this);
            formData.append('firma-digital', file);
    
            fetch('../../backend/registros/reg_directorio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Éxito", data.message, "success");
                    this.reset();
                    signaturePadMedico.clear();
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire("Error", "Ocurrió un error al enviar el formulario.", "error");
            });
        } else {
            Swal.fire("Advertencia", "Por favor, firme el documento.", "warning");
        }
    });
    
    function dataURLToBlob(dataURL) {
        const byteString = atob(dataURL.split(',')[1]);
        const mimeString = dataURL.split(',')[0].split(':')[1].split(';')[0];
        const ab = new ArrayBuffer(byteString.length);
        const ia = new Uint8Array(ab);
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        return new Blob([ab], { type: mimeString });
    }

    document.getElementById('comercial-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (signaturePadComercial && !signaturePadComercial.isEmpty()) {
            const dataURL = signaturePadComercial.toDataURL('image/png');
            const blob = dataURLToBlob(dataURL);
            const file = new File([blob], `${document.getElementById('empresa').value}.png`, { type: 'image/png' });
            const formData = new FormData(this);
            formData.append('firma-digital-comercial', file);

            fetch('../../backend/registros/reg_directorio_comercial.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Éxito", data.message, "success");
                    this.reset();
                    signaturePadComercial.clear();
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire("Error", "Ocurrió un error al enviar el formulario.", "error");
            });
        } else {
            Swal.fire("Advertencia", "Por favor, firme el documento.", "warning");
        }
    });

    function dataURLToBlob(dataURL) {
        const byteString = atob(dataURL.split(',')[1]);
        const mimeString = dataURL.split(',')[0].split(':')[1].split(';')[0];
        const ab = new ArrayBuffer(byteString.length);
        const ia = new Uint8Array(ab);
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        return new Blob([ab], { type: mimeString });
    }
});