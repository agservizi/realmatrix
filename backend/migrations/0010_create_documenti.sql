CREATE TABLE IF NOT EXISTS documenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(150),
    file_path VARCHAR(255),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
