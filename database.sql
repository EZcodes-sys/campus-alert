-- ============================================================
-- Campus Emergency Alert System (CEAS)
-- Database Schema — matches Chapter 3 ERD (5 tables)
-- Roles follow the 4-tier RBAC model from Chapter 2 (2.6):
--   admin, security_officer, lecturer, student
-- ============================================================

CREATE DATABASE IF NOT EXISTS campus_alert_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE campus_alert_db;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    phone           VARCHAR(20)   NULL,
    department      VARCHAR(100)  NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    role            ENUM('admin','security_officer','lecturer','student') NOT NULL DEFAULT 'student',
    profile_photo   VARCHAR(255)  NULL,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    reset_token_hash VARCHAR(255) NULL,
    reset_token_expires DATETIME  NULL,
    last_login      DATETIME      NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: alerts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alerts (
    alert_id        INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150)  NOT NULL,
    message         TEXT          NOT NULL,
    severity        ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    created_by      INT           NOT NULL,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alerts_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: incidents
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS incidents (
    incident_id     INT AUTO_INCREMENT PRIMARY KEY,
    tracking_code   VARCHAR(20)   NOT NULL UNIQUE,
    reported_by     INT           NOT NULL,
    incident_type   VARCHAR(100)  NOT NULL,
    location        VARCHAR(150)  NOT NULL,
    description     TEXT          NOT NULL,
    status          ENUM('open','investigating','resolved','closed') NOT NULL DEFAULT 'open',
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_incidents_user FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: notifications  (junction/log table: users <-> alerts)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id        INT           NOT NULL,
    user_id         INT           NOT NULL,
    channel         ENUM('email','sms','push','in_app') NOT NULL DEFAULT 'in_app',
    status          ENUM('sent','failed','queued') NOT NULL DEFAULT 'sent',
    is_read         TINYINT(1)    NOT NULL DEFAULT 0,
    sent_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_alert FOREIGN KEY (alert_id) REFERENCES alerts(alert_id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: site_settings  (key/value store for admin-editable content,
-- e.g. evacuation information, without needing a schema change)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key     VARCHAR(100)  PRIMARY KEY,
    setting_value   TEXT          NULL,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: emergency_contacts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS emergency_contacts (
    contact_id      INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    designation     VARCHAR(100)  NOT NULL,
    phone           VARCHAR(20)   NOT NULL,
    email           VARCHAR(150)  NULL,
    category        VARCHAR(60)   NOT NULL DEFAULT 'General',
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Seed data
-- ============================================================

-- Default admin account: admin@campus.ac.ke / Admin@1234
INSERT INTO users (name, email, phone, department, password_hash, role, is_active)
VALUES (
    'System Administrator',
    'admin@campus.ac.ke',
    '0700000000',
    'ICT Department',
    '$2b$12$S35kA50IGGjMj1Jsth.9ROzO6OQ0d6W0GJVwJBLCPhAi.faInxaju', -- Admin@1234 (bcrypt, cost 12)
    'admin',
    1
);

-- Sample security officer
INSERT INTO users (name, email, phone, department, password_hash, role, is_active)
VALUES (
    'James Mwangi',
    'security@campus.ac.ke',
    '0700111222',
    'Campus Security',
    '$2b$12$S35kA50IGGjMj1Jsth.9ROzO6OQ0d6W0GJVwJBLCPhAi.faInxaju', -- Admin@1234
    'security_officer',
    1
);

-- Sample lecturer
INSERT INTO users (name, email, phone, department, password_hash, role, is_active)
VALUES (
    'Dr. Grace Otieno',
    'lecturer@campus.ac.ke',
    '0700333444',
    'School of Computing',
    '$2b$12$S35kA50IGGjMj1Jsth.9ROzO6OQ0d6W0GJVwJBLCPhAi.faInxaju', -- Admin@1234
    'lecturer',
    1
);

-- Sample student
INSERT INTO users (name, email, phone, department, password_hash, role, is_active)
VALUES (
    'John Kamau',
    'student@campus.ac.ke',
    '0700555666',
    'School of Computing',
    '$2b$12$S35kA50IGGjMj1Jsth.9ROzO6OQ0d6W0GJVwJBLCPhAi.faInxaju', -- Admin@1234
    'student',
    1
);

INSERT INTO emergency_contacts (name, designation, phone, email, category) VALUES
('Campus Security Office', 'Security Desk', '0700-100-100', 'security@campus.ac.ke', 'Security'),
('University Clinic', 'Medical Emergency', '0700-200-200', 'clinic@campus.ac.ke', 'Medical'),
('Fire & Rescue Unit', 'Fire Department', '0700-300-300', 'fire@campus.ac.ke', 'Fire');

-- Default evacuation information (admin-editable via /evacuation.php)
INSERT INTO site_settings (setting_key, setting_value) VALUES
('evacuation_info', 'FIRE / GENERAL EVACUATION\n1. Remain calm. Stop what you are doing and prepare to leave immediately.\n2. Do not use elevators. Use the nearest marked stairwell/fire exit.\n3. Proceed to your building\'s designated Assembly Point (see notice boards in each block).\n4. Do not re-enter the building until Campus Security or Fire & Rescue gives the all-clear.\n5. Report any missing colleagues or classmates to the Assembly Point marshal.\n\nASSEMBLY POINTS\n- School of Computing: Car Park B\n- Main Administration Block: Sports Field (North Side)\n- Lecture Theatres 1-4: Car Park A\n- Hostels: Hostel Quadrangle\n\nMEDICAL EMERGENCY\nCall the University Clinic (0700-200-200) or Campus Security (0700-100-100) immediately. Do not move a seriously injured person unless they are in immediate danger.\n\nLOCKDOWN (security threat)\n1. Move away from doors and windows.\n2. Lock/barricade the door if possible and switch off lights.\n3. Silence your phone; wait for an official CEAS alert confirming all-clear.\n3. Only exit when instructed by Security or law enforcement.');
