CREATE TABLE IF NOT EXISTS appuntamenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(150),
    data_app DATETIME,
    note TEXT,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
