document.addEventListener('DOMContentLoaded', function() {
    const balanceInput = document.getElementById('balance');
    const impuestosInput = document.getElementById('impuestos');
    const montoInput = document.getElementById('monto');
    const impVentasInput = document.getElementById('imp_ventas');
    const totalAsignadoInput = document.getElementById('total_asignado');
    const impuestoInput = document.getElementById('impuesto');
    const fueraBalanceInput = document.getElementById('fuera_balance');

    function calcularFueraDeBalance() {
        const balance = parseFloat(balanceInput.value) || 0;
        const impuestos = parseFloat(impuestosInput.value) || 0;
        const monto = parseFloat(montoInput.value) || 0;
        const impVentas = parseFloat(impVentasInput.value) || 0;
        const totalAsignado = parseFloat(totalAsignadoInput.value) || 0;
        const impuesto = parseFloat(impuestoInput.value) || 0;

        // Calcular la suma total de los montos (excluyendo "Cantidad" y "Asignar Monto")
        const totalSuma = impuestos + monto + impVentas + totalAsignado + impuesto;

        // Verificar si la suma está fuera del balance
        const fueraDeBalance = balance - totalSuma;

        // Actualizar el campo "Fuera de Balance"
        fueraBalanceInput.value = fueraDeBalance.toFixed(2);

        // Cambiar el color del campo "Fuera de Balance" según el desbalance
        if (fueraDeBalance !== 0) {
            fueraBalanceInput.style.backgroundColor = 'red';
            fueraBalanceInput.style.color = 'white';
        } else {
            fueraBalanceInput.style.backgroundColor = '';
            fueraBalanceInput.style.color = '';
        }
    }

    // Escuchar cambios SOLO en los campos relevantes para el cálculo de "Fuera de Balance"
    balanceInput.addEventListener('input', calcularFueraDeBalance);
    impuestosInput.addEventListener('input', calcularFueraDeBalance);
    montoInput.addEventListener('input', calcularFueraDeBalance);
    impVentasInput.addEventListener('input', calcularFueraDeBalance);
    totalAsignadoInput.addEventListener('input', calcularFueraDeBalance);
    impuestoInput.addEventListener('input', calcularFueraDeBalance);
});
