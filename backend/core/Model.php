<?php

require_once __DIR__ . '/Database.php';

class Model
{
    protected mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
}
