<?php

class Permissions
{
    public const LIST = [
        'immobili_manage',
        'clienti_manage',
        'lead_manage',
        'appuntamenti_manage',
        'contratti_manage',
        'documenti_manage',
        'fatture_manage',
        'homesharing_manage',
        'config_manage',
        'dashboard_full'
    ];

    public static function defaultsForRole(string $role): array
    {
        switch ($role) {
            case 'admin':
                return self::LIST;
            case 'agente':
                return ['immobili_manage', 'lead_manage', 'appuntamenti_manage', 'homesharing_manage'];
            case 'segreteria':
                return ['immobili_manage', 'clienti_manage', 'lead_manage', 'appuntamenti_manage'];
            case 'contabile':
                return ['contratti_manage', 'fatture_manage', 'documenti_manage'];
            case 'visualizzatore':
                return ['dashboard_full'];
            default:
                return [];
        }
    }
}
