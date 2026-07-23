<?php
/**
 * Compatibilidad: el editor unificado RRHH es editar_colaborador_usr.php
 * (incluye Documentos + enlace externo).
 */
include_once '../../backend/registros/session_check.php';

$id = (int) ($_GET['id'] ?? 0);
header('Location: editar_colaborador_usr.php?id=' . $id . '&table=staff_administrative');
exit;
