<!-- Formulario Modal -->
<form method="POST">
    <input type="checkbox" id="medifarma-modal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;">
            <h2>Farmacia MEDIFARMA</h2>
            <hr><br>

            <!-- Sección: Datos Generales -->
            <h3>Datos Generales</h3>
            <br>
            <label>Médico Operando</label>
            <input type="text" name="medico_operando" required>

            <label>Cirugía a Realizar</label>
            <input type="text" name="cirugia_realizar" required>

            <label>Nombre Solicitante</label>
            <input type="text" name="nombre_solicitante" required>

            <br><br>

            <!-- Sección: Medicamento y Material Quirúrgico -->
            <h3>Medicamento y Material Quirúrgico</h3>
            <br>
            <label>Medicamentos</label>
            <input type="text" name="medicamentos" required>

            <label>Material</label>
            <input type="text" name="material" required>

            <label>Cantidad</label>
            <input type="number" name="cantidad" required min="1">

            <br><br>

            <input type="hidden" id="idpa" name="idpa" value="<?php echo $id; ?>">
            <input type="hidden" id="procesado_por" name="procesado_por" value="<?php echo $name; ?>">

            <input type="button" class="registerbtn" value="Guardar" onclick="enviarMedifarma();">
        </div>
        <label for="medifarma-modal" class="cerrar-modal"></label>
    </div>
</form>
