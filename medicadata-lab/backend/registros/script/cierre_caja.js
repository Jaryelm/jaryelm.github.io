// Función para formatear números con comas (formato hondureño)
function formatearMoneda(numero) {
    return new Intl.NumberFormat('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numero);
}

// Función para iniciar turno (sin efectivo inicial)
// Solo definir si no existe ya (para no sobrescribir la función de perfil.php)
if (typeof iniciarTurno === 'undefined') {
    window.iniciarTurno = function() {
    // Crear el formulario solo con el campo de turno
    const formHTML = `
        <div style="text-align: left; padding: 10px 0;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #035c67;">Turno:</label>
                <select id="turno-select-modal" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                    <option value="">Seleccione un turno</option>
                    <option value="Turno A">Turno A</option>
                    <option value="Turno B">Turno B</option>
                    <option value="Turno C">Turno C</option>
                </select>
            </div>
        </div>
    `;
    
    // Crear contenedor y parsear HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = formHTML;
    const formContainer = tempDiv.firstElementChild;
    
    swal({
        title: "Iniciar Turno",
        content: formContainer,
        buttons: {
            cancel: {
                text: "Cancelar",
                value: null,
                visible: true,
                className: "btn-cancel",
                closeModal: true,
            },
            confirm: {
                text: "Iniciar Turno",
                value: true,
                visible: true,
                className: "btn-confirm",
                closeModal: false,
            },
        },
    }).then((continuar) => {
        if (continuar) {
            const turnoSelect = document.getElementById('turno-select-modal');
            
            const turno = turnoSelect ? turnoSelect.value : '';
            
            if (!turno || turno === '') {
                swal("Error", "Debe seleccionar un turno.", "error");
                return;
            }
            
            // Guardar solo el turno (efectivo inicial será 0.00 por defecto)
            const formData = new FormData();
            formData.append('turno', turno);
            formData.append('accion', 'iniciar_turno');
            
            fetch('../../backend/registros/iniciar_turno.php', { 
                method: 'POST',
                body: formData
            })
                .then(response => response.text())
                .then(script => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = script;
                    const scripts = tempDiv.querySelectorAll('script');
                    scripts.forEach((scriptEl) => {
                        const newScript = document.createElement('script');
                        newScript.textContent = scriptEl.textContent;
                        document.body.appendChild(newScript);
                        newScript.remove();
                    });
                })
                .catch(error => {
                    console.error('Error al iniciar turno:', error);
                    swal("Error", "Error al iniciar turno. Intente nuevamente.", "error");
                });
        }
    });
    };
}

// Función para cierre de caja
// Solo agregar event listener si el elemento existe y no tiene ya un onclick
document.addEventListener('DOMContentLoaded', function() {
    const btnCierre = document.getElementById('cierreCaja');
    if (btnCierre && !btnCierre.onclick) {
        btnCierre.addEventListener('click', function () {
    // Verificar si ya se inició el turno
    fetch('../../backend/registros/verificar_turno.php')
        .then(response => response.json())
        .then(data => {
            if (!data.turno_iniciado) {
                swal("Error", "Debe iniciar su turno antes de realizar el cierre de caja.", "error")
                .then(() => {
                    iniciarTurno();
                });
                return;
            }
            
            // Continuar con el proceso de cierre
            mostrarModalSobrante();
        })
        .catch(error => {
            console.error('Error al verificar turno:', error);
            // Si hay error, continuar con el proceso normal
            mostrarModalSobrante();
        });
        });
    }
});

function mostrarModalSobrante() {
    // Confirmar directamente el cierre sin pedir efectivo físico
    swal({
        title: "CONFIRMAR CIERRE DE CAJA",
        text: "¿Está seguro que desea realizar el cierre de caja?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                value: null,
                visible: true,
                className: "btn-cancel",
                closeModal: true,
            },
            confirm: {
                text: "Sí, realizar cierre",
                        value: true,
                        visible: true,
                        className: "btn-confirm",
                        closeModal: false,
                    },
                },
            }).then((willClose) => {
                if (willClose) {
            // Crear FormData sin efectivo físico ni sobrante
                    const formData = new FormData();
            // El backend establecerá efectivo_fisico_final y sobrante_caja en 0.00
                    
                    fetch('../../backend/registros/cierre_caja.php', { 
                        method: 'POST',
                        body: formData
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.text();
                        })
                        .then(script => {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = script;

                            const scripts = tempDiv.querySelectorAll('script');
                            scripts.forEach((scriptEl) => {
                                const newScript = document.createElement('script');
                                newScript.textContent = scriptEl.textContent;
                                document.body.appendChild(newScript);
                                newScript.remove();
                            });
                            
                            // Mostrar mensaje de éxito y actualizar estado
                            swal("Éxito", "Cierre de caja realizado correctamente. Puede iniciar un nuevo turno.", "success")
                            .then(() => {
                                // Actualizar estado de botones si estamos en perfil.php
                                if (typeof verificarEstadoTurno === 'function') {
                                    verificarEstadoTurno();
                                }
                                // Recargar la página para asegurar sincronización
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            });
                        })
                        .catch(error => {
                            console.error('Error al realizar el cierre:', error);
                            swal("Error", "Ocurrió un error inesperado. Por favor, intenta nuevamente.", "error");
                        });
        }
    });
}



// NOTA: La verificación del estado del turno se maneja desde perfil.php
// Este archivo solo se encarga de las funciones de cierre de caja