CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_code VARCHAR(40) NOT NULL UNIQUE,
    resource_name VARCHAR(120) NOT NULL,
    resource_type ENUM('Room','Equipment') NOT NULL,
    location VARCHAR(120) NOT NULL,
    capacity INT NULL,
    status ENUM('Available','Unavailable') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_code VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    resource_id INT NOT NULL,
    purpose VARCHAR(180) NOT NULL,
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
    admin_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE RESTRICT
);

INSERT IGNORE INTO resources
(resource_code, resource_name, resource_type, location, capacity, status) VALUES
('ROOM-AVR-01','Audio Visual Room','Room','Main Building - 2nd Floor',40,'Available'),
('ROOM-LAB-02','Computer Laboratory 2','Room','ICT Building',35,'Available'),
('EQ-PROJ-01','LCD Projector','Equipment','ICT Office',NULL,'Available'),
('EQ-LAP-01','Laptop Set','Equipment','ICT Office',10,'Available');

CREATE TABLE IF NOT EXISTS reservation_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    actor_user_id INT NOT NULL,
    action VARCHAR(40) NOT NULL,
    details VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_reservation (reservation_id),
    CONSTRAINT fk_activity_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE RESTRICT
);
