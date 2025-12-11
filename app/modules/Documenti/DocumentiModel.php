<?php
namespace App\Modules\Documenti;

use App\Core\Database;
use PDO;

class DocumentiModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function store(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO documenti (agency_id, titolo, tag, path, mime, created_at) VALUES (:agency_id, :titolo, :tag, :path, :mime, NOW())');
        $stmt->execute([
            'agency_id' => $agencyId,
            'titolo' => $data['titolo'] ?? '',
            'tag' => $data['tag'] ?? '',
            'path' => $data['path'] ?? '',
            'mime' => $data['mime'] ?? '',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $agencyId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM documenti WHERE agency_id = :agency_id');
        $stmt->execute(['agency_id' => $agencyId]);
        return $stmt->fetchAll();
    }
}
