<?php
namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->pdo();
    }
}
