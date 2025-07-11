CREATE DATABASE IF NOT EXISTS appsalud_db_sigesmancor;
USE appsalud_db_sigesmancor;

-- Crear tabla para registrar la fecha y hora de las actualizaciones de fechas
CREATE TABLE IF NOT EXISTS log_actualizaciones_fechas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Módulos: Almacena los módulos del sistema dinámicamente
CREATE TABLE modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    esta_activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
);

-- Tabla de Permisos: Define permisos granulares asociados a módulos
CREATE TABLE permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modulo_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE RESTRICT,
    INDEX idx_nombre (nombre)
);

-- Tabla de Roles: Almacena roles dinámicos
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    esta_activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
);

-- Tabla de Relación Roles-Permisos: Asocia permisos a roles
CREATE TABLE roles_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    permiso_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT,
    FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE RESTRICT,
    UNIQUE (rol_id, permiso_id)
);

-- Tabla de Usuarios: Gestiona los usuarios del sistema
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE,
    dni VARCHAR(20) UNIQUE,
    telefono VARCHAR(20),
    direccion TEXT,
    area VARCHAR(50),
    fotografia VARCHAR(255),
    creado_por INT,
    token_recordatorio VARCHAR(255),
    esta_activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_dni (dni)
);

-- Tabla de Preferencias de Usuarios: Almacena configuraciones personalizadas de los usuarios
CREATE TABLE preferencias_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tema VARCHAR(50) DEFAULT 'claro',
    idioma VARCHAR(10) DEFAULT 'es',
    navbar_design VARCHAR(50) DEFAULT 'default',
    navbar_bg_color VARCHAR(7),
    navbar_text_color VARCHAR(7),
    navbar_active_bg_color VARCHAR(7),
    navbar_active_text_color VARCHAR(7),
    topbar_bg_color VARCHAR(7),
    topbar_text_color VARCHAR(7),
    pagina_inicio VARCHAR(50) DEFAULT 'dashboard',
    elementos_por_pagina INT DEFAULT 10,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id)
);

-- Tabla de Relación Usuarios-Roles: Asocia roles a usuarios
CREATE TABLE usuarios_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    rol_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT,
    UNIQUE (usuario_id, rol_id)
);

-- Tabla de Sesiones de Usuarios: Registra ingresos y cierres de sesión
CREATE TABLE sesiones_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    inicio_sesion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fin_sesion TIMESTAMP,
    esta_activa BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de Personal: Gestiona el personal (sin acceso al sistema por defecto)
CREATE TABLE personal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE,
    telefono VARCHAR(20),
    direccion TEXT,
    area VARCHAR(50),
    fecha_ingreso DATE NOT NULL,
    fecha_baja DATE,
    imagen VARCHAR(255),
    esta_activo BOOLEAN DEFAULT TRUE,
    creado_por INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_dni (dni)
);

-- Tabla de Categorías de Equipos: Clasifica los tipos de equipos
CREATE TABLE categorias_equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
);

-- Tabla de Equipos: Almacena todos los equipos, incluyendo máquinas y motores
CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    tipo_equipo VARCHAR(100) NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    numero_serie VARCHAR(50) UNIQUE,
    capacidad VARCHAR(50),
    fase VARCHAR(20),
    linea_electrica VARCHAR(50),
    ubicacion VARCHAR(100),
    estado ENUM('activo', 'mantenimiento', 'averiado', 'vendido', 'descanso') DEFAULT 'activo',
    tipo_orometro ENUM('horas', 'kilometros') NOT NULL,
    orometro_actual DECIMAL(15,2) DEFAULT 0,
    anterior_orometro DECIMAL(15,2) DEFAULT 0,
    proximo_orometro DECIMAL(15,2),
    limite DECIMAL(15,2) CHECK (limite BETWEEN 0 AND 1000000),
    notificacion DECIMAL(15,2) CHECK (notificacion BETWEEN 0 AND 1000),
    mantenimiento DECIMAL(15,2) CHECK (mantenimiento >= 0),
    observaciones TEXT,
    imagen VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias_equipos(id) ON DELETE RESTRICT,
    INDEX idx_codigo (codigo)
);

-- Tabla de Componentes: Partes de los equipos
CREATE TABLE componentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    marca VARCHAR(50),
    numero_serie VARCHAR(50),
    modelo VARCHAR(50),
    tipo_orometro ENUM('horas', 'kilometros') NOT NULL,
    orometro_actual DECIMAL(15,2) DEFAULT 0,
    anterior_orometro DECIMAL(15,2) DEFAULT 0,
    proximo_orometro DECIMAL(15,2),
    estado ENUM('activo', 'mantenimiento', 'averiado', 'vendido', 'descanso') DEFAULT 'activo',
    limite DECIMAL(15,2) CHECK (limite BETWEEN 0 AND 1000000),
    notificacion DECIMAL(15,2) CHECK (notificacion BETWEEN 0 AND 1000),
    mantenimiento DECIMAL(15,2) CHECK (mantenimiento >= 0),
    observaciones TEXT,
    imagen VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT,
    INDEX idx_codigo (codigo)
);

-- Tabla de Mantenimiento Correctivo: Registra fallos imprevistos
CREATE TABLE mantenimiento_correctivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT,
    componente_id INT,
    descripcion_problema TEXT NOT NULL,
    orometro_actual DECIMAL(15,2),
    fecha_hora_problema DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'completado') DEFAULT 'pendiente',
    fecha_realizado DATETIME,
    observaciones TEXT,
    imagen VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT,
    FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE RESTRICT,
    CHECK (
        (equipo_id IS NOT NULL AND componente_id IS NULL) OR
        (equipo_id IS NULL AND componente_id IS NOT NULL)
    )
);

-- Tabla de Mantenimiento Preventivo: Registra mantenimientos automáticos basados en patrones
CREATE TABLE mantenimiento_preventivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT,
    componente_id INT,
    descripcion_razon TEXT NOT NULL,
    fecha_programada DATE,
    orometro_programado DECIMAL(15,2),
    estado ENUM('pendiente', 'completado', 'cancelado', 'correctivo') DEFAULT 'pendiente',
    fecha_realizado DATE,
    observaciones TEXT,
    imagen VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT,
    FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE RESTRICT,
    CHECK (
        (equipo_id IS NOT NULL AND componente_id IS NULL) OR
        (equipo_id IS NULL AND componente_id IS NOT NULL)
    )
);

-- Tabla de Mantenimiento Programado (Predictivo): Registra mantenimientos definidos por el usuario
CREATE TABLE mantenimiento_programado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT,
    componente_id INT,
    descripcion_razon TEXT NOT NULL,
    fecha_programada DATE,
    orometro_programado DECIMAL(15,2),
    estado ENUM('pendiente', 'completado') DEFAULT 'pendiente',
    fecha_realizado DATE,
    observaciones TEXT,
    imagen VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT,
    FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE RESTRICT,
    CHECK (
        (equipo_id IS NOT NULL AND componente_id IS NULL) OR
        (equipo_id IS NULL AND componente_id IS NOT NULL)
    )
);

-- Tabla de Historial de Mantenimiento: Almacena mantenimientos completados para reportes y calendarios
CREATE TABLE historial_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_mantenimiento ENUM('correctivo', 'preventivo', 'predictivo') NOT NULL,
    mantenimiento_id INT NOT NULL,
    equipo_id INT,
    componente_id INT,
    descripcion TEXT NOT NULL,
    fecha_realizado DATETIME NOT NULL,
    orometro_realizado DECIMAL(15,2),
    observaciones TEXT,
    imagen VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT,
    FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE RESTRICT,
    CHECK (
        (equipo_id IS NOT NULL AND componente_id IS NULL) OR
        (equipo_id IS NULL AND componente_id IS NOT NULL)
    )
);

-- Tabla de Notificaciones: Almacena notificaciones para mantenimientos preventivos
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    mantenimiento_preventivo_id INT,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (mantenimiento_preventivo_id) REFERENCES mantenimiento_preventivo(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_mantenimiento_preventivo_id (mantenimiento_preventivo_id)
);

-- Tabla de Historial de Trabajo de Equipos
CREATE TABLE historial_trabajo_equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    fecha DATE NOT NULL,
    horas_trabajadas DECIMAL(15,2) NOT NULL CHECK (horas_trabajadas >= 0),
    fuente ENUM('manual', 'automatico') NOT NULL DEFAULT 'manual',
    observaciones TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT,
    UNIQUE (equipo_id, fecha),
    INDEX idx_equipo_fecha (equipo_id, fecha)
);

-- Tabla de Historial de Trabajo de Componentes
CREATE TABLE historial_trabajo_componentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    componente_id INT NOT NULL,
    fecha DATE NOT NULL,
    horas_trabajadas DECIMAL(15,2) NOT NULL CHECK (horas_trabajadas >= 0),
    fuente ENUM('manual', 'automatico') NOT NULL DEFAULT 'manual',
    observaciones TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE RESTRICT,
    UNIQUE (componente_id, fecha),
    INDEX idx_componente_fecha (componente_id, fecha)
);

-- Tabla de Historial de Trabajo Diaro de equipos y componentes
CREATE TABLE historial_trabajo_diario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT,
    componente_id INT,
    fecha DATE NOT NULL,
    horas_trabajadas DECIMAL(15,2) NOT NULL CHECK (horas_trabajadas >= 0),
    fuente ENUM('manual', 'automatico') NOT NULL DEFAULT 'manual',
    observaciones TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT,
    FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE RESTRICT,
    UNIQUE (equipo_id, fecha),
    UNIQUE (componente_id, fecha),
    CHECK (
        (equipo_id IS NOT NULL AND componente_id IS NULL) OR
        (equipo_id IS NULL AND componente_id IS NOT NULL)
    )
);

-- =====================================================
-- INSERCIÓN DE DATOS INICIALES
-- =====================================================

-- Inserción inicial de módulos (CORREGIDA - HISTORIAL Y ESTADÍSTICA INDEPENDIENTES)
INSERT INTO modulos (nombre, descripcion) VALUES
('dashboard', 'Panel principal del sistema'),
('equipos', 'Gestión de equipos, máquinas, motores y equipos eléctricos'),
('componentes', 'Gestión de componentes de equipos'),
('mantenimientos', 'Gestión de mantenimientos'),
('mantenimientos.preventivo', 'Submódulo de mantenimiento preventivo'),
('mantenimientos.correctivo', 'Submódulo de mantenimiento correctivo'),
('mantenimientos.programado', 'Submódulo de mantenimiento programado (predictivo)'),
('historial', 'Módulo de historial de mantenimientos'),
('estadisticas', 'Módulo de estadísticas y reportes'),
('estadisticas.graficas', 'Submódulo de gráficas y visualización de datos'),
('administracion', 'Gestión administrativa'),
('administracion.usuarios', 'Submódulo de usuarios'),
('administracion.personal', 'Submódulo de personal'),
('administracion.roles_permisos', 'Submódulo de roles y permisos'),
('administracion.categorias', 'Submódulo de categorías de equipos'),
('administracion.horarios', 'Submódulo de administración de horarios de equipos y componentes');

-- Inserción inicial de permisos (CORREGIDA - HISTORIAL Y ESTADÍSTICA INDEPENDIENTES)
INSERT INTO permisos (modulo_id, nombre, descripcion) VALUES
-- Dashboard (modulo_id: 1)
(1, 'dashboard.acceder', 'Permite acceder al panel principal'),
(1, 'dashboard.ver', 'Permite ver el panel principal'),
-- Equipos (modulo_id: 2)
(2, 'equipos.acceder', 'Permite acceder al módulo de equipos'),
(2, 'equipos.ver', 'Permite ver la lista de equipos'),
(2, 'equipos.crear', 'Permite crear nuevos equipos'),
(2, 'equipos.editar', 'Permite editar equipos existentes'),
(2, 'equipos.eliminar', 'Permite eliminar equipos existentes'),
-- Componentes (modulo_id: 3)
(3, 'componentes.acceder', 'Permite acceder al módulo de componentes'),
(3, 'componentes.ver', 'Permite ver la lista de componentes'),
(3, 'componentes.crear', 'Permite crear nuevos componentes'),
(3, 'componentes.editar', 'Permite editar componentes existentes'),
(3, 'componentes.eliminar', 'Permite eliminar componentes existentes'),
-- Mantenimientos (modulo_id: 4)
(4, 'mantenimientos.acceder', 'Permite acceder al módulo de mantenimientos'),
(4, 'mantenimientos.ver', 'Permite ver todos los mantenimientos'),
-- Mantenimiento Preventivo (modulo_id: 5)
(5, 'mantenimientos.preventivo.acceder', 'Permite acceder al submódulo de mantenimiento preventivo'),
(5, 'mantenimientos.preventivo.ver', 'Permite ver mantenimientos preventivos'),
(5, 'mantenimientos.preventivo.crear', 'Permite crear mantenimientos preventivos'),
(5, 'mantenimientos.preventivo.editar', 'Permite editar mantenimientos preventivos'),
-- Mantenimiento Correctivo (modulo_id: 6)
(6, 'mantenimientos.correctivo.acceder', 'Permite acceder al submódulo de mantenimiento correctivo'),
(6, 'mantenimientos.correctivo.ver', 'Permite ver mantenimientos correctivos'),
(6, 'mantenimientos.correctivo.crear', 'Permite crear mantenimientos correctivos'),
(6, 'mantenimientos.correctivo.editar', 'Permite editar mantenimientos correctivos'),
-- Mantenimiento Programado (modulo_id: 7)
(7, 'mantenimientos.programado.acceder', 'Permite acceder al submódulo de mantenimiento programado (predictivo)'),
(7, 'mantenimientos.programado.ver', 'Permite ver mantenimientos programados'),
(7, 'mantenimientos.programado.crear', 'Permite crear mantenimientos programados'),
(7, 'mantenimientos.programado.editar', 'Permite editar mantenimientos programados'),
-- Historial (modulo_id: 8) - MÓDULO INDEPENDIENTE
(8, 'historial.acceder', 'Permite acceder al módulo de historial'),
(8, 'historial.ver', 'Permite ver el historial de mantenimientos'),
(8, 'historial.exportar', 'Permite exportar historial de mantenimientos'),
-- Estadísticas (modulo_id: 9) - MÓDULO INDEPENDIENTE
(9, 'estadisticas.acceder', 'Permite acceder al módulo de estadísticas'),
(9, 'estadisticas.ver', 'Permite ver el panel de estadísticas'),
(9, 'estadisticas.exportar', 'Permite exportar estadísticas y reportes'),
-- Estadísticas Gráficas (modulo_id: 10) - SUBMÓDULO DE ESTADÍSTICAS
(10, 'estadisticas.graficas.acceder', 'Permite acceder al submódulo de gráficas'),
(10, 'estadisticas.graficas.ver', 'Permite ver gráficas y visualizaciones'),
(10, 'estadisticas.graficas.exportar', 'Permite exportar gráficas y reportes'),
-- Administracion (modulo_id: 11)
(11, 'administracion.acceder', 'Permite acceder al módulo de administración'),
(11, 'administracion.ver', 'Permite ver el panel de administración'),
-- Administracion Usuarios (modulo_id: 12)
(12, 'administracion.usuarios.acceder', 'Permite acceder al submódulo de usuarios'),
(12, 'administracion.usuarios.ver', 'Permite ver la lista de usuarios'),
(12, 'administracion.usuarios.crear', 'Permite crear nuevos usuarios'),
(12, 'administracion.usuarios.editar', 'Permite editar usuarios existentes'),
-- Administracion Personal (modulo_id: 13)
(13, 'administracion.personal.acceder', 'Permite acceder al submódulo de personal'),
(13, 'administracion.personal.ver', 'Permite ver la lista de personal'),
(13, 'administracion.personal.crear', 'Permite crear nuevo personal'),
(13, 'administracion.personal.editar', 'Permite editar personal existente'),
-- Administracion Roles y Permisos (modulo_id: 14)
(14, 'administracion.roles_permisos.acceder', 'Permite acceder al submódulo de roles y permisos'),
(14, 'administracion.roles_permisos.ver', 'Permite ver roles y permisos'),
(14, 'administracion.roles_permisos.crear', 'Permite crear roles y permisos'),
(14, 'administracion.roles_permisos.editar', 'Permite editar roles y permisos'),
-- Administracion Categorías (modulo_id: 15)
(15, 'administracion.categorias.acceder', 'Permite acceder al submódulo de categorías'),
(15, 'administracion.categorias.ver', 'Permite ver la lista de categorías'),
(15, 'administracion.categorias.crear', 'Permite crear nuevas categorías'),
(15, 'administracion.categorias.editar', 'Permite editar categorías existentes'),
(15, 'administracion.categorias.eliminar', 'Permite eliminar categorías existentes'),
-- Administracion Horarios (modulo_id: 16)
(16, 'administracion.horarios.acceder', 'Permite acceder al submódulo de horarios'),
(16, 'administracion.horarios.ver', 'Permite ver el panel de horarios'),
(16, 'administracion.horarios.editar', 'Permite editar horarios predefinidos y manuales');

-- Inserción inicial de roles
INSERT INTO roles (nombre, descripcion) VALUES
('superadmin', 'Rol con acceso completo a todas las funcionalidades del sistema'),
('admin', 'Rol con acceso completo inicial, configurable posteriormente'),
('jefe', 'Rol para supervisores con acceso a equipos y mantenimientos'),
('invitado', 'Rol con permisos limitados, solo visualización');

-- Inserción inicial de roles_permisos (CORREGIDA - HISTORIAL Y ESTADÍSTICA INDEPENDIENTES)
INSERT INTO roles_permisos (rol_id, permiso_id) VALUES
-- Superadmin: Todos los permisos (1-50)
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18), (1, 19), (1, 20),
(1, 21), (1, 22), (1, 23), (1, 24), (1, 25), (1, 26), (1, 27), (1, 28), (1, 29), (1, 30),
(1, 31), (1, 32), (1, 33), (1, 34), (1, 35), (1, 36), (1, 37), (1, 38), (1, 39), (1, 40),
(1, 41), (1, 42), (1, 43), (1, 44), (1, 45), (1, 46), (1, 47), (1, 48), (1, 49), (1, 50),
-- Admin: Todos los permisos (igual que superadmin)
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10),
(2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18), (2, 19), (2, 20),
(2, 21), (2, 22), (2, 23), (2, 24), (2, 25), (2, 26), (2, 27), (2, 28), (2, 29), (2, 30),
(2, 31), (2, 32), (2, 33), (2, 34), (2, 35), (2, 36), (2, 37), (2, 38), (2, 39), (2, 40),
(2, 41), (2, 42), (2, 43), (2, 44), (2, 45), (2, 46), (2, 47), (2, 48), (2, 49), (2, 50),
-- Jefe: Permisos operativos (dashboard, equipos, componentes, mantenimientos, historial, estadísticas, ver categorías y horarios)
(3, 1), (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 9), (3, 10),
(3, 11), (3, 12), (3, 13), (3, 14), (3, 15), (3, 16), (3, 17), (3, 18), (3, 19), (3, 20),
(3, 21), (3, 22), (3, 23), (3, 24), (3, 25), (3, 26), (3, 27), (3, 28), (3, 29), (3, 30),
(3, 31), (3, 32), (3, 33), (3, 44), (3, 45), (3, 49), (3, 50),
-- Invitado: Solo visualización
(4, 1), (4, 2), (4, 3), (4, 4), (4, 8), (4, 9), (4, 13), (4, 14), (4, 17), (4, 18),
(4, 21), (4, 22), (4, 25), (4, 26), (4, 28), (4, 29), (4, 31), (4, 32), (4, 44), (4, 45), (4, 49), (4, 50);

-- Inserción inicial de categorías de equipos
INSERT INTO categorias_equipos (nombre, descripcion) VALUES
('Máquinas', 'Máquinas'),
('Componentes', 'Componentes de máquinas');

-- =====================================================
-- VERIFICACIÓN DE LA BASE DE DATOS
-- =====================================================

-- Verificar módulos creados
SELECT 'Módulos creados:' as verificacion, COUNT(*) as total FROM modulos;

-- Verificar permisos creados
SELECT 'Permisos creados:' as verificacion, COUNT(*) as total FROM permisos;

-- Verificar roles creados
SELECT 'Roles creados:' as verificacion, COUNT(*) as total FROM roles;

-- Verificar categorías creadas
SELECT 'Categorías creadas:' as verificacion, COUNT(*) as total FROM categorias_equipos;

-- Verificar permisos por rol
SELECT 'Permisos por rol:' as verificacion, r.nombre as rol, COUNT(rp.permiso_id) as total_permisos
FROM roles r
LEFT JOIN roles_permisos rp ON r.id = rp.rol_id
GROUP BY r.id, r.nombre
ORDER BY r.id;

-- Mostrar estructura de módulos y permisos
SELECT 'Estructura de permisos:' as verificacion, 
       m.nombre as modulo, 
       COUNT(p.id) as total_permisos
FROM modulos m
LEFT JOIN permisos p ON m.id = p.modulo_id
GROUP BY m.id, m.nombre
ORDER BY m.id;

-- =====================================================
-- BASE DE DATOS COMPLETA CREADA EXITOSAMENTE
-- =====================================================
