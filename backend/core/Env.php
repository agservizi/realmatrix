<?php

class Env
{
    private static bool $loaded = false;

    public static function load(string $path = __DIR__ . '/../../.env'): void
    {
        if (self::$loaded) {
            return;
        }
        if (file_exists($path)) {
            $vars = parse_ini_file($path, false, INI_SCANNER_TYPED);
            if (is_array($vars)) {
                foreach ($vars as $key => $value) {
                    if (getenv($key) === false) {
                        putenv("{$key}={$value}");
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                    }
                }
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
