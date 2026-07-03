(function ($) {
    'use strict';

    function deleteDoctor(id, redirectUrl) {
        Swal.fire({
            title: '¿Eliminar médico?',
            text: 'Se eliminarán también citas, eventos y registros vinculados a este médico.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                type: 'POST',
                url: '../../backend/php/delete_doctor.php',
                data: { idodc: id },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success').then(function () {
                            window.location.href = redirectUrl || 'mostrar.php';
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

    window.deleteDoctor = deleteDoctor;

    $(document).ready(function () {
        $('.doctor-state-toggle').on('change', function () {
            const id = $(this).data('id');
            const state = this.checked ? 1 : 0;
            const self = this;

            $.ajax({
                type: 'POST',
                url: '../../backend/php/toggle_doctor_state.php',
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

        $('.btn-delete-doctor').on('click', function (e) {
            e.preventDefault();
            deleteDoctor($(this).data('id'), $(this).data('redirect') || 'mostrar.php');
        });
    });
})(jQuery);
