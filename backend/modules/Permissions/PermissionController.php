<?php

require_once __DIR__ . '/PermissionModel.php';
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

class PermissionController extends Controller
{
    private PermissionModel $model;

    public function __construct()
    {
        $this->model = new PermissionModel();
    }

    public function list(): void
    {
        AuthMiddleware::handle();
        $this->ok($this->model->all());
    }
}
