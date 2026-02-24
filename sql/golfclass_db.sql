-- 1. Crear la base de datos
CREATE DATABASE IF NOT EXISTS golfclass_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE golfclass_db;

-- 2. Tabla de Roles
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL
);

-- Insertar los roles por defecto
INSERT INTO roles (nombre_rol) VALUES ('Administrador'), ('Profesor'), ('Alumno');

-- 3. Tabla de Usuarios (Sirve para Admins, Profes y Alumnos)
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Aquí guardaremos el password_hash()
    telefono VARCHAR(20),
    activo TINYINT(1) DEFAULT 1, -- BAJA LÓGICA: 1 = Activo, 0 = Inactivo/Borrado
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- 4. Tabla de Campos de Golf (Zonas/Ubicaciones)
CREATE TABLE campos_golf (
    id_campo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_campo VARCHAR(150) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    direccion VARCHAR(255),
    foto_campo VARCHAR(255), -- Ruta de la imagen en /uploads
    activo TINYINT(1) DEFAULT 1
);

-- 5. Tabla de Profesores (Extiende la info del usuario si es profesor)
-- Este es el "Recurso" principal que los alumnos van a buscar y reservar
CREATE TABLE profesores (
    id_profesor INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    id_campo INT NOT NULL, -- Dónde da las clases
    bio TEXT, -- Descripción de su experiencia
    precio_hora DECIMAL(6,2) NOT NULL,
    foto_perfil VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 5.00, -- Nota media
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_campo) REFERENCES campos_golf(id_campo)
);

-- 6. Tabla de Reservas
CREATE TABLE reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL, -- El usuario que reserva
    id_profesor INT NOT NULL, -- El recurso reservado
    fecha_clase DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    estado ENUM('Pendiente', 'Confirmada', 'Cancelada', 'Completada') DEFAULT 'Pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) DEFAULT 1, -- BAJA LÓGICA de la reserva (historial)
    FOREIGN KEY (id_alumno) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_profesor) REFERENCES profesores(id_profesor)
);