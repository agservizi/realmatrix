CREATE TABLE IF NOT EXISTS lead (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    source VARCHAR(100),
    note TEXT,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
