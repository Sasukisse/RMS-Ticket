-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 09 mai 2026 à 11:51
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `rms_ticket`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(19, 7, 'logout', '', NULL, '\"Déconnexion du panneau admin\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 09:28:50'),
(20, 9, 'ticket_assign', '', NULL, '\"Ticket ID: 7 assigné à user_id: 9\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 09:42:18'),
(21, 9, 'ticket_priority_update', '', NULL, '\"Ticket ID: 7, Nouvelle priorité: high\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 09:43:17'),
(22, 9, 'logout', '', NULL, '\"Déconnexion du panneau admin\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 09:45:33'),
(23, 7, 'logout', '', NULL, '\"Déconnexion du panneau admin\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-09 09:47:01');

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `category`, `created_at`) VALUES
(1, 'users.view', 'Voir les utilisateurs', 'Consulter la liste des utilisateurs', 'users', '2025-09-16 08:47:49'),
(2, 'users.create', 'Cr??er des utilisateurs', 'Cr??er de nouveaux comptes utilisateur', 'users', '2025-09-16 08:47:49'),
(3, 'users.edit', 'Modifier les utilisateurs', 'Modifier les informations utilisateur', 'users', '2025-09-16 08:47:49'),
(4, 'users.delete', 'Supprimer les utilisateurs', 'Supprimer des comptes utilisateur', 'users', '2025-09-16 08:47:49'),
(5, 'users.manage_roles', 'G??rer les r??les utilisateur', 'Assigner/retirer des r??les aux utilisateurs', 'users', '2025-09-16 08:47:49'),
(6, 'tickets.view_all', 'Voir tous les tickets', 'Consulter tous les tickets du syst??me', 'tickets', '2025-09-16 08:47:49'),
(7, 'tickets.view_own', 'Voir ses propres tickets', 'Consulter ses propres tickets', 'tickets', '2025-09-16 08:47:49'),
(8, 'tickets.create', 'Cr??er des tickets', 'Cr??er de nouveaux tickets', 'tickets', '2025-09-16 08:47:49'),
(9, 'tickets.edit', 'Modifier les tickets', 'Modifier les informations des tickets', 'tickets', '2025-09-16 08:47:49'),
(10, 'tickets.assign', 'Assigner les tickets', 'Assigner des tickets aux techniciens', 'tickets', '2025-09-16 08:47:49'),
(11, 'tickets.close', 'Fermer les tickets', 'Marquer les tickets comme r??solus/ferm??s', 'tickets', '2025-09-16 08:47:49'),
(12, 'tickets.delete', 'Supprimer les tickets', 'Supprimer des tickets', 'tickets', '2025-09-16 08:47:49'),
(13, 'roles.view', 'Voir les r??les', 'Consulter la liste des r??les', 'roles', '2025-09-16 08:47:49'),
(14, 'roles.create', 'Cr??er des r??les', 'Cr??er de nouveaux r??les', 'roles', '2025-09-16 08:47:49'),
(15, 'roles.edit', 'Modifier les r??les', 'Modifier les r??les existants', 'roles', '2025-09-16 08:47:49'),
(16, 'roles.delete', 'Supprimer les r??les', 'Supprimer des r??les', 'roles', '2025-09-16 08:47:49'),
(17, 'permissions.manage', 'G??rer les permissions', 'Assigner/retirer des permissions aux r??les', 'permissions', '2025-09-16 08:47:49'),
(18, 'admin.logs', 'Consulter les logs', 'Acc??der aux logs d\'administration', 'admin', '2025-09-16 08:47:49'),
(19, 'admin.settings', 'Param??tres syst??me', 'Modifier les param??tres du syst??me', 'admin', '2025-09-16 08:47:49'),
(20, 'admin.maintenance', 'Mode maintenance', 'Activer/d??sactiver le mode maintenance', 'admin', '2025-09-16 08:47:49');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`) VALUES
(2, 'admin', 'Administrateur', 'Acc??s ?? la plupart des fonctionnalit??s d\'administration', '2025-09-16 08:47:49'),
(3, 'support', 'Support Technique', 'Gestion des tickets et support utilisateur', '2025-09-16 08:47:49'),
(4, 'user', 'Utilisateur', 'Acc??s utilisateur standard', '2025-09-16 08:47:49');

-- --------------------------------------------------------

--
-- Structure de la table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 18),
(2, 19),
(3, 1),
(3, 6),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(3, 11),
(3, 12),
(3, 18),
(4, 7),
(4, 8);

-- --------------------------------------------------------

--
-- Structure de la table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` enum('materiel','logiciel','reseau','autre') NOT NULL,
  `type` enum('incident','demande') NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `assigned_to`, `title`, `description`, `category`, `type`, `priority`, `status`, `created_at`, `updated_at`, `closed_at`, `resolution_notes`) VALUES
(6, 11, NULL, 'Souris HS', 'Bonjour, \r\nMa souris d\'ordinateur ne fonctionne plus.\r\nSerait-il possible d\'en avoir une nouvelle ?\r\nMerci', 'materiel', 'demande', 'medium', 'open', '2026-05-09 09:31:30', '2026-05-09 09:31:30', NULL, NULL),
(7, 10, 9, 'Problème de démarrage Teams', 'Bonjour,\r\nLe logiciel Teams ne veut plus démarrer sur mon pc.\r\nPouvez-vous m\'aider à régler ce souci ?\r\nMerci à vous', 'logiciel', 'incident', 'high', 'in_progress', '2026-05-09 09:34:55', '2026-05-09 09:44:52', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `ticket_comments`
--

CREATE TABLE `ticket_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ticket_message_reads`
--

CREATE TABLE `ticket_message_reads` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ticket_responses`
--

CREATE TABLE `ticket_responses` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `response_text` text NOT NULL,
  `is_admin_response` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ticket_responses`
--

INSERT INTO `ticket_responses` (`id`, `ticket_id`, `user_id`, `response_text`, `is_admin_response`, `created_at`) VALUES
(15, 7, 9, 'Bonjour Sally Carrera, avez-vous déjà redémarré votre ordinateur ?', 1, '2026-05-09 09:44:52');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `numero_telephone` varchar(20) DEFAULT NULL,
  `droit` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `nom`, `prenom`, `email`, `password_hash`, `numero_telephone`, `droit`, `created_at`) VALUES
(1, 'Romain', 'Sanjivy', 'Romain', 'sanjivy.romain@gmail.com', '$2y$10$Ie9E49FbaXAECHTTHLQHlO9Y5isscMkmFMfS0rIXjGNusN57.sDAq', '0123456789', 2, '2026-02-17 08:25:31'),
(7, 'Jurys_SIO', 'SIO', 'Jurys', 'jurys.sio@gmail.com', '$2y$10$aUCRPgAmBm/UhjZnyOvfLOHwFpm3TYMkxs6rlnmLfslJxnz1RPl22', '0123456789', 2, '2026-04-08 08:40:48'),
(8, 'Samuel', 'Tardy', 'Samuel', 'samuel.tardy78@gmail.com', '$2y$10$ydqTCuGoNDPQCCsYKZHRCu9wN7Mg0fDpexXdaM8hcVAAybMZRfvNO', '0123456789', 2, '2026-05-05 06:42:34'),
(9, 'Dylan', 'Dupont', 'Dylan', 'dylan.dupont@gmail.com', '$2y$10$4.cDuEsiaT5zqWIPrjvVg.8XPngT53ywS4KAok8XMaiIAZLlCy4gy', '0123456789', 1, '2026-05-05 06:45:27'),
(10, 'Sally', 'Carrera', 'Sally', 'sally.carrera@gmail.com', '$2y$10$.JfFGEsrxW100flQ5vItl.bsKGHumMYw4R7n.dw04W9HqdOU/1m6O', '0123456789', 0, '2026-05-05 06:46:02'),
(11, 'Pope', 'Raylon', 'Pope', 'pope.raylon@gmail.com', '$2y$10$eFMhZOUGydk/x5XD5dMXRe/z2c7IM/enohNNq77fD1HyjJMELvS8i', '0123456789', 0, '2026-05-05 06:46:52');

-- --------------------------------------------------------

--
-- Structure de la table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_category` (`category`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Index pour la table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `ticket_message_reads`
--
ALTER TABLE `ticket_message_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_ticket_user` (`ticket_id`,`user_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_ticket` (`ticket_id`);

--
-- Index pour la table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_users_email` (`email`),
  ADD UNIQUE KEY `uniq_users_username` (`username`);

--
-- Index pour la table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Index pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ticket_message_reads`
--
ALTER TABLE `ticket_message_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=317;

--
-- AUTO_INCREMENT pour la table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD CONSTRAINT `ticket_comments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
