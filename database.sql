-- ============================================================
-- CampusGuard: Campus Incident Reporting System
-- Database Schema + Seed Data
--
-- HOW TO IMPORT:
--   Option 1 (phpMyAdmin):
--     - Create database:  campus_guard
--     - Click Import → upload this file → Go
--
--   Option 2 (Terminal):
--     mysql -u root -p < database.sql
--
--   Password for ALL demo accounts:  Password1!
-- ============================================================

CREATE DATABASE IF NOT EXISTS campus_guard
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE campus_guard;

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    student_no  VARCHAR(30)  NOT NULL,
    fullname    VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('student','technician','admin') NOT NULL DEFAULT 'student',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(60) NOT NULL,
    icon          VARCHAR(10) NOT NULL DEFAULT '📋'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS incidents (
    incident_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    category_id  INT NOT NULL,
    title        VARCHAR(255) NOT NULL,
    description  TEXT NOT NULL,
    location     VARCHAR(150) NOT NULL,
    priority     ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    status       ENUM('Open','Assigned','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
    attachment   VARCHAR(255) DEFAULT NULL,
    assigned_to  INT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
    comment_id  INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    user_id     INT NOT NULL,
    comment     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(user_id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    notif_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    incident_id INT NOT NULL,
    message     VARCHAR(255) NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(user_id)         ON DELETE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO categories (category_name, icon) VALUES
  ('Network',        '📶'),
  ('Security',       '🛡️'),
  ('Infrastructure', '🏗️'),
  ('Academic',       '🎓');

-- All demo passwords = Password1!
INSERT INTO users (student_no, fullname, email, password, role) VALUES
  ('ADMIN-001',   'System Admin',  'admin@campusguard.ac.ke',  '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LeMMBw7rQdX5x9oiO', 'admin'),
  ('TECH-001',    'Daniel Mwangi', 'daniel@campusguard.ac.ke', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LeMMBw7rQdX5x9oiO', 'technician'),
  ('BIT-0231-23', 'Jane Wanjiru',  'jane@campusguard.ac.ke',   '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LeMMBw7rQdX5x9oiO', 'student'),
  ('BIT-0198-23', 'Brian Otieno',  'brian@campusguard.ac.ke',  '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LeMMBw7rQdX5x9oiO', 'student');

INSERT INTO incidents (user_id, category_id, title, description, location, priority, status, assigned_to) VALUES
  (3, 1, 'WiFi unavailable in LT4',
   'Students in LT4 cannot connect. Authentication fails on all devices. About 60 students affected.',
   'Lecture Theatre 4', 'High', 'Open', NULL),
  (4, 2, 'Suspicious phishing email received',
   'Email claiming to be from IT asking for credentials. Link goes to external site.',
   'Student Email', 'High', 'In Progress', 2),
  (3, 3, 'Projector not powering on in Room 212',
   'The projector stopped mid-lecture. Power light does not come on.',
   'Room 212, Block B', 'Low', 'Resolved', 2),
  (4, 4, 'LMS not loading assignment uploads',
   'Cannot upload files on Moodle. Page times out. Deadline tomorrow.',
   'Online (Moodle)', 'Medium', 'Closed', NULL);

INSERT INTO comments (incident_id, user_id, comment) VALUES
  (1, 1, 'Received. Marked High priority — technician notified.'),
  (1, 3, 'Still down as of 10am. Lecturer is using mobile hotspot.'),
  (2, 2, 'Phishing domain blocked at firewall level.'),
  (2, 4, 'Thank you for the quick response!'),
  (3, 2, 'Replaced power cable. Projector is working.'),
  (3, 3, 'Confirmed working now. Thank you!');

INSERT INTO notifications (user_id, incident_id, message) VALUES
  (3, 1, 'Your incident #1 has been received and is under review.'),
  (4, 2, 'Your incident #2 status changed to In Progress.'),
  (3, 3, 'Your incident #3 has been Resolved.');
