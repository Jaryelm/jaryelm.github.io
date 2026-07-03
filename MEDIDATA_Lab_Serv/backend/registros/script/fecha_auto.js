document.addEventListener('DOMContentLoaded', function() {
    const fechaInput = document.getElementById('fecha');
    const today = new Date();

    // Ajusta la fecha a la zona horaria de Honduras (UTC-6)
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset() - 360);

    const formattedDate = today.toISOString().split('T')[0]; // Formato YYYY-MM-DD
    fechaInput.value = formattedDate; // Asigna la fecha ajustada al input
});
