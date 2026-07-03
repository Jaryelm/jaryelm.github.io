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

    window.medidataComprasReporte = {
        pagina: 'reporte_compras_ingresadas.php',
        endpoint: '../../backend/registros/gestion_compra.php',

        init: function(opciones) {
            this.pagina = opciones.pagina || this.pagina;
            this.endpoint = opciones.endpoint || this.endpoint;
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;
            $(document).on('click', '.btn-editar-compra', function() {
                self.abrirEditar(parseInt($(this).data('id'), 10));
            });
            $(document).on('click', '.btn-eliminar-compra', function() {
                self.confirmarEliminar(parseInt($(this).data('id'), 10));
            });
            $('#formEditarCompra').on('submit', function(ev) {
                ev.preventDefault();
                self.guardarEdicion();
            });
        },

        abrirEditar: function(idCompra) {
            var self = this;
            $.post(self.endpoint, { accion: 'obtener', id_compra: idCompra }, null, 'json')
                .done(function(resp) {
                    if (!resp || !resp.success || !resp.compra) {
                        Swal.fire('Error', (resp && resp.message) ? resp.message : 'No se pudo cargar la compra.', 'error');
                        return;
                    }
                    var c = resp.compra;
                    $('#editCompraId').val(c.id_compra);
                    $('#editCompraFecha').val(c.fecha_emision || '');
                    $('#editCompraProveedor').val(c.prov_datos || '');
                    $('#editCompraFactura').val(c.dato_fac || '');
                    $('#editCompraSubtotal').val(parseFloat(c.sub_total || 0).toFixed(2));
                    $('#editCompraIsv').val(parseFloat(c.isv_global || 0).toFixed(2));
                    $('#editCompraTotal').val(parseFloat(c.total || 0).toFixed(2));
                    $('#editCompraMotivo').val('');

                    var bloqueado = !!c.tiene_pagos;
                    $('#editCompraSubtotal, #editCompraIsv, #editCompraTotal').prop('readonly', bloqueado);
                    $('#avisoCompraPagos').toggle(bloqueado);

                    $('#modalEditarCompra').css('display', 'flex');
                })
                .fail(function() {
                    Swal.fire('Error', 'No se pudo cargar la compra.', 'error');
                });
        },

        guardarEdicion: function() {
            var self = this;
            var payload = $('#formEditarCompra').serializeArray();
            payload.push({ name: 'accion', value: 'actualizar' });

            $.post(self.endpoint, $.param(payload), null, 'json')
                .done(function(resp) {
                    if (!resp || !resp.success) {
                        Swal.fire('Error', (resp && resp.message) ? resp.message : 'No se pudo actualizar.', 'error');
                        return;
                    }
                    var fecha = (resp.data && resp.data.fecha_emision) ? resp.data.fecha_emision : $('#editCompraFecha').val();
                    Swal.fire('Actualizado', resp.message || 'Compra actualizada.', 'success').then(function() {
                        self.recargarConFecha(fecha);
                    });
                })
                .fail(function(xhr) {
                    var msg = 'No se pudo actualizar la compra.';
                    try {
                        var json = JSON.parse(xhr.responseText || '{}');
                        if (json.message) msg = json.message;
                    } catch (e) {}
                    Swal.fire('Error', msg, 'error');
                });
        },

        confirmarEliminar: function(idCompra) {
            var self = this;
            Swal.fire({
                title: 'Eliminar compra',
                html: '<p>Esta acción eliminará la compra, su detalle y la partida contable asociada.</p>' +
                    '<textarea id="swalMotivoEliminar" class="filter-input" rows="3" maxlength="255" ' +
                    'placeholder="Motivo de eliminación (obligatorio)" style="width:100%;margin-top:10px;"></textarea>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                preConfirm: function() {
                    var motivo = ($('#swalMotivoEliminar').val() || '').trim();
                    if (!motivo) {
                        Swal.showValidationMessage('Indique el motivo de eliminación.');
                    }
                    return motivo;
                }
            }).then(function(result) {
                if (!result.isConfirmed) return;
                $.post(self.endpoint, {
                    accion: 'eliminar',
                    id_compra: idCompra,
                    motivo: result.value
                }, null, 'json').done(function(resp) {
                    if (!resp || !resp.success) {
                        Swal.fire('Error', (resp && resp.message) ? resp.message : 'No se pudo eliminar.', 'error');
                        return;
                    }
                    Swal.fire('Eliminado', resp.message || 'Compra eliminada.', 'success').then(function() {
                        window.location.reload();
                    });
                }).fail(function(xhr) {
                    var msg = 'No se pudo eliminar la compra.';
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

    window.cerrarModalEditarCompra = function() {
        $('#modalEditarCompra').hide();
    };

})(window, jQuery);
