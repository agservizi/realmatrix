<?php

require_once __DIR__ . '/../../core/Model.php';

class HomeSharingModel extends Model
{
    public function listByAgency(int $agencyId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare('SELECT id, from_agency_id, to_agency_id, immobile_id, messaggio, stato FROM homesharing_requests WHERE from_agency_id = ? OR to_agency_id = ? ORDER BY id DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('iiii', $agencyId, $agencyId, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function countByAgency(int $agencyId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM homesharing_requests WHERE from_agency_id = ? OR to_agency_id = ?');
        $stmt->bind_param('ii', $agencyId, $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return (int)($row['c'] ?? 0);
    }

    public function create(int $fromAgencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO homesharing_requests (from_agency_id, to_agency_id, immobile_id, messaggio, stato) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iiiss', $fromAgencyId, $data['to_agency_id'], $data['immobile_id'], $data['messaggio'], $data['stato']);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to create homesharing request');
        }
        return $this->db->insert_id;
    }

    public function updateStatus(int $agencyId, int $id, string $stato): bool
    {
        // Allow only if requester is involved
        $stmt = $this->db->prepare('UPDATE homesharing_requests SET stato = ? WHERE id = ? AND (from_agency_id = ? OR to_agency_id = ?)');
        $stmt->bind_param('siii', $stato, $id, $agencyId, $agencyId);
        return $stmt->execute();
    }
}
