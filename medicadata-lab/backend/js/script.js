// SIDEBAR DROPDOWN
const allDropdown = document.querySelectorAll('#sidebar .side-dropdown');
const sidebar = document.getElementById('sidebar');

/** Preferencia menú colapsado (solo íconos): persiste entre páginas y sesiones */
const SIDEBAR_COLLAPSED_KEY = 'medidata_sidebar_collapsed';

function readSidebarCollapsedPref() {
	try {
		return localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1';
	} catch (e) {
		return false;
	}
}

function writeSidebarCollapsedPref(collapsed) {
	try {
		if (collapsed) {
			localStorage.setItem(SIDEBAR_COLLAPSED_KEY, '1');
		} else {
			localStorage.setItem(SIDEBAR_COLLAPSED_KEY, '0');
		}
	} catch (e) {
		/* modo privado u otro bloqueo */
	}
}

// Verificar que sidebar exista antes de usar
if (sidebar) {
allDropdown.forEach(item=> {
	const a = item.parentElement.querySelector('a:first-child');
		if (a) {
	a.addEventListener('click', function (e) {
		e.preventDefault();

		if(!this.classList.contains('active')) {
			allDropdown.forEach(i=> {
				const aLink = i.parentElement.querySelector('a:first-child');
						if (aLink) {
				aLink.classList.remove('active');
						}
				i.classList.remove('show');
			})
		}

		this.classList.toggle('active');
		item.classList.toggle('show');
	})
		}
})
}





// SIDEBAR COLLAPSE
const toggleSidebar = document.querySelector('nav .toggle-sidebar');
const allSideDivider = document.querySelectorAll('#sidebar .divider');

function closeDropdownsForCollapsedSidebar() {
	allDropdown.forEach(item => {
		const a = item.parentElement.querySelector('a:first-child');
		if (a) {
			a.classList.remove('active');
		}
		item.classList.remove('show');
	});
}

function applySidebarCollapsedState(collapsed) {
	if (!sidebar) {
		return;
	}
	if (collapsed) {
		sidebar.classList.add('hide');
		allSideDivider.forEach(item => {
			item.textContent = '-';
		});
		closeDropdownsForCollapsedSidebar();
	} else {
		sidebar.classList.remove('hide');
		allSideDivider.forEach(item => {
			item.textContent = item.dataset.text;
		});
	}
}

// Restaurar preferencia al cargar la vista (misma experiencia en todo el sistema)
if (sidebar) {
	if (readSidebarCollapsedPref()) {
		applySidebarCollapsedState(true);
	} else {
		applySidebarCollapsedState(false);
	}
}

// Verificar que sidebar y toggleSidebar existan antes de usarlos
if (sidebar && toggleSidebar) {
	toggleSidebar.addEventListener('click', function (e) {
		e.preventDefault();
		sidebar.classList.toggle('hide');
		const collapsed = sidebar.classList.contains('hide');
		writeSidebarCollapsedPref(collapsed);
		if (collapsed) {
			allSideDivider.forEach(item => {
				item.textContent = '-';
			});
			closeDropdownsForCollapsedSidebar();
		} else {
			allSideDivider.forEach(item => {
				item.textContent = item.dataset.text;
			});
		}
	});
}




if (sidebar) {
sidebar.addEventListener('mouseleave', function () {
	if(this.classList.contains('hide')) {
		allDropdown.forEach(item=> {
			const a = item.parentElement.querySelector('a:first-child');
				if (a) {
			a.classList.remove('active');
				}
			item.classList.remove('show');
		})
		allSideDivider.forEach(item=> {
			item.textContent = '-'
		})
	}
})

sidebar.addEventListener('mouseenter', function () {
	if(this.classList.contains('hide')) {
		allDropdown.forEach(item=> {
			const a = item.parentElement.querySelector('a:first-child');
				if (a) {
			a.classList.remove('active');
				}
			item.classList.remove('show');
		})
		allSideDivider.forEach(item=> {
			item.textContent = item.dataset.text;
		})
	}
})
}




// PROFILE DROPDOWN
const profile = document.querySelector('nav .profile');
let imgProfile = null;
let dropdownProfile = null;

if (profile) {
    imgProfile = profile.querySelector('img');
    dropdownProfile = profile.querySelector('.profile-link');
    
    if (imgProfile && dropdownProfile) {
        imgProfile.addEventListener('click', function () {
            dropdownProfile.classList.toggle('show');
        });
    }
}




// MENU
const allMenu = document.querySelectorAll('main .content-data .head .menu');

if (allMenu.length > 0) {
    allMenu.forEach(item=> {
        const icon = item.querySelector('.icon');
        const menuLink = item.querySelector('.menu-link');
        
        if (icon && menuLink) {
            icon.addEventListener('click', function () {
                menuLink.classList.toggle('show');
            });
        }
    });
}



window.addEventListener('click', function (e) {
    // Solo ejecutar si profile existe
    if (profile && imgProfile && dropdownProfile) {
        if(e.target !== imgProfile) {
            if(e.target !== dropdownProfile) {
                if(dropdownProfile.classList.contains('show')) {
                    dropdownProfile.classList.remove('show');
                }
            }
        }
    }

    // Solo ejecutar si allMenu tiene elementos
    if (allMenu.length > 0) {
        allMenu.forEach(item=> {
            const icon = item.querySelector('.icon');
            const menuLink = item.querySelector('.menu-link');

            if (icon && menuLink) {
                if(e.target !== icon) {
                    if(e.target !== menuLink) {
                        if (menuLink.classList.contains('show')) {
                            menuLink.classList.remove('show')
                        }
                    }
                }
            }
        });
    }
});





// PROGRESSBAR
const allProgress = document.querySelectorAll('main .card .progress');

if (allProgress.length > 0) {
    allProgress.forEach(item=> {
        if (item.dataset.value) {
            item.style.setProperty('--value', item.dataset.value);
        }
    });
}






// APEXCHART
const chartElement = document.querySelector("#chart");
if (chartElement) {
    var options = {
        series: [{
            name: 'series1',
            data: [31, 40, 28, 51, 42, 109, 100]
        }, {
            name: 'series2',
            data: [11, 32, 45, 32, 34, 52, 41]
        }],
        chart: {
            height: 350,
            type: 'area'
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth'
        },
        xaxis: {
            type: 'datetime',
            categories: ["2018-09-19T00:00:00.000Z", "2018-09-19T01:30:00.000Z", "2018-09-19T02:30:00.000Z", "2018-09-19T03:30:00.000Z", "2018-09-19T04:30:00.000Z", "2018-09-19T05:30:00.000Z", "2018-09-19T06:30:00.000Z"]
        },
        tooltip: {
            x: {
                format: 'dd/MM/yy HH:mm'
            },
        },
    };

    var chart = new ApexCharts(chartElement, options);
    chart.render();
}

/**
 * === Novedades para el cliente (tipo “actualización” de apps móvil) ===
 *
 * Cuando haya mejoras visibles del menú, cabecera o navegación:
 * 1. Cambia MEDIDATA_UI_RELEASE_ID (un valor nuevo cada entrega).
 * 2. Ajusta MEDIDATA_UI_RELEASE_LEAD y MEDIDATA_UI_RELEASE_ITEMS abajo.
 * 3. La vista debe cargar admin.css + este script; solo se muestra si existe #sidebar.
 *
 * Igual que iOS/Android: el usuario ve el aviso una vez por versión; al pulsar «Entendido»
 * queda registrado en localStorage y no se repite hasta la próxima versión.
 */
(function medidataUiReleaseNotes() {
	const MEDIDATA_UI_RELEASE_ID = 'menu-ui-2026-05-18';
	const STORAGE_KEY = 'medidata_ui_release_seen_id';

	function alreadySeenThisRelease() {
		try {
			return localStorage.getItem(STORAGE_KEY) === MEDIDATA_UI_RELEASE_ID;
		} catch (e) {
			return true;
		}
	}

	function markReleaseSeen() {
		try {
			localStorage.setItem(STORAGE_KEY, MEDIDATA_UI_RELEASE_ID);
		} catch (e) {
			/* sin espacio / modo privado */
		}
	}

	function dismissOverlay(overlayEl) {
		markReleaseSeen();
		if (overlayEl && overlayEl.parentNode) {
			overlayEl.parentNode.removeChild(overlayEl);
		}
	}

	if (!document.getElementById('sidebar')) {
		return;
	}
	if (alreadySeenThisRelease()) {
		return;
	}

	const MEDIDATA_UI_RELEASE_LEAD =
		'Estos cambios hacen más cómodo moverse entre módulos y mantener pantallas ordenadas. Tu flujo habitual no cambia.';

	const MEDIDATA_UI_RELEASE_ITEMS = [
		{ title: 'Barra superior', text: 'Mejor alineación del menú y del perfil Hospital MEDICASA, sin huecos molestos.' },
		{ title: 'Menú contraído', text: 'Al reducir el panel lateral los íconos siguen visibles; ya no «desaparecen» por error.' },
		{ title: 'Tu preferencia guardada', text: 'Si dejas el menú colapsado o expandido, se mantiene al abrir otras vistas del sistema.' },
	];

	window.setTimeout(function () {
		if (!document.getElementById('sidebar')) {
			return;
		}
		const overlay = document.createElement('div');
		overlay.className = 'medidata-release-overlay';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');
		overlay.setAttribute('aria-labelledby', 'medidataReleaseTitle');

		const panel = document.createElement('div');
		panel.className = 'medidata-release-panel';

		const ulHtml = MEDIDATA_UI_RELEASE_ITEMS.map(function (row) {
			return '<li><strong>' + row.title + ':</strong> ' + row.text + '</li>';
		}).join('');

		panel.innerHTML =
			'<span class="medidata-release-badge">Actualización MEDIDATA</span>' +
			'<h2 id="medidataReleaseTitle">Mejoras en menú y cabecera</h2>' +
			'<p class="medidata-release-lead">' + MEDIDATA_UI_RELEASE_LEAD + '</p>' +
			'<ul>' +
			ulHtml +
			'</ul>' +
			'<div class="medidata-release-actions">' +
			'<button type="button" class="medidata-release-btn">Entendido</button>' +
			'</div>';

		overlay.appendChild(panel);
		document.body.appendChild(overlay);

		function closeIt() {
			dismissOverlay(overlay);
			document.removeEventListener('keydown', onEscape);
		}

		function onEscape(e) {
			if (e.key === 'Escape') {
				closeIt();
			}
		}
		document.addEventListener('keydown', onEscape);

		panel.querySelector('.medidata-release-btn').addEventListener('click', closeIt);

		overlay.addEventListener('click', function (e) {
			if (e.target === overlay) {
				closeIt();
			}
		});
	}, 520);
})();