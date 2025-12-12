# sl.amarsite.top version-1.02

# database a update korte hobe
* first shool_notes table a next_meet name a column add korte hobe
==> ALTER TABLE school_notes 
ADD COLUMN next_meet DATETIME NULL AFTER note_date;

* new table add  korte hobe 
==> CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `note_id` bigint(20) unsigned NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `action_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_note_id` (`note_id`),
  CONSTRAINT `fk_notifications_note` FOREIGN KEY (`note_id`) 
      REFERENCES `school_notes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;






# code file update
* layout_header_index.php 
=> ball icon add
* index file 
=> note add modal a next_meet name input add kora 
* core/add_note_core.php
=> new input ar logic add kora

* new file add notificaitons.php



# feature add
1. notification systme
2. work school notes




# old data insert on note_logs query

==> INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text, action_at)
SELECT
    NULL AS note_id,
    s.id AS school_id,
    s.created_by AS user_id,
    'create school' AS action,
    NULL AS old_text,
    JSON_OBJECT(
        'district', s.district,
        'upazila', s.upazila,
        'school_name', s.school_name,
        'mobile', s.mobile,
        'status', s.status,
        'photo_path', s.photo_path
    ) AS new_text,
    s.created_at AS action_at
FROM schools s;

==> INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text, action_at)
SELECT
    NULL AS note_id,
    s.id AS school_id,
    s.updated_by AS user_id,
    'update school' AS action,
    NULL AS old_text,
    JSON_OBJECT(
        'district', s.district,
        'upazila', s.upazila,
        'school_name', s.school_name,
        'mobile', s.mobile,
        'status', s.status,
        'photo_path', s.photo_path
    ) AS new_text,
    s.updated_at AS action_at
FROM schools s
WHERE s.updated_at > s.created_at;







# feature ==> invoice

3 ta table import korte hobe 
data base thik korte hobe





next feature => schools.php te School List lekhar pashe search box diye search system banabo.