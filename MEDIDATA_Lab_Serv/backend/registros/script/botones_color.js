/**
 * Acceso rápido: resalta el botón de la vista actual.
 * Compara por nombre de archivo (RRHH: "puestos_trabajo.php", Usuarios: "../usuarios/mostrar.php").
 */
(function () {
    var ACTIVE_COLOR = '#06adbf';
    var INACTIVE_COLOR = '#035c67';
    var STORAGE_KEY = 'medidata_nav_active_url';

    function getUrlFileName(url) {
        if (!url || url === '#') {
            return '';
        }
        var clean = String(url).split('?')[0].split('#')[0].replace(/\\/g, '/');
        var pos = clean.lastIndexOf('/');
        return pos === -1 ? clean : clean.substring(pos + 1);
    }

    function normalizePageKey(urlOrFile) {
        return getUrlFileName(urlOrFile).toLowerCase();
    }

    function extractNavUrl(button) {
        var onclick = button.getAttribute('onclick') || '';
        var match = onclick.match(/cambiarColor\([^,]+,\s*['"]([^'"]+)['"]\)/);
        var url = match ? match[1] : '';
        if (!url || url === '#') {
            return '';
        }
        return url;
    }

    function getCurrentPageFile() {
        var path = window.location.pathname || '';
        return path.substring(path.lastIndexOf('/') + 1);
    }

    function getNavButtons() {
        return Array.prototype.slice.call(
            document.querySelectorAll('button.button[onclick*="cambiarColor"]')
        );
    }

    function getNavEntries(navButtons) {
        return navButtons.map(function (button) {
            var url = extractNavUrl(button);
            return {
                button: button,
                url: url,
                key: normalizePageKey(url),
            };
        }).filter(function (entry) {
            return entry.key !== '';
        });
    }

  /**
     * Determina qué botón debe verse activo según la página actual.
     */
    function findActiveNavUrl(navEntries, currentPageFile) {
        var currentKey = normalizePageKey(currentPageFile);
        var keys = navEntries.map(function (e) {
            return e.key;
        });

        var i;
        var idx = keys.indexOf(currentKey);
        if (idx !== -1) {
            return navEntries[idx].url;
        }

        /* Variante _usr.php ↔ .php (mismo módulo, distinto rol) */
        var currentBase = currentKey.replace(/_usr\.php$/i, '.php');
        for (i = 0; i < keys.length; i++) {
            var navBase = keys[i].replace(/_usr\.php$/i, '.php');
            if (currentBase === navBase) {
                return navEntries[i].url;
            }
        }

        /* Formularios de edición / contraseña → resaltar lista del módulo */
        if (/^editar|^edit_|password|_info\.php$|historial/i.test(currentKey)) {
            for (i = 0; i < keys.length; i++) {
                if (/mostrar|lista|listar|puestos_trabajo|vacantes_trabajo|positions|colaboradores/i.test(keys[i])) {
                    return navEntries[i].url;
                }
            }
        }

        /* registrar_*_usr en página registrar sin sufijo exacto, etc. */
        if (/^registrar_|^nuevo/.test(currentKey)) {
            for (i = 0; i < keys.length; i++) {
                if (keys[i] === currentKey || currentKey.indexOf(keys[i].replace('.php', '')) === 0) {
                    return navEntries[i].url;
                }
            }
        }

        return null;
    }

    function applyNavButtonState(activeUrl) {
        var activeKey = normalizePageKey(activeUrl);
        getNavButtons().forEach(function (button) {
            var url = extractNavUrl(button);
            var key = normalizePageKey(url);
            var isActive = activeKey !== '' && key === activeKey;
            button.style.backgroundColor = isActive ? ACTIVE_COLOR : INACTIVE_COLOR;
        });
    }

    window.cambiarColor = function (boton, url) {
        if (!url || url === '#') {
            return;
        }
        try {
            localStorage.setItem(STORAGE_KEY, url);
        } catch (e) {}

        applyNavButtonState(url);
        window.location.href = url;
    };

    document.addEventListener('DOMContentLoaded', function () {
        var navButtons = getNavButtons();
        if (!navButtons.length) {
            return;
        }

        var navEntries = getNavEntries(navButtons);
        if (!navEntries.length) {
            return;
        }

        var currentPage = getCurrentPageFile();
        var activeUrl = findActiveNavUrl(navEntries, currentPage);

        if (activeUrl) {
            try {
                localStorage.setItem(STORAGE_KEY, activeUrl);
            } catch (e) {}
            applyNavButtonState(activeUrl);
        } else {
            /* Sin coincidencia: ningún botón activo (evita marcar siempre el primero) */
            navButtons.forEach(function (button) {
                button.style.backgroundColor = INACTIVE_COLOR;
            });
        }
    });
})();
