CREATE TABLE agencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50),
    created_at DATETIME NOT NULL
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    permissions JSON,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE immobili (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(255) NOT NULL,
    descrizione TEXT,
    prezzo DECIMAL(12,2) DEFAULT 0,
    stato VARCHAR(50) DEFAULT 'disponibile',
    indirizzo VARCHAR(255),
    superficie INT,
    camere INT,
    bagni INT,
    immagine_path VARCHAR(255),
    planimetria_path VARCHAR(255),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE clienti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(50),
    note TEXT,
    lead_score INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE lead (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    fonte VARCHAR(100),
    cliente_id INT,
    immobile_id INT,
    stato VARCHAR(50) DEFAULT 'nuovo',
    note TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE appuntamenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(150),
    cliente_id INT,
    immobile_id INT,
    data_appuntamento DATETIME,
    note TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE contratti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(150),
    cliente_id INT,
    immobile_id INT,
    valore DECIMAL(12,2) DEFAULT 0,
    stato VARCHAR(50) DEFAULT 'bozza',
    pdf_path VARCHAR(255),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE documenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    titolo VARCHAR(150),
    tag VARCHAR(150),
    path VARCHAR(255),
    mime VARCHAR(100),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE fatture (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    numero VARCHAR(100),
    cliente_id INT,
    importo DECIMAL(12,2) DEFAULT 0,
    stato VARCHAR(50) DEFAULT 'bozza',
    pdf_path VARCHAR(255),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE sharing_immobili (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    immobile_id INT NOT NULL,
    visibilita VARCHAR(50) DEFAULT 'base',
    prezzo_visibile TINYINT(1) DEFAULT 1,
    descrizione_visibile TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE sharing_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_agency INT NOT NULL,
    to_agency INT NOT NULL,
    immobile_id INT NOT NULL,
    messaggio TEXT,
    stato VARCHAR(50) DEFAULT 'in_attesa',
    created_at DATETIME NOT NULL
);

CREATE TABLE sharing_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    from_agency INT NOT NULL,
    to_agency INT NOT NULL,
    testo TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (request_id) REFERENCES sharing_requests(id)
);
