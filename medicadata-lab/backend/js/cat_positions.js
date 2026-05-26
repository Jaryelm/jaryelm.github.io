$(() => {
    $.post("../../frontend/funciones/positions.php").done(function(response) {
        $("#positions_datos").html(response);
    });
    $('#positions_datos').change(function() {
        var positionSeleccionada = $(this).val();
    });
});