-- Índices para Diario General (filtros y agrupación por partida)
-- Ejecutar en producción si las consultas del diario siguen lentas.

ALTER TABLE diario_general_transacciones
  ADD INDEX idx_dg_tipo_fecha (tipo_transaccion, fecha_ocurrencia),
  ADD INDEX idx_dg_referencia (referencia(32));

-- order_details: ya existe KEY order_id; verificar en phpMyAdmin antes de añadir:
-- ALTER TABLE order_details ADD INDEX idx_order_details_order_id (order_id);
