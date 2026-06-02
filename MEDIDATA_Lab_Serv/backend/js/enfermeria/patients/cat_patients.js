$(() => {
    $.post('../../frontend/funciones/cat_patients.php').done((response) => {
        $('#patients').html(response);
    })

    $('#patients').change(function() {
        var pacientesSeleccionado = $(this).val();
    });
});