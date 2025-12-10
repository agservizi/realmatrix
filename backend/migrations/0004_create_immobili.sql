CREATE TABLE IF NOT EXISTS immobili (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(255),
    descrizione TEXT,
    prezzo DECIMAL(12,2),
    stato VARCHAR(50),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
