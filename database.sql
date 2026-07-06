CREATE DATABASE IF NOT EXISTS campus_guard
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE campus_guard;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    student_no VARCHAR(50) DEFAULT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'technician', 'admin') NOT NULL DEFAULT 'student',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(120) NOT NULL,
    icon VARCHAR(20) DEFAULT '📌',
    description TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS incidents (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    assigned_to INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    status ENUM('Open', 'Assigned', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Open',
    attachment VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_incidents_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_incidents_category
        FOREIGN KEY (category_id)
        REFERENCES categories(category_id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_incidents_assigned_to
        FOREIGN KEY (assigned_to)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comments_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    incident_id INT DEFAULT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notifications_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS incident_intelligence (
    intelligence_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    technician_id INT NOT NULL,
    category_id INT NOT NULL,
    status_before VARCHAR(50) DEFAULT NULL,
    root_cause TEXT NOT NULL,
    resolution_summary TEXT NOT NULL,
    prevention_recommendation TEXT DEFAULT NULL,
    learning_signal TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_intelligence_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_intelligence_technician
        FOREIGN KEY (technician_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_intelligence_category
        FOREIGN KEY (category_id)
        REFERENCES categories(category_id)
        ON DELETE CASCADE
);

INSERT INTO categories (category_name, icon, description) VALUES
('IT / Network', '💻', 'WiFi, systems, internet, software, and hardware issues'),
('Facilities', '🏢', 'Buildings, rooms, water, power, furniture, or maintenance issues'),
('Security', '🛡️', 'Safety, theft, suspicious activity, and emergency concerns'),
('Academic', '📚', 'Classroom, timetable, lab, and learning-related issues'),
('Health & Safety', '🚑', 'Medical, hygiene, hazard, or wellbeing concerns')
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

INSERT INTO users (fullname, student_no, email, password, role) VALUES
('Student Demo', '190439', '190439@student.school.ac.ke', '$2y$12$BFKm/pC9WS39sytEd7hpfuTqlZ0eOyIFwXpWILiZXbMP2Xrq6ev/y', 'student'),
('Admin Demo', 'ADM001', 'admin@school.ac.ke', '$2y$12$BFKm/pC9WS39sytEd7hpfuTqlZ0eOyIFwXpWILiZXbMP2Xrq6ev/y', 'admin'),
('Technician Demo', 'ICT001', 'technician@school.ac.ke', '$2y$12$BFKm/pC9WS39sytEd7hpfuTqlZ0eOyIFwXpWILiZXbMP2Xrq6ev/y', 'technician');
