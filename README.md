# RealMatrix — CRM/ERP Immobiliare

Stack: PHP 8 (pure) REST backend, MySQL/MariaDB, frontend HTML+JS+Bulma, JWT auth. Struttura separata backend/frontend.

## Avvio rapido
1. Copia `.env.example` in `.env` e imposta le credenziali DB e la chiave JWT.
2. Importa `sql/schema.sql` nel tuo database MySQL/MariaDB.
3. Servi il backend con PHP built-in: `php -S localhost:8000 -t backend/public`.
4. Apri `frontend/index.html` con un server statico (es. `npx serve frontend`).

### Con Docker
1. `docker-compose up -d`
2. Backend su `http://localhost:8000`, frontend statico su `http://localhost:3000`.
3. Credenziali seed: `admin@example.com` / `Admin123!`.

## Rotte principali
- `POST /agency/register` crea agenzia e admin.
- `POST /auth/login` login, ritorna JWT con agency_id, ruolo, permessi.
- `GET/POST/PUT/DELETE /agency/collaborators` CRUD collaboratori (permesso `config_manage`).
- `GET /agency/permissions` elenco permessi.
- Rotte placeholder per moduli Immobili, Clienti, Lead, Appuntamenti, Contratti, Fatture, Documenti, HomeSharing.

## Sicurezza
- JWT include `user_id`, `agency_id`, `role`, `permissions`.
- Middleware: auth, permessi, guardia agenzia.
- Rate limit su login/registrazione, TTL configurabili via env.

## Frontend
- UI Bulma, login, gestione collaboratori, dashboard filtrata dai permessi.
