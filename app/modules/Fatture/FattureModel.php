<?php
namespace App\Modules\Fatture;

use App\Core\Database;
use PDO;

class FattureModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO fatture (agency_id, numero, cliente_id, importo, stato, pdf_path, created_at) VALUES (:agency_id, :numero, :cliente_id, :importo, :stato, :pdf_path, NOW())');
        $stmt->execute([
            'agency_id' => $agencyId,
            'numero' => $data['numero'] ?? '',
            'cliente_id' => $data['cliente_id'] ?? null,
            'importo' => $data['importo'] ?? 0,
            'stato' => $data['stato'] ?? 'bozza',
            'pdf_path' => $data['pdf_path'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $agencyId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM fatture WHERE agency_id = :agency_id');
        $stmt->execute(['agency_id' => $agencyId]);
        return $stmt->fetchAll();
    }

    public function updatePdfPath(int $agencyId, int $id, string $path): void
    {
        $stmt = $this->db->prepare('UPDATE fatture SET pdf_path = :pdf WHERE id = :id AND agency_id = :agency_id');
        $stmt->execute(['pdf' => $path, 'id' => $id, 'agency_id' => $agencyId]);
    }
}
