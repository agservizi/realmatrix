<?php

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Response.php';

class AgencyGuard
{
    public static function ensureSameAgency(int $resourceAgencyId): void
    {
        $user = Auth::requireUser();
        if ((int)$user['agency_id'] !== (int)$resourceAgencyId) {
            Response::error('Forbidden: cross-agency access denied', 403);
        }
    }
}
