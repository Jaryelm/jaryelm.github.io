(function ($) {
    'use strict';

    function bindStaffActions(cfg) {
        $(cfg.toggleSelector).on('change', function () {
            const id = $(this).data('id');
            const state = this.checked ? 1 : 0;
            const self = this;

            $.ajax({
                type: 'POST',
                url: cfg.toggleUrl,
                data: { id: id, state: state },
                dataType: 'json',
                success: function (response) {
                    if (!response.success) {
                        self.checked = !self.checked;
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    self.checked = !self.checked;
                    Swal.fire('Error', 'No se pudo cambiar el estado', 'error');
                },
            });
        });

        function deleteStaff(id) {
            Swal.fire({
                title: cfg.deleteTitle,
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                const payload = {};
                payload[cfg.idParam] = id;
                $.ajax({
                    type: 'POST',
                    url: cfg.deleteUrl,
                    data: payload,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Eliminado', response.message, 'success').then(function () {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Ocurrió un error en el servidor', 'error');
                    },
                });
            });
        }

        $(cfg.deleteSelector).on('click', function (e) {
            e.preventDefault();
            deleteStaff($(this).data('id'));
        });

        window[cfg.deleteFn] = function (id) {
            deleteStaff(id);
        };
    }

    $(document).ready(function () {
        if (window.MEDIDATA_STAFF_ADMIN) {
            bindStaffActions(window.MEDIDATA_STAFF_ADMIN);
        }
        if (window.MEDIDATA_STAFF_SG) {
            bindStaffActions(window.MEDIDATA_STAFF_SG);
        }
    });
})(jQuery);
