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




CREATE TABLE IF NOT EXISTS classrooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(60) NOT NULL UNIQUE,
    building VARCHAR(120) NOT NULL,
    room_name VARCHAR(160) NOT NULL,
    capacity INT NOT NULL DEFAULT 40,
    room_type ENUM('Lecture Room','Computer Lab','Seminar Room','Auditorium','Meeting Room') NOT NULL DEFAULT 'Lecture Room',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS class_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT DEFAULT NULL,
    lecturer_user_id INT DEFAULT NULL,
    room_id INT NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    session_title VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_class_session_unit
        FOREIGN KEY (unit_id)
        REFERENCES academic_units(unit_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_class_session_lecturer
        FOREIGN KEY (lecturer_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_class_session_room
        FOREIGN KEY (room_id)
        REFERENCES classrooms(room_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS classroom_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    incident_id INT DEFAULT NULL,
    status ENUM('Available','Occupied','Reserved','Maintenance') NOT NULL DEFAULT 'Available',
    status_note TEXT DEFAULT NULL,
    observed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_classroom_status_room
        FOREIGN KEY (room_id)
        REFERENCES classrooms(room_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_classroom_status_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS academic_relocation_recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    from_room_text VARCHAR(180) DEFAULT NULL,
    recommended_room_id INT NOT NULL,
    reason TEXT NOT NULL,
    recommendation_message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_academic_relocation_incident (incident_id),

    CONSTRAINT fk_relocation_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_relocation_room
        FOREIGN KEY (recommended_room_id)
        REFERENCES classrooms(room_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS academic_units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_code VARCHAR(40) NOT NULL UNIQUE,
    unit_name VARCHAR(160) NOT NULL,
    school_name VARCHAR(160) NOT NULL DEFAULT 'School of Computing and Engineering Sciences',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS incident_academic_context (
    context_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    student_user_id INT DEFAULT NULL,
    lecturer_user_id INT DEFAULT NULL,
    unit_id INT DEFAULT NULL,
    attendance_impact ENUM('None','Low','Moderate','High','Critical') NOT NULL DEFAULT 'None',
    registrar_required TINYINT(1) NOT NULL DEFAULT 0,
    lecturer_required TINYINT(1) NOT NULL DEFAULT 0,
    academic_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_incident_academic_context (incident_id),

    CONSTRAINT fk_academic_context_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_academic_context_student
        FOREIGN KEY (student_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_academic_context_lecturer
        FOREIGN KEY (lecturer_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_academic_context_unit
        FOREIGN KEY (unit_id)
        REFERENCES academic_units(unit_id)
        ON DELETE SET NULL
);


CREATE TABLE IF NOT EXISTS dining_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    location_type ENUM('Central Kitchen','Cafe','Retail Point') NOT NULL DEFAULT 'Cafe',
    manager_user_id INT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_dining_location_manager
        FOREIGN KEY (manager_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS dining_stock_alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    stock_status ENUM('Available','Low','Finished') NOT NULL DEFAULT 'Available',
    alert_message TEXT NOT NULL,
    incident_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_dining_stock_alert_once (location_id, item_name, stock_status, incident_id),

    CONSTRAINT fk_stock_alert_location
        FOREIGN KEY (location_id)
        REFERENCES dining_locations(location_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_stock_alert_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    domain VARCHAR(120) NOT NULL,
    severity_level ENUM('Normal','Sensitive','Critical') NOT NULL DEFAULT 'Normal',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS department_members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    user_id INT NOT NULL,
    role_title VARCHAR(120) NOT NULL DEFAULT 'Responder',
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_department_member (department_id, user_id),

    CONSTRAINT fk_department_member_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_department_member_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS department_route_rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    recommended_route VARCHAR(120) NOT NULL UNIQUE,
    department_id INT NOT NULL,
    default_action TEXT NOT NULL,
    auto_assign TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_route_rule_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS incident_duties (
    duty_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    department_id INT NOT NULL,
    prediction_id INT DEFAULT NULL,
    assigned_user_id INT DEFAULT NULL,
    duty_status ENUM('Queued','Assigned','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Queued',
    duty_summary TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_incident_department_duty (incident_id, department_id),

    CONSTRAINT fk_incident_duty_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_incident_duty_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_incident_duty_prediction
        FOREIGN KEY (prediction_id)
        REFERENCES incident_predictions(prediction_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_incident_duty_user
        FOREIGN KEY (assigned_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);



CREATE TABLE IF NOT EXISTS ai_reasoning_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    prediction_id INT DEFAULT NULL,
    model_name VARCHAR(120) NOT NULL,
    reasoning_type ENUM('Summary','Action Plan','Root Cause','Executive Brief') NOT NULL DEFAULT 'Summary',
    prompt_text MEDIUMTEXT NOT NULL,
    response_text MEDIUMTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_ai_reasoning_once (incident_id, reasoning_type),

    CONSTRAINT fk_ai_reasoning_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ai_reasoning_prediction
        FOREIGN KEY (prediction_id)
        REFERENCES incident_predictions(prediction_id)
        ON DELETE SET NULL
);



CREATE TABLE IF NOT EXISTS phoenix_domains (
    domain_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    risk_weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS phoenix_domain_rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NOT NULL,
    keyword VARCHAR(160) NOT NULL,
    weight INT NOT NULL DEFAULT 10,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_phoenix_domain_keyword (domain_id, keyword),

    CONSTRAINT fk_phoenix_rule_domain
        FOREIGN KEY (domain_id)
        REFERENCES phoenix_domains(domain_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS phoenix_intelligence_signals (
    signal_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    domain_id INT NOT NULL,
    signal_type ENUM(
        'Domain Detection',
        'Risk Escalation',
        'Private Advisory',
        'Operational Recommendation',
        'Pattern Detection'
    ) NOT NULL DEFAULT 'Domain Detection',
    signal_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    summary TEXT NOT NULL,
    recommended_action TEXT NOT NULL,
    visibility ENUM('Admin','Assigned Office','Private','System') NOT NULL DEFAULT 'Assigned Office',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_phoenix_incident_domain_signal (incident_id, domain_id, signal_type),

    CONSTRAINT fk_phoenix_signal_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_phoenix_signal_domain
        FOREIGN KEY (domain_id)
        REFERENCES phoenix_domains(domain_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS phoenix_private_advisories (
    advisory_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    target_user_id INT NOT NULL,
    domain_id INT NOT NULL,
    advisory_type ENUM(
        'Academic Attendance',
        'Food Continuity',
        'Finance Clearance',
        'Facilities Relocation',
        'IT Security',
        'General'
    ) NOT NULL DEFAULT 'General',
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_phoenix_private_advisory_once (incident_id, target_user_id, advisory_type),

    CONSTRAINT fk_phoenix_advisory_incident
        FOREIGN KEY (incident_id)
        REFERENCES incidents(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_phoenix_advisory_target
        FOREIGN KEY (target_user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_phoenix_advisory_domain
        FOREIGN KEY (domain_id)
        REFERENCES phoenix_domains(domain_id)
        ON DELETE CASCADE
);



CREATE TABLE IF NOT EXISTS compliance_ledger (
    ledger_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    previous_hash CHAR(64) NOT NULL DEFAULT '0000000000000000000000000000000000000000000000000000000000000000',
    event_hash CHAR(64) NOT NULL UNIQUE,
    event_type VARCHAR(100) NOT NULL,
    actor_user_id INT DEFAULT NULL,
    actor_role VARCHAR(80) DEFAULT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_hash CHAR(64) NOT NULL,
    metadata_text TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_compliance_ledger_entity (entity_type, entity_hash),
    INDEX idx_compliance_ledger_event_type (event_type),
    INDEX idx_compliance_ledger_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS compliance_alert_tokens (
    token_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    token_hash CHAR(64) NOT NULL UNIQUE,
    source VARCHAR(100) NOT NULL,
    severity ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    entity_type VARCHAR(80) NOT NULL,
    entity_hash CHAR(64) NOT NULL,
    token_payload TEXT NOT NULL,
    workflow_state ENUM('Queued','Evaluating','Routed','Closed') NOT NULL DEFAULT 'Queued',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_compliance_token_state (workflow_state),
    INDEX idx_compliance_token_severity (severity),
    INDEX idx_compliance_token_entity (entity_type, entity_hash)
);



CREATE TABLE IF NOT EXISTS department_data_boundaries (
    boundary_id INT AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(80) NOT NULL UNIQUE,
    department_name VARCHAR(160) NOT NULL,
    data_scope TEXT NOT NULL,
    restricted_fields TEXT NOT NULL,
    production_storage_target VARCHAR(160) NOT NULL,
    rls_policy_name VARCHAR(160) NOT NULL,
    kms_key_alias VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS role_data_access_matrix (
    access_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(80) NOT NULL,
    domain_code VARCHAR(80) NOT NULL,
    can_view_finance TINYINT(1) NOT NULL DEFAULT 0,
    can_view_grades TINYINT(1) NOT NULL DEFAULT 0,
    can_view_attendance TINYINT(1) NOT NULL DEFAULT 0,
    can_view_medical TINYINT(1) NOT NULL DEFAULT 0,
    can_view_full_identity TINYINT(1) NOT NULL DEFAULT 0,
    access_summary TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_role_domain_access (role_name, domain_code)
);



CREATE TABLE IF NOT EXISTS integration_endpoints (
    endpoint_id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint_code VARCHAR(100) NOT NULL UNIQUE,
    endpoint_name VARCHAR(160) NOT NULL,
    endpoint_type ENUM('REST','gRPC','Webhook','SIS','LMS','CampusPolice','AIInference') NOT NULL,
    base_url VARCHAR(255) NOT NULL,
    auth_mode ENUM('OAuth2_mTLS','SignedWebhook','InternalToken','Disabled') NOT NULL DEFAULT 'Disabled',
    rate_limit_per_minute INT NOT NULL DEFAULT 60,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS integration_event_queue (
    event_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    endpoint_id INT NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    severity ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    token_hash CHAR(64) NOT NULL,
    payload_text TEXT NOT NULL,
    delivery_state ENUM('Queued','Dispatched','Failed','Suppressed') NOT NULL DEFAULT 'Queued',
    attempts INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_integration_event_state (delivery_state),
    INDEX idx_integration_event_severity (severity),
    INDEX idx_integration_event_token (token_hash),

    CONSTRAINT fk_integration_queue_endpoint
        FOREIGN KEY (endpoint_id)
        REFERENCES integration_endpoints(endpoint_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ai_inference_rate_limits (
    limit_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    client_key VARCHAR(160) NOT NULL UNIQUE,
    window_start DATETIME NOT NULL,
    request_count INT NOT NULL DEFAULT 0,
    max_requests_per_minute INT NOT NULL DEFAULT 12,
    blocked_until DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

