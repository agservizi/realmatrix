<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Permissions.php';
require_once __DIR__ . '/../../core/Env.php';

class AgencyModel extends Model
{
    public function createAgency(string $name, string $adminName, string $email, string $password): array
    {
        $stmt = $this->db->prepare('INSERT INTO agencies (name) VALUES (?)');
        $stmt->bind_param('s', $name);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to create agency');
        }
        $agencyId = $this->db->insert_id;

        $hash = $this->hash($password);
        $role = 'admin';
        $active = 1;
        $stmtUser = $this->db->prepare('INSERT INTO users (agency_id, name, email, password_hash, role, active) VALUES (?, ?, ?, ?, ?, ?)');
        $stmtUser->bind_param('issssi', $agencyId, $adminName, $email, $hash, $role, $active);
        if (!$stmtUser->execute()) {
            throw new RuntimeException('Unable to create admin user');
        }
        $userId = $this->db->insert_id;

        $permissions = Permissions::defaultsForRole('admin');
        $this->syncPermissions($userId, $permissions);

        return [
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'role' => 'admin',
            'permissions' => $permissions
        ];
    }

    public function createCollaborator(int $agencyId, array $data): int
    {
        $hash = $this->hash($data['password']);
        $active = 1;
        $stmt = $this->db->prepare('INSERT INTO users (agency_id, name, email, password_hash, role, active) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssi', $agencyId, $data['name'], $data['email'], $hash, $data['role'], $active);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to create collaborator');
        }
        $userId = $this->db->insert_id;
        $perms = $data['permissions'] ?? Permissions::defaultsForRole($data['role']);
        $this->syncPermissions($userId, $perms);
        return $userId;
    }

    public function listCollaborators(int $agencyId): array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, role, active FROM users WHERE agency_id = ?');
        $stmt->bind_param('i', $agencyId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateCollaborator(int $agencyId, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET role = ?, active = ? WHERE id = ? AND agency_id = ?');
        $active = isset($data['active']) ? (int)$data['active'] : 1;
        $stmt->bind_param('siii', $data['role'], $active, $userId, $agencyId);
        $ok = $stmt->execute();
        if (!$ok) {
            return false;
        }
        if (isset($data['permissions'])) {
            $this->syncPermissions($userId, $data['permissions']);
        }
        return true;
    }

    public function deactivateCollaborator(int $agencyId, int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET active = 0 WHERE id = ? AND agency_id = ?');
        $stmt->bind_param('ii', $userId, $agencyId);
        return $stmt->execute();
    }

    public function getPermissionsByUserId(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT permission_key FROM user_permissions WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'permission_key');
    }

    private function syncPermissions(int $userId, array $permissions): void
    {
        $delete = $this->db->prepare('DELETE FROM user_permissions WHERE user_id = ?');
        $delete->bind_param('i', $userId);
        $delete->execute();

        $stmt = $this->db->prepare('INSERT INTO user_permissions (user_id, permission_key) VALUES (?, ?)');
        foreach ($permissions as $perm) {
            $stmt->bind_param('is', $userId, $perm);
            $stmt->execute();
        }
    }

    private function hash(string $password): string
    {
        $cost = (int)Env::get('PASSWORD_COST', 12);
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}
