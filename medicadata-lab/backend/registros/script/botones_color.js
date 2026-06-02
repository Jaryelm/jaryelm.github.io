(function () {
    var ACTIVE_COLOR = '#06adbf';
    var INACTIVE_COLOR = '#035c67';
    var STORAGE_KEY = 'selectedButtonUrl';

    function extractNavUrl(button) {
        var onclick = button.getAttribute('onclick') || '';
        var match = onclick.match(/cambiarColor\([^,]+,\s*['"]([^'"]+)['"]\)/);
        return match ? match[1] : '';
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

    function applyNavButtonState(activeUrl) {
        getNavButtons().forEach(function (button) {
            var url = extractNavUrl(button);
            button.style.backgroundColor = (url === activeUrl) ? ACTIVE_COLOR : INACTIVE_COLOR;
        });
    }

    window.cambiarColor = function (boton, url) {
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

        var navUrls = navButtons.map(extractNavUrl).filter(Boolean);
        var currentPage = getCurrentPageFile();
        var activeUrl = null;

        if (navUrls.indexOf(currentPage) !== -1) {
            activeUrl = currentPage;
        } else {
            try {
                var stored = localStorage.getItem(STORAGE_KEY);
                if (stored && navUrls.indexOf(stored) !== -1) {
                    activeUrl = stored;
                }
            } catch (e) {}
        }

        if (!activeUrl && navUrls.length) {
            activeUrl = navUrls[0];
        }

        if (activeUrl) {
            try {
                localStorage.setItem(STORAGE_KEY, activeUrl);
            } catch (e) {}
            applyNavButtonState(activeUrl);
        }
    });
})();
