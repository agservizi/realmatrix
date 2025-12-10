<?php

require_once __DIR__ . '/../../core/Model.php';

class ImmobiliModel extends Model
{
    public function listByAgency(int $agencyId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare('SELECT id, titolo, descrizione, prezzo, stato FROM immobili WHERE agency_id = ? LIMIT ? OFFSET ?');
        $stmt->bind_param('iii', $agencyId, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function countByAgency(int $agencyId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM immobili WHERE agency_id = ?');
        $stmt->bind_param('i', $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return (int)($row['c'] ?? 0);
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO immobili (agency_id, titolo, descrizione, prezzo, stato) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issds', $agencyId, $data['titolo'], $data['descrizione'], $data['prezzo'], $data['stato']);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to create immobile');
        }
        return $this->db->insert_id;
    }

    public function update(int $agencyId, int $id, array $data): bool
    {
        if (!$this->belongsToAgency($id, $agencyId)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE immobili SET titolo = ?, descrizione = ?, prezzo = ?, stato = ? WHERE id = ?');
        $stmt->bind_param('ssdsi', $data['titolo'], $data['descrizione'], $data['prezzo'], $data['stato'], $id);
        return $stmt->execute();
    }

    public function delete(int $agencyId, int $id): bool
    {
        if (!$this->belongsToAgency($id, $agencyId)) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM immobili WHERE id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    private function belongsToAgency(int $id, int $agencyId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM immobili WHERE id = ? AND agency_id = ?');
        $stmt->bind_param('ii', $id, $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        return (bool)$res->fetch_row();
    }
}
