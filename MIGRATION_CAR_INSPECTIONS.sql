-- Migration: Create car_inspections table
CREATE TABLE IF NOT EXISTS car_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    inspector_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    inspection_time TIME NOT NULL,
    total_vehicles INT DEFAULT 0,
    violation_count INT DEFAULT 0,
    violation_details TEXT, -- List of license plates not in system
    results_summary TEXT,   -- Summary of findings
    other_opinions TEXT,    -- Auditor's opinions
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (inspector_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
