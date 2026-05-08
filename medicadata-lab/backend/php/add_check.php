<?php
if (isset($_POST['order'])) {
    try {
        date_default_timezone_set('America/Tegucigalpa');

        // === VERIFICAR Y CREAR COLUMNAS AUTOMÁTICAMENTE ANTES DE LA TRANSACCIÓN ===
        try {
            // Verificar si las columnas existen y crearlas si no están
            $columnas_verificar = [
                'remite' => "ALTER TABLE orders ADD COLUMN remite VARCHAR(255) DEFAULT NULL",
                'telefono_remite' => "ALTER TABLE orders ADD COLUMN telefono_remite VARCHAR(20) DEFAULT NULL",
                'tipo_tarjeta' => "ALTER TABLE orders ADD COLUMN tipo_tarjeta VARCHAR(50) DEFAULT NULL",
                'banco_emisor' => "ALTER TABLE orders ADD COLUMN banco_emisor VARCHAR(100) DEFAULT NULL",
                'pos_cobrado' => "ALTER TABLE orders ADD COLUMN pos_cobrado VARCHAR(100) DEFAULT NULL",
                'banco_transferencia' => "ALTER TABLE orders ADD COLUMN banco_transferencia VARCHAR(100) DEFAULT NULL",
                'num_referencia' => "ALTER TABLE orders ADD COLUMN num_referencia VARCHAR(100) DEFAULT NULL",
                'tipo_pago_mixto' => "ALTER TABLE orders ADD COLUMN tipo_pago_mixto VARCHAR(100) DEFAULT NULL",
                'banco_boton_pago' => "ALTER TABLE orders ADD COLUMN banco_boton_pago VARCHAR(100) DEFAULT NULL",
                'num_referencia_boton_pago' => "ALTER TABLE orders ADD COLUMN num_referencia_boton_pago VARCHAR(100) DEFAULT NULL",
                'banco_transferencia_local' => "ALTER TABLE orders ADD COLUMN banco_transferencia_local VARCHAR(100) DEFAULT NULL",
                'num_referencia_transferencia_local' => "ALTER TABLE orders ADD COLUMN num_referencia_transferencia_local VARCHAR(100) DEFAULT NULL",
                'banco_transferencia_internacional' => "ALTER TABLE orders ADD COLUMN banco_transferencia_internacional VARCHAR(100) DEFAULT NULL",
                'num_referencia_transferencia_internacional' => "ALTER TABLE orders ADD COLUMN num_referencia_transferencia_internacional VARCHAR(100) DEFAULT NULL",
                'monto_recibido' => "ALTER TABLE orders ADD COLUMN monto_recibido DECIMAL(10,2) DEFAULT NULL COMMENT 'Monto recibido del cliente en efectivo'",
                'cambio_devolver' => "ALTER TABLE orders ADD COLUMN cambio_devolver DECIMAL(10,2) DEFAULT NULL COMMENT 'Cambio a devolver al cliente'",
                'monto_tarjeta_mixto' => "ALTER TABLE orders ADD COLUMN monto_tarjeta_mixto DECIMAL(10,2) DEFAULT NULL COMMENT 'Monto pagado con tarjeta en pago mixto'",
                'monto_efectivo_mixto' => "ALTER TABLE orders ADD COLUMN monto_efectivo_mixto DECIMAL(10,2) DEFAULT NULL COMMENT 'Monto pagado en efectivo en pago mixto'",
                'telefono_paciente' => "ALTER TABLE orders ADD COLUMN telefono_paciente VARCHAR(30) DEFAULT NULL COMMENT 'Teléfono del paciente (ambulatorio manual)'",
                'tax_amount' => "ALTER TABLE orders ADD COLUMN tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Impuesto ISV calculado al momento del checkout (suma de items gravados 15%)'"
            ];

            foreach ($columnas_verificar as $columna => $sql_agregar) {
                $stmt_check = $connect->prepare("SHOW COLUMNS FROM orders LIKE '$columna'");
                $stmt_check->execute();
                if ($stmt_check->rowCount() == 0) {
                    $connect->exec($sql_agregar);
                    error_log("Columna '$columna' agregada a la tabla orders.");
                }
            }

            // === NUEVO: Verificar y modificar la columna remitente para permitir NULL ===
            $stmt_check_remitente = $connect->prepare("SHOW COLUMNS FROM orders LIKE 'remitente'");
            $stmt_check_remitente->execute();
            $columna_info = $stmt_check_remitente->fetch(PDO::FETCH_ASSOC);
            
            if ($columna_info && $columna_info['Null'] === 'NO') {
                // La columna existe pero no permite NULL, modificarla
                $connect->exec("ALTER TABLE orders MODIFY COLUMN remitente VARCHAR(100) DEFAULT NULL");
                error_log("Columna 'remitente' modificada para permitir NULL.");
            }

            // === NUEVO: Verificar y modificar la columna edad para permitir NULL ===
            $stmt_check_edad = $connect->prepare("SHOW COLUMNS FROM orders LIKE 'edad'");
            $stmt_check_edad->execute();
            $columna_edad_info = $stmt_check_edad->fetch(PDO::FETCH_ASSOC);
            
            if ($columna_edad_info && $columna_edad_info['Null'] === 'NO') {
                // La columna existe pero no permite NULL, modificarla
                $connect->exec("ALTER TABLE orders MODIFY COLUMN edad INT DEFAULT NULL");
                error_log("Columna 'edad' modificada para permitir NULL.");
            }

        } catch (Exception $e) {
            error_log("Error al verificar/crear columnas: " . $e->getMessage());
            // No lanzar excepción aquí, solo registrar el error
        }

        $placed_on = date('Y-m-d H:i:s');
        $connect->beginTransaction();

        // Datos del formulario
        $user_id = $_POST['pdrus'];
        $nomcl_dynamic = $_POST['nomcl_dynamic'] ?? null;
        $nomcl_manual = $_POST['nomcl_manual'] ?? null;
        $dni_dynamic = $_POST['dni_paciente'] ?? null;
        $dni_manual = $_POST['dni_manual'] ?? null;
        $edad_manual = $_POST['edad_manual'] ?? null;
        $telefono_manual = !empty($_POST['telefono_manual']) ? trim($_POST['telefono_manual']) : null;

        // === PACIENTES AMBULATORIOS: guardar en patients_ambulatorios para futuras ocasiones ===
        // (La tabla se crea UNA SOLA VEZ al abrir checkout.php; aquí solo hacemos INSERT)
        try {
            $nombre_ambulatorio = trim($nomcl_manual ?? '');
            if ($nombre_ambulatorio !== '') {
                $partes = preg_split('/\s+/', $nombre_ambulatorio, 2);
                $nompa_amb = trim($partes[0]);
                $apepa_amb = isset($partes[1]) ? trim($partes[1]) : '-';
                $dni_amb = trim($dni_manual ?? '') !== '' ? trim($dni_manual) : 'N/A';
                $cump_amb = null;
                if (!empty($edad_manual) && is_numeric($edad_manual) && (int)$edad_manual >= 0 && (int)$edad_manual <= 120) {
                    $year_nac = (int)date('Y') - (int)$edad_manual;
                    $cump_amb = $year_nac . '-01-01';
                }

                $ya_existe = false;
                if ($dni_amb !== 'N/A' && $dni_amb !== '') {
                    $stmt_ex = $connect->prepare("SELECT id FROM patients_ambulatorios WHERE numhs = ? LIMIT 1");
                    $stmt_ex->execute([$dni_amb]);
                    $ya_existe = $stmt_ex->rowCount() > 0;
                }
                if (!$ya_existe) {
                    $nombre_completo_buscar = trim($nompa_amb . ' ' . $apepa_amb);
                    $stmt_ex2 = $connect->prepare("SELECT id FROM patients_ambulatorios WHERE TRIM(CONCAT(IFNULL(nompa,''), ' ', IFNULL(apepa,''))) = ? LIMIT 1");
                    $stmt_ex2->execute([$nombre_completo_buscar]);
                    $ya_existe = $stmt_ex2->rowCount() > 0;
                }

                if (!$ya_existe) {
                    // Asegurar que la columna telefono existe en patients_ambulatorios
                    try {
                        $stmt_col = $connect->prepare("SHOW COLUMNS FROM patients_ambulatorios LIKE 'telefono'");
                        $stmt_col->execute();
                        if ($stmt_col->rowCount() == 0) {
                            $connect->exec("ALTER TABLE patients_ambulatorios ADD COLUMN telefono VARCHAR(30) DEFAULT NULL");
                        }
                    } catch (Exception $e) { /* ignorar si ya existe */ }
                    $stmt_ins = $connect->prepare("INSERT INTO patients_ambulatorios (nompa, apepa, numhs, cump, telefono) VALUES (?, ?, ?, ?, ?)");
                    $stmt_ins->execute([$nompa_amb, $apepa_amb, $dni_amb, $cump_amb, $telefono_manual]);
                    error_log("Paciente ambulatorio guardado: " . $nombre_ambulatorio);
                }
            }
        } catch (Exception $e) {
            error_log("Error al guardar paciente ambulatorio: " . $e->getMessage());
        }

        // Determinar el nombre y DNI finales (OPCIONALES)
        $nomcl = !empty($nomcl_dynamic) ? $nomcl_dynamic : $nomcl_manual;
        $dni_final = !empty($dni_dynamic) ? $dni_dynamic : $dni_manual; // Se guarda siempre en dni_paciente

        // Los campos del paciente NO son obligatorios - solo informativos
        if (empty($nomcl)) {
            $nomcl = 'Paciente Ambulatorio'; // Valor por defecto si no hay nombre
        }

        if (empty($dni_final)) {
            $dni_final = 'N/A'; // Valor por defecto si no hay DNI
        }

        // Teléfono del paciente: solo cuando es ambulatorio manual
        $telefono_paciente = !empty($nomcl_manual) ? $telefono_manual : null;

        // Calcular o asignar la edad final (COMPLETAMENTE OPCIONAL)
        $edad_dynamic = $_POST['edad_dynamic'] ?? '';
        $edad_manual = $_POST['edad_manual'] ?? '';
        
        // La edad es completamente opcional - puede estar vacía
        if (!empty($edad_dynamic)) {
            $edad_final = $edad_dynamic;
        } elseif (!empty($edad_manual)) {
            $edad_final = $edad_manual;
        } else {
            $edad_final = null; // No hay edad, es válido
        }

        // Solo validar si se proporcionó una edad y es inválida
        if ($edad_final !== null && (!is_numeric($edad_final) || $edad_final <= 0 || $edad_final > 120)) {
            error_log("Advertencia: Edad inválida detectada: " . json_encode(['edad_dynamic' => $edad_dynamic, 'edad_manual' => $edad_manual]));
            $edad_final = null; // Asignar null si la edad es inválida
        }

        error_log("Datos del paciente procesados correctamente: " . json_encode([
            'nombre' => $nomcl,
            'dni_paciente' => $dni_final,
            'edad' => $edad_final
        ]));

        // Datos adicionales del formulario - AHORA OPCIONALES
        $tipo = $_POST['tipo'] ?? null;
        $remitente = !empty($_POST['remitente']) ? $_POST['remitente'] : null; // Opcional ahora
        $remite = !empty($_POST['remite']) ? $_POST['remite'] : null; // Nuevo campo opcional
        $telefono_remite = !empty($_POST['telefono_remite']) ? $_POST['telefono_remite'] : null; // Nuevo campo opcional
        $method = $_POST['cxtcre'] ?? null;
        $tipc = $_POST['cxcom'] ?? 'Boleta';

        // === VALIDACIÓN DE CAMPOS OBLIGATORIOS ===
        if (empty($tipo)) {
            throw new Exception("El campo 'Tipo' es obligatorio.");
        }
        if (empty($method)) {
            throw new Exception("El campo 'Método de Pago' es obligatorio.");
        }
        
        // Asignar "N/A" a campos opcionales si están vacíos o son NULL
        $pagador = !empty($_POST['pagador']) ? $_POST['pagador'] : 'N/A';
        $rtn_pagador = !empty($_POST['rtn_pagador']) ? $_POST['rtn_pagador'] : 'N/A';
        $num_poliza = !empty($_POST['num_poliza']) ? $_POST['num_poliza'] : 'N/A';
        $num_cuenta = !empty($_POST['num_cuenta']) ? $_POST['num_cuenta'] : 'N/A';
        
        // Capturar datos de tarjeta (para Tarjeta y Crédito Colaborador)
        $tipo_tarjeta = !empty($_POST['tipo_tarjeta']) ? $_POST['tipo_tarjeta'] : NULL;
        $banco_emisor = !empty($_POST['banco_emisor']) ? trim($_POST['banco_emisor']) : NULL; // Eliminar espacios
        $pos_cobrado = !empty($_POST['pos_cobrado']) ? $_POST['pos_cobrado'] : NULL;
        
        // Log para depuración de tarjetas
        if ($method === 'Tarjeta') {
            error_log("===== GUARDAR ORDEN CON TARJETA =====");
            error_log("Tipo tarjeta: " . var_export($tipo_tarjeta, true));
            error_log("Banco emisor RAW: '" . var_export($banco_emisor, true) . "'");
            error_log("Banco emisor length: " . strlen($banco_emisor ?? ''));
            error_log("POS cobrado: " . var_export($pos_cobrado, true));
            error_log("======================================");
        }
        
        // Capturar datos de transferencia (para Transferencia)
        $banco_transferencia = !empty($_POST['banco_transferencia']) ? $_POST['banco_transferencia'] : NULL;
        $num_referencia = !empty($_POST['num_referencia']) ? $_POST['num_referencia'] : NULL;
        
        // Capturar datos de pago mixto (para Pago Mixto)
        $tipo_pago_mixto = !empty($_POST['tipo_pago_mixto']) ? $_POST['tipo_pago_mixto'] : NULL;
        $monto_tarjeta_mixto = !empty($_POST['monto_tarjeta_mixto']) ? floatval($_POST['monto_tarjeta_mixto']) : NULL;
        $monto_efectivo_mixto = !empty($_POST['monto_efectivo_mixto']) ? floatval($_POST['monto_efectivo_mixto']) : NULL;
        
        // Capturar datos de efectivo (para Efectivo)
        $monto_recibido = !empty($_POST['monto_recibido']) ? floatval($_POST['monto_recibido']) : NULL;
        $cambio_devolver = !empty($_POST['cambio_devolver']) ? floatval($_POST['cambio_devolver']) : NULL;
        
        // Capturar datos de botón de pago
        $banco_boton_pago = !empty($_POST['banco_boton_pago']) ? $_POST['banco_boton_pago'] : NULL;
        $num_referencia_boton_pago = !empty($_POST['num_referencia_boton_pago']) ? $_POST['num_referencia_boton_pago'] : NULL;
        
        // Capturar datos de transferencia local
        $banco_transferencia_local = !empty($_POST['banco_transferencia_local']) ? $_POST['banco_transferencia_local'] : NULL;
        $num_referencia_transferencia_local = !empty($_POST['num_referencia_transferencia_local']) ? $_POST['num_referencia_transferencia_local'] : NULL;
        
        // Capturar datos de transferencia internacional
        $banco_transferencia_internacional = !empty($_POST['banco_transferencia_internacional']) ? $_POST['banco_transferencia_internacional'] : NULL;
        $num_referencia_transferencia_internacional = !empty($_POST['num_referencia_transferencia_internacional']) ? $_POST['num_referencia_transferencia_internacional'] : NULL;
        
        $processed_by = $_SESSION['name'];

        $cart_grand_total = '0.00';
        $cart_original_total = '0.00';
        $total_discount_amount = '0.00';
        $cart_products_list = [];

        // Generar número de factura
        // Número de factura inicial: 410000 (según CAI autorizado)
        $numeroFacturaInicial = 410000;
        
        $stmt = $connect->prepare("SELECT MAX(invoice_number) AS last_invoice FROM orders");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastInvoice = $result['last_invoice'] ?? "000-001-01-0000000";
        
        // Extraer los últimos 7 dígitos del último número de factura
        $ultimoNumero = (int)substr($lastInvoice, -7);
        
        // Si el último número es menor al inicial, usar el inicial
        // Si es mayor o igual, continuar con la secuencia
        $nuevoNumero = max($ultimoNumero + 1, $numeroFacturaInicial);
        
        $invoice_number = sprintf("000-001-01-%07d", $nuevoNumero);

        // Consultar carrito
        $cart_query = $connect->prepare("
            SELECT cart.idv, cart.name, cart.price, cart.quantity, 
                   cart.discount, cart.age_discount_30, cart.age_discount_40,
                   cart.promotion_discount, cart.other_discount, cart.type,
                   cart.idprcd,
                   product.idprcd AS product_id, 
                   servicios_hospital.id AS service_id,
                   almacen_hospitalario.idprcd AS hospitalario_id,
                   product.codpro AS prod_cod, 
                   servicios_hospital.codigo_servicio AS serv_cod,
                   almacen_hospitalario.codpro AS hosp_cod,
                   product.impuesto AS prod_impuesto,
                   servicios_hospital.impuesto AS serv_impuesto,
                   almacen_hospitalario.impuesto AS hosp_impuesto
            FROM cart 
            LEFT JOIN product ON cart.idprcd = product.idprcd AND cart.type = 'producto'
            LEFT JOIN servicios_hospital ON cart.id_servicio = servicios_hospital.id AND cart.type = 'servicio'
            LEFT JOIN almacen_hospitalario ON cart.id_producto_hospitalario = almacen_hospitalario.idprcd AND cart.type = 'producto_hospitalario'
            WHERE cart.user_id = ?
        ");
        $cart_query->execute([$user_id]);

        if ($cart_query->rowCount() > 0) {
            // 1. PRIMER RECORRIDO: Calcular totales antes de insertar la orden
            $cart_query->execute([$user_id]);
            $cart_items = $cart_query->fetchAll(PDO::FETCH_ASSOC);
            if (count($cart_items) === 0) {
                throw new Exception("El carrito está vacío.");
            }
            $cart_grand_total = '0.00';
            $cart_original_total = '0.00';
            $total_discount_amount = '0.00';
            $tax_amount_total = '0.00';
            foreach ($cart_items as $cart_item) {
                // Detectar si es PLACA DE RAYOS X (no debe sumarse al total de la orden)
                $item_name = $cart_item['name'] ?? '';
                $es_placa = (stripos($item_name, 'PLACA') !== false && stripos($item_name, 'RAYOS') !== false);
                
                // Si es la placa, saltarla del cálculo de totales (pero se guardará en order_details para contabilidad)
                if ($es_placa) {
                    continue; // No sumar al total, pero seguirá en el carrito para guardarse en order_details
                }
                
                $price = (float)$cart_item['price'];
                $quantity = (int)$cart_item['quantity'];
                $sub_total = bcmul($price, $quantity, 2);

                $item_discount = bcmul($sub_total, bcdiv((float)($cart_item['discount'] ?? '0.00'), '100', 2), 2);
                $promotion_discount = bcmul($sub_total, bcdiv((float)($cart_item['promotion_discount'] ?? '0.00'), '100', 2), 2);
                // Otros descuento ahora es monto fijo en Lempiras, no porcentaje
                $other_discount = (string)floatval($cart_item['other_discount'] ?? '0.00');
                $age_discount_30 = $cart_item['age_discount_30'] ? bcmul($sub_total, '0.30', 2) : '0.00';
                $age_discount_40 = $cart_item['age_discount_40'] ? bcmul($sub_total, '0.40', 2) : '0.00';

                $total_discount = bcadd(
                    bcadd(bcadd($item_discount, $promotion_discount, 2), $other_discount, 2),
                    bcadd($age_discount_30, $age_discount_40, 2),
                    2
                );
                $total_after_discount = bcsub($sub_total, $total_discount, 2);

                // ISV (15%) - se considera "gravado" cualquier flag que NO sea 'E','N','0' (exento/no-aplica).
                // El precio del carrito ya incluye el ISV, por lo que se extrae con la fórmula 15/115.
                $flag_imp_raw = '';
                switch ($cart_item['type']) {
                    case 'producto':
                        $flag_imp_raw = (string)($cart_item['prod_impuesto'] ?? '');
                        break;
                    case 'servicio':
                        $flag_imp_raw = (string)($cart_item['serv_impuesto'] ?? '');
                        break;
                    case 'producto_hospitalario':
                        $flag_imp_raw = (string)($cart_item['hosp_impuesto'] ?? '');
                        break;
                }
                $flag_imp = strtoupper(trim($flag_imp_raw));
                $es_gravado = !in_array($flag_imp, ['', 'E', 'N', '0'], true);
                $item_tax = $es_gravado ? bcdiv(bcmul($total_after_discount, '15', 4), '115', 2) : '0.00';

                $cart_original_total = bcadd($cart_original_total, $sub_total, 2);
                $cart_grand_total = bcadd($cart_grand_total, $total_after_discount, 2);
                $total_discount_amount = bcadd($total_discount_amount, $total_discount, 2);
                $tax_amount_total = bcadd($tax_amount_total, $item_tax, 2);
            }

            // 2. Insertar la orden con los totales correctos - INCLUYENDO NUEVOS CAMPOS DE TARJETA, EFECTIVO Y PAGO MIXTO
            $insert_order = $connect->prepare("
                INSERT INTO orders (
                    user_id, nomcl, dni_paciente, edad, tipo, remitente, remite, telefono_remite, method, 
                    total_price, price_without_discount, placed_on, 
                    payment_status, tipc, pagador, rtn_pagador, num_poliza, num_cuenta, 
                    discount_amount, tax_amount, processed_by, invoice_number, tipo_tarjeta, banco_emisor, pos_cobrado,
                    banco_transferencia, num_referencia, tipo_pago_mixto, banco_boton_pago, num_referencia_boton_pago,
                    banco_transferencia_local, num_referencia_transferencia_local, banco_transferencia_internacional, num_referencia_transferencia_internacional,
                    monto_recibido, cambio_devolver, monto_tarjeta_mixto, monto_efectivo_mixto, telefono_paciente
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aceptado', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Manejar la edad para que sea compatible con la base de datos
            $edad_db = ($edad_final !== null && $edad_final > 0) ? $edad_final : null;
            
            $insert_order->execute([
                $user_id,
                $nomcl,
                $dni_final,
                $edad_db,              // Edad opcional, puede ser NULL
                $tipo,
                $remitente,        // Opcional
                $remite,           // Nuevo campo opcional
                $telefono_remite,  // Nuevo campo opcional
                $method,
                $cart_grand_total,
                $cart_original_total,
                $placed_on,
                $tipc,
                $pagador,
                $rtn_pagador,
                $num_poliza,
                $num_cuenta,
                $total_discount_amount,
                $tax_amount_total,    // ISV (15%) extraído de items gravados
                $processed_by,
                $invoice_number,
                $tipo_tarjeta,         // Campo de tarjeta (Tarjeta/Crédito)
                $banco_emisor,         // Campo de tarjeta (Tarjeta/Crédito)
                $pos_cobrado,          // Campo de tarjeta (Tarjeta/Crédito)
                $banco_transferencia,  // Campo de transferencia
                $num_referencia,       // Campo de transferencia
                $tipo_pago_mixto,       // Campo de pago mixto
                $banco_boton_pago,     // Campo de botón de pago
                $num_referencia_boton_pago, // Campo de botón de pago
                $banco_transferencia_local, // Campo de transferencia local
                $num_referencia_transferencia_local, // Campo de transferencia local
                $banco_transferencia_internacional, // Campo de transferencia internacional
                $num_referencia_transferencia_internacional, // Campo de transferencia internacional
                $monto_recibido,       // Campo de efectivo: monto recibido
                $cambio_devolver,      // Campo de efectivo: cambio a devolver
                $monto_tarjeta_mixto,  // Campo de pago mixto: monto en tarjeta
                $monto_efectivo_mixto, // Campo de pago mixto: monto en efectivo
                $telefono_paciente    // Teléfono del paciente (ambulatorio manual)
            ]);

            // Obtener el ID de la orden recién insertada
            $order_id = $connect->lastInsertId();

            // 3. SEGUNDO RECORRIDO: insertar los detalles usando el array $cart_items
            foreach ($cart_items as $cart_item) {
                $price = (float)$cart_item['price'];
                $quantity = (int)$cart_item['quantity'];
                $sub_total = bcmul($price, $quantity, 2);

                $item_discount = bcmul($sub_total, bcdiv((float)($cart_item['discount'] ?? '0.00'), '100', 2), 2);
                $promotion_discount = bcmul($sub_total, bcdiv((float)($cart_item['promotion_discount'] ?? '0.00'), '100', 2), 2);
                // Otros descuento ahora es monto fijo en Lempiras, no porcentaje
                $other_discount = (string)floatval($cart_item['other_discount'] ?? '0.00');
                $age_discount_30 = $cart_item['age_discount_30'] ? bcmul($sub_total, '0.30', 2) : '0.00';
                $age_discount_40 = $cart_item['age_discount_40'] ? bcmul($sub_total, '0.40', 2) : '0.00';

                $total_discount = bcadd(
                    bcadd(bcadd($item_discount, $promotion_discount, 2), $other_discount, 2),
                    bcadd($age_discount_30, $age_discount_40, 2),
                    2
                );
                $total_after_discount = bcsub($sub_total, $total_discount, 2);

                // Insertar detalle en order_details
                $insert_detail = $connect->prepare("
                    INSERT INTO order_details (
                        order_id, 
                        product_id, 
                        service_id,
                        hospitalario_id,
                        codpro, 
                        descripcion, 
                        cantidad, 
                        discount_percentage, 
                        age_discount_30, 
                        age_discount_40, 
                        promotion_discount, 
                        other_discount, 
                        total_discount, 
                        total_after_discount, 
                        item_type
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                // Determinar el código, nombre y tipo según el tipo
                $codigo = '';
                $descripcion = '';
                $item_type = $cart_item['type'];
                switch ($cart_item['type']) {
                    case 'producto':
                        $codigo = $cart_item['prod_cod'];
                        $descripcion = $cart_item['name'];
                        $item_type = 'producto';
                        break;
                    case 'servicio':
                        $codigo = $cart_item['serv_cod'];
                        $descripcion = $cart_item['name'];
                        $item_type = 'servicio';
                        break;
                    case 'producto_hospitalario':
                        $codigo = $cart_item['hosp_cod']; // Código real del hospitalario
                        $descripcion = $cart_item['name'];
                        $item_type = 'producto'; // Guardar como producto
                        break;
                }

                $insert_detail->execute([
                    $order_id,
                    $cart_item['type'] === 'producto' ? $cart_item['product_id'] : null,
                    $cart_item['type'] === 'servicio' ? $cart_item['service_id'] : null,
                    $cart_item['type'] === 'producto_hospitalario' ? $cart_item['hospitalario_id'] : null,
                    $codigo,
                    $descripcion,
                    $cart_item['quantity'],
                    $item_discount,
                    $age_discount_30,
                    $age_discount_40,
                    $promotion_discount,
                    $other_discount,
                    $total_discount,
                    $total_after_discount,
                    $item_type // Aquí se guarda el tipo correcto
                ]);

                // Actualizar stock según el tipo de item
                if ($cart_item['type'] === 'producto') {
                    // Usar cart.idprcd si product_id es null (por si el JOIN falla)
                    $product_id_to_use = $cart_item['product_id'] ?? $cart_item['idprcd'];
                    
                    if ($product_id_to_use) {
                    $update_stock = $connect->prepare("UPDATE product SET stock = stock - ? WHERE idprcd = ?");
                        $update_stock->execute([$cart_item['quantity'], $product_id_to_use]);
                    }
                } elseif ($cart_item['type'] === 'producto_hospitalario' && $cart_item['hospitalario_id']) {
                    $update_stock = $connect->prepare("UPDATE almacen_hospitalario SET stock = stock - ? WHERE idprcd = ?");
                    $update_stock->execute([$cart_item['quantity'], $cart_item['hospitalario_id']]);
                }
            }

            // Insertar detalles y vaciar carrito
            $delete_cart = $connect->prepare("DELETE FROM cart WHERE user_id = ?");
            $delete_cart->execute([$user_id]);

            $connect->commit();

            echo '<script type="text/javascript">
            swal("¡Registrado!", "Compra completada correctamente", "success").then(function() {
                window.location = "venta.php";
            });
            </script>';
        } else {
            throw new Exception("El carrito está vacío.");
        }
    } catch (Exception $e) {
        // Verificar si hay una transacción activa antes de hacer rollback
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        error_log($e->getMessage());
        echo '<script type="text/javascript">
        swal("Error!", "Hubo un problema al procesar el pago: ' . $e->getMessage() . '", "error");
        </script>';
    }
}
?>