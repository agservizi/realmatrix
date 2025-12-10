INSERT INTO agencies (id, name) VALUES (1, 'Agenzia Demo') ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (id, agency_id, name, email, password_hash, role, active)
VALUES (1, 1, 'Admin Demo', 'admin@example.com', '$2y$12$XJge1wnyAlKXaPYmsDN5ouv8mdS.QF3Il/MiRqvTSDZEw4wQsJOvy%', 'admin', 1)
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO user_permissions (user_id, permission_key) VALUES
(1, 'immobili_manage'),
(1, 'clienti_manage'),
(1, 'lead_manage'),
(1, 'appuntamenti_manage'),
(1, 'contratti_manage'),
(1, 'documenti_manage'),
(1, 'fatture_manage'),
(1, 'homesharing_manage'),
(1, 'config_manage'),
(1, 'dashboard_full')
ON DUPLICATE KEY UPDATE permission_key = VALUES(permission_key);

INSERT INTO immobili (id, agency_id, titolo, descrizione, prezzo, stato)
VALUES (1, 1, 'Bilocale Centro', 'Appartamento demo', 210000, 'disponibile')
ON DUPLICATE KEY UPDATE titolo = VALUES(titolo);
