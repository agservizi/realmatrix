<?php

require_once __DIR__ . '/../../core/Model.php';

class ClientiModel extends Model
{
    public function listByAgency(int $agencyId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare('SELECT id, nome, telefono, email FROM clienti WHERE agency_id = ? LIMIT ? OFFSET ?');
        $stmt->bind_param('iii', $agencyId, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function countByAgency(int $agencyId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM clienti WHERE agency_id = ?');
        $stmt->bind_param('i', $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return (int)($row['c'] ?? 0);
    }

    public function create(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO clienti (agency_id, nome, telefono, email) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $agencyId, $data['nome'], $data['telefono'], $data['email']);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to create cliente');
        }
        return $this->db->insert_id;
    }

    public function update(int $agencyId, int $id, array $data): bool
    {
        if (!$this->belongsToAgency($id, $agencyId)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE clienti SET nome = ?, telefono = ?, email = ? WHERE id = ?');
        $stmt->bind_param('sssi', $data['nome'], $data['telefono'], $data['email'], $id);
        return $stmt->execute();
    }

    public function delete(int $agencyId, int $id): bool
    {
        if (!$this->belongsToAgency($id, $agencyId)) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM clienti WHERE id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    private function belongsToAgency(int $id, int $agencyId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM clienti WHERE id = ? AND agency_id = ?');
        $stmt->bind_param('ii', $id, $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        return (bool)$res->fetch_row();
    }
}
