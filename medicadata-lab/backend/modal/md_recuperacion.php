<!-- Formulario Modal -->
<form method="POST">
    <input type="checkbox" id="recuperacion-modal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;">
            <h2>Recuperación</h2>
            <hr><br>

            <!-- Sección: Datos Generales -->
            <h3>Datos Generales</h3>
            <br>
            <label>Diagnóstico</label>
            <input type="text" name="diagnostico" required>

            <label>Cirujano Realizada</label>
            <input type="text" name="cirujano_realizada" required>

            <label>Cirujano Principal</label>
            <input type="text" name="cirujano_principal" required>

            <label>Anestesista</label>
            <input type="text" name="anestesista" required>

            <label>Tipo de Anestesia</label>
            <input type="text" name="tipo_anestesia" required>

            <label>Fecha</label>
            <input type="date" name="fecha" required>

            <label>Hora Inicio Cirugía</label>
            <input type="time" name="hora_inicio_cirugia" required>
<br>
            <label>Hora Finaliza Cirugía</label>
            <input type="time" name="hora_fin_cirugia" required>
            <br><br>

<!-- Sección: Cuidados Post Operatorios Inmediatos -->
<h3>Cuidados Post Operatorios Inmediatos</h3>
<br>
<?php 
$cuidados = [
    "Reflejos", "Canula Endotraqueal", "Oxígeno", "Sonda Foley", "Sonda NSG",
    "CVP", "CVC", "Drenos"
];
foreach ($cuidados as $cuidado): ?>
    <div style="display: flex; align-items: center; gap: 20px;">
        <label style="flex: 1;"><?php echo htmlspecialchars($cuidado, ENT_QUOTES, 'UTF-8'); ?></label>
        <div style="display: flex; gap: 10px;">
            <label><input type="radio" name="<?php echo strtolower(str_replace(' ', '_', $cuidado)); ?>" value="Si"> Sí</label>
            <label><input type="radio" name="<?php echo strtolower(str_replace(' ', '_', $cuidado)); ?>" value="No"> No</label>
        </div>
    </div>
<?php endforeach; ?>

<br>
            <label>Tipo</label>
            <input type="text" name="tipo_cuidado">

            <label>Líquidos en Infusión</label>
            <input type="text" name="liquidos_infusion">

            <label>Cantidad</label>
            <input type="text" name="cantidad_liquidos">

            <label>Mezcla</label>
            <input type="text" name="mezcla_liquidos">
            <br><br>

            <!-- Sección: Signos Vitales -->
            <h3>Signos Vitales</h3>
<br>
            <label>Hora</label>
            <input type="time" name="hora_signos" required>
<br>
            <label>PA</label>
            <input type="text" name="pa_signos" required>

            <label>FC</label>
            <input type="text" name="fc_signos" required>

            <label>FR</label>
            <input type="text" name="fr_signos" required>

            <label>TA</label>
            <input type="text" name="ta_signos" required>

            <label>SPO2</label>
            <input type="text" name="spo2_signos" required>
            <br><br>

            <h3>Medicamentos</h3>
<br>
            <label>Medicamento</label>
            <input type="text" name="medicamento">

            <label>Dosis</label>
            <input type="text" name="dosis">

            <label>Vía</label>
            <input type="text" name="via">

            <label>Hora Medicamento</label>
            <input type="time" name="hora_medicamento">
            <br><br>

            <!-- Sección: Control de Líquidos -->
            <h3>Control de Líquidos</h3>
<br>
            <h4>Ingestas</h4>
            <label>Orales</label>
            <input type="text" name="ingestas_orales">

            <label>I/V</label>
            <input type="text" name="ingestas_iv">

            <h4>Excretas</h4>
            <label>Orina</label>
            <input type="text" name="excretas_orina">

            <label>Vómitos</label>
            <input type="text" name="excretas_vomitos">

            <label>Succión</label>
            <input type="text" name="excretas_succion">
            <br><br>

            <!-- Observaciones -->
            <h3>Observaciones</h3>
            <textarea name="observaciones" rows="4"></textarea>
            <br><br>

            <input type="hidden" id="csidpa" name="csidpa" value="<?php echo $d->idpa; ?>">
            <input type="hidden" id="csnopa" name="csnopa" value="<?php echo $d->nompa; ?>">

            <input type="button" class="registerbtn" name="submit" value="Guardar" onclick="enviarRecuperacion();"> 
        </div>
        <label for="recuperacion-modal" class="cerrar-modal"></label>
    </div>
</form>
