-- ============================================================
-- THE_LEGACY_HOUSE - Export Base de Données
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `the_legacy_house` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `the_legacy_house`;

-- -----------------------------------------------
-- Table : users
-- -----------------------------------------------
CREATE TABLE `users` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `pseudo`     VARCHAR(60)     NOT NULL,
  `email`      VARCHAR(180)    NOT NULL,
  `password`   VARCHAR(255)    NOT NULL,
  `avatar`     VARCHAR(255)    DEFAULT NULL,
  `role`       ENUM('member','admin') NOT NULL DEFAULT 'member',
  `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Table : categories
-- -----------------------------------------------
CREATE TABLE `categories` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(10)  DEFAULT '📦',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`name`, `icon`) VALUES
('Électronique',  '📱'),
('Mobilier',      '🪑'),
('Vêtements',     '👗'),
('Livres & BD',   '📚'),
('Sport & Loisir','⚽'),
('Maison & Jardin','🌿'),
('Jeux & Jouets', '🎮'),
('Véhicules',     '🚗'),
('Autre',         '📦');

-- -----------------------------------------------
-- Table : annonces
-- -----------------------------------------------
CREATE TABLE `annonces` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    NOT NULL,
  `category_id` INT UNSIGNED    DEFAULT NULL,
  `titre`       VARCHAR(200)    NOT NULL,
  `description` TEXT            NOT NULL,
  `prix`        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `etat`        ENUM('neuf','bon_etat','correct','pour_pieces') NOT NULL DEFAULT 'bon_etat',
  `image`       VARCHAR(255)    DEFAULT NULL,
  `views`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_annonce_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_annonce_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Table : favoris
-- -----------------------------------------------
CREATE TABLE `favoris` (
  `user_id`    INT UNSIGNED NOT NULL,
  `annonce_id` INT UNSIGNED NOT NULL,
  `added_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `annonce_id`),
  CONSTRAINT `fk_fav_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_fav_annonce` FOREIGN KEY (`annonce_id`) REFERENCES `annonces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Table : discussions
-- -----------------------------------------------
CREATE TABLE `discussions` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `annonce_id` INT UNSIGNED NOT NULL,
  `buyer_id`   INT UNSIGNED NOT NULL,
  `seller_id`  INT UNSIGNED NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_thread` (`annonce_id`, `buyer_id`),
  CONSTRAINT `fk_disc_annonce` FOREIGN KEY (`annonce_id`) REFERENCES `annonces`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_disc_buyer`   FOREIGN KEY (`buyer_id`)   REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_disc_seller`  FOREIGN KEY (`seller_id`)  REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Table : messages
-- -----------------------------------------------
CREATE TABLE `messages` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `discussion_id` INT UNSIGNED NOT NULL,
  `sender_id`     INT UNSIGNED NOT NULL,
  `contenu`       TEXT         NOT NULL,
  `lu`            TINYINT(1)   NOT NULL DEFAULT 0,
  `sent_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `discussion_id` (`discussion_id`),
  CONSTRAINT `fk_msg_disc`   FOREIGN KEY (`discussion_id`) REFERENCES `discussions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`)     REFERENCES `users`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Compte admin par défaut
-- password : Admin1234!
-- -----------------------------------------------
INSERT INTO `users` (`pseudo`, `email`, `password`, `role`) VALUES
('Admin', 'admin@the_legacy_house.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
