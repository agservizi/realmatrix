<?php
namespace App\Modules\Agency;

use App\Core\Database;
use App\Core\Permissions;
use PDO;
use Exception;

class AgencyModel
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }

    public function registerAgency(array $data): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO agencies (name, email, phone, created_at) VALUES (:name, :email, :phone, NOW())');
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);
            $agencyId = (int)$this->db->lastInsertId();

            $userStmt = $this->db->prepare('INSERT INTO users (agency_id, name, email, password, role, permissions, active, created_at) VALUES (:agency_id, :name, :email, :password, :role, :permissions, 1, NOW())');
            $userStmt->execute([
                'agency_id' => $agencyId,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => password_hash($data['admin_password'], PASSWORD_BCRYPT),
                'role' => 'admin',
                'permissions' => json_encode(Permissions::all()),
            ]);
            $userId = (int)$this->db->lastInsertId();

            $this->db->commit();
            return ['agency_id' => $agencyId, 'user_id' => $userId];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
