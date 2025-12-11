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
 KEY `fk_notes_updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

school_notes table note_text column a note gula ase