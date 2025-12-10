CREATE TABLE IF NOT EXISTS fatture (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    numero VARCHAR(50),
    importo DECIMAL(12,2),
    stato VARCHAR(50),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
