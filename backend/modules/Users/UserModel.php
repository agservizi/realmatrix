<?php

require_once __DIR__ . '/../../core/Model.php';

class UserModel extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT id, agency_id, name, email, password_hash, role, active FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        return $user ?: null;
    }

    public function getPermissions(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT permission_key FROM user_permissions WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'permission_key');
    }
}
