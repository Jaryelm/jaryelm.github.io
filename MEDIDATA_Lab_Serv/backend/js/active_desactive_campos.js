document.addEventListener('DOMContentLoaded', function () {
    // Obtener los elementos del formulario
    const lineaSelect = document.getElementById('linea');
    const prinActivo = document.getElementById('prin_activo').closest('div');
    const categoriaUso = document.getElementById('cat').closest('div');
    const presentacion = document.getElementById('presentacion').closest('div');
    const formaFarmaceutica = document.getElementById('forma_farmaceutica').closest('div');
    const concentracion = document.getElementById('concentracion').closest('div');
    const viaAdministracion = document.getElementById('via_administracion').closest('div');

    // Verifica que todos los elementos existan antes de continuar
    if (!lineaSelect || !prinActivo || !categoriaUso || !presentacion || !formaFarmaceutica || !concentracion || !viaAdministracion) {
        console.error("No se encontraron algunos elementos del formulario. Verifica el HTML.");
        return;
    }

    // Lista de valores que habilitarán los campos adicionales
    const habilitarValores = [
        "MEDICAMENTOS",
        "MATERIAL DESCARTABLE",
        "INSUMOS DESCARTABLES"
    ];

    // Función para mostrar u ocultar los campos
    function actualizarCampos() {
        const valorSeleccionado = lineaSelect.value;

        if (habilitarValores.includes(valorSeleccionado)) {
            // Mostrar campos
            prinActivo.style.display = '';
            categoriaUso.style.display = '';
            presentacion.style.display = '';
            formaFarmaceutica.style.display = '';
            concentracion.style.display = '';
            viaAdministracion.style.display = '';
        } else {
            // Ocultar campos y limpiar sus valores
            prinActivo.style.display = 'none';
            categoriaUso.style.display = 'none';
            presentacion.style.display = 'none';
            formaFarmaceutica.style.display = 'none';
            concentracion.style.display = 'none';
            viaAdministracion.style.display = 'none';

            document.getElementById('prin_activo').value = "";
            document.getElementById('cat').value = "";
            document.getElementById('presentacion').value = "";
            document.getElementById('forma_farmaceutica').value = "";
            document.getElementById('concentracion').value = "";
            document.getElementById('via_administracion').value = "";
        }
    }

    // Ocultar los campos al cargar la página
    prinActivo.style.display = 'none';
    categoriaUso.style.display = 'none';
    presentacion.style.display = 'none';
    formaFarmaceutica.style.display = 'none';
    concentracion.style.display = 'none';
    viaAdministracion.style.display = 'none';

    // Escuchar el cambio en el campo "Linea"
    lineaSelect.addEventListener('change', actualizarCampos);
});
