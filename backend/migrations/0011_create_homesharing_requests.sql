CREATE TABLE IF NOT EXISTS homesharing_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_agency_id INT NOT NULL,
    to_agency_id INT NOT NULL,
    immobile_id INT,
    messaggio TEXT,
    stato VARCHAR(50) DEFAULT 'pending',
    FOREIGN KEY (from_agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
    FOREIGN KEY (to_agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
