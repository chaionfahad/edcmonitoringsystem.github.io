-- EDC Monitoring System - Database Schema
-- Run this SQL to initialize the database

CREATE DATABASE IF NOT EXISTS edc_monitoring;
USE edc_monitoring;

-- Users table (Admin / Vendor roles)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `role` enum('admin','vendor') NOT NULL DEFAULT 'vendor',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Institutions table
CREATE TABLE `institutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `pppoe_user` varchar(200) NOT NULL COMMENT 'PPPoE username on MikroTik',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Optional static IP',
  `type` enum('govt','private','others') NOT NULL DEFAULT 'others' COMMENT 'govt = government, private, others',
  `thana` varchar(100) DEFAULT NULL COMMENT 'Thana/Upazila name',
  `union_name` varchar(100) DEFAULT NULL COMMENT 'Union/Ward name',
  `vendor_id` int(11) NOT NULL,
  `current_status` enum('online','offline','unknown') NOT NULL DEFAULT 'unknown',
  `last_checked` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `type` (`type`),
  CONSTRAINT `institutions_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Status logs table
CREATE TABLE `logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `status` enum('online','offline') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `institution_id` (`institution_id`),
  KEY `timestamp` (`timestamp`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MikroTik settings table (singleton row)
CREATE TABLE `settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `mt_ip` varchar(45) NOT NULL,
  `mt_api_port` int(11) NOT NULL DEFAULT 8728,
  `mt_username` varchar(100) NOT NULL,
  `mt_password` varchar(255) NOT NULL,
  `check_interval` int(11) NOT NULL DEFAULT 60 COMMENT 'seconds between checks',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `status`) VALUES
('admin', '$2y$10$gu78EQhAe7NslekYCKzHw.0K.8jX6QXWiiQ5AUIbo/74f5dEyMbwu', 'Administrator', 'admin', 1);

-- Insert default MikroTik settings placeholder
INSERT INTO `settings` (`id`, `mt_ip`, `mt_api_port`, `mt_username`, `mt_password`) VALUES
(1, '192.168.88.1', 8728, 'api_user', 'api_password');

-- Add head_name and mobile columns for institutions
ALTER TABLE `institutions` ADD COLUMN IF NOT EXISTS `head_name` varchar(200) DEFAULT NULL AFTER `name`;
ALTER TABLE `institutions` ADD COLUMN IF NOT EXISTS `mobile` varchar(11) DEFAULT NULL AFTER `head_name`;
ALTER TABLE `institutions` ADD COLUMN IF NOT EXISTS `address` text DEFAULT NULL AFTER `mobile`;

-- Comments table for vendor-admin communication
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `institution_id` (`institution_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_super_admin` tinyint(1) NOT NULL DEFAULT 0 AFTER `role`;
