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

        Swal.fire({
            title: "Iniciar Turno",
            html: formHTML,
            showCancelButton: true,
            confirmButtonText: "Iniciar Turno",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#035c67",
            focusConfirm: false,
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            const turno = document.getElementById('turno-select-modal')?.value || '';

            if (!turno) {
                Swal.fire("Error", "Debe seleccionar un turno.", "error");
                return;
            }

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
                    Swal.fire("Error", "Error al iniciar turno. Intente nuevamente.", "error");
                });
        });
    };
}

// Función para cierre de caja
// Solo agregar event listener si el elemento existe y no tiene ya un onclick
document.addEventListener('DOMContentLoaded', function() {
    const btnCierre = document.getElementById('cierreCaja');
    if (btnCierre && !btnCierre.onclick) {
        btnCierre.addEventListener('click', function () {
            fetch('../../backend/registros/verificar_turno.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.turno_iniciado) {
                        Swal.fire("Error", "Debe iniciar su turno antes de realizar el cierre de caja.", "error")
                            .then(() => {
                                iniciarTurno();
                            });
                        return;
                    }

                    mostrarModalSobrante();
                })
                .catch(error => {
                    console.error('Error al verificar turno:', error);
                    mostrarModalSobrante();
                });
        });
    }
});

function mostrarModalSobrante() {
    Swal.fire({
        title: "CONFIRMAR CIERRE DE CAJA",
        text: "¿Está seguro que desea realizar el cierre de caja?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, realizar cierre",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#035c67",
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        fetch('../../backend/registros/cierre_caja.php', {
            method: 'POST',
            body: new FormData()
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

                Swal.fire("Éxito", "Cierre de caja realizado correctamente. Puede iniciar un nuevo turno.", "success")
                    .then(() => {
                        if (typeof verificarEstadoTurno === 'function') {
                            verificarEstadoTurno();
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    });
            })
            .catch(error => {
                console.error('Error al realizar el cierre:', error);
                Swal.fire("Error", "Ocurrió un error inesperado. Por favor, intenta nuevamente.", "error");
            });
    });
}

// NOTA: La verificación del estado del turno se maneja desde perfil.php
// Este archivo solo se encarga de las funciones de cierre de caja
