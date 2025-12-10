<?php

require_once __DIR__ . '/../../core/Model.php';

class LeadModel extends Model
{
    public function listByAgency(int $agencyId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare('SELECT id, source, note FROM lead WHERE agency_id = ? LIMIT ? OFFSET ?');
        $stmt->bind_param('iii', $agencyId, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function countByAgency(int $agencyId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM lead WHERE agency_id = ?');
        $stmt->bind_param('i', $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return (int)($row['c'] ?? 0);
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO lead (agency_id, source, note) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $agencyId, $data['source'], $data['note']);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to create lead');
        }
        return $this->db->insert_id;
    }

    public function update(int $agencyId, int $id, array $data): bool
    {
        if (!$this->belongsToAgency($id, $agencyId)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE lead SET source = ?, note = ? WHERE id = ?');
        $stmt->bind_param('ssi', $data['source'], $data['note'], $id);
        return $stmt->execute();
    }

    public function delete(int $agencyId, int $id): bool
    {
        if (!$this->belongsToAgency($id, $agencyId)) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM lead WHERE id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    private function belongsToAgency(int $id, int $agencyId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM lead WHERE id = ? AND agency_id = ?');
        $stmt->bind_param('ii', $id, $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        return (bool)$res->fetch_row();
    }
}
