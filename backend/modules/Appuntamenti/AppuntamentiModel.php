<?php

require_once __DIR__ . '/../../core/Model.php';

class AppuntamentiModel extends Model
{
    public function listByAgency(int $agencyId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare('SELECT id, titolo, data_app, note FROM appuntamenti WHERE agency_id = ? ORDER BY data_app DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('iii', $agencyId, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function countByAgency(int $agencyId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM appuntamenti WHERE agency_id = ?');
        $stmt->bind_param('i', $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return (int)($row['c'] ?? 0);
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO appuntamenti (agency_id, titolo, data_app, note) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $agencyId, $data['titolo'], $data['data_app'], $data['note']);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to create appuntamento');
        }
        return $this->db->insert_id;
    }

    public function update(int $agencyId, int $id, array $data): bool
    {
        if (!$this->belongsToAgency($id, $agencyId)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE appuntamenti SET titolo = ?, data_app = ?, note = ? WHERE id = ?');
        $stmt->bind_param('sssi', $data['titolo'], $data['data_app'], $data['note'], $id);
        return $stmt->execute();
    }

    public function delete(int $agencyId, int $id): bool
    {
        if (!$this->belongsToAgency($id, $agencyId)) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM appuntamenti WHERE id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    private function belongsToAgency(int $id, int $agencyId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM appuntamenti WHERE id = ? AND agency_id = ?');
        $stmt->bind_param('ii', $id, $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        return (bool)$res->fetch_row();
    }
}
