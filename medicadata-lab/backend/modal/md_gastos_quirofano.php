<!-- Modal para registrar Gastos de Quirófano -->
<form method="POST">
    <input type="checkbox" id="gastosquirofano-modal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;"> <!-- Scroll vertical -->
            <h2>Registrar Gastos de Quirófano</h2>
            <hr>

            <br>

            <label for="insumo_material_descartable"><b>Insumo (Material Descartable)</b></label><span class="badge-warning">*</span>
            <input type="text" name="insumo_material_descartable" id="insumo_material_descartable" required>

            <label for="cantidad_material_descartable"><b>Cantidad (Material Descartable)</b></label><span class="badge-warning">*</span>
            <input type="number" name="cantidad_material_descartable" id="cantidad_material_descartable" required>

            <label for="insumo_medicamentos"><b>Insumo (Medicamentos)</b></label><span class="badge-warning">*</span>
            <input type="text" name="insumo_medicamentos" id="insumo_medicamentos" required>

            <label for="cantidad_medicamentos"><b>Cantidad (Medicamentos)</b></label><span class="badge-warning">*</span>
            <input type="number" name="cantidad_medicamentos" id="cantidad_medicamentos" required>

            <label for="insumo_anestesicos"><b>Insumo (Anestésicos)</b></label><span class="badge-warning">*</span>
            <input type="text" name="insumo_anestesicos" id="insumo_anestesicos" required>

            <label for="cantidad_anestesicos"><b>Cantidad (Anestésicos)</b></label><span class="badge-warning">*</span>
            <input type="number" name="cantidad_anestesicos" id="cantidad_anestesicos" required>

            <label for="insumo_equipo_medico"><b>Insumo (Equipo Médico Quirúrgico)</b></label><span class="badge-warning">*</span>
            <input type="text" name="insumo_equipo_medico" id="insumo_equipo_medico" required>

            <label for="cantidad_equipo_medico"><b>Cantidad (Equipo Médico Quirúrgico)</b></label><span class="badge-warning">*</span>
            <input type="number" name="cantidad_equipo_medico" id="cantidad_equipo_medico" required>

            <label for="medico_referente"><b>Médico Anestesiólogo</b></label><span class="badge-warning">*</span>
            <input type="text" name="medico_referente" id="medico_referente" required>

            <label for="cirujano_principal"><b>Cirujano Principal</b></label><span class="badge-warning">*</span>
            <input type="text" name="cirujano_principal" id="cirujano_principal" required>

            <input type="hidden" id="idpa" name="idpa" value="<?php echo $id; ?>">
            <input type="hidden" id="procesado_por" name="procesado_por" value="<?php echo $name; ?>">

            <input type="button" class="registerbtn" name="submit" value="Guardar" onclick="enviarGasto();"> 
        </div>
        <label for="gastosquirofano-modal" class="cerrar-modal"></label>
    </div>
</form>