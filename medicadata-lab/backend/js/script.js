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