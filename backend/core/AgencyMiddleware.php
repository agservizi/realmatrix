<?php

require_once __DIR__ . '/AgencyGuard.php';

class AgencyMiddleware
{
    public static function handle(int $agencyId): void
    {
        AgencyGuard::ensureSameAgency($agencyId);
    }
}
