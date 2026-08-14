<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class LocationRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->database->query(
            'SELECT id, name, latitude, longitude, environment, notes
             FROM measurement_locations ORDER BY name'
        )->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $allowed = ['indoor', 'outdoor', 'underground', 'mixed', 'unknown'];
        $environment = in_array($data['environment'] ?? '', $allowed, true)
            ? $data['environment']
            : 'unknown';

        $statement = $this->database->prepare(
            'INSERT INTO measurement_locations
             (name, latitude, longitude, environment, notes, created_at, updated_at)
             VALUES (:name, :latitude, :longitude, :environment, :notes, NOW(), NOW())'
        );
        $statement->execute([
            'name' => trim((string) $data['name']),
            'latitude' => $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            'longitude' => $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            'environment' => $environment,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ]);
    }
}
