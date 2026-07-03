-- =====================================================================
-- Triggers de notificaciones en tiempo real (Socket.IO) para el
-- calendario de Recursos Humanos.
-- Fecha: 2026-06-16
--
-- Tabla origen : medic9ue_medi_rrhh_interviews.rrhh_custom_events
-- Cola destino : medic9ue_medi_data.ws_notificaciones_pendientes
--
-- Convención de salas:
--   rrhh:empleado:{id_user}   -> dueño del evento
--   rrhh:calendario:global    -> eventos públicos (is_public = 1)
-- Evento emitido por el servidor: 'actualizacion'
-- Payload: { tipo: 'creado'|'editado'|'eliminado', datos: { ...campos } }
--
-- NOTA: estos triggers YA están desplegados en producción. Este archivo
-- es la fuente de verdad versionada para poder recrearlos si se pierden.
-- Idempotente: hace DROP IF EXISTS antes de crear.
-- =====================================================================

USE `medic9ue_medi_rrhh_interviews`;

DROP TRIGGER IF EXISTS `trg_rrhh_custom_events_ws_ai`;
DROP TRIGGER IF EXISTS `trg_rrhh_custom_events_ws_au`;
DROP TRIGGER IF EXISTS `trg_rrhh_custom_events_ws_ad`;

DELIMITER $$

-- ---------------------------------------------------------------------
-- AFTER INSERT -> 'creado'
-- ---------------------------------------------------------------------
CREATE TRIGGER `trg_rrhh_custom_events_ws_ai`
AFTER INSERT ON `rrhh_custom_events`
FOR EACH ROW
BEGIN
    DECLARE v_event_type_name VARCHAR(50) DEFAULT NULL;
    DECLARE v_payload LONGTEXT;

    SET v_event_type_name = (
        SELECT t.name FROM rrhh_calendar_event_types t
        WHERE t.id = NEW.id_event_type LIMIT 1
    );

    SET v_payload = JSON_OBJECT(
        'tipo', 'creado',
        'datos', JSON_OBJECT(
            'id', NEW.id,
            'raw_id', NEW.id,
            'title', NEW.title,
            'start_date', NEW.start_date,
            'start_time', NEW.start_time,
            'end_date', NEW.end_date,
            'end_time', NEW.end_time,
            'color', NEW.color,
            'id_event_type', NEW.id_event_type,
            'event_type_name', v_event_type_name,
            'description', NEW.description,
            'all_day', NEW.all_day,
            'is_public', NEW.is_public,
            'recurrence', NEW.recurrence,
            'recurrence_until', NEW.recurrence_until,
            'id_user', NEW.id_user
        )
    );

    IF NEW.id_user IS NOT NULL AND NEW.id_user > 0 THEN
        INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
        VALUES (CONCAT('rrhh:empleado:', NEW.id_user), 'actualizacion', v_payload);
    END IF;

    IF NEW.is_public = 1 THEN
        INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
        VALUES ('rrhh:calendario:global', 'actualizacion', v_payload);
    END IF;
END$$

-- ---------------------------------------------------------------------
-- AFTER UPDATE -> 'editado' (y 'eliminado' en soft-delete / cambio de dueño / quitar público)
-- ---------------------------------------------------------------------
CREATE TRIGGER `trg_rrhh_custom_events_ws_au`
AFTER UPDATE ON `rrhh_custom_events`
FOR EACH ROW
BEGIN
    DECLARE v_event_type_name VARCHAR(50) DEFAULT NULL;
    DECLARE v_payload_new LONGTEXT;
    DECLARE v_payload_old LONGTEXT;
    DECLARE v_payload_deleted LONGTEXT;

    SET v_event_type_name = (
        SELECT t.name FROM rrhh_calendar_event_types t
        WHERE t.id = NEW.id_event_type LIMIT 1
    );

    SET v_payload_new = JSON_OBJECT(
        'tipo', 'editado',
        'datos', JSON_OBJECT(
            'id', NEW.id,
            'raw_id', NEW.id,
            'title', NEW.title,
            'start_date', NEW.start_date,
            'start_time', NEW.start_time,
            'end_date', NEW.end_date,
            'end_time', NEW.end_time,
            'color', NEW.color,
            'id_event_type', NEW.id_event_type,
            'event_type_name', v_event_type_name,
            'description', NEW.description,
            'all_day', NEW.all_day,
            'is_public', NEW.is_public,
            'recurrence', NEW.recurrence,
            'recurrence_until', NEW.recurrence_until,
            'id_user', NEW.id_user
        )
    );

    -- Soft delete (deleted: 0 -> 1) debe notificar como eliminado.
    IF IFNULL(OLD.deleted, 0) = 0 AND IFNULL(NEW.deleted, 0) = 1 THEN
        SET v_payload_deleted = JSON_OBJECT(
            'tipo', 'eliminado',
            'datos', JSON_OBJECT('id', OLD.id, 'raw_id', OLD.id, 'id_user', OLD.id_user)
        );

        IF OLD.id_user IS NOT NULL AND OLD.id_user > 0 THEN
            INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
            VALUES (CONCAT('rrhh:empleado:', OLD.id_user), 'actualizacion', v_payload_deleted);
        END IF;

        IF OLD.is_public = 1 THEN
            INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
            VALUES ('rrhh:calendario:global', 'actualizacion', v_payload_deleted);
        END IF;
    ELSE
        -- Si cambia de empleado asignado, el empleado anterior debe quitarlo del calendario.
        IF OLD.id_user IS NOT NULL
           AND OLD.id_user > 0
           AND (NEW.id_user IS NULL OR NEW.id_user <> OLD.id_user) THEN
            SET v_payload_old = JSON_OBJECT(
                'tipo', 'eliminado',
                'datos', JSON_OBJECT('id', OLD.id, 'raw_id', OLD.id, 'id_user', OLD.id_user)
            );

            INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
            VALUES (CONCAT('rrhh:empleado:', OLD.id_user), 'actualizacion', v_payload_old);
        END IF;

        IF NEW.id_user IS NOT NULL AND NEW.id_user > 0 THEN
            INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
            VALUES (CONCAT('rrhh:empleado:', NEW.id_user), 'actualizacion', v_payload_new);
        END IF;

        -- Control de visibilidad pública para sala global.
        IF OLD.is_public = 1 AND NEW.is_public = 0 THEN
            SET v_payload_old = JSON_OBJECT(
                'tipo', 'eliminado',
                'datos', JSON_OBJECT('id', OLD.id, 'raw_id', OLD.id, 'id_user', OLD.id_user)
            );

            INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
            VALUES ('rrhh:calendario:global', 'actualizacion', v_payload_old);
        ELSEIF NEW.is_public = 1 THEN
            INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
            VALUES ('rrhh:calendario:global', 'actualizacion', v_payload_new);
        END IF;
    END IF;
END$$

-- ---------------------------------------------------------------------
-- AFTER DELETE -> 'eliminado' (hard delete)
-- ---------------------------------------------------------------------
CREATE TRIGGER `trg_rrhh_custom_events_ws_ad`
AFTER DELETE ON `rrhh_custom_events`
FOR EACH ROW
BEGIN
    DECLARE v_payload LONGTEXT;

    SET v_payload = JSON_OBJECT(
        'tipo', 'eliminado',
        'datos', JSON_OBJECT(
            'id', OLD.id,
            'raw_id', OLD.id,
            'title', OLD.title,
            'id_user', OLD.id_user,
            'is_public', OLD.is_public
        )
    );

    IF OLD.id_user IS NOT NULL AND OLD.id_user > 0 THEN
        INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
        VALUES (CONCAT('rrhh:empleado:', OLD.id_user), 'actualizacion', v_payload);
    END IF;

    IF OLD.is_public = 1 THEN
        INSERT INTO medic9ue_medi_data.ws_notificaciones_pendientes (sala, tipo_evento, datos_json)
        VALUES ('rrhh:calendario:global', 'actualizacion', v_payload);
    END IF;
END$$

DELIMITER ;
