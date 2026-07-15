<?php

namespace App\Config;

class Env
{
    private static array $variables = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            // Silently ignore or throw exception based on preference. 
            // We'll throw an exception to ensure the env is properly set.
            throw new \RuntimeException("Environment file not found: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
                
                self::$variables[$name] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$variables[$key] ?? $_ENV[$key] ?? $default;
    }
}
