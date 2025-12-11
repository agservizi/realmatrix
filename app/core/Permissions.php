<?php
namespace App\Core;

class Permissions
{
    private const AVAILABLE = [
        'immobili', 'clienti', 'lead', 'appuntamenti', 'documenti', 'contratti', 'fatture', 'home-sharing', 'statistiche', 'impostazioni'
    ];

    public static function all(): array
    {
        return self::AVAILABLE;
    }

    public static function hasPermission(array $userPerms, string $required): bool
    {
        return in_array($required, $userPerms, true);
    }
}
