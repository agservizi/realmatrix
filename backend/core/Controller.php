<?php

require_once __DIR__ . '/Response.php';

class Controller
{
    protected function input(): array
    {
        $payload = file_get_contents('php://input');
        $json = json_decode($payload, true);
        return is_array($json) ? $json : [];
    }

    protected function ok($data = []): void
    {
        Response::json($data, 200);
    }

    protected function bad(string $message, int $status = 400): void
    {
        Response::error($message, $status);
    }

    protected function pagination(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = (int)($_GET['limit'] ?? 20);
        if ($limit <= 0) {
            $limit = 20;
        }
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        return ['page' => $page, 'limit' => $limit, 'offset' => $offset];
    }
}
