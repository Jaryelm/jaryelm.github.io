<?php
/**
 * Formulario compartido editar departamento (admin + RRHH).
 * Variables: $depContext ('admin'|'usr'), $depEditSelf, $depListUrl, $depNewUrl
 */
$depContext = ($depContext ?? 'admin') === 'usr' ? 'usr' : 'admin';
$depEditSelf = $depEditSelf ?? ('departamentos_editar' . ($depContext === 'usr' ? '_usr' : '') . '.php');
$depListUrl = $depListUrl ?? ($depContext === 'usr' ? 'departamentos_usr.php' : 'departamentos.php');
$depNewUrl = $depNewUrl ?? ($depContext === 'usr' ? 'departamentos_nuevo_usr.php' : 'departamentos_nuevo.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = [];

if ($id > 0 && isset($connect_rrhh)) {
    $sentencia = $connect_rrhh->prepare('SELECT * FROM departaments WHERE id = :id');
    $sentencia->bindParam(':id', $id, PDO::PARAM_INT);
    $sentencia->execute();
    while ($r = $sentencia->fetchObject()) {
        $data[] = $r;
    }
}
?>
        <button class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($depNewUrl); ?>')">Registrar Departamento</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($depListUrl); ?>')">Departamentos</button>

<?php if (count($data) > 0): ?>
    <?php foreach ($data as $d): ?>
<form action="" method="POST" autocomplete="off">
  <div class="containerss">
    <h1>Actualizar Departamento</h1>
    <hr>
    <input type="hidden" name="dep_id" value="<?php echo (int) $d->id; ?>">
    <input type="hidden" name="dep_context" value="<?php echo htmlspecialchars($depContext); ?>">

    <label for="dep_code"><b>Código Departamento</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: RRHH-001" value="<?php echo htmlspecialchars($d->departament_code ?? ''); ?>" name="dep_code" required>

    <label for="dep_name"><b>Nombre Departamento</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Recursos Humanos" value="<?php echo htmlspecialchars($d->name ?? ''); ?>" name="dep_name" required>

    <label for="dep_head"><b>Jefe Departamento</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="Nombre completo" value="<?php echo htmlspecialchars($d->head_departament ?? ''); ?>" name="dep_head" required>

    <label for="dep_description"><b>Descripción</b></label><span class="badge-warning">*</span>
    <textarea name="dep_description" style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;" rows="3" required><?php echo htmlspecialchars($d->description ?? ''); ?></textarea>

    <label for="dep_email"><b>Correo Departamento</b></label>
    <input type="email" placeholder="rrhh@medicasa.hn" value="<?php echo htmlspecialchars($d->email ?? ''); ?>" name="dep_email">

    <label for="dep_phone"><b>Teléfono Departamento</b></label>
    <input type="text" placeholder="2234-1001 (opcional)" value="<?php echo htmlspecialchars($d->phone ?? ''); ?>" name="dep_phone" maxlength="10">

    <label for="dep_phone_ext"><b>Extensión (Ext)</b></label>
    <input type="text" placeholder="ejm: 1205" value="<?php echo htmlspecialchars($d->phone_ext ?? ''); ?>" name="dep_phone_ext" maxlength="10">

    <label for="dep_status"><b>Estado</b></label><span class="badge-warning">*</span>
    <select class="select2" required name="dep_status">
        <option value="<?php echo htmlspecialchars($d->status ?? 'Activo'); ?>"><?php echo htmlspecialchars($d->status ?? 'Activo'); ?></option>
        <option value="">-------------------------</option>
        <option value="Activo">Activo</option>
        <option value="Inactivo">Inactivo</option>
    </select>

    <label for="dep_observations"><b>Observaciones</b></label>
    <textarea name="dep_observations" style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;" rows="2"><?php echo htmlspecialchars($d->observations ?? ''); ?></textarea>

    <hr>
    <button type="submit" name="upd_departament" class="registerbtn">Guardar</button>
  </div>
</form>
    <?php endforeach; ?>
<?php else: ?>
    <p class="alert alert-warning">No hay datos</p>
<?php endif; ?>
