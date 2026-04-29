-- Migración: ampliar tabla profesores
-- Ejecutar SOLO si los campos no existen ya

USE golfclass_db;

-- Nota: MySQL nativo no admite IF NOT EXISTS en ALTER TABLE.
-- Ejecutar solo si las columnas NO existen todavía (comprueba con SHOW COLUMNS FROM profesores).
ALTER TABLE profesores
    ADD COLUMN experiencia_anos INT DEFAULT 0 AFTER bio,
    ADD COLUMN ciudad VARCHAR(100) NULL AFTER experiencia_anos,
    ADD COLUMN puntuacion_simulada DECIMAL(3,1) DEFAULT 5.0 AFTER ciudad;

-- Actualizar datos de prueba (seed) con los nuevos campos
UPDATE profesores p
JOIN usuarios u ON p.id_usuario = u.id_usuario
SET p.experiencia_anos = 10, p.ciudad = 'Madrid', p.puntuacion_simulada = 4.8
WHERE u.email = 'carlos@golfclass.com';

UPDATE profesores p
JOIN usuarios u ON p.id_usuario = u.id_usuario
SET p.experiencia_anos = 8, p.ciudad = 'Barcelona', p.puntuacion_simulada = 4.9
WHERE u.email = 'maria@golfclass.com';

UPDATE profesores p
JOIN usuarios u ON p.id_usuario = u.id_usuario
SET p.experiencia_anos = 5, p.ciudad = 'Cádiz', p.puntuacion_simulada = 4.6
WHERE u.email = 'juan@golfclass.com';
