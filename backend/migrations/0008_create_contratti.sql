CREATE TABLE IF NOT EXISTS contratti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    nome VARCHAR(150),
    valore DECIMAL(12,2),
    pdf_path VARCHAR(255),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
