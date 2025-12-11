<?php
namespace App\Modules\HomeSharing;

use App\Core\Database;
use PDO;

class HomeSharingModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function listImmobili(): array
    {
        $stmt = $this->db->query('SELECT * FROM sharing_immobili');
        return $stmt->fetchAll();
    }

    public function shareImmobile(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO sharing_immobili (agency_id, immobile_id, visibilita, prezzo_visibile, descrizione_visibile, created_at) VALUES (:agency_id, :immobile_id, :visibilita, :prezzo_visibile, :descrizione_visibile, NOW())');
        $stmt->execute([
            'agency_id' => $data['agency_id'],
            'immobile_id' => $data['immobile_id'],
            'visibilita' => $data['visibilita'] ?? 'base',
            'prezzo_visibile' => (int)($data['prezzo_visibile'] ?? 1),
            'descrizione_visibile' => (int)($data['descrizione_visibile'] ?? 1),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listAgenzie(): array
    {
        $stmt = $this->db->query('SELECT id, name, email FROM agencies');
        return $stmt->fetchAll();
    }

    public function createRequest(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO sharing_requests (from_agency, to_agency, immobile_id, messaggio, stato, created_at) VALUES (:from_agency, :to_agency, :immobile_id, :messaggio, :stato, NOW())');
        $stmt->execute([
            'from_agency' => $data['from_agency'],
            'to_agency' => $data['to_agency'],
            'immobile_id' => $data['immobile_id'],
            'messaggio' => $data['messaggio'] ?? '',
            'stato' => 'in_attesa',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function sendMessage(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO sharing_messages (request_id, from_agency, to_agency, testo, created_at) VALUES (:request_id, :from_agency, :to_agency, :testo, NOW())');
        $stmt->execute([
            'request_id' => $data['request_id'],
            'from_agency' => $data['from_agency'],
            'to_agency' => $data['to_agency'],
            'testo' => $data['testo'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listMessages(int $requestId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sharing_messages WHERE request_id = :id');
        $stmt->execute(['id' => $requestId]);
        return $stmt->fetchAll();
    }
}
