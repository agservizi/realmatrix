<?php
namespace App\Modules\Users;

use App\Core\Database;
use PDO;

class UsersModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function createCollaborator(int $agencyId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (agency_id, name, email, password, role, permissions, active, created_at) VALUES (:agency_id, :name, :email, :password, :role, :permissions, 1, NOW())');
        $stmt->execute([
            'agency_id' => $agencyId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role'],
            'permissions' => json_encode($data['permissions'] ?? []),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listCollaborators(int $agencyId): array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, role, permissions, active FROM users WHERE agency_id = :agency_id');
        $stmt->execute(['agency_id' => $agencyId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['permissions'] = $row['permissions'] ? json_decode($row['permissions'], true) : [];
        }
        return $rows;
    }

    public function updateCollaborator(int $agencyId, int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET role = :role, permissions = :permissions, active = :active WHERE id = :id AND agency_id = :agency_id');
        return $stmt->execute([
            'role' => $data['role'],
            'permissions' => json_encode($data['permissions'] ?? []),
            'active' => $data['active'] ?? 1,
            'id' => $id,
            'agency_id' => $agencyId,
        ]);
    }

    public function deactivateCollaborator(int $agencyId, int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET active = 0 WHERE id = :id AND agency_id = :agency_id');
        return $stmt->execute(['id' => $id, 'agency_id' => $agencyId]);
    }
}
