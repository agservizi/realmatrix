<?php
namespace App\Modules\Clienti;

use App\Core\Database;
use PDO;

class ClientiModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO clienti (agency_id, nome, email, telefono, note, lead_score, created_at) VALUES (:agency_id, :nome, :email, :telefono, :note, :lead_score, NOW())');
        $stmt->execute([
            'agency_id' => $agencyId,
            'nome' => $data['nome'] ?? '',
            'email' => $data['email'] ?? '',
            'telefono' => $data['telefono'] ?? '',
            'note' => $data['note'] ?? '',
            'lead_score' => $data['lead_score'] ?? 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $agencyId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM clienti WHERE agency_id = :agency_id');
        $stmt->execute(['agency_id' => $agencyId]);
        return $stmt->fetchAll();
    }

    public function update(int $agencyId, int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE clienti SET nome = :nome, email = :email, telefono = :telefono, note = :note, lead_score = :lead_score WHERE id = :id AND agency_id = :agency_id');
        return $stmt->execute([
            'nome' => $data['nome'] ?? '',
            'email' => $data['email'] ?? '',
            'telefono' => $data['telefono'] ?? '',
            'note' => $data['note'] ?? '',
            'lead_score' => $data['lead_score'] ?? 0,
            'id' => $id,
            'agency_id' => $agencyId,
        ]);
    }
}
