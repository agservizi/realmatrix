<?php
namespace App\Modules\Lead;

use App\Core\Database;
use PDO;

class LeadModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO lead (agency_id, fonte, cliente_id, immobile_id, stato, note, created_at) VALUES (:agency_id, :fonte, :cliente_id, :immobile_id, :stato, :note, NOW())');
        $stmt->execute([
            'agency_id' => $agencyId,
            'fonte' => $data['fonte'] ?? '',
            'cliente_id' => $data['cliente_id'] ?? null,
            'immobile_id' => $data['immobile_id'] ?? null,
            'stato' => $data['stato'] ?? 'nuovo',
            'note' => $data['note'] ?? '',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $agencyId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM lead WHERE agency_id = :agency_id');
        $stmt->execute(['agency_id' => $agencyId]);
        return $stmt->fetchAll();
    }

    public function update(int $agencyId, int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE lead SET stato = :stato, note = :note WHERE id = :id AND agency_id = :agency_id');
        return $stmt->execute([
            'stato' => $data['stato'] ?? 'nuovo',
            'note' => $data['note'] ?? '',
            'id' => $id,
            'agency_id' => $agencyId,
        ]);
    }
}
