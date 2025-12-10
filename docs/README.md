# Documentazione API (estratto)

## Autenticazione
- `POST /auth/login` → body `{email, password}` → ritorna `{token, user}`.

## Registrazione Agenzia
- `POST /agency/register` → body `{agency_name, name, email, password}` → crea agenzia autonoma e admin.

## Collaboratori
- `GET /agency/collaborators` → lista collaboratori dell'agenzia.
- `POST /agency/collaborators` → crea collaboratore `{name,email,password,role,permissions[]}`.
- `PUT /agency/collaborators/{id}` → aggiorna ruolo/permessi/active.
- `DELETE /agency/collaborators/{id}` → disattiva collaboratore.

Tutte richiedono permesso `config_manage` e JWT valido.

## Permessi
- `GET /agency/permissions` → elenco permessi granulari disponibili.

## Moduli dominio (placeholder)
- `GET /immobili`, `/clienti`, `/lead`, `/appuntamenti`, `/contratti`, `/fatture`, `/documenti`, `/homesharing` con controllo permessi specifico.

## JWT Payload
```
{
  "user_id": 1,
  "agency_id": 1,
  "role": "admin",
  "permissions": ["immobili_manage", ...],
  "iat": 123,
  "exp": 123
}
```
