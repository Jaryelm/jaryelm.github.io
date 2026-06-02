<?php
require_once('../../backend/bd/Conexion.php');

if (isset($_POST['add_to_cart'])) {
    $user_id = $_POST['pdrus'];
    $name = $_POST['name'];
    $price = $_POST['prec'];
    $quantity = $_POST['p_qty'];
    $type = $_POST['type'];

    // Determinar el tipo de item y asignar el ID correspondiente
    $idprcd = null;
    $id_servicio = null;
    $id_producto_hospitalario = null;
    $codpro = null;

    switch ($type) {
        case 'producto':
            $idprcd = $_POST['prdt'];
            break;
        case 'servicio':
            $id_servicio = $_POST['prdt'];
            break;
        case 'producto_hospitalario':
            $id_producto_hospitalario = $_POST['prdt'];
            // Obtener el código del producto hospitalario
            $stmt = $connect->prepare("SELECT codpro FROM almacen_hospitalario WHERE idprcd = ? LIMIT 1");
            $stmt->execute([$id_producto_hospitalario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $codpro = $row ? $row['codpro'] : null;
            break;
    }

    // Verificar si el item ya está en el carrito
    $check_cart = $connect->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ? AND type = ?");
    $check_cart->execute([$name, $user_id, $type]);

    if ($check_cart->rowCount() > 0) {
        echo '<script type="text/javascript">
        Swal.fire("Error!", "Ya está agregado ", "error").then(function() {
            window.location = "new_sale.php";
        });
        </script>';
    } else {
        try {
            // Insertar el producto o servicio en el carrito con su tipo y ID correspondiente
            $insert_cart = $connect->prepare("INSERT INTO `cart`(user_id, idprcd, id_servicio, id_producto_hospitalario, name, price, quantity, type) VALUES(?,?,?,?,?,?,?,?)");
            $insert_cart->execute([$user_id, $idprcd, $id_servicio, $id_producto_hospitalario, $name, $price, $quantity, $type]);

            // AUTOMATIZACIÓN: Si el servicio es de "RADIOLOGÍA E IMAGEN" o "RADIOGRAFÍA RX DENTAL", agregar automáticamente "PLACA DE RAYOS X"
            if ($type === 'servicio' && $id_servicio) {
                // Verificar la categoría y el código de cuenta contable del servicio
                $stmt_servicio = $connect->prepare("SELECT categoria_servicio, codigo_servicio FROM servicios_hospital WHERE id = ? LIMIT 1");
                $stmt_servicio->execute([$id_servicio]);
                $servicio_data = $stmt_servicio->fetch(PDO::FETCH_ASSOC);
                
                $es_radiologia_imagen = false;
                
                if ($servicio_data) {
                    $categoria_servicio = trim($servicio_data['categoria_servicio'] ?? '');
                    $codigo_servicio = trim($servicio_data['codigo_servicio'] ?? '');
                    
                    // Método 1: Verificar por código de cuenta contable
                    // 410100101 = Radiología e Imagenes
                    // 410100102 = Radiografía RX Dental (Odontología)
                    if ($codigo_servicio === '410100101' || $codigo_servicio === '410100102') {
                        $es_radiologia_imagen = true;
                    }
                    
                    // Método 2: Verificar por categoría (si no se detectó por código)
                    if (!$es_radiologia_imagen && !empty($categoria_servicio)) {
                        // Verificar si es "RADIOLOGÍA E IMAGEN" (con variaciones de encoding)
                        // Buscar "RADIOLOG" e "IMAGEN" por separado para evitar problemas de encoding
                        $es_radiologia = (
                            stripos($categoria_servicio, 'RADIOLOG') !== false || 
                            stripos($categoria_servicio, 'RADIOGRAF') !== false
                        );
                        $es_imagen = stripos($categoria_servicio, 'IMAGEN') !== false;
                        
                        if ($es_radiologia && $es_imagen) {
                            $es_radiologia_imagen = true;
                        }
                    }
                }
                
                if ($es_radiologia_imagen) {
                        
                        // Buscar el insumo "PLACA DE RAYOS X" en la tabla product
                        $stmt_placa = $connect->prepare("
                            SELECT idprcd, nompro, precio_venta, stock 
                            FROM product 
                            WHERE nompro LIKE '%PLACA DE RAYOS X%' 
                            OR nompro LIKE '%PLACA%RAYOS%'
                            LIMIT 1
                        ");
                        $stmt_placa->execute();
                        $placa_data = $stmt_placa->fetch(PDO::FETCH_ASSOC);
                        
                        if ($placa_data) {
                            $placa_id = $placa_data['idprcd'];
                            $placa_nombre = $placa_data['nompro'];
                            $placa_precio = $placa_data['precio_venta'];
                            $placa_stock = intval($placa_data['stock']);
                            
                            // Verificar stock disponible
                            if ($placa_stock >= $quantity) {
                                // Verificar que no esté ya en el carrito (evitar duplicados)
                                $check_placa_cart = $connect->prepare("
                                    SELECT * FROM `cart` 
                                    WHERE user_id = ? 
                                    AND idprcd = ? 
                                    AND type = 'producto'
                                    AND name LIKE '%PLACA%RAYOS%'
                                ");
                                $check_placa_cart->execute([$user_id, $placa_id]);
                                
                                if ($check_placa_cart->rowCount() == 0) {
                                    // Agregar automáticamente "PLACA DE RAYOS X" al carrito con su precio normal
                                    $insert_placa = $connect->prepare("
                                        INSERT INTO `cart`
                                        (user_id, idprcd, id_servicio, id_producto_hospitalario, name, price, quantity, type) 
                                        VALUES(?, ?, ?, ?, ?, ?, ?, ?)
                                    ");
                                    $insert_placa->execute([
                                        $user_id, 
                                        $placa_id,  // idprcd del insumo
                                        null,       // id_servicio (null porque es producto)
                                        null,       // id_producto_hospitalario (null)
                                        $placa_nombre, 
                                        $placa_precio,  // Precio real de la placa
                                        $quantity,  // Misma cantidad que el servicio
                                        'producto'
                                    ]);
                                }
                            }
                        }
                    }
            }

            // Verificar si se agregó una placa automáticamente
            $mensaje_exito = "Agregado correctamente";
            if ($type === 'servicio' && $id_servicio) {
                // Verificar si se agregó la placa
                $check_placa_agregada = $connect->prepare("
                    SELECT COUNT(*) FROM `cart` 
                    WHERE user_id = ? 
                    AND type = 'producto'
                    AND name LIKE '%PLACA%RAYOS%'
                ");
                $check_placa_agregada->execute([$user_id]);
                $placa_count = $check_placa_agregada->fetchColumn();
                
                if ($placa_count > 0) {
                    $mensaje_exito = "Servicio agregado. Se incluyó automáticamente PLACA DE RAYOS X.";
                }
            }
            
            echo '<script type="text/javascript">
            Swal.fire("¡Registrado!", "' . $mensaje_exito . '", "success").then(function() {
                window.location = "new_sale.php";
            });
            </script>';
        } catch (PDOException $e) {
            echo '<script type="text/javascript">
            Swal.fire("Error!", "Error en la inserción: ' . $e->getMessage() . '", "error");
            </script>';
        }
    }
}
