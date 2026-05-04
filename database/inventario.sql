-- Se ejecuta al crear el contenedor MySQL (docker-entrypoint-initdb.d).
-- Base creada por MYSQL_DATABASE en docker-compose.

USE inventario;

-- Base y tablas en utf8mb4 (recomendado para español, símbolos y caracteres “raros”).
ALTER DATABASE inventario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS productos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(64) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_productos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO productos (codigo, nombre, descripcion, precio, stock, activo) VALUES
('SKU-001', 'Martillo de goma', 'Cabezal de goma, mango de madera', 12.50, 45, 1),
('SKU-002', 'Destornillador plano 6 mm', 'Vástago cromo-vanadio 150 mm', 4.25, 120, 1),
('SKU-003', 'Cinta métrica 5 m', 'Carcasa ABS, bloqueo automático', 8.90, 30, 1),
('SKU-004', 'Guantes nitrilo talla M', 'Caja 100 unidades, desechables', 18.00, 22, 1),
('SKU-005', 'Pila AA alcalina (blister 4)', '1,5 V, larga duración', 3.75, 200, 1),
('SKU-006', 'Cinta adhesiva transparente', '48 mm x 66 m', 2.40, 85, 1),
('SKU-007', 'Tornillo hexagonal M6 x 20', 'Bolsa 50 uds, acero zincado', 6.10, 0, 0),
('SKU-008', 'Linterna LED recargable', '500 lm, USB-C, imán base', 24.99, 15, 1),
('SKU-009', 'Ñoño — café ½ litro (prueba UTF-8)', '€ símbolos: áéíóú ñ · 日本語', 9.99, 5, 1);

CREATE TABLE IF NOT EXISTS parametros (
    clave VARCHAR(64) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO parametros (clave, valor) VALUES ('iva_porcentaje', '15');

CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(200) NOT NULL,
    documento VARCHAR(32) NULL,
    telefono VARCHAR(40) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_clientes_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO clientes (nombre, documento) VALUES
('Consumidor final', '9999999999999'),
('Distribuidora Norte S.A.', '1791234567001'),
('Taller Mecánico López', '0912345678');

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login VARCHAR(64) NOT NULL,
    nombre_mostrar VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (login, nombre_mostrar, password_hash, activo) VALUES
('demo', 'Usuario demo', '$2y$10$84AQvFydOIS9WVSKUSYLE.IABqmZSg25R7QFifYxZVqwiJeiEJRfu', 1);

CREATE TABLE IF NOT EXISTS ventas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cliente_id INT UNSIGNED NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(12, 2) NOT NULL,
    iva_porcentaje DECIMAL(5, 2) NOT NULL,
    iva_monto DECIMAL(12, 2) NOT NULL,
    total DECIMAL(12, 2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_ventas_fecha (fecha),
    CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS venta_detalle (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    venta_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(12, 2) NOT NULL,
    subtotal_linea DECIMAL(12, 2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_detalle_venta (venta_id),
    CONSTRAINT fk_detalle_venta FOREIGN KEY (venta_id) REFERENCES ventas (id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES productos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
