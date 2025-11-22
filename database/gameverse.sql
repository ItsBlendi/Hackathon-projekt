-- Create houses table
CREATE TABLE `houses` (
  `house_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `color` varchar(20) DEFAULT NULL,
  `total_xp` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`house_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create games table
CREATE TABLE [games](cci:7://file:///c:/xampp/htdocs/GameVerse/Hackathon-projekt/pages/games:0:0-0:0) (
  `game_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text,
  `difficulty` enum('Easy','Medium','Hard') DEFAULT 'Medium',
  `icon` varchar(10) DEFAULT '🎮',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`game_id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create users table
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `house_id` int(11) DEFAULT NULL,
  `xp` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `house_id` (`house_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create scores table
CREATE TABLE `scores` (
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
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add foreign key constraints
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`house_id`) REFERENCES `houses` (`house_id`) ON DELETE SET NULL;

ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`house_id`) REFERENCES `houses` (`house_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scores_ibfk_3` FOREIGN KEY (`game_id`) REFERENCES [games](cci:7://file:///c:/xampp/htdocs/GameVerse/Hackathon-projekt/pages/games:0:0-0:0) (`game_id`) ON DELETE CASCADE;

-- Insert default houses
INSERT INTO `houses` (`house_id`, `name`, `description`, `color`) VALUES
(1, 'Hipsters', 'The creative and innovative ones', '#00ff9d'),
(2, 'Speedsters', 'Fast and precise', '#00b8ff'),
(3, 'Engineers', 'Masters of logic and strategy', '#ff00c8'),
(4, 'Shadows', 'Stealth and precision', '#ff3c00');

-- Insert default games
INSERT INTO [games](cci:7://file:///c:/xampp/htdocs/GameVerse/Hackathon-projekt/pages/games:0:0-0:0) (`game_id`, `title`, `slug`, `description`, `difficulty`, `icon`) VALUES
(1, 'Reaction Rush', 'reaction-rush', 'Test your reflexes! Click when the screen changes color.', 'Easy', '⚡'),
(2, 'Number Ninja', 'number-ninja', 'Solve math problems under pressure!', 'Medium', '🔢'),
(3, 'Memory Grid', 'memory-grid', 'Match pairs in this memory challenge!', 'Medium', '🧠'),
(4, 'Dodge Squares', 'dodge-squares', 'Avoid the red tiles and survive!', 'Hard', '🎮');