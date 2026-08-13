<?php

declare(strict_types=1);

namespace App;

final class Config
{
    /** @var array<string, string> */
    private array $values = [];

    public static function fromEnvironment(string $projectRoot): self
    {
        $config = new self();
        $file = $projectRoot . '/.env';

        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = array_map('trim', explode('=', $line, 2));
                $config->values[$key] = trim($value, "\"" . "'");
            }
        }

        return $config;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $environmentValue = getenv($key);

        return $environmentValue !== false
            ? $environmentValue
            : ($this->values[$key] ?? $default);
    }

    public function require(string $key): string
    {
        $value = $this->get($key);
        if ($value === null || $value === '') {
            throw new \RuntimeException(sprintf('Missing configuration value: %s', $key));
        }

        return $value;
    }
}
