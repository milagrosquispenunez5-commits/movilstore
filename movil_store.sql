
CREATE DATABASE IF NOT EXISTS movilstore
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE movilstore;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(10) NOT NULL DEFAULT 'cliente',
    nombre VARCHAR(100) NOT NULL DEFAULT '',
    dni VARCHAR(15) NOT NULL DEFAULT '',
    telefono VARCHAR(20) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'rol');
SET @sql = IF(@existe = 0,
    'ALTER TABLE users ADD COLUMN rol VARCHAR(10) NOT NULL DEFAULT ''cliente''',
    'SELECT ''La columna users.rol ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'nombre');
SET @sql = IF(@existe = 0,
    'ALTER TABLE users ADD COLUMN nombre VARCHAR(100) NOT NULL DEFAULT ''''',
    'SELECT ''La columna users.nombre ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'dni');
SET @sql = IF(@existe = 0,
    'ALTER TABLE users ADD COLUMN dni VARCHAR(15) NOT NULL DEFAULT ''''',
    'SELECT ''La columna users.dni ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'telefono');
SET @sql = IF(@existe = 0,
    'ALTER TABLE users ADD COLUMN telefono VARCHAR(20) NOT NULL DEFAULT ''''',
    'SELECT ''La columna users.telefono ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE users SET rol = 'admin', nombre = 'Administrador'
WHERE username = 'admin' AND rol <> 'admin';

INSERT INTO users (username, password, rol, nombre)
SELECT 'admin', '$2y$12$//D.f4pUP0zmAx6mtPNlyeZnnaDIMDBZY4oWYpfG/5usVRsg8/.n.', 'admin', 'Administrador'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE rol = 'admin');

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(15) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opinions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    opinion TEXT NOT NULL,
    rating INT NOT NULL DEFAULT 5,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'opinions' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@existe = 0,
    'ALTER TABLE opinions ADD COLUMN user_id INT NULL',
    'SELECT ''La columna opinions.user_id ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    cliente_nombre VARCHAR(100) NULL,
    items TEXT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'cliente_nombre');
SET @sql = IF(@existe = 0,
    'ALTER TABLE pedidos ADD COLUMN cliente_nombre VARCHAR(100) NULL',
    'SELECT ''La columna pedidos.cliente_nombre ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @nulable = (SELECT IS_NULLABLE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@nulable = 'NO',
    'ALTER TABLE pedidos MODIFY user_id INT NULL',
    'SELECT ''La columna pedidos.user_id ya permite NULL'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255) NOT NULL DEFAULT '',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO productos (nombre, precio, imagen)
SELECT nombre, precio, imagen FROM (
    SELECT 'HONOR X8C' AS nombre, 900.00 AS precio, 'img/x8c.jpg' AS imagen
    UNION ALL SELECT 'SAMSUNG A56', 1800.00, 'img/samsung a56.jpg'
    UNION ALL SELECT 'IPHONE 16', 5500.00, 'img/iphone16.jpg'
) AS iniciales
WHERE NOT EXISTS (SELECT 1 FROM productos LIMIT 1);
