-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-10-2026 a las 20:52:08
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.5.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mosca_crud`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidas`
--

CREATE TABLE `partidas` (
  `id` int(11) NOT NULL,
  `tablero` varchar(50) NOT NULL,
  `intentos` int(11) NOT NULL,
  `activo` int(1) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `partidas`
--

INSERT INTO `partidas` (`id`, `tablero`, `intentos`, `activo`, `id_usuario`) VALUES
(3, '{\"tamano\":10,\"intentos_iniciales\":5,\"pos_mosca\":1,', 5, 0, 2),
(4, '{\"tamano\":10,\"intentos_iniciales\":5,\"pos_mosca\":1,', 0, 0, 2),
(5, '{\"tamano\":10,\"intentos_iniciales\":5,\"pos_mosca\":1,', 0, 0, 2),
(6, '{\"tamano\":10,\"intentos_iniciales\":5,\"pos_mosca\":1,', 0, 0, 2),
(7, '{\"tamano\":10,\"intentos_iniciales\":5,\"pos_mosca\":1,', 0, 0, 2),
(8, '{\"tamano\":10,\"intentos_iniciales\":5,\"pos_mosca\":1,', 0, 0, 2),
(9, '{\"tamano\":10,\"intentos_iniciales\":5,\"pos_mosca\":1,', 0, 0, 2),
(10, '{\"tamano\":10,\"intentos_iniciales\":5,\"pos_mosca\":1,', 99, 0, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `rol`) VALUES
(1, 0),
(2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles_asignados`
--

CREATE TABLE `roles_asignados` (
  `id` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles_asignados`
--

INSERT INTO `roles_asignados` (`id`, `id_rol`, `id_usuario`) VALUES
(2, 1, 2),
(3, 1, 3),
(4, 2, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `user` varchar(50) NOT NULL,
  `edad` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pass` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `user`, `edad`, `email`, `pass`) VALUES
(2, 'jugador1', 40, 'jugador1@email.com', 'e10adc3949ba59abbe56e057f20f883e'),
(3, 'jugador2', 25, 'jugador2@gmail.com', 'e10adc3949ba59abbe56e057f20f883e'),
(4, 'admin', 25, 'admin@gmail.com', 'e10adc3949ba59abbe56e057f20f883e');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `partidas`
--
ALTER TABLE `partidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_user2` (`id_usuario`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles_asignados`
--
ALTER TABLE `roles_asignados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_rol` (`id_rol`),
  ADD KEY `fk_id_user` (`id_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `roles_asignados`
--
ALTER TABLE `roles_asignados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `partidas`
--
ALTER TABLE `partidas`
  ADD CONSTRAINT `fk_id_user2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `roles_asignados`
--
ALTER TABLE `roles_asignados`
  ADD CONSTRAINT `fk_id_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_id_user` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
