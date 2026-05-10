<!--Ventana Modal-->
<form method="POST">
    <input type="checkbox" id="btns-modal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;"> <!-- Scroll vertical -->
            <h2>Nueva Atención</h2>
            <hr>

            <br>

            <!-- Servicio Área -->
            <label for="servicio"><b>Servicio</b></label><span class="badge-warning">*</span>
            <input type="text" name="servicio" id="servicio" placeholder="Servicio médico asociado" required>

            <!-- No Habitación -->
            <label for="habitacion_no"><b>NO. Habitación</b></label>
            <input type="text" name="habitacion_no" id="habitacion_no" placeholder="Número de habitación">

            <!-- Fecha Hora Ingreso -->
            <label for="fecha_hora_ingreso"><b>Fecha Hora Ingreso</b></label>
            <input type="datetime-local" name="fecha_hora_ingreso" id="fecha_hora_ingreso">

            <!-- Fecha Hora Egreso -->
            <label for="fecha_hora_egreso"><b>Fecha Hora Egreso</b></label>
            <input type="datetime-local" name="fecha_hora_egreso" id="fecha_hora_egreso">

            <!-- Otros campos -->
            <label for="consl"><b>Motivo de la consulta</b></label><span class="badge-warning">*</span>
            <textarea name="consl" id="consl" required placeholder="Escribe algo..." style="height:200px"></textarea>

            <label for="medico_tratante"><b>Médico Tratante</b></label><span class="badge-warning">*</span>
            <input type="text" name="medico_tratante" id="medico_tratante" placeholder="Nombre del médico tratante" required>

            <label for="especialidad"><b>Especialidad</b></label><span class="badge-warning">*</span>
            <input type="text" name="especialidad" id="especialidad" placeholder="Especialidad médica" required>

            <input type="hidden" id="csidpa" name="csidpa" value="<?php echo $d->idpa; ?>">
            <input type="hidden" id="csnopa" name="csnopa" value="<?php echo $d->nompa; ?>">

            <input type="button" class="registerbtn" name="submit" value="Guardar" onclick="enviar();"> 
        </div>
        <label for="btns-modal" class="cerrar-modal"></label>
    </div>
</form>
<!--Fin de Ventana Modal-->