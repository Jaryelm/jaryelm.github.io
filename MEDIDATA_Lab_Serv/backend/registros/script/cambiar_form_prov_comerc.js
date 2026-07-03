function showForm(formId) {
    const forms = document.querySelectorAll('.b_form-section');
    const buttons = document.querySelectorAll('.form-selector button');

    // Ocultar todos los formularios y remover la clase activa de los botones
    forms.forEach(form => {
        form.classList.remove('active');
    });
    buttons.forEach(button => {
        button.classList.remove('selected');
    });

    // Mostrar el formulario seleccionado y marcar el botón correspondiente como activo
    document.getElementById(formId).classList.add('active');
    document.querySelector(`button[data-form="${formId}"]`).classList.add('selected');
}
