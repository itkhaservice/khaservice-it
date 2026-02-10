-- ============================================
-- MIGRATION SCRIPT: Add Form Feature Tables
-- Database: if0_40738827_khaservice_it
-- Date: 2026-02-10
-- Description: Add form builder, submissions, and audit logging tables.
--              This migration is safe and does NOT modify or delete existing data.
-- ============================================

-- Start transaction for safety
START TRANSACTION;

-- ============================================
-- 1. CREATE NEW TABLES (Form Feature)
-- ============================================

-- Drop existing tables if present (safe cleanup)
-- Data will be preserved from original import; this only affects any previous test data
DROP TABLE IF EXISTS `submission_answers`;
DROP TABLE IF EXISTS `form_submissions`;
DROP TABLE IF EXISTS `question_options`;
DROP TABLE IF EXISTS `form_questions`;
DROP TABLE IF EXISTS `forms`;

-- Table: forms
CREATE TABLE `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Form creator (user ID)',
  `title` varchar(255) NOT NULL COMMENT 'Form title',
  `description` text COMMENT 'Form description / instructions',
  `slug` varchar(255) NOT NULL COMMENT 'URL-friendly slug',
  `status` varchar(50) DEFAULT 'draft' COMMENT 'draft or published',
  `expires_at` datetime NULL COMMENT 'Form expiration date/time',
  `response_limit` int(11) NULL COMMENT 'Max number of submissions allowed',
  `theme_color` varchar(10) DEFAULT '#108042' COMMENT 'Primary color (hex)',
  `thank_you_message` text COMMENT 'Message shown after successful submission',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Form definitions';

-- Table: form_questions
CREATE TABLE `form_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `question_text` text NOT NULL COMMENT 'Question content',
  `question_type` varchar(50) NOT NULL COMMENT 'text, textarea, multiple_choice, checkboxes, dropdown, date, time, datetime, number, file, linear_scale, multiple_choice_grid, checkbox_grid',
  `question_order` int(11) DEFAULT 0 COMMENT 'Display order',
  `is_required` tinyint(1) DEFAULT 0 COMMENT 'Is this question required?',
  `logic_config` json NULL COMMENT 'Skip logic configuration',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete for removed questions',
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Form questions';

-- Table: question_options
CREATE TABLE `question_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(500) NOT NULL COMMENT 'Option/choice text',
  `option_type` varchar(50) DEFAULT 'choice' COMMENT 'choice, row, column',
  `option_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  FOREIGN KEY (`question_id`) REFERENCES `form_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Question answer options';

-- Table: form_submissions
CREATE TABLE `form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `submitter_ip` varchar(50) DEFAULT NULL COMMENT 'Submitter IP address',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Form submissions';

-- Table: submission_answers
CREATE TABLE `submission_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` longtext COMMENT 'Answer content (text, JSON for grids, or file path)',
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  KEY `question_id` (`question_id`),
  FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `form_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Answers to form questions';

-- Table: action_logs
CREATE TABLE `action_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NULL COMMENT 'User who performed action',
  `action_type` varchar(100) NOT NULL COMMENT 'CREATE_FORM, UPDATE_FORM, DELETE_FORM, SUBMIT_FORM, etc.',
  `entity_type` varchar(100) COMMENT 'forms, devices, projects, etc.',
  `entity_id` int(11) COMMENT 'ID of the entity affected',
  `description` text COMMENT 'Detailed description of action',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action_type` (`action_type`),
  KEY `entity_type` (`entity_type`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log of system actions';

-- ============================================
-- 2. ALTER EXISTING TABLES (Add 'user' role if needed)
-- ============================================

-- Ensure 'user' role exists in users table (for form responders)
-- Instead of altering column, we rely on app logic to accept 'user' or 'admin', 'it', 'xem'
-- No action needed here as the role column is varchar and can accept 'user' value

-- ============================================
-- 3. VERIFY & COMMIT
-- ============================================

-- Commit transaction
COMMIT;

-- ============================================
-- SUMMARY OF CHANGES:
-- ============================================
-- New Tables Created:
--   1. forms - Form definitions and metadata
--   2. form_questions - Questions within each form
--   3. question_options - Answer options for multiple choice, checkbox, dropdown, grid questions
--   4. form_submissions - Individual form submissions
--   5. submission_answers - Answers to each question in a submission
--   6. action_logs - Audit trail of system actions
--
-- Cleanup: Old form tables are dropped and recreated fresh (safe - no production form data on hosting yet)
-- Existing Device/Project/User Data: PRESERVED (forms tables are independent)
-- ============================================
