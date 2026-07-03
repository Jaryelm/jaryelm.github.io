<form id="modalForm">
    <input type="checkbox" id="btns-modal-temps" class="modal-input" style="display: none;">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;">
            <h2>Ingrese Datos de Temperatura</h2>
            <hr>
            <input type="hidden" id="turnoActual" name="turno">
            <input type="hidden" id="idpa" name="idpa" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">
            <input type="hidden" id="procesado_por" name="procesado_por" value="<?php echo htmlspecialchars($name); ?>">

            <label for="frecuenciac">Frecuencia Cardiaca:</label>
            <input type="text" id="frecuenciac" name="frecuenciac" placeholder="Ingrese la frecuencia cardiaca">

            <label for="tensiona">Presión Arterial:</label>
            <input type="text" id="tensiona" name="tensiona" placeholder="Ingrese la tensión arterial">

            <label for="temp"><b>Valor de Temperatura: <span style="color:red">*</span></b></label>
            <input type="text" id="temps" name="temps" placeholder="Ingrese la temperatura" required>

            <label for="spo_2">SPO2:</label>
            <input type="text" id="spo_2" name="spo_2" placeholder="Ingrese el SPO2">

            <label for="peso_kg">Peso (kg):</label>
            <input type="text" id="peso_kg" name="peso_kg" placeholder="Ingrese el peso">

            <label for="talla_temp">Talla (cm):</label>
            <input type="text" id="talla_temp" name="talla_temp" placeholder="Ingrese la talla">

            <label for="imc_temp">IMC:</label>
            <input type="text" id="imc_temp" name="imc_temp" placeholder="Ingrese el IMC">

            <label for="glucap_temp">Glucometría:</label>
            <input type="text" id="glucap_temp" name="glucap_temp" placeholder="Ingrese glucometría">

            <label for="fresp_temp">Frecuencia Respiratoria:</label>
            <input type="text" id="fresp_temp" name="fresp_temp" placeholder="Ingrese la frecuencia respiratoria">

            <button type="button" class="registerbtn" onclick="guardarDato()">Guardar</button>
            <label for="btns-modal-temps" class="cerrar-modal">Cerrar</label>
        </div>
    </div>
</form>
