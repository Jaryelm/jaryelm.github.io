$(() => {
    $.post('../../frontend/funciones/cat_outpatients.php').done((response) => {
        $('#outpatients').html(response);
    })

    $('#outpatients').change(function() {
        var pacientesSeleccionado = $(this).val();
    });
});