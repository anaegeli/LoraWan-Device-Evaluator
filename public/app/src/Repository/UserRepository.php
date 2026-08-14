<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $database) {}

    /** @return array<string, mixed> */
    public function upsert(string $subject, ?string $email, string $displayName, string $role): array
    {
        $statement = $this->database->prepare('SELECT * FROM users WHERE external_subject = :subject');
        $statement->execute(['subject' => $subject]);
        $existing = $statement->fetch();
        if ($existing) {
            $update = $this->database->prepare(
                'UPDATE users SET email = :email, display_name = :name, updated_at = NOW() WHERE id = :id'
            );
            $update->execute(['email' => $email, 'name' => $displayName, 'id' => $existing['id']]);
        } else {
            $insert = $this->database->prepare(
                'INSERT INTO users (external_subject, email, display_name, role, created_at, updated_at)
                 VALUES (:subject, :email, :name, :role, NOW(), NOW())'
            );
            $insert->execute(['subject' => $subject, 'email' => $email, 'name' => $displayName, 'role' => $role]);
        }

        $statement->execute(['subject' => $subject]);
        return $statement->fetch();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->database->query(
            'SELECT id, external_subject, email, display_name, role, created_at, updated_at
             FROM users ORDER BY display_name, email'
        )->fetchAll();
    }

    public function updateRole(int $userId, string $role, int $currentUserId): void
    {
        if (!in_array($role, ['viewer', 'editor', 'admin'], true)) {
            throw new \InvalidArgumentException('Ungültige Rolle.');
        }
        if ($userId === $currentUserId && $role !== 'admin') {
            throw new \RuntimeException('Die eigene Administratorrolle kann nicht entfernt werden.');
        }
        $statement = $this->database->prepare('UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id');
        $statement->execute(['role' => $role, 'id' => $userId]);
        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException('Benutzer wurde nicht gefunden oder nicht geändert.');
        }
    }
}
