-- Script para agregar campos necesarios para el sistema de pagos Premium
-- Ejecutar este script antes de usar el sistema de pagos

-- Agregar campos de Premium a la tabla usuarios (si no existen)
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS fecha_expiracion_premium DATETIME NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS fecha_ultimo_pago DATETIME NULL DEFAULT NULL;

-- Crear tabla para registrar suscripciones de Mercado Pago
CREATE TABLE IF NOT EXISTS suscripciones_premium (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    preapproval_id VARCHAR(100) NOT NULL UNIQUE,
    plan_id VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    monto DECIMAL(10,2) NOT NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'MXN',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio DATETIME NULL,
    fecha_fin DATETIME NULL,
    auto_recurring TINYINT(1) NOT NULL DEFAULT 1,
    datos_suscripcion TEXT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_preapproval (preapproval_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla para registrar pagos individuales de suscripciones
CREATE TABLE IF NOT EXISTS pagos_suscripcion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suscripcion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    payment_id VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    monto DECIMAL(10,2) NOT NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'MXN',
    fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datos_pago TEXT NULL,
    FOREIGN KEY (suscripcion_id) REFERENCES suscripciones_premium(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_suscripcion (suscripcion_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_payment (payment_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla para logs de webhooks de Mercado Pago
CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    payment_id VARCHAR(100) NULL,
    datos_recibidos TEXT NOT NULL,
    procesado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_recepcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment (payment_id),
    INDEX idx_procesado (procesado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
