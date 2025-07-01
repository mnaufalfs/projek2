CREATE TABLE IF NOT EXISTS `order_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `note_type` enum('general','issue','payment','vehicle','driver','customer') NOT NULL DEFAULT 'general',
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `order_notes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `rental_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_notes_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 