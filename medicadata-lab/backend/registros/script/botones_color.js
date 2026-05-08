    // Función para cambiar el color del botón y almacenar la URL seleccionada
    function cambiarColor(boton, url) {
        // Guarda la URL seleccionada en el localStorage
        localStorage.setItem('selectedButtonUrl', url);

        // Redirige a la nueva ubicación
        location.href = url;
    }

    // Al cargar la página, verifica si hay un botón previamente seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        const selectedUrl = localStorage.getItem('selectedButtonUrl');
        if (selectedUrl) {
            // Encuentra el botón que coincide con la URL guardada y cambia su color
            const botones = document.querySelectorAll('.button');
            botones.forEach(function(boton) {
                if (boton.getAttribute('onclick').includes(selectedUrl)) {
                    boton.style.backgroundColor = '#06adbf';
                }
            });
        }
    });