<?php
namespace App\Modules\Immobili;

use App\Core\Database;
use PDO;

class ImmobiliModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO immobili (agency_id, titolo, descrizione, prezzo, stato, indirizzo, superficie, camere, bagni, immagine_path, planimetria_path, created_at) VALUES (:agency_id, :titolo, :descrizione, :prezzo, :stato, :indirizzo, :superficie, :camere, :bagni, :immagine, :planimetria, NOW())');
        $stmt->execute([
            'agency_id' => $agencyId,
            'titolo' => $data['titolo'] ?? '',
            'descrizione' => $data['descrizione'] ?? '',
            'prezzo' => $data['prezzo'] ?? 0,
            'stato' => $data['stato'] ?? 'disponibile',
            'indirizzo' => $data['indirizzo'] ?? '',
            'superficie' => $data['superficie'] ?? null,
            'camere' => $data['camere'] ?? null,
            'bagni' => $data['bagni'] ?? null,
            'immagine' => $data['immagine'] ?? null,
            'planimetria' => $data['planimetria'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function list(int $agencyId, array $filters = []): array
    {
        $sql = 'SELECT * FROM immobili WHERE agency_id = :agency_id';
        $params = ['agency_id' => $agencyId];
        if (!empty($filters['stato'])) {
            $sql .= ' AND stato = :stato';
            $params['stato'] = $filters['stato'];
        }
        if (!empty($filters['prezzo_max'])) {
            $sql .= ' AND prezzo <= :prezzo_max';
            $params['prezzo_max'] = $filters['prezzo_max'];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function update(int $agencyId, int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE immobili SET titolo = :titolo, descrizione = :descrizione, prezzo = :prezzo, stato = :stato, indirizzo = :indirizzo, superficie = :superficie, camere = :camere, bagni = :bagni, immagine_path = :immagine, planimetria_path = :planimetria WHERE id = :id AND agency_id = :agency_id');
        return $stmt->execute([
            'titolo' => $data['titolo'] ?? '',
            'descrizione' => $data['descrizione'] ?? '',
            'prezzo' => $data['prezzo'] ?? 0,
            'stato' => $data['stato'] ?? 'disponibile',
            'indirizzo' => $data['indirizzo'] ?? '',
            'superficie' => $data['superficie'] ?? null,
            'camere' => $data['camere'] ?? null,
            'bagni' => $data['bagni'] ?? null,
            'immagine' => $data['immagine'] ?? null,
            'planimetria' => $data['planimetria'] ?? null,
            'id' => $id,
            'agency_id' => $agencyId,
        ]);
    }

    public function delete(int $agencyId, int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM immobili WHERE id = :id AND agency_id = :agency_id');
        return $stmt->execute(['id' => $id, 'agency_id' => $agencyId]);
    }
}
