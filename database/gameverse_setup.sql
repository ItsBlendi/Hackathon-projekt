-- Create database
CREATE DATABASE IF NOT EXISTS `gameverse` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gameverse`;

-- Create houses table
CREATE TABLE IF NOT EXISTS `houses` (
  `house_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `color` varchar(20) DEFAULT '#6c757d',
  `total_xp` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`house_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create games table
CREATE TABLE IF NOT EXISTS `games` (
  `game_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text,
  `slug` varchar(50) NOT NULL,
  `difficulty` enum('Easy','Medium','Hard') DEFAULT 'Medium',
  `icon` varchar(10) DEFAULT '🎮',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`game_id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `house_id` int(11) DEFAULT NULL,
  `xp` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `house_id` (`house_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`house_id`) REFERENCES `houses` (`house_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create scores table
CREATE TABLE IF NOT EXISTS `scores` (
  `score_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `house_id` int(11) DEFAULT NULL,
  `game_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `xp_earned` int(11) NOT NULL,
  `played_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`score_id`),
  KEY `user_id` (`user_id`),
  KEY `house_id` (`house_id`),
  KEY `game_id` (`game_id`),
  CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`house_id`) REFERENCES `houses` (`house_id`) ON DELETE SET NULL,
  CONSTRAINT `scores_ibfk_3` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default houses
INSERT INTO `houses` (`name`, `description`, `color`) VALUES
('Gryffindor', 'Brave and daring', '#740001'),
('Ravenclaw', 'Wise and clever', '#0e1a40'),
('Hufflepuff', 'Loyal and hardworking', '#ecb939'),
('Slytherin', 'Ambitious and cunning', '#1a472a')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Insert default games
INSERT INTO `games` (`title`, `description`, `slug`, `difficulty`, `icon`) VALUES
('Flappy Bird', 'Guide the bird through pipes', 'flappy-bird', 'Medium', '🐦'),
('Reaction Rush', 'Test your reaction time', 'reaction-rush', 'Easy', '⚡'),
('Number Ninja', 'Solve math problems quickly', 'number-ninja', 'Medium', '🔢'),
('Memory Grid', 'Match pairs of cards', 'memory-grid', 'Medium', '🧠'),
('Dodge Squares', 'Avoid falling squares', 'dodge-squares', 'Hard', '🎮')
ON DUPLICATE KEY UPDATE `title`=`title`;
