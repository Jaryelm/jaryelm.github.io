<div class="profile">
    <span>Hospital MEDICASA</span>
    <img src="../../backend/img/icon.png" alt="">
    <ul class="profile-link">
        <li>
            <button id="iniciarTurno" class="btn-iniciar" onclick="iniciarTurno()" style="display: block;">
                <i class='bx bxs-time'></i> Iniciar Turno
            </button>
        </li>
        <li>
            <button id="cierreCaja" class="btn-cierre" onclick="cierreCaja()" style="display: none;">
                <i class='bx bxs-log-out-circle'></i> Cierre de Caja
            </button>
        </li>
        <li>
            <a href="../facturacion/firma_user.php"><i class='bx bxs-log-out-circle'></i>Firma Digital</a>
        </li>
        <li>
            <a href="https://soporte.medicasa.hn/" target="_blank"><i class='bx bxs-help-circle'></i> Soporte TI</a>
        </li>
        <li>
            <a href="../salir.php"><i class='bx bxs-log-out-circle'></i> Salir</a>
        </li>
    </ul>
</div>

<script>
// Verificar estado del turno al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    verificarEstadoTurno();
});

function verificarEstadoTurno() {
    fetch('../../backend/registros/verificar_turno.php')
        .then(response => response.json())
        .then(data => {
            const btnIniciar = document.getElementById('iniciarTurno');
            const btnCierre = document.getElementById('cierreCaja');
            
            if (data.turno_iniciado) {
                // Hay turno activo - mostrar botón de cierre
                btnIniciar.style.display = 'none';
                btnCierre.style.display = 'block';
            } else {
                // No hay turno activo - mostrar botón de iniciar
                btnIniciar.style.display = 'block';
                btnCierre.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // En caso de error, mostrar botón de iniciar
            document.getElementById('iniciarTurno').style.display = 'block';
            document.getElementById('cierreCaja').style.display = 'none';
        });
}

function iniciarTurno() {
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

                setTimeout(() => verificarEstadoTurno(), 500);
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire("Error", "Error al iniciar turno.", "error");
            });
    });
}

function cierreCaja() {
    Swal.fire({
        title: "CONFIRMAR CIERRE DE CAJA",
        text: "¿Está seguro que desea realizar el cierre de caja?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, realizar cierre",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#035c67",
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../backend/registros/cierre_caja.php', { 
                method: 'POST',
                body: new FormData()
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
                    console.error('Error:', error);
                    Swal.fire("Error", "Error al realizar el cierre.", "error");
                });
        }
    });
}
</script>

<style>
.btn-iniciar, .btn-cierre {
    background-color: #035c67;
    color: #fff;
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;
    text-align: left;
}

.btn-iniciar:hover, .btn-cierre:hover {
    background-color: #06adbf;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.swal-button--confirm {
    background-color: #035c67 !important;
}

.swal-button--confirm:hover {
    background-color: #06adbf !important;
}
</style>
