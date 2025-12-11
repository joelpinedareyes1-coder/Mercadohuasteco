-- Script SQL para crear la base de datos del Directorio de Tiendas Locales
CREATE DATABASE IF NOT EXISTS directorio_tiendas;
USE directorio_tiendas;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'vendedor', 'admin') NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- Índices para optimizar consultas
CREATE INDEX idx_email ON usuarios(email);
CREATE INDEX idx_rol ON usuarios(rol);

-- Tabla de tiendas
CREATE TABLE tiendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendedor_id INT NOT NULL,
    nombre_tienda VARCHAR(200) NOT NULL,
    descripcion TEXT,
    logo VARCHAR(500),
    url_tienda VARCHAR(500) NOT NULL,
    categoria VARCHAR(100) NOT NULL DEFAULT 'General',
    clics INT DEFAULT 0,
    es_destacado TINYINT(1) DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices para la tabla tiendas
CREATE INDEX idx_vendedor_id ON tiendas(vendedor_id);
CREATE INDEX idx_activo ON tiendas(activo);
CREATE INDEX idx_es_destacado ON tiendas(es_destacado);

-- Tabla de calificaciones y comentarios
CREATE TABLE calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tienda_id INT NOT NULL,
    estrellas INT NOT NULL CHECK (estrellas >= 1 AND estrellas <= 5),
    comentario TEXT,
    fecha_calificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    esta_aprobada TINYINT(1) DEFAULT 1,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tienda (user_id, tienda_id)
);

-- Índices para la tabla calificaciones
CREATE INDEX idx_user_id ON calificaciones(user_id);
CREATE INDEX idx_tienda_id ON calificaciones(tienda_id);
CREATE INDEX idx_estrellas ON calificaciones(estrellas);
CREATE INDEX idx_esta_aprobada ON calificaciones(esta_aprobada);

-- Tabla de galería de fotos para tiendas
CREATE TABLE galeria_tiendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tienda_id INT NOT NULL,
    url_imagen VARCHAR(500) NOT NULL,
    descripcion VARCHAR(255),
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE
);

-- Índices para la tabla galeria_tiendas
CREATE INDEX idx_tienda_id_galeria ON galeria_tiendas(tienda_id);
CREATE INDEX idx_activo_galeria ON galeria_tiendas(activo);-- 
Tabla de favoritos
CREATE TABLE favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tienda_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorito (usuario_id, tienda_id)
);

-- Índices para optimizar consultas de favoritos
CREATE INDEX idx_usuario_favoritos ON favoritos(usuario_id);
CREATE INDEX idx_tienda_favoritos ON favoritos(tienda_id);