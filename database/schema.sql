-- Brainware University Employee Subject Selection System
-- MySQL Schema Specification
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+, and PlanetScale/Aiven/Railway

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Faculty Table
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `submission_subjects`;
DROP TABLE IF EXISTS `submissions`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `faculty`;

CREATE TABLE `faculty` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_faculty_email` (`email`),
  INDEX `idx_faculty_emp_code` (`employee_code`),
  INDEX `idx_faculty_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Subjects Table
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_code` VARCHAR(50) NOT NULL,
  `subject_name` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_subject_code` (`subject_code`),
  INDEX `idx_subject_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Submissions Table (Enforces ONE EMAIL / ONE FACULTY = ONE FINAL SUBMISSION)
CREATE TABLE `submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL,
  `faculty_email` VARCHAR(191) NOT NULL,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(20) NOT NULL DEFAULT 'SUBMITTED',
  UNIQUE KEY `uniq_sub_faculty_id` (`faculty_id`),
  UNIQUE KEY `uniq_sub_faculty_email` (`faculty_email`),
  INDEX `idx_sub_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_submissions_faculty_id` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submissions_faculty_email` FOREIGN KEY (`faculty_email`) REFERENCES `faculty` (`email`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Submission Subjects Table (Preserves exact 1-to-5 order and prevents duplicates)
CREATE TABLE `submission_subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `submission_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `selection_order` INT NOT NULL,
  UNIQUE KEY `uniq_sub_order` (`submission_id`, `selection_order`),
  UNIQUE KEY `uniq_sub_subject` (`submission_id`, `subject_id`),
  INDEX `idx_sub_subj_subid` (`submission_id`),
  INDEX `idx_sub_subj_subjid` (`subject_id`),
  CONSTRAINT `fk_sub_subjects_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Admins / HOD Table
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Rate Limiting Table (For persistent brute-force defense)
CREATE TABLE `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_attempts_ip_time` (`ip_address`, `attempted_at`),
  INDEX `idx_attempts_email_time` (`email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
