// Seleccionamos todos los enlaces del nuevo submenú
const newSubmenuLinks = document.querySelectorAll('.new-submenu-link');

newSubmenuLinks.forEach(link => {
    link.addEventListener('click', (event) => {
        event.preventDefault(); // Evita que el enlace recargue la página

        // Encontramos el submenú asociado
        const newSubMenu = link.nextElementSibling;

        // Si el submenú ya está visible, lo ocultamos
        if (newSubMenu.classList.contains('show')) {
            newSubMenu.classList.remove('show');
        } else {
            // Cerramos cualquier otro submenú abierto
            document.querySelectorAll('.new-side-dropdown.show').forEach(menu => {
                menu.classList.remove('show');
            });

            // Mostramos el submenú actual
            newSubMenu.classList.add('show');
        }
    });
});