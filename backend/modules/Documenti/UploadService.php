<?php

require_once __DIR__ . '/../../core/Env.php';
require_once __DIR__ . '/../../core/Response.php';

class UploadService
{
    public static function saveBase64(string $base64, string $directory, string $filename): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $data = base64_decode($base64);
        $max = (int)Env::get('UPLOAD_MAX_BYTES', 5000000);
        if ($data === false || strlen($data) > $max) {
            Response::error('File too large or invalid', 400);
        }
        $path = rtrim($directory, '/') . '/' . $filename;
        file_put_contents($path, $data);
        return $path;
    }

    public static function saveUploaded(array $file, string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $max = (int)Env::get('UPLOAD_MAX_BYTES', 5000000);
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::error('Upload error', 400);
        }
        if (($file['size'] ?? 0) > $max) {
            Response::error('File too large', 400);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($file['name'] ?? 'file'));
        $target = rtrim($directory, '/') . '/' . uniqid('doc_', true) . '_' . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            Response::error('Unable to save file', 500);
        }
        return $target;
    }
}
