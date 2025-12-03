CREATE TABLE `note_logs` (
 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `note_id` bigint(20) unsigned DEFAULT NULL,
 `school_id` bigint(20) unsigned DEFAULT NULL,
 `user_id` int(10) unsigned DEFAULT NULL,
 `action` enum('create','update','delete','restore') NOT NULL,
 `old_text` text DEFAULT NULL,
 `new_text` text DEFAULT NULL,
 `action_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `idx_note_logs_note_id` (`note_id`),
 KEY `idx_note_logs_school_id` (`school_id`),
 KEY `idx_note_logs_user_id` (`user_id`),
 CONSTRAINT `fk_note_logs_note` FOREIGN KEY (`note_id`) REFERENCES `school_notes` (`id`) ON DELETE SET NULL,
 CONSTRAINT `fk_note_logs_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL,
 CONSTRAINT `fk_note_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `schools` (
 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `district` varchar(100) NOT NULL,
 `upazila` varchar(100) NOT NULL,
 `school_name` varchar(255) NOT NULL,
 `mobile` varchar(20) DEFAULT NULL,
 `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
 `photo_path` varchar(255) DEFAULT NULL,
 `updated_by` int(10) unsigned DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `fk_schools_updated_by` (`updated_by`),
 CONSTRAINT `fk_schools_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `school_notes` (
 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `school_id` bigint(20) unsigned NOT NULL,
 `note_text` text NOT NULL,
 `note_date` datetime NOT NULL DEFAULT current_timestamp(),
 `updated_by` int(10) unsigned DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `fk_school_notes_school` (`school_id`),
 KEY `fk_notes_updated_by` (`updated_by`),
 CONSTRAINT `fk_notes_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
 CONSTRAINT `fk_school_notes_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `trash_schools` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `school_id` int(11) DEFAULT NULL,
 `district` varchar(150) DEFAULT NULL,
 `upazila` varchar(150) DEFAULT NULL,
 `school_name` varchar(255) DEFAULT NULL,
 `mobile` varchar(50) DEFAULT NULL,
 `status` varchar(50) DEFAULT NULL,
 `photo_path` varchar(255) DEFAULT NULL,
 `deleted_by` int(11) DEFAULT NULL,
 `deleted_at` timestamp NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci

CREATE TABLE `users` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` varchar(100) NOT NULL,
 `username` varchar(50) NOT NULL,
 `password` varchar(255) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
