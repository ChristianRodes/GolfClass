-- ============================================================
-- GolfClass Database Schema — Complete & Final
-- ============================================================

CREATE DATABASE IF NOT EXISTS golfclass_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE golfclass_db;

-- ── Roles ─────────────────────────────────────────────────────
CREATE TABLE roles (
    id_rol     INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL
);

INSERT INTO roles (nombre_rol) VALUES
    ('Administrador'),
    ('Profesor'),
    ('Alumno');

-- ── Users ─────────────────────────────────────────────────────
CREATE TABLE usuarios (
    id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
    id_rol         INT NOT NULL,
    nombre         VARCHAR(100) NOT NULL,
    apellidos      VARCHAR(100),
    email          VARCHAR(150) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    telefono       VARCHAR(20),
    activo         TINYINT(1) DEFAULT 1,
    ban_hasta      DATETIME NULL,
    reset_token    VARCHAR(64) NULL,
    reset_expiry   DATETIME NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- ── Golf courses ───────────────────────────────────────────────
CREATE TABLE campos_golf (
    id_campo     INT AUTO_INCREMENT PRIMARY KEY,
    nombre_campo VARCHAR(150) NOT NULL,
    ciudad       VARCHAR(100) NOT NULL,
    direccion    VARCHAR(255),
    foto_campo   VARCHAR(255),
    activo       TINYINT(1) DEFAULT 1
);

-- ── Instructors (extends usuarios for role=2) ─────────────────
CREATE TABLE profesores (
    id_profesor         INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario          INT NOT NULL UNIQUE,
    id_campo            INT NULL,
    bio                 TEXT,
    titulaciones        TEXT,
    clases_online       TINYINT(1) DEFAULT 0,
    ubicacion           VARCHAR(255),
    experiencia_anos    INT DEFAULT 0,
    ciudad              VARCHAR(100),
    puntuacion_simulada DECIMAL(3,1) DEFAULT 5.0,
    precio_hora         DECIMAL(6,2) NOT NULL DEFAULT 0,
    capacidad           INT DEFAULT 1,
    foto_perfil         VARCHAR(255),
    rating              DECIMAL(3,2) DEFAULT 5.00,
    activo              TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_campo)   REFERENCES campos_golf(id_campo)
);

-- ── Bookings ───────────────────────────────────────────────────
CREATE TABLE reservas (
    id_reserva    INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno     INT NOT NULL,
    id_profesor   INT NOT NULL,
    fecha_clase   DATE NOT NULL,
    hora_inicio   TIME NOT NULL,
    hora_fin      TIME NOT NULL,
    estado        ENUM('Pendiente','Confirmada','Cancelada','Completada','No presentado')
                  DEFAULT 'Pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo        TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_alumno)   REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_profesor) REFERENCES profesores(id_profesor)
);

-- ── Blocked time slots (instructor) ───────────────────────────
CREATE TABLE slots_bloqueados (
    id_bloqueo     INT AUTO_INCREMENT PRIMARY KEY,
    id_profesor    INT NOT NULL,
    fecha          DATE NOT NULL,
    hora_inicio    TIME NOT NULL,
    hora_fin       TIME NOT NULL,
    motivo         VARCHAR(255) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_profesor) REFERENCES profesores(id_profesor)
);

-- ── Strike system ─────────────────────────────────────────────
CREATE TABLE strikes (
    id_strike  INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno  INT NOT NULL,
    id_reserva INT NOT NULL,
    motivo     ENUM('cancelacion','no_presentado') NOT NULL,
    fecha      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_alumno)  REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva),
    INDEX idx_alumno (id_alumno),
    UNIQUE KEY uq_reserva_strike (id_reserva)
);

-- ── Internal messaging / chat ──────────────────────────────────
CREATE TABLE mensajes (
    id_mensaje  INT AUTO_INCREMENT PRIMARY KEY,
    id_emisor   INT NOT NULL,
    id_receptor INT NOT NULL,
    mensaje     TEXT NOT NULL,
    leido       TINYINT(1) DEFAULT 0,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_emisor)   REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_receptor) REFERENCES usuarios(id_usuario),
    INDEX idx_conversacion (id_emisor, id_receptor),
    INDEX idx_receptor_leido (id_receptor, leido)
);
