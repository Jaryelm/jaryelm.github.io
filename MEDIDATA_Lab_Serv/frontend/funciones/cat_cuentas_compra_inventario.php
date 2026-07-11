<?php
require '../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/cuentas_compra_inventario_lib.php';

header('Content-Type: text/html; charset=utf-8');
medidata_render_options_cuentas_compra_inventario($connect);
