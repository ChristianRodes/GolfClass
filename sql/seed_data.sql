-- Datos de prueba para GolfClass
-- Password de todos los usuarios de prueba: password123

USE golfclass_db;

-- Campos de golf
INSERT INTO campos_golf (nombre_campo, ciudad, direccion, activo) VALUES
('Club de Golf La Moraleja', 'Madrid', 'Av. de Europa, 28109 Alcobendas', 1),
('Real Club de Golf El Prat', 'Barcelona', 'Camí de Can Rabia s/n, El Prat de Llobregat', 1),
('Club de Golf Valderrama', 'Cádiz', 'Av. Los Cortijos s/n, 11310 Sotogrande', 1);

-- Usuario administrador
INSERT INTO usuarios (id_rol, nombre, apellidos, email, password, activo) VALUES
(1, 'Admin', 'GolfClass', 'admin@golfclass.com', '$2y$12$yErTQwvsD.7g/dVEvOM8Uuk7i1sxD5abqo7hrVkBKy7zSEtbmanSC', 1);

-- Usuarios profesores
INSERT INTO usuarios (id_rol, nombre, apellidos, email, password, activo) VALUES
(2, 'Carlos', 'Rodríguez', 'carlos@golfclass.com', '$2y$12$yErTQwvsD.7g/dVEvOM8Uuk7i1sxD5abqo7hrVkBKy7zSEtbmanSC', 1),
(2, 'María', 'López', 'maria@golfclass.com', '$2y$12$yErTQwvsD.7g/dVEvOM8Uuk7i1sxD5abqo7hrVkBKy7zSEtbmanSC', 1),
(2, 'Juan', 'García', 'juan@golfclass.com', '$2y$12$yErTQwvsD.7g/dVEvOM8Uuk7i1sxD5abqo7hrVkBKy7zSEtbmanSC', 1);

-- Perfiles de profesores
INSERT INTO profesores (id_usuario, id_campo, bio, precio_hora, rating, activo) VALUES
((SELECT id_usuario FROM usuarios WHERE email = 'carlos@golfclass.com'), 1, 'Profesional con 10 años de experiencia en el Tour Europeo. Especialista en swing, putting y juego corto.', 45.00, 4.80, 1),
((SELECT id_usuario FROM usuarios WHERE email = 'maria@golfclass.com'), 2, 'Ex competidora profesional durante 8 años. Clases adaptadas a todos los niveles, desde principiante hasta avanzado.', 60.00, 4.90, 1),
((SELECT id_usuario FROM usuarios WHERE email = 'juan@golfclass.com'), 3, 'Instructor certificado PGA con metodología propia. Especializado en iniciación al golf para adultos y jóvenes.', 35.00, 4.60, 1);
