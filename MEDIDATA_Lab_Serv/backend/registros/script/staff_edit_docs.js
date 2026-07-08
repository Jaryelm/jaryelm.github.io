(function () {
    'use strict';

    function getDocGrid() {
        return document.querySelector('.staff-doc-grid[data-staff-id]');
    }

    function getDocContext() {
        var grid = getDocGrid();
        if (!grid) return null;
        return {
            id: parseInt(grid.getAttribute('data-staff-id'), 10) || 0,
            table: grid.getAttribute('data-staff-table') || 'staff_administrative',
            endpoint: grid.getAttribute('data-endpoint') || '../../backend/php/staff_doc_manage.php'
        };
    }

    function swalLoading(title) {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            title: title || 'Procesando...',
            allowOutsideClick: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });
    }

    function updateFilenameHint(input) {
        var card = input.closest('.staff-doc-card');
        if (!card) return;
        var hint = card.querySelector('.staff-doc-card__filename');
        if (!hint) return;
        if (input.files && input.files.length > 0) {
            hint.textContent = 'Seleccionado: ' + input.files[0].name;
            hint.classList.add('is-selected');
        } else {
            hint.textContent = hint.getAttribute('data-empty') || 'Ningún archivo seleccionado';
            hint.classList.remove('is-selected');
        }
    }

    window.subirDocumentoStaff = function (input) {
        var ctx = getDocContext();
        if (!ctx || !ctx.id || !input || !input.files || !input.files.length) return;

        var card = input.closest('.staff-doc-card');
        if (!card) return;

        var docKey = card.getAttribute('data-doc-key');
        if (!docKey) return;

        var formData = new FormData();
        formData.append('action', 'upload');
        formData.append('id', String(ctx.id));
        formData.append('table', ctx.table);
        formData.append('doc', docKey);
        formData.append('file', input.files[0]);

        swalLoading('Subiendo documento...');

        fetch(ctx.endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (typeof Swal === 'undefined') {
                    if (data.status === 'success') location.reload();
                    return;
                }
                if (data.status === 'success') {
                    Swal.fire('Éxito', data.message || 'Documento subido.', 'success').then(function () {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'No se pudo subir el documento.', 'error');
                    input.value = '';
                    updateFilenameHint(input);
                }
            })
            .catch(function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                }
                input.value = '';
                updateFilenameHint(input);
            });
    };

    window.eliminarDocumentoStaff = function (btn) {
        var ctx = getDocContext();
        if (!ctx || !ctx.id || !btn) return;

        var card = btn.closest('.staff-doc-card');
        if (!card) return;

        var docKey = card.getAttribute('data-doc-key');
        var label = card.getAttribute('data-doc-label') || 'este documento';
        if (!docKey) return;

        var confirmFn = function () {
            swalLoading('Eliminando documento...');
            var body = new URLSearchParams();
            body.append('action', 'delete');
            body.append('id', String(ctx.id));
            body.append('table', ctx.table);
            body.append('doc', docKey);

            fetch(ctx.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString(),
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (typeof Swal === 'undefined') {
                        if (data.status === 'success') location.reload();
                        return;
                    }
                    if (data.status === 'success') {
                        Swal.fire('Eliminado', data.message || 'Documento eliminado.', 'success').then(function () {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo eliminar el documento.', 'error');
                    }
                })
                .catch(function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    }
                });
        };

        if (typeof Swal === 'undefined') {
            if (window.confirm('¿Eliminar ' + label + '?')) confirmFn();
            return;
        }

        Swal.fire({
            title: '¿Eliminar documento?',
            text: 'Se eliminará: ' + label + '. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c0392b',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) confirmFn();
        });
    };

    document.addEventListener('change', function (event) {
        var input = event.target;
        if (!input || !input.classList || !input.classList.contains('staff-doc-card__file')) {
            return;
        }
        updateFilenameHint(input);
        if (input.files && input.files.length > 0) {
            subirDocumentoStaff(input);
        }
    });
})();
