-- Creación de la tabla ROLES
-- id_rol: Identificador único de cada rol
-- nombre: Nombre del rol

CREATE TABLE ROLES (
    id_rol SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Creación de la tabla SALAS
-- id_sala: Identificador único de cada sala
-- nombre: Nombre de la sala
-- capacidad_max: Capacidad máxima de la sala

CREATE TABLE SALAS (
    id_sala SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    capacidad_max INT NOT NULL
);

-- Creación de la tabla ACTIVIDADES
-- id_actividad: Identificador único de cada actividad
-- nombre: Nombre de la actividad
-- descripcion: Descripción de la actividad

CREATE TABLE ACTIVIDADES (
    id_actividad SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

-- Creación de la tabla USUARIOS
-- id_usuario: Identificador único de cada usuario
-- nombre: Nombre del usuario
-- apellidos: Apellidos del usuario
-- email: Email del usuario
-- telefono: Teléfono del usuario
-- hash_password: Contraseña del usuario
-- id_rol: Identificador del rol del usuario

CREATE TABLE USUARIOS (
    id_usuario SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    hash_password VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    -- Relación con la tabla ROLES (RESTRICT para que no se pueda borrar un rol que tenga usuarios)
    CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol) REFERENCES ROLES(id_rol) ON DELETE RESTRICT
);

-- Creación de la tabla CLASES
-- id_clase: Identificador único de cada clase
-- fecha: Fecha de la clase
-- hora_inicio: Hora de inicio de la clase
-- hora_fin: Hora de fin de la clase
-- id_sala: Identificador de la sala de la clase
-- id_actividad: Identificador de la actividad de la clase
-- id_usuario: Identificador del usuario que imparte la clase

CREATE TABLE CLASES (
    id_clase SERIAL PRIMARY KEY,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    cupo_maximo INT NOT NULL,
    id_sala INT NOT NULL,
    id_actividad INT NOT NULL,
    id_usuario INT NOT NULL, -- Profesor de la clase
    -- Relación con la tabla SALAS (RESTRICT para que no se pueda borrar una sala que tenga clases)
    CONSTRAINT fk_clases_salas FOREIGN KEY (id_sala) REFERENCES SALAS(id_sala) ON DELETE RESTRICT,
    -- Relación con la tabla ACTIVIDADES (CASCADE para que se borren las clases si se borra una actividad)
    CONSTRAINT fk_clases_actividades FOREIGN KEY (id_actividad) REFERENCES ACTIVIDADES(id_actividad) ON DELETE CASCADE,
    -- Relación con la tabla USUARIOS (RESTRICT para que no se pueda borrar un profesor que tenga clases)
    CONSTRAINT fk_clases_usuarios FOREIGN KEY (id_usuario) REFERENCES USUARIOS(id_usuario) ON DELETE RESTRICT
);

-- Creación de la tabla RESERVAS
-- id_reserva: Identificador único de cada reserva
-- fecha_creacion: Fecha de creación de la reserva
-- estado: Estado de la reserva
-- id_usuario: Identificador del usuario que hace la reserva
-- id_clase: Identificador de la clase que se reserva

CREATE TABLE RESERVAS (
    id_reserva SERIAL PRIMARY KEY,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(50) NOT NULL,
    id_usuario INT NOT NULL,
    id_clase INT NOT NULL,
    -- Relación con la tabla USUARIOS (CASCADE para que si se borra un usuario se borren sus reservas)
    CONSTRAINT fk_reservas_usuarios FOREIGN KEY (id_usuario) REFERENCES USUARIOS(id_usuario) ON DELETE CASCADE,
    -- Relación con la tabla CLASES (CASCADE para que se borren las reservas si se borra una clase)
    CONSTRAINT fk_reservas_clases FOREIGN KEY (id_clase) REFERENCES CLASES(id_clase) ON DELETE CASCADE,
    -- Restricción para que un usuario no pueda reservar dos veces la misma clase
    CONSTRAINT chk_reservas_unicas UNIQUE (id_usuario, id_clase)
);
