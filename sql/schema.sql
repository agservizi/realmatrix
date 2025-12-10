CREATE TABLE IF NOT EXISTS agencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission_key VARCHAR(100) NOT NULL,
    PRIMARY KEY (user_id, permission_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Domain tables (minimal placeholders)
CREATE TABLE IF NOT EXISTS immobili (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(255),
    descrizione TEXT,
    prezzo DECIMAL(12,2),
    stato VARCHAR(50),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clienti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    nome VARCHAR(150),
    telefono VARCHAR(50),
    email VARCHAR(150),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lead (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    source VARCHAR(100),
    note TEXT,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS appuntamenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(150),
    data_app DATETIME,
    note TEXT,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contratti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    nome VARCHAR(150),
    valore DECIMAL(12,2),
    pdf_path VARCHAR(255),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS fatture (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    numero VARCHAR(50),
    importo DECIMAL(12,2),
    stato VARCHAR(50),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS documenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(150),
    file_path VARCHAR(255),
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

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
