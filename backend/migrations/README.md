# Migrazioni

Ordine suggerito di applicazione (già numerato):
0001_create_agencies.sql
0002_create_users.sql
0003_create_user_permissions.sql
0004_create_immobili.sql
0005_create_clienti.sql
0006_create_lead.sql
0007_create_appuntamenti.sql
0008_create_contratti.sql
0009_create_fatture.sql
0010_create_documenti.sql
0011_create_homesharing_requests.sql

Esegui ad esempio:
```
mysql -uUSER -pPASS DB_NAME < backend/migrations/0001_create_agencies.sql
...
```
Oppure importa tutto `sql/schema.sql` che contiene le stesse definizioni aggregate.
