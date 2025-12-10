<?php

require_once __DIR__ . '/../../core/Permissions.php';

class PermissionModel
{
    public function all(): array
    {
        return Permissions::LIST;
    }
}
