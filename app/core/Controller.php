<?php
namespace App\Core;

class Controller
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function view(string $template, array $data = []): void
    {
        extract($data);
        $layout = __DIR__ . '/../../views/layout.php';
        $templatePath = __DIR__ . '/../../views/' . $template . '.php';
        if (!file_exists($templatePath)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }
        include $layout;
    }

    protected function json($data, int $status = 200): void
    {
        Response::json($data, $status);
    }
}
