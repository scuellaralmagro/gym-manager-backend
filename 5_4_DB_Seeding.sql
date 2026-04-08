-- Inserción de ROLES
INSERT INTO ROLES (nombre) VALUES 
('Administrador'), 
('Entrenador'), 
('Cliente');

-- Inserción de SALAS
INSERT INTO SALAS (nombre, capacidad_max) VALUES 
('Sala Principal (Musculación)', 30),
('Sala Actividades 1', 15),
('Sala Actividades 2', 20),
('Sala Ciclo Indoor', 12),
('Espacio Exterior', 25);

-- Inserción e ACTIVIDADES
INSERT INTO ACTIVIDADES (nombre, descripcion) VALUES 
('Yoga', 'Clase de hatha yoga para todos los niveles.'),
('Pilates', 'Tipo de gimnasia que se centra en movilidad y fuerza.'),
('Ciclo Indoor', 'Entrenamiento tipo cardio realizado en bicicleta estática.'),
('Cross-Training', 'Circuito de fuerza y resistencia con pesas y peso corporal.'),
('Zumba', 'Clase aeróbica con ritmos latinos orientada a todos los públicos.');

-- Inserción de USUARIOS
-- Nota: Las contraseñas son ejemplos, en la práctica serán hasheadas por el backend.
INSERT INTO USUARIOS (nombre, apellidos, email, telefono, hash_password, id_rol) VALUES 
('Sergio', 'Cuéllar', 'admin@gymmanager.com', '+34600111222', '$2y$10$EjemploHashPassword12345', 1),
('Laura', 'Gómez', 'laura.entrenadora@gymmanager.com', '+34611222333', '$2y$10$EjemploHashPassword12345', 2),
('Miguel', 'López', 'miguel.entrenador@gymmanager.com', '+34622333444', '$2y$10$EjemploHashPassword12345', 2),
('Ana', 'Martínez', 'ana.cliente@email.com', '+34633444555', '$2y$10$EjemploHashPassword12345', 3),
('David', 'Pérez', 'david.cliente@email.com', '+34644555666', '$2y$10$EjemploHashPassword12345', 3),
('Sara', 'Ruiz', 'sara.cliente@email.com', '+34655666777', '$2y$10$EjemploHashPassword12345', 3),
('Jorge', 'Sánchez', 'jorge.cliente@email.com', '+34666777888', '$2y$10$EjemploHashPassword12345', 3);

-- Inserción de CLASES
INSERT INTO CLASES (fecha, hora_inicio, hora_fin, cupo_maximo, id_sala, id_actividad, id_usuario) VALUES 
('2026-03-02', '18:00:00', '19:00:00', 15, 2, 1, 2), -- id 1: Yoga con Laura en Sala Act 1
('2026-03-03', '10:00:00', '11:30:00', 17, 3, 2, 2), -- id 2: Pilates con Laura en Sala Act 2
('2026-03-03', '19:00:00', '20:00:00', 12, 4, 3, 3), -- id 3: Ciclo con Miguel en Ciclo Indoor
('2026-03-04', '19:00:00', '20:00:00', 25, 1, 4, 3), -- id 4: Cross-Training con Miguel en Sala Principal
('2026-03-04', '20:00:00', '21:00:00', 20, 3, 5, 2), -- id 5: Zumba con Laura en Sala Act 2
('2026-03-06', '10:00:00', '11:00:00', 15, 2, 1, 2); -- id 6: Yoga con Laura en Sala Act 1

-- Inserción de RESERVAS
-- La fecha de creación se autogenera con NOW()
INSERT INTO RESERVAS (estado, id_usuario, id_clase) VALUES 
('Activa', 4, 1), -- Ana reserva Yoga (Lunes)
('Activa', 5, 1), -- David reserva Yoga (Lunes)
('Cancelada', 6, 2), -- Sara canceló Pilates (Martes)
('Activa', 4, 3), -- Ana reserva Ciclo (Martes)
('Activa', 7, 3), -- Jorge reserva Ciclo (Martes)
('Activa', 5, 4), -- David reserva Cross-Training (Miércoles)
('Activa', 6, 4), -- Sara reserva Cross-Training (Miércoles)
('Activa', 7, 5); -- Jorge reserva Zumba (Miércoles)