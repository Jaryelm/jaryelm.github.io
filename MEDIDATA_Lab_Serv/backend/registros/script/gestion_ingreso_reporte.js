(function(window, $) {
    'use strict';

    function mesActualRango() {
        var d = new Date();
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var ultimo = new Date(y, d.getMonth() + 1, 0).getDate();
        return {
            desde: y + '-' + m + '-01',
            hasta: y + '-' + m + '-' + String(ultimo).padStart(2, '0')
        };
    }

    function rangoMesDeFecha(fechaYmd) {
        if (!fechaYmd || fechaYmd.length < 7) {
            return mesActualRango();
        }
        var partes = fechaYmd.split('-');
        var y = parseInt(partes[0], 10);
        var m = parseInt(partes[1], 10);
        var ultimo = new Date(y, m, 0).getDate();
        return {
            desde: y + '-' + String(m).padStart(2, '0') + '-01',
            hasta: y + '-' + String(m).padStart(2, '0') + '-' + String(ultimo).padStart(2, '0')
        };
    }

    window.medidataIngresosReporte = {
        pagina: 'reporte_detalle_factura.php',
        endpoint: '../../backend/registros/gestion_ingreso.php',

        init: function(opciones) {
            this.pagina = opciones.pagina || this.pagina;
            this.endpoint = opciones.endpoint || this.endpoint;
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;
            $(document).on('click', '.btn-editar-ingreso', function() {
                self.abrirEditar(parseInt($(this).data('id'), 10));
            });
            $(document).on('click', '.btn-eliminar-ingreso', function() {
                self.confirmarEliminar(parseInt($(this).data('id'), 10));
            });
            $('#formEditarIngreso').on('submit', function(ev) {
                ev.preventDefault();
                self.guardarEdicion();
            });
        },

        abrirEditar: function(idord) {
            var self = this;
            $.post(self.endpoint, { accion: 'obtener', idord: idord }, null, 'json')
                .done(function(resp) {
                    if (!resp || !resp.success || !resp.ingreso) {
                        Swal.fire('Error', (resp && resp.message) ? resp.message : 'No se pudo cargar el ingreso.', 'error');
                        return;
                    }
                    var i = resp.ingreso;
                    $('#editIngresoId').val(i.idord);
                    $('#editIngresoFecha').val(i.placed_on || '');
                    $('#editIngresoFactura').val(i.invoice_number || '');
                    $('#editIngresoMetodo').val(i.method || '');
                    $('#editIngresoTotal').val(parseFloat(i.total_price || 0).toFixed(2));
                    $('#editIngresoMotivo').val('');
                    $('#modalEditarIngreso').css('display', 'flex');
                })
                .fail(function() {
                    Swal.fire('Error', 'No se pudo cargar el ingreso.', 'error');
                });
        },

        guardarEdicion: function() {
            var self = this;
            var payload = $('#formEditarIngreso').serializeArray();
            payload.push({ name: 'accion', value: 'actualizar' });

            $.post(self.endpoint, $.param(payload), null, 'json')
                .done(function(resp) {
                    if (!resp || !resp.success) {
                        Swal.fire('Error', (resp && resp.message) ? resp.message : 'No se pudo actualizar.', 'error');
                        return;
                    }
                    var fecha = (resp.data && resp.data.placed_on) ? resp.data.placed_on : $('#editIngresoFecha').val();
                    Swal.fire('Actualizado', resp.message || 'Ingreso actualizado.', 'success').then(function() {
                        self.recargarConFecha(fecha);
                    });
                })
                .fail(function(xhr) {
                    var msg = 'No se pudo actualizar el ingreso.';
                    try {
                        var json = JSON.parse(xhr.responseText || '{}');
                        if (json.message) msg = json.message;
                    } catch (e) {}
                    Swal.fire('Error', msg, 'error');
                });
        },

        confirmarEliminar: function(idord) {
            var self = this;
            Swal.fire({
                title: 'Revertir ingreso',
                html: '<p>Se eliminará la partida contable y la factura volverá a estado <strong>Pendiente</strong>.</p>' +
                    '<textarea id="swalMotivoEliminarIngreso" class="filter-input" rows="3" maxlength="255" ' +
                    'placeholder="Motivo (obligatorio)" style="width:100%;margin-top:10px;"></textarea>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Revertir',
                cancelButtonText: 'Cancelar',
                preConfirm: function() {
                    var motivo = ($('#swalMotivoEliminarIngreso').val() || '').trim();
                    if (!motivo) {
                        Swal.showValidationMessage('Indique el motivo.');
                    }
                    return motivo;
                }
            }).then(function(result) {
                if (!result.isConfirmed) return;
                $.post(self.endpoint, {
                    accion: 'eliminar',
                    idord: idord,
                    motivo: result.value
                }, null, 'json').done(function(resp) {
                    if (!resp || !resp.success) {
                        Swal.fire('Error', (resp && resp.message) ? resp.message : 'No se pudo revertir.', 'error');
                        return;
                    }
                    Swal.fire('Revertido', resp.message || 'Ingreso revertido.', 'success').then(function() {
                        window.location.reload();
                    });
                }).fail(function(xhr) {
                    var msg = 'No se pudo revertir el ingreso.';
                    try {
                        var json = JSON.parse(xhr.responseText || '{}');
                        if (json.message) msg = json.message;
                    } catch (e) {}
                    Swal.fire('Error', msg, 'error');
                });
            });
        },

        recargarConFecha: function(fechaYmd) {
            var rango = rangoMesDeFecha(fechaYmd);
            window.location.href = this.pagina + '?desde=' + encodeURIComponent(rango.desde) +
                '&hasta=' + encodeURIComponent(rango.hasta);
        },

        limpiarFiltros: function() {
            var rango = mesActualRango();
            window.location.href = this.pagina + '?desde=' + encodeURIComponent(rango.desde) +
                '&hasta=' + encodeURIComponent(rango.hasta);
        }
    };

    window.cerrarModalEditarIngreso = function() {
        $('#modalEditarIngreso').hide();
    };

})(window, jQuery);
