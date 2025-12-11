<?php
namespace App\Modules\Appuntamenti;

use App\Core\Database;
use PDO;

class AppuntamentiModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO appuntamenti (agency_id, titolo, cliente_id, immobile_id, data_appuntamento, note, created_at) VALUES (:agency_id, :titolo, :cliente_id, :immobile_id, :data_appuntamento, :note, NOW())');
        $stmt->execute([
            'agency_id' => $agencyId,
            'titolo' => $data['titolo'] ?? '',
            'cliente_id' => $data['cliente_id'] ?? null,
            'immobile_id' => $data['immobile_id'] ?? null,
            'data_appuntamento' => $data['data_appuntamento'] ?? null,
            'note' => $data['note'] ?? '',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $agencyId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM appuntamenti WHERE agency_id = :agency_id');
        $stmt->execute(['agency_id' => $agencyId]);
        return $stmt->fetchAll();
    }

    public function update(int $agencyId, int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE appuntamenti SET titolo = :titolo, data_appuntamento = :data_appuntamento, note = :note WHERE id = :id AND agency_id = :agency_id');
        return $stmt->execute([
            'titolo' => $data['titolo'] ?? '',
            'data_appuntamento' => $data['data_appuntamento'] ?? null,
            'note' => $data['note'] ?? '',
            'id' => $id,
            'agency_id' => $agencyId,
        ]);
    }
}
