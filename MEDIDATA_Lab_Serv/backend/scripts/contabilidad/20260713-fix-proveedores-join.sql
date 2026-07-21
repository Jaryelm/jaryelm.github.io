USE medic9ue_medi_data;

-- Alinear nombres abreviados en compras con el directorio comercial (match único por prefijo).
UPDATE compras c
INNER JOIN proveedor_comercial pc
    ON UPPER(TRIM(pc.nombre_empresa)) LIKE CONCAT(UPPER(TRIM(c.prov_datos)), ' %')
SET c.prov_datos = pc.nombre_empresa
WHERE UPPER(TRIM(c.prov_datos)) <> UPPER(TRIM(pc.nombre_empresa))
  AND (
      SELECT COUNT(*)
      FROM proveedor_comercial pc2
      WHERE UPPER(TRIM(pc2.nombre_empresa)) LIKE CONCAT(UPPER(TRIM(c.prov_datos)), ' %')
  ) = 1;

DELIMITER //

DROP PROCEDURE IF EXISTS sp_rep_compras_encabezado //
CREATE PROCEDURE sp_rep_compras_encabezado(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT
        CP.id_compra,
        COALESCE(PC.nombre_empresa, CP.prov_datos) AS Proveedor,
        CP.fecha_emision      AS Fecha,
        CP.dato_fac           AS NumeroFactura,
        CP.fech_vence         AS Fecha_Vencimiento,
        CP.total              AS ValorFactura,
        COALESCE(Pagos.TotalSaldado, 0) AS TotalSaldado,
        CASE
            WHEN (CP.total - COALESCE(Pagos.TotalSaldado, 0)) <= 0 THEN 'Pagado'
            ELSE 'Pendiente'
        END                   AS Estado
    FROM
        compras CP
    LEFT JOIN proveedor_comercial PC ON PC.id = (
        SELECT pc2.id
        FROM proveedor_comercial pc2
        WHERE UPPER(TRIM(CP.prov_datos)) = UPPER(TRIM(pc2.nombre_empresa))
           OR UPPER(TRIM(pc2.nombre_empresa)) LIKE CONCAT(UPPER(TRIM(CP.prov_datos)), ' %')
        ORDER BY
            CASE WHEN UPPER(TRIM(CP.prov_datos)) = UPPER(TRIM(pc2.nombre_empresa)) THEN 0 ELSE 1 END,
            CHAR_LENGTH(pc2.nombre_empresa) DESC
        LIMIT 1
    )
    LEFT JOIN (
        SELECT
            referencia,
            SUM(debe) AS TotalSaldado
        FROM
            diario_general_transacciones
        WHERE
            cuenta = '210200107'
        GROUP BY
            referencia
    ) AS Pagos
        ON Pagos.referencia = CONCAT('COMP-', CP.id_compra)
    WHERE
        CP.fecha_emision >= p_fecha_inicio
        AND CP.fecha_emision <= p_fecha_fin
    ORDER BY
        CP.fecha_emision DESC;
END //

DROP PROCEDURE IF EXISTS sp_rep_proveedor_comercial_detallado //
CREATE PROCEDURE sp_rep_proveedor_comercial_detallado(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT
        PC.id                                                  AS IdComerciante,
        COALESCE(PC.nombre_empresa, CP.prov_datos)             AS ProveedorComercial,
        SUM(CP.total)                                          AS Total_Comprado,
        COALESCE(SUM(Pagos.TotalSaldado), 0)                   AS Total_Saldado,
        (SUM(CP.total) - COALESCE(SUM(Pagos.TotalSaldado), 0)) AS Saldo_Pendiente
    FROM
        compras CP
    LEFT JOIN proveedor_comercial PC ON PC.id = (
        SELECT pc2.id
        FROM proveedor_comercial pc2
        WHERE UPPER(TRIM(CP.prov_datos)) = UPPER(TRIM(pc2.nombre_empresa))
           OR UPPER(TRIM(pc2.nombre_empresa)) LIKE CONCAT(UPPER(TRIM(CP.prov_datos)), ' %')
        ORDER BY
            CASE WHEN UPPER(TRIM(CP.prov_datos)) = UPPER(TRIM(pc2.nombre_empresa)) THEN 0 ELSE 1 END,
            CHAR_LENGTH(pc2.nombre_empresa) DESC
        LIMIT 1
    )
    LEFT JOIN (
        SELECT
            referencia,
            SUM(debe) AS TotalSaldado
        FROM
            diario_general_transacciones
        WHERE
            cuenta = '210200107'
        GROUP BY
            referencia
    ) AS Pagos
        ON Pagos.referencia = CONCAT('COMP-', CP.id_compra)
    WHERE
        CP.fecha_emision >= p_fecha_inicio
        AND CP.fecha_emision <= p_fecha_fin
    GROUP BY
        PC.id,
        COALESCE(PC.nombre_empresa, CP.prov_datos);
END //

DROP PROCEDURE IF EXISTS sp_rep_proveedor_comercial_global //
CREATE PROCEDURE sp_rep_proveedor_comercial_global(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT
        'Proveedores Comerciales'                              AS Tipo,
        SUM(CP.total)                                          AS Total_Comprado,
        COALESCE(SUM(Pagos.TotalSaldado), 0)                   AS Total_Saldado,
        (SUM(CP.total) - COALESCE(SUM(Pagos.TotalSaldado), 0)) AS Saldo_Neto
    FROM
        compras CP
    LEFT JOIN proveedor_comercial PC ON PC.id = (
        SELECT pc2.id
        FROM proveedor_comercial pc2
        WHERE UPPER(TRIM(CP.prov_datos)) = UPPER(TRIM(pc2.nombre_empresa))
           OR UPPER(TRIM(pc2.nombre_empresa)) LIKE CONCAT(UPPER(TRIM(CP.prov_datos)), ' %')
        ORDER BY
            CASE WHEN UPPER(TRIM(CP.prov_datos)) = UPPER(TRIM(pc2.nombre_empresa)) THEN 0 ELSE 1 END,
            CHAR_LENGTH(pc2.nombre_empresa) DESC
        LIMIT 1
    )
    LEFT JOIN (
        SELECT
            referencia,
            SUM(debe) AS TotalSaldado
        FROM
            diario_general_transacciones
        WHERE
            cuenta = '210200107'
        GROUP BY
            referencia
    ) AS Pagos
        ON Pagos.referencia = CONCAT('COMP-', CP.id_compra)
    WHERE
        CP.fecha_emision >= p_fecha_inicio
        AND CP.fecha_emision <= p_fecha_fin;
END //

DROP PROCEDURE IF EXISTS sp_rep_pagos_factura_comercial //
CREATE PROCEDURE sp_rep_pagos_factura_comercial(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT
        CP.id_compra,
        CP.dato_fac              AS NumeroFactura,
        COALESCE(PC.nombre_empresa, CP.prov_datos) AS Proveedor,
        CP.total                 AS Total_Compra,
        DGT.numero_partida       AS PartidaPago,
        DGT.fecha_ocurrencia     AS FechaPago,
        DGT.debe                 AS MontoAbonado,
        DGT.descripcion          AS DescripcionPago
    FROM
        compras CP
    LEFT JOIN proveedor_comercial PC ON PC.id = (
        SELECT pc2.id
        FROM proveedor_comercial pc2
        WHERE UPPER(TRIM(CP.prov_datos)) = UPPER(TRIM(pc2.nombre_empresa))
           OR UPPER(TRIM(pc2.nombre_empresa)) LIKE CONCAT(UPPER(TRIM(CP.prov_datos)), ' %')
        ORDER BY
            CASE WHEN UPPER(TRIM(CP.prov_datos)) = UPPER(TRIM(pc2.nombre_empresa)) THEN 0 ELSE 1 END,
            CHAR_LENGTH(pc2.nombre_empresa) DESC
        LIMIT 1
    )
    INNER JOIN
        diario_general_transacciones DGT
            ON DGT.referencia = CONCAT('COMP-', CP.id_compra)
            AND DGT.cuenta = '210200107'
            AND DGT.debe > 0
    WHERE
        CP.fecha_emision >= p_fecha_inicio
        AND CP.fecha_emision <= p_fecha_fin
    ORDER BY
        CP.id_compra,
        DGT.fecha_ocurrencia;
END //

DELIMITER ;
