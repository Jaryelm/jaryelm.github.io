    function toggleCampos(option) {
        document.getElementById('dias_credito_field').style.display = 'none';
        document.getElementById('prima_fields').style.display = 'none';

        document.getElementById('dias_credito').value = '';
        document.getElementById('porcentaje_prima').value = '';
        document.getElementById('cuotas_pendientes').value = '';

        switch (option) {
            case 'credito':
                document.getElementById('dias_credito_field').style.display = 'block';
                break;
            case 'prima':
                document.getElementById('prima_fields').style.display = 'block';
                break;
        }
        calcularFechaVencimiento(); // recalcula cuando cambia el término de pago
    }

    function calcularFechaVencimiento() {
        const fechaEmision = document.getElementById('fecha_emision').value;
        const terminoPago = document.querySelector('input[name="cred_cont"]:checked')?.value;
        const diasCredito = parseInt(document.getElementById('dias_credito').value) || 0;
        const cuotasPendientes = parseInt(document.getElementById('cuotas_pendientes').value) || 0;
        const fechaVencimiento = document.getElementById('fech_vence');

        if (!fechaEmision || !terminoPago) {
            fechaVencimiento.value = '';
            return;
        }

        let fechaBase = new Date(fechaEmision);

        if (terminoPago === 'Credito') {
            fechaBase.setDate(fechaBase.getDate() + diasCredito);
        } else if (terminoPago === 'Prima') {
            fechaBase.setDate(fechaBase.getDate() + (cuotasPendientes * 30)); // Aproximado de 30 días por cuota
        }

        const vencimientoFormateado = fechaBase.toISOString().split('T')[0];
        fechaVencimiento.value = vencimientoFormateado;
    }