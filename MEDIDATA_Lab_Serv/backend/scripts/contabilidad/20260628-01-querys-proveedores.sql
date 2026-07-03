USE medic9ue_medi_data;

-- =================================================================================================
-- Consulta 1: Estado de Cuenta Detallado - Proveedores Comerciales
-- =================================================================================================
SELECT
    PC.id                                                  AS IdComerciante,
    CP.prov_datos                                          AS ProveedorComercial,
    SUM(CP.total)                                          AS Total_Comprado,
    COALESCE(SUM(Pagos.TotalSaldado), 0)                   AS Total_Saldado,
    (SUM(CP.total) - COALESCE(SUM(Pagos.TotalSaldado), 0)) AS Saldo_Pendiente
FROM
    compras CP
INNER JOIN
    proveedor_comercial PC
        ON CP.prov_datos = PC.nombre_empresa
LEFT JOIN (
    SELECT
        referencia,
        SUM(haber) AS TotalSaldado
    FROM
        diario_general_transacciones
    WHERE
        tipo_transaccion = 'COMPRA_PROVEEDOR'
    GROUP BY
        referencia
) AS Pagos
    ON Pagos.referencia = CONCAT('COMP-', CP.id_compra)
WHERE
    CP.fecha_emision >= '2026-05-01'
    AND CP.fecha_emision < '2026-06-01'
GROUP BY
    PC.id,
    CP.prov_datos;

-- =================================================================================================
-- Consulta 2: Estado de Cuenta Detallado - Proveedores Médicos (Doctores)
-- =================================================================================================
SELECT
    d.idodc                                                                        AS IdDoctor,
    CONCAT(d.nodoc, ' ', d.apdoc)                                                  AS ProveedorMedico,
    SUM(hm.monto_honorario)                                                        AS Total_Honorarios,
    SUM(CASE WHEN hm.estado_pago = 'pagado' THEN hm.monto_honorario ELSE 0 END)    AS Total_Pagado,
    SUM(CASE WHEN hm.estado_pago = 'pendiente' THEN hm.monto_honorario ELSE 0 END) AS Saldo_Pendiente
FROM
    honorarios_medicos hm
INNER JOIN
    doctor d
        ON hm.id_doctor = d.idodc
INNER JOIN
    orders o
        ON hm.id_factura = o.idord
WHERE
    o.placed_on >= '2025-06-01'
    AND o.placed_on < '2025-07-01'
GROUP BY
    d.idodc,
    d.nodoc,
    d.apdoc;


-- =================================================================================================
-- Consulta 3: Condensado Global - Proveedores Comerciales (Una sola fila)
-- =================================================================================================
SELECT
    'Proveedores Comerciales'                              AS Tipo,
    SUM(CP.total)                                          AS Total_Comprado,
    COALESCE(SUM(Pagos.TotalSaldado), 0)                   AS Total_Saldado,
    (SUM(CP.total) - COALESCE(SUM(Pagos.TotalSaldado), 0)) AS Saldo_Neto
FROM
    compras CP
INNER JOIN
    proveedor_comercial PC
        ON CP.prov_datos = PC.nombre_empresa
LEFT JOIN (
    SELECT
        referencia,
        SUM(haber) AS TotalSaldado
    FROM
        diario_general_transacciones
    WHERE
        tipo_transaccion = 'COMPRA_PROVEEDOR'
    GROUP BY
        referencia
) AS Pagos
    ON Pagos.referencia = CONCAT('COMP-', CP.id_compra)
WHERE
    CP.fecha_emision >= '2025-04-01'
    AND CP.fecha_emision < '2025-05-01';


-- =================================================================================================
-- Consulta 4: Condensado Global - Proveedores Médicos (Una sola fila)
-- =================================================================================================
SELECT
    'Proveedores Medicos'                                                          AS Tipo,
    SUM(hm.monto_honorario)                                                        AS Total_Comprado,
    SUM(CASE WHEN hm.estado_pago = 'pagado' THEN hm.monto_honorario ELSE 0 END)    AS Total_Saldado,
    SUM(CASE WHEN hm.estado_pago = 'pendiente' THEN hm.monto_honorario ELSE 0 END) AS Saldo_Neto
FROM
    honorarios_medicos hm
INNER JOIN
    doctor d
        ON hm.id_doctor = d.idodc
INNER JOIN
    orders o
        ON hm.id_factura = o.idord
WHERE
    o.placed_on >= '2025-04-01'
    AND o.placed_on < '2025-05-01';


-- =================================================================================================
-- Consulta 5: Detalle de Compras - Proveedores Comerciales (Encabezado)
-- =================================================================================================
SELECT 
    CP.id_compra,
    CP.fecha_emision      AS Fecha,
    CP.dato_fac           AS NumeroFactura,
    CP.fech_vence         AS Fecha_Vencimiento,
    CP.total              AS ValorFactura,
    CASE 
        WHEN (CP.total - COALESCE(Pagos.TotalSaldado, 0)) <= 0 THEN 'Pagado'
        ELSE 'Pendiente'
    END                   AS Estado
FROM 
    compras CP
INNER JOIN 
    proveedor_comercial PC 
        ON CP.prov_datos = PC.nombre_empresa
LEFT JOIN (
    SELECT 
        referencia, 
        SUM(haber) AS TotalSaldado
    FROM 
        diario_general_transacciones
    WHERE 
        tipo_transaccion = 'COMPRA_PROVEEDOR'
    GROUP BY 
        referencia
) AS Pagos 
    ON Pagos.referencia = CONCAT('COMP-', CP.id_compra)
WHERE 
    CP.fecha_emision >= '2025-04-01' 
    AND CP.fecha_emision < '2025-05-01'
ORDER BY 
    CP.fecha_emision DESC;


-- =================================================================================================
-- Consulta 6: Detalle de Honorarios - Proveedores Médicos (Encabezado)
-- =================================================================================================
SELECT 
    hm.id                         AS IdHonorario,
    o.placed_on                   AS Fecha,
    o.invoice_number              AS NumeroFactura,
    o.nomcl                       AS NombrePaciente,
    o.method                      AS Estudio,
    hm.monto_honorario            AS ValorFactura,
    hm.estado_pago                AS Estado
FROM 
    honorarios_medicos hm
INNER JOIN 
    doctor d 
        ON hm.id_doctor = d.idodc
INNER JOIN 
    orders o 
        ON hm.id_factura = o.idord
WHERE 
    o.placed_on >= '2025-04-01' 
    AND o.placed_on < '2025-05-01'
ORDER BY 
    o.placed_on DESC;


-- =================================================================================================
-- Consulta 7: Historial de Pagos (Partidas) por Factura - Proveedores Comerciales
-- =================================================================================================
SELECT 
    CP.id_compra,
    CP.dato_fac              AS NumeroFactura,
    PC.nombre_empresa        AS Proveedor,
    CP.total                 AS Total_Compra,
    DGT.numero_partida       AS PartidaPago,
    DGT.fecha_ocurrencia     AS FechaPago,
    DGT.haber                AS MontoAbonado,
    DGT.descripcion          AS DescripcionPago
FROM 
    compras CP
INNER JOIN 
    proveedor_comercial PC 
        ON CP.prov_datos = PC.nombre_empresa
INNER JOIN 
    diario_general_transacciones DGT 
        ON DGT.referencia = CONCAT('COMP-', CP.id_compra)
        AND DGT.tipo_transaccion = 'COMPRA_PROVEEDOR'
WHERE 
    CP.fecha_emision >= '2025-04-01' 
    AND CP.fecha_emision < '2025-05-01'
ORDER BY 
    CP.id_compra, 
    DGT.fecha_ocurrencia;


-- =================================================================================================
-- Consulta 8: Historial de Pagos por Honorario - Proveedores Médicos
-- Nota: Puesto que honorarios_medicos maneja su propio estado ('pagado') sin registrar
-- en diario_general_transacciones, mostramos el registro de pago interno exacto.
-- =================================================================================================
SELECT 
    hm.id                         AS IdHonorario,
    CONCAT(d.nodoc, ' ', d.apdoc) AS ProveedorMedico,
    o.idord                       AS NumeroOrden,
    hm.monto_honorario            AS Total_Honorario,
    hm.estado_pago                AS EstadoPago,
    hm.fecha_pago                 AS FechaEfectivaPago,
    hm.updated_by                 AS PagadoPorUsuario
FROM 
    honorarios_medicos hm
INNER JOIN 
    doctor d 
        ON hm.id_doctor = d.idodc
INNER JOIN 
    orders o 
        ON hm.id_factura = o.idord
WHERE 
    hm.estado_pago = 'pagado'
    AND o.placed_on >= '2025-04-01' 
    AND o.placed_on < '2025-05-01'
ORDER BY 
    hm.id DESC;

