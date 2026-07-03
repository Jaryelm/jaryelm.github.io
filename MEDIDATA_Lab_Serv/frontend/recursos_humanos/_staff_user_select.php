<?php
/**
 * Selector opcional de usuario del sistema (id_user) para staff RRHH.
 */
$staffUserFieldName = $staffUserFieldName ?? 'staff_id_user';
$staffSelectedUserId = isset($staffSelectedUserId) ? (int) $staffSelectedUserId : 0;
if (!isset($staffUsers)) {
    $staffUsers = medidata_staff_fetch_users_for_select($connect);
}
?>
<label><b>Usuario del sistema (opcional)</b></label>
<select class="select2" name="<?php echo htmlspecialchars($staffUserFieldName); ?>">
    <option value="">Sin vincular</option>
    <?php foreach ($staffUsers as $usr): ?>
        <option value="<?php echo (int) $usr['id']; ?>" <?php echo (int) $usr['id'] === $staffSelectedUserId ? 'selected' : ''; ?>>
            <?php echo (int) $usr['id']; ?> — <?php echo htmlspecialchars($usr['name']); ?>
            (<?php echo htmlspecialchars($usr['username']); ?> / <?php echo htmlspecialchars($usr['rol'] ?? ''); ?>)
        </option>
    <?php endforeach; ?>
</select>
<p style="font-size:0.85rem;color:#666;margin-top:6px;">
    Si vincula un usuario, la lista de colaboradores usará el ID de <strong>users</strong> y no mostrará un registro duplicado como &quot;Usuario&quot;.
</p>
