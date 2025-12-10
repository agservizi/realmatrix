CREATE TABLE IF NOT EXISTS clienti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    nome VARCHAR(150),
    telefono VARCHAR(50),
    email VARCHAR(150),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
