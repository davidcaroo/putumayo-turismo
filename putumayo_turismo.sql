-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-02-2026 a las 03:05:47
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `putumayo_turismo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `id` int(11) NOT NULL,
  `destino_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `duracion` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `actividades`
--

INSERT INTO `actividades` (`id`, `destino_id`, `nombre`, `descripcion`, `imagen`, `precio`, `duracion`, `activo`) VALUES
(8, 16, 'caminata', 'nnnnn', 'actividad_695a8bba11b51.jpeg', 50000.00, '2 horas', 1),
(9, 19, 'caminata', 'hhhhh', 'actividad_695a8cae8e771.jpeg', 50000.00, '2 horas', 1),
(10, 22, 'Cabalgata', 'xxxxxxxxxxx', 'actividad_696c44869099e.jpg', 150000.00, '1 hora', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'Destino eliminado:  (ID: 7)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 15:04:44'),
(2, 3, 'eliminar_actividad', 'Actividad eliminada: City Tour Mocoa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:07:41'),
(3, 3, 'eliminar_actividad', 'Actividad eliminada: Senderismo a la Cascada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:07:46'),
(4, 3, 'eliminar_actividad', 'Actividad eliminada: Baño en Río Sangoyaco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:07:54'),
(5, 3, 'toggle_actividad', 'Actividad desactivada: Avistamiento de Aves', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:07:58'),
(6, 3, 'toggle_actividad', 'Actividad activada: Avistamiento de Aves', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:08:00'),
(7, 3, 'eliminar_actividad', 'Actividad eliminada: Avistamiento de Aves', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:08:03'),
(8, 3, 'toggle_actividad', 'Actividad desactivada: Tour Cultural Sibundoy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:09:36'),
(9, 3, 'toggle_actividad', 'Actividad activada: Tour Cultural Sibundoy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:10:11'),
(10, NULL, 'change_user_role', 'Rol cambiado a admin para usuario ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:57:02'),
(11, 3, 'activate_user', 'Usuario activado ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 20:05:41'),
(12, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 17:40:01'),
(13, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 17:43:41'),
(14, 3, 'subir_imagen', 'Imagen subida: PRUEBA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 17:45:37'),
(15, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 18:04:28'),
(16, 3, 'editar_imagen', 'Imagen editada: PRUEBA (ID: 1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 18:08:54'),
(17, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 18:09:44'),
(18, 3, 'subir_imagen', 'Imagen subida: prueba 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 18:10:34'),
(19, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 18:17:34'),
(20, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 18:17:57'),
(21, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 18:40:04'),
(22, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 19:19:13'),
(23, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 23:38:01'),
(24, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 23:41:24'),
(25, 3, 'editar_imagen', 'Imagen editada: prueba 2 (ID: 2)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 23:42:27'),
(26, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 23:46:21'),
(27, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 23:47:09'),
(28, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 23:50:55'),
(29, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 23:52:17'),
(30, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 02:23:09'),
(31, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 02:26:34'),
(32, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 03:15:59'),
(33, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:08:45'),
(34, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:16:14'),
(35, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:17:44'),
(36, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:29:51'),
(37, 3, 'admin_login', 'Inicio de sesión administrativo: admin@putumayoturismo.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:30:50'),
(38, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:39:09'),
(39, 6, 'user_login', 'Inicio de sesión: angel@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:39:49'),
(40, 6, 'user_login', 'Inicio de sesión desde: reserva.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:45:02'),
(41, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reserva.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:50:33'),
(42, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php?destino=19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 04:59:44'),
(43, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:04:35'),
(44, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:08:51'),
(45, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:09:20'),
(46, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:11:49'),
(47, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:12:55'),
(48, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:19:37'),
(49, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:20:56'),
(50, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: destino-detalle.php?id=16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:24:00'),
(51, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:24:15'),
(52, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:24:37'),
(53, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php?destino=19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:29:17'),
(54, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:30:30'),
(55, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:33:27'),
(56, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 05:33:53'),
(57, 7, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reserva.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 06:03:28'),
(58, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 06:07:00'),
(59, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 06:09:29'),
(60, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: destino-detalle.php?id=16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 00:05:33'),
(61, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 00:08:47'),
(62, 3, 'deactivate_user', 'Usuario desactivado ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 00:12:38'),
(63, 3, 'activate_user', 'Usuario activado ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 00:13:04'),
(64, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: putumayo_tourism', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 14:26:06'),
(65, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 14:27:01'),
(66, 3, 'update_review', 'Reseña ID 2 actualizada a estado: aprobado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 14:36:05'),
(67, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 14:36:58'),
(68, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 14:38:08'),
(69, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php?destino=16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 14:51:56'),
(70, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 15:34:20'),
(71, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 15:35:17'),
(72, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 15:47:42'),
(73, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 15:48:23'),
(74, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php?destino=16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 15:50:57'),
(75, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: destino-detalle.php?id=19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 15:52:32'),
(76, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 18:55:28'),
(77, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php?destino=16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 19:01:26'),
(78, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 19:02:10'),
(79, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 19:15:51'),
(80, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 19:16:16'),
(81, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 20:54:24'),
(82, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 20:54:55'),
(83, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 21:39:29'),
(84, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php', '::1', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36 Edg/143.0.0.0', '2026-01-04 22:23:09'),
(85, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:07:40'),
(86, 3, 'update_config', 'Configuración general actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:08:03'),
(87, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:08:21'),
(88, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:15:58'),
(89, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:17:11'),
(90, 3, 'update_config', 'Configuración general actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:21:29'),
(91, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:21:41'),
(92, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:25:39'),
(93, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:26:20'),
(94, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:30:24'),
(95, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:30:42'),
(96, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:34:24'),
(97, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:34:37'),
(98, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:34:47'),
(99, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:35:00'),
(100, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:35:12'),
(101, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:35:20'),
(102, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:36:27'),
(103, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:37:27'),
(104, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:48:35'),
(105, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:48:50'),
(106, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: reservas.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-04 23:59:44'),
(107, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 00:15:13'),
(108, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 00:15:26'),
(109, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 00:15:38'),
(110, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 00:16:08'),
(111, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0', '2026-01-05 00:29:39'),
(112, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 00:57:00'),
(113, 3, 'update_config', 'Configuración general actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 00:57:24'),
(114, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 00:57:39'),
(115, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 01:23:23'),
(116, 3, 'update_config', 'Configuración general actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 01:25:17'),
(117, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 01:38:38'),
(118, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 01:47:00'),
(119, 3, 'update_social', 'Configuración de redes sociales actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 01:47:57'),
(120, 3, 'update_social', 'Configuración de redes sociales actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 01:48:14'),
(121, 3, 'update_review', 'Reseña ID 1 actualizada a estado: aprobado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 02:02:28'),
(122, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: galeria.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 02:16:02'),
(123, 3, 'update_social', 'Configuración de redes sociales actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 02:23:36'),
(124, 3, 'update_whatsapp_chatbot', 'Configuración del chatbot WhatsApp actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 03:12:47'),
(125, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 03:18:48'),
(126, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 03:38:55'),
(127, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 03:40:18'),
(128, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 03:40:51'),
(129, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 03:51:11'),
(130, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 04:40:49'),
(131, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 04:48:32'),
(132, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 04:49:06'),
(133, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 05:26:17'),
(134, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 05:37:56'),
(135, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: dashboard.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 05:44:26'),
(136, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 05:45:37'),
(137, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: destino-detalle.php?id=25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 16:03:13'),
(138, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: dashboard.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 17:16:17'),
(139, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: login.php?type=usuario&redirect=', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 17:31:08'),
(140, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: dashboard.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 17:32:10'),
(141, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: dashboard.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 17:33:15'),
(142, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 17:33:41'),
(143, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 17:34:48'),
(144, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: login.php?type=usuario&redirect=', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 02:20:30'),
(145, 3, 'update_appearance', 'Apariencia del sitio actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 02:29:57'),
(146, 3, 'update_footer', 'Configuración del footer actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 02:30:24'),
(147, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: dashboard.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 18:31:16'),
(148, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: login.php?type=admin&redirect=', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 18:41:52'),
(149, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php?welcome=1&login_success=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 18:48:52'),
(150, 6, 'user_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php?welcome=1&login_success=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 18:49:14'),
(151, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: destinos.php?welcome=1&login_success=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 19:23:40'),
(152, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: index.php?welcome=1&login_success=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 19:33:55'),
(153, 3, 'admin_login', 'Inicio de sesión exitoso. Redirigiendo a: dashboard.php?welcome=1&login_success=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 21:11:06'),
(154, 3, 'update_config', 'Configuración general actualizada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 23:04:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `condiciones_transporte`
--

CREATE TABLE `condiciones_transporte` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `icono` varchar(50) DEFAULT 'fas fa-car',
  `orden` int(11) DEFAULT 1,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `condiciones_transporte`
--

INSERT INTO `condiciones_transporte` (`id`, `titulo`, `descripcion`, `icono`, `orden`, `activo`, `fecha_creacion`) VALUES
(1, 'VAN', 'vehiculo para 10 personas maximo', 'fas fa-car', 1, 1, '2026-01-04 19:00:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL COMMENT 'Clave única de configuración',
  `valor` text DEFAULT NULL COMMENT 'Valor de la configuración',
  `categoria` varchar(50) DEFAULT 'general' COMMENT 'Categoría: general, apariencia, footer, carrusel, seo, social',
  `tipo` varchar(30) DEFAULT 'texto' COMMENT 'Tipo: texto, color, número, booleano, url, email, archivo',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción del propósito de esta configuración',
  `orden` int(11) DEFAULT 0 COMMENT 'Orden de visualización',
  `editable` tinyint(1) DEFAULT 1 COMMENT 'Si el usuario puede editar esta configuración',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuraciones del sistema';

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `config_key`, `valor`, `categoria`, `tipo`, `descripcion`, `orden`, `editable`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Turismo Putumayo', 'general', 'texto', 'Nombre del sitio web', 1, 1, '2026-01-04 23:21:14', '2026-01-31 23:04:27'),
(2, 'site_description', 'Descubre la belleza del Putumayo', 'general', 'texto', 'Descripción del sitio web', 2, 1, '2026-01-04 23:21:14', '2026-01-31 23:04:27'),
(3, 'site_keywords', 'turismo, putumayo, viajes, naturaleza', 'general', 'texto', 'Palabras clave para SEO', 3, 1, '2026-01-04 23:21:14', '2026-01-31 23:04:27'),
(4, 'contact_email', 'info@putumayoturismo.com', 'general', 'email', 'Email de contacto principal', 4, 1, '2026-01-04 23:21:14', '2026-01-31 23:04:27'),
(5, 'contact_phone', '+57 3025191138', 'general', 'texto', 'Teléfono de contacto', 5, 1, '2026-01-04 23:21:14', '2026-01-31 23:04:27'),
(6, 'contact_address', 'Mocoa, Putumayo, Colombia', 'general', 'texto', 'Dirección física', 6, 1, '2026-01-04 23:21:14', '2026-01-31 23:04:27'),
(7, 'primary_color', '#045403', 'apariencia', 'color', 'Color primario del sitio', 11, 1, '2026-01-04 23:21:14', '2026-01-18 02:29:57'),
(8, 'secondary_color', '#f8fafc', 'apariencia', 'color', 'Color secundario del sitio', 12, 1, '2026-01-04 23:21:14', '2026-01-18 02:29:57'),
(9, 'accent_color', '#000000', 'apariencia', 'color', 'Color de acento', 13, 1, '2026-01-04 23:21:14', '2026-01-18 02:29:57'),
(10, 'font_family', 'Inter', 'apariencia', 'texto', 'Fuente principal del sitio', 14, 1, '2026-01-04 23:21:14', '2026-01-18 02:29:57'),
(11, 'logo_text', 'Turismo putumayo', 'apariencia', 'texto', 'Texto alternativo del logo', 15, 1, '2026-01-04 23:21:14', '2026-01-18 02:29:57'),
(12, 'logo_file', '', 'apariencia', 'archivo', 'Archivo del logo del sitio', 16, 1, '2026-01-04 23:21:14', '2026-01-04 23:36:14'),
(13, 'favicon_file', '', 'apariencia', 'archivo', 'Archivo del favicon', 17, 1, '2026-01-04 23:21:14', '2026-01-04 23:36:14'),
(14, 'footer_text', '© 2026 Turismo Putumayo. Todos los derechos reservados.', 'footer', 'texto', 'Texto del pie de página', 21, 1, '2026-01-04 23:21:14', '2026-01-18 02:30:24'),
(15, 'facebook_url', 'https://facebook.com/putumayoturismo', 'footer', 'url', 'URL de Facebook', 22, 1, '2026-01-04 23:21:14', '2026-01-18 02:30:24'),
(16, 'instagram_url', 'https://instagram.com/putumayoturismo', 'footer', 'url', 'URL de Instagram', 23, 1, '2026-01-04 23:21:14', '2026-01-18 02:30:24'),
(17, 'twitter_url', 'https://twitter.com/putumayoturismo', 'footer', 'url', 'URL de Twitter/X', 24, 1, '2026-01-04 23:21:14', '2026-01-18 02:30:24'),
(18, 'whatsapp_number', '+573025191138', 'footer', 'texto', 'Número de WhatsApp', 25, 1, '2026-01-04 23:21:14', '2026-01-18 02:30:24'),
(19, 'show_social', '1', 'footer', 'booleano', 'Mostrar enlaces a redes sociales', 26, 1, '2026-01-04 23:21:14', '2026-01-18 02:30:24'),
(20, 'carousel_speed', '5000', 'carrusel', 'número', 'Velocidad del carrusel en milisegundos', 31, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(21, 'carousel_autoplay', '1', 'carrusel', 'booleano', 'Reproducción automática del carrusel', 32, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(22, 'show_indicators', '1', 'carrusel', 'booleano', 'Mostrar indicadores del carrusel', 33, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(23, 'show_controls', '1', 'carrusel', 'booleano', 'Mostrar controles del carrusel', 34, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(24, 'meta_title', 'Putumayo Turismo', 'seo', 'texto', 'Meta título por defecto', 41, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(25, 'meta_description', 'Descubre la belleza del Putumayo', 'seo', 'texto', 'Meta descripción por defecto', 42, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(26, 'meta_keywords', 'turismo, putumayo, viajes, naturaleza', 'seo', 'texto', 'Meta keywords por defecto', 43, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(27, 'enable_og_tags', '1', 'seo', 'booleano', 'Habilitar Open Graph tags', 44, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(28, 'enable_schema', '1', 'seo', 'booleano', 'Habilitar Schema markup', 45, 1, '2026-01-04 23:21:14', '2026-01-04 23:21:14'),
(29, 'social_facebook', 'https://facebook.com/putumayoturismo', 'social', 'url', 'URL completa de Facebook', 51, 1, '2026-01-04 23:21:14', '2026-01-05 02:23:36'),
(30, 'social_instagram', 'https://instagram.com/putumayoturismo', 'social', 'url', 'URL completa de Instagram', 52, 1, '2026-01-04 23:21:14', '2026-01-05 02:23:36'),
(31, 'social_twitter', '', 'social', 'url', 'URL completa de Twitter/X', 53, 1, '2026-01-04 23:21:14', '2026-01-05 02:23:36'),
(32, 'social_youtube', '', 'social', 'url', 'URL completa de YouTube', 54, 1, '2026-01-04 23:21:14', '2026-01-05 02:23:36'),
(33, 'social_linkedin', '', 'social', 'url', 'URL completa de LinkedIn', 55, 1, '2026-01-04 23:21:14', '2026-01-05 02:23:36'),
(34, 'social_whatsapp', '+573025191138', 'social', 'texto', 'Número de WhatsApp para compartir', 56, 1, '2026-01-04 23:21:14', '2026-01-05 02:23:36'),
(35, 'social_share', '1', 'social', 'booleano', 'Habilitar botones para compartir', 57, 1, '2026-01-04 23:21:14', '2026-01-05 02:23:36'),
(43, 'social_tiktok', '', 'general', 'texto', NULL, 0, 1, '2026-01-05 02:23:36', '2026-01-05 02:23:36'),
(44, 'whatsapp_titulo', 'Chat con Asesores', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(45, 'whatsapp_descripcion', 'Selecciona un asesor para chatear', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(46, 'whatsapp_mensaje_default', 'Hola, estoy interesado en información sobre turismo en Putumayo', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(47, 'whatsapp_color_primario', '#25d366', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(48, 'whatsapp_color_secundario', '#128c7e', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(49, 'whatsapp_posicion', 'derecha', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(50, 'whatsapp_auto_abrir', '0', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(51, 'whatsapp_mostrar_horarios', '0', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(52, 'whatsapp_mostrar_especialidades', '0', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47'),
(53, 'whatsapp_activo', '1', 'general', 'texto', NULL, 0, 1, '2026-01-05 03:12:47', '2026-01-05 03:12:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones_backup`
--

CREATE TABLE `configuraciones_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'text',
  `categoria` varchar(50) DEFAULT 'general',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuraciones_backup`
--

INSERT INTO `configuraciones_backup` (`id`, `clave`, `valor`, `tipo`, `categoria`, `fecha_actualizacion`) VALUES
(1, 'site_name', 'Putumayo Turismo', 'text', 'general', '2025-12-15 19:14:51'),
(2, 'site_description', 'Descubre la belleza del Putumayo', 'textarea', 'general', '2025-12-15 19:14:51'),
(3, 'primary_color', '#2E8B57', 'color', 'appearance', '2025-12-15 19:14:51'),
(4, 'footer_text', '© 2024 Putumayo Turismo. Todos los derechos reservados.', 'textarea', 'footer', '2025-12-15 19:14:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones_old`
--

CREATE TABLE `configuraciones_old` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'text',
  `categoria` varchar(50) DEFAULT 'general',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `descripcion` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `editable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuraciones_old`
--

INSERT INTO `configuraciones_old` (`id`, `clave`, `valor`, `tipo`, `categoria`, `fecha_actualizacion`, `descripcion`, `orden`, `editable`) VALUES
(1, 'site_name', 'Putumayo Turismo', 'text', 'general', '2025-12-15 19:14:51', NULL, 0, 1),
(2, 'site_description', 'Descubre la belleza del Putumayo', 'textarea', 'general', '2025-12-15 19:14:51', NULL, 0, 1),
(3, 'primary_color', '#2E8B57', 'color', 'appearance', '2025-12-15 19:14:51', NULL, 0, 1),
(4, 'footer_text', '© 2024 Putumayo Turismo. Todos los derechos reservados.', 'textarea', 'footer', '2025-12-15 19:14:51', NULL, 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `destinos`
--

CREATE TABLE `destinos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `imagen_principal` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `destinos`
--

INSERT INTO `destinos` (`id`, `nombre`, `slug`, `descripcion`, `ubicacion`, `imagen_principal`, `activo`, `orden`, `fecha_creacion`) VALUES
(15, 'Santiago', 'santiago', 'Municipio conocido por su biodiversidad y paisajes', 'Putumayo, Colombia', '697e70a68a1ea_1769894054_255408187_3439202919640006_2303870955975306695_n.png', 1, 1, '2025-12-23 02:11:40'),
(16, 'Colón', 'colon', 'Destino con rica cultura y tradiciones indígenas', 'Putumayo, Colombia', '697e70b566879_1769894069_apple-m4-macbook-pro-lead-672b861685fd0.png', 1, 2, '2025-12-23 02:11:40'),
(17, 'Sibundoy', 'sibundoy', 'Valle interandino famoso por su festival y artesan', 'Putumayo, Colombia', '697e70bf6c9d4_1769894079_255408187_3439202919640006_2303870955975306695_n.png', 1, 3, '2025-12-23 02:11:40'),
(18, 'San Francisco', 'san-francisco', 'Pueblo con hermosas cascadas y rutas de senderismo', 'Putumayo, Colombia', '697e70c8e5112_1769894088_255408187_3439202919640006_2303870955975306695_n.png', 1, 4, '2025-12-23 02:11:40'),
(19, 'Mocoa', 'mocoa', 'Capital del Putumayo, puerta de entrada a la Amazo', 'Putumayo, Colombia', '697e70ffb9fc9_1769894143_Steven_lvarez_-_Founder_Unova__1_-removebg-preview.png', 1, 5, '2025-12-23 02:11:40'),
(20, 'Villagarzón', 'villagarzon', 'Municipio con diversos atractivos naturales y ecot', 'Putumayo, Colombia', '697e710ebb644_1769894158_LogoRedCiudadanadeLibertadReligiosaBolvar.png', 1, 6, '2025-12-23 02:11:40'),
(21, 'Orito', 'orito', 'Conocido por su producción agrícola y paisajes', 'Putumayo, Colombia', '697e711878ce6_1769894168_LogoRedCiudadanadeLibertadReligiosaBolvar.png', 1, 7, '2025-12-23 02:11:40'),
(22, 'Puerto Asís', 'puerto-asis', 'Importante puerto fluvial y centro comercial', 'Putumayo, Colombia', '697e7124aa4be_1769894180_Image_fx5.png', 1, 8, '2025-12-23 02:11:40'),
(23, 'Valle del Guamuez', 'valle-del-guamuez', 'Región con diversidad cultural y natural', 'Putumayo, Colombia', '697e714a5ae66_1769894218_Image_fx5.png', 1, 9, '2025-12-23 02:11:40'),
(24, 'San Miguel', 'san-miguel', 'Fronterizo con Ecuador, con paisajes andinos', 'Putumayo, Colombia', NULL, 1, 10, '2025-12-23 02:11:40'),
(25, 'Puerto Caicedo', 'puerto-caicedo', 'Municipio con rica biodiversidad amazónica', 'Putumayo, Colombia', NULL, 1, 11, '2025-12-23 02:11:40'),
(26, 'Puerto Guzmán', 'puerto-guzman', 'Destino ecoturístico con selvas vírgenes', 'Putumayo, Colombia', NULL, 1, 12, '2025-12-23 02:11:40'),
(27, 'Puerto Leguízamo', 'puerto-leguizamo', 'Ubicado en el corazón de la Amazonía colombiana', 'Putumayo, Colombia', NULL, 1, 13, '2025-12-23 02:11:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `destino_imagenes`
--

CREATE TABLE `destino_imagenes` (
  `id` int(11) NOT NULL,
  `destino_id` int(11) DEFAULT NULL,
  `imagen` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `descripcion_corta` varchar(500) DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `tipo_evento` varchar(50) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT 0.00,
  `capacidad_max` int(11) DEFAULT 0,
  `inscripciones_actual` int(11) DEFAULT 0,
  `destacado` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `galeria`
--

CREATE TABLE `galeria` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `carrusel` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `galeria`
--

INSERT INTO `galeria` (`id`, `titulo`, `descripcion`, `imagen`, `categoria`, `activo`, `fecha_subida`, `carrusel`) VALUES
(1, 'PRUEBA', 'HHHHHHH', '69443dc10265e_1766079937.jpg', 'general', 1, '2025-12-18 17:45:37', 1),
(2, 'prueba 2', 'jjjjjjjj', '6944439ab33b3_1766081434.jpg', 'general', 1, '2025-12-18 18:10:34', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_reservas`
--

CREATE TABLE `historial_reservas` (
  `id` int(11) NOT NULL,
  `reserva_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `detalles` text DEFAULT NULL,
  `fecha_accion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_reservas`
--

INSERT INTO `historial_reservas` (`id`, `reserva_id`, `usuario_id`, `accion`, `detalles`, `fecha_accion`) VALUES
(1, 7, 6, 'modificacion', '{\"nombre_anterior\":\"angel\",\"nombre_nuevo\":\"angel\",\"email_anterior\":\"angel@gmail.com\",\"email_nuevo\":\"angel@gmail.com\",\"telefono_anterior\":\"1111111111\",\"telefono_nuevo\":\"1111111111\",\"cantidad_personas_anterior\":1,\"cantidad_personas_nuevo\":2,\"fecha_viaje_anterior\":\"2026-01-06\",\"fecha_viaje_nuevo\":\"2026-01-06\",\"precio_total_anterior\":null,\"precio_total_nuevo\":100000,\"notas_anterior\":\"\",\"notas_nuevo\":\"\",\"metodo_pago_anterior\":\"efectivo\",\"metodo_pago_nuevo\":\"efectivo\"}', '2026-01-05 01:07:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas`
--

CREATE TABLE `resenas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `destino_id` int(11) DEFAULT NULL,
  `comentario` text NOT NULL,
  `calificacion` int(11) DEFAULT NULL CHECK (`calificacion` >= 1 and `calificacion` <= 5),
  `aprobado` tinyint(1) DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas_destino`
--

CREATE TABLE `resenas_destino` (
  `id` int(11) NOT NULL,
  `destino_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `calificacion` int(11) NOT NULL CHECK (`calificacion` >= 1 and `calificacion` <= 5),
  `titulo` varchar(200) NOT NULL,
  `comentario` text NOT NULL,
  `respuesta` text DEFAULT NULL,
  `estado` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resenas_destino`
--

INSERT INTO `resenas_destino` (`id`, `destino_id`, `usuario_id`, `nombre`, `email`, `telefono`, `calificacion`, `titulo`, `comentario`, `respuesta`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 16, 6, 'Usuario', 'sin-email@ejemplo.com', NULL, 5, 'Genial, excelente servicio', 'excelente lugar', '', 'aprobado', '2025-12-23 06:02:14', '2026-01-05 02:02:28'),
(2, 15, 7, 'Usuario', 'sin-email@ejemplo.com', NULL, 4, 'Genial, excelente servicio', 'excelente lugar, muy hermoso', '', 'aprobado', '2025-12-23 06:06:46', '2026-01-04 14:36:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `destino_id` int(11) DEFAULT NULL,
  `actividad_id` int(11) DEFAULT NULL,
  `fecha_reserva` date DEFAULT NULL,
  `fecha_viaje` date DEFAULT NULL,
  `cantidad_personas` int(11) DEFAULT NULL,
  `precio_total` decimal(10,2) DEFAULT NULL,
  `estado` enum('pendiente','confirmada','cancelada') DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `codigo_reserva` varchar(20) DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `usuario_id`, `nombre`, `email`, `telefono`, `destino_id`, `actividad_id`, `fecha_reserva`, `fecha_viaje`, `cantidad_personas`, `precio_total`, `estado`, `fecha_creacion`, `codigo_reserva`, `metodo_pago`, `notas`, `fecha_modificacion`) VALUES
(7, 6, 'angel', 'angel@gmail.com', '1111111111', 16, 8, NULL, '2026-01-06', 2, 100000.00, 'confirmada', '2026-01-04 19:16:09', 'RES-000007-202601', 'efectivo', '', '2026-01-05 06:07:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios_reserva`
--

CREATE TABLE `servicios_reserva` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT 0.00,
  `categoria` varchar(50) DEFAULT 'General',
  `orden` int(11) DEFAULT 1,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios_reserva`
--

INSERT INTO `servicios_reserva` (`id`, `nombre`, `descripcion`, `precio`, `categoria`, `orden`, `activo`, `fecha_creacion`) VALUES
(1, 'Almuerzo', '', 20000.00, 'General', 1, 1, '2026-01-04 19:01:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('superadmin','admin','usuario') DEFAULT 'usuario',
  `activo` tinyint(1) DEFAULT 0,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `telefono`, `password`, `rol`, `activo`, `fecha_registro`) VALUES
(3, 'Superadmin', 'admin@putumayoturismo.com', NULL, '$2y$10$V8y4TtknA3liCKW4rNOvBOksm2.aIzWpP2UIIdE6vthV8ZXGT4iz6', 'superadmin', 1, '2025-11-30 16:48:36'),
(5, 'David', 'david@gmail.com', NULL, '$2y$10$UdLsS3IIwBvup5f/HhUuiORu/f4lIvG8LT5v/BSqnqpNq2PMUjIp2', 'admin', 1, '2025-12-15 18:46:12'),
(6, 'angel', 'angel@gmail.com', '1111111111', '$2y$10$.zaBa07R9a23VVShYMGKLevqrSTYLg4Tw4lDvPIW6san6pfx8UH1e', 'usuario', 1, '2025-12-15 20:05:12'),
(7, 'july', 'july@gmail.com', '3222222222', '$2y$10$pNiUcIWS1OP3dEX/UYYn7e0YE0dfTjheY.uFPdYKGpRYoKZdKarUm', 'usuario', 1, '2025-12-23 06:03:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `whatsapp_asesores`
--

CREATE TABLE `whatsapp_asesores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `numero_whatsapp` varchar(20) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `especialidad` varchar(200) DEFAULT NULL,
  `horario` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `whatsapp_asesores`
--

INSERT INTO `whatsapp_asesores` (`id`, `nombre`, `numero_whatsapp`, `cargo`, `especialidad`, `horario`, `avatar`, `activo`, `orden`, `created_at`, `updated_at`) VALUES
(6, 'Edwin ', '+573025191138', 'Gerente', 'Desarrollador web ', 'lun-vie 8 am - 5 pm', '', 1, 1, '2026-01-18 02:38:29', '2026-01-18 02:38:29');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destino_id` (`destino_id`);

--
-- Indices de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `condiciones_transporte`
--
ALTER TABLE `condiciones_transporte`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Indices de la tabla `configuraciones_old`
--
ALTER TABLE `configuraciones_old`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Indices de la tabla `destinos`
--
ALTER TABLE `destinos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indices de la tabla `destino_imagenes`
--
ALTER TABLE `destino_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destino_id` (`destino_id`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug_eventos` (`slug`);

--
-- Indices de la tabla `galeria`
--
ALTER TABLE `galeria`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_reservas`
--
ALTER TABLE `historial_reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reserva_id` (`reserva_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destino_id` (`destino_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `resenas_destino`
--
ALTER TABLE `resenas_destino`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_destino_estado` (`destino_id`,`estado`),
  ADD KEY `idx_usuario_destino` (`usuario_id`,`destino_id`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_reserva` (`codigo_reserva`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `destino_id` (`destino_id`),
  ADD KEY `actividad_id` (`actividad_id`);

--
-- Indices de la tabla `servicios_reserva`
--
ALTER TABLE `servicios_reserva`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `whatsapp_asesores`
--
ALTER TABLE `whatsapp_asesores`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT de la tabla `condiciones_transporte`
--
ALTER TABLE `condiciones_transporte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `configuraciones_old`
--
ALTER TABLE `configuraciones_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `destinos`
--
ALTER TABLE `destinos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `destino_imagenes`
--
ALTER TABLE `destino_imagenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `galeria`
--
ALTER TABLE `galeria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `historial_reservas`
--
ALTER TABLE `historial_reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `resenas`
--
ALTER TABLE `resenas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resenas_destino`
--
ALTER TABLE `resenas_destino`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `servicios_reserva`
--
ALTER TABLE `servicios_reserva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `whatsapp_asesores`
--
ALTER TABLE `whatsapp_asesores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD CONSTRAINT `actividades_ibfk_1` FOREIGN KEY (`destino_id`) REFERENCES `destinos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `destino_imagenes`
--
ALTER TABLE `destino_imagenes`
  ADD CONSTRAINT `destino_imagenes_ibfk_1` FOREIGN KEY (`destino_id`) REFERENCES `destinos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_reservas`
--
ALTER TABLE `historial_reservas`
  ADD CONSTRAINT `historial_reservas_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`),
  ADD CONSTRAINT `historial_reservas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD CONSTRAINT `resenas_ibfk_1` FOREIGN KEY (`destino_id`) REFERENCES `destinos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resenas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `resenas_destino`
--
ALTER TABLE `resenas_destino`
  ADD CONSTRAINT `resenas_destino_ibfk_1` FOREIGN KEY (`destino_id`) REFERENCES `destinos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resenas_destino_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`destino_id`) REFERENCES `destinos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_3` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
