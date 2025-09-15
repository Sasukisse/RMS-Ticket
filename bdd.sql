CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `numero_telephone` VARCHAR(20) DEFAULT NULL,
  `droit` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  UNIQUE KEY `uniq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion du compte administrateur Mehdi
INSERT INTO `users` (`username`, `nom`, `prenom`, `email`, `password_hash`, `numero_telephone`, `droit`) 
VALUES ('mehdi', 'Benali', 'Mehdi', 'mehdi@admin.com', '$2y$10$SBvx/fLk4EGQYrXgVwMEl.T7GBfHa5eN72YRJJ7upoPjtZ9SA72B.', '+33123456789', 2);