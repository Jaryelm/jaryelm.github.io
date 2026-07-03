<?php
/**
 * Enfermería: lista unificada en el tablero (pestaña "Lista de Colaboradores").
 * Esta página antigua queda como redirección para evitar navegación duplicada.
 */
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';
header('Location: ../recursos_humanos/lista_colaboradores.php');
exit;
