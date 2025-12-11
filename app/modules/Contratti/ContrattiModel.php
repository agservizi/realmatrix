<?php
namespace App\Modules\Contratti;

use App\Core\Database;
use PDO;

class ContrattiModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO contratti (agency_id, titolo, cliente_id, immobile_id, valore, stato, pdf_path, created_at) VALUES (:agency_id, :titolo, :cliente_id, :immobile_id, :valore, :stato, :pdf_path, NOW())');
        $stmt->execute([
            'agency_id' => $agencyId,
            'titolo' => $data['titolo'] ?? '',
            'cliente_id' => $data['cliente_id'] ?? null,
            'immobile_id' => $data['immobile_id'] ?? null,
            'valore' => $data['valore'] ?? 0,
            'stato' => $data['stato'] ?? 'bozza',
            'pdf_path' => $data['pdf_path'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $agencyId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM contratti WHERE agency_id = :agency_id');
        $stmt->execute(['agency_id' => $agencyId]);
        return $stmt->fetchAll();
    }

    public function updatePdfPath(int $agencyId, int $id, string $path): void
    {
        $stmt = $this->db->prepare('UPDATE contratti SET pdf_path = :pdf WHERE id = :id AND agency_id = :agency_id');
        $stmt->execute(['pdf' => $path, 'id' => $id, 'agency_id' => $agencyId]);
    }
}
