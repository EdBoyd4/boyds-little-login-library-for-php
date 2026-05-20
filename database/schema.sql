-- Database Schema for Boyd's Little Login Library for PHP

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- 2. Roles Table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(100) NOT NULL UNIQUE
);

-- 3. User Roles Mapping Table
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE
);

-- 4. Login Attempts Table (for Brute-Force Protection)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(255) NOT NULL,
    attempt_time INT NOT NULL
);

CREATE INDEX idx_login_attempts_ip ON login_attempts(ip_address);

-- 5. Security Audit Logs Table
CREATE TABLE IF NOT EXISTS security_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    username_involved VARCHAR(255) NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_ip ON security_audit_logs(ip_address);
CREATE INDEX idx_audit_user ON security_audit_logs(username_involved);
CREATE INDEX idx_audit_event ON security_audit_logs(event_type);

-- Example Role Data (Optional)
-- INSERT INTO roles (role) VALUES ('admin'), ('editor'), ('user');
