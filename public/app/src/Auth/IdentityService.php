<?php

declare(strict_types=1);

namespace App\Auth;

use App\Config;
use App\Repository\UserRepository;

final class IdentityService
{
    public function __construct(private Config $config, private UserRepository $users) {}

    /** @return array<string, mixed> */
    public function login(string $driver, string $subject, ?string $email, string $displayName): array
    {
        $identity = $driver . ':' . $subject;
        $admins = array_filter(array_map('trim', explode(',', (string) $this->config->get('AUTH_ADMIN_IDENTITIES', ''))));
        $role = in_array($subject, $admins, true) || ($email !== null && in_array($email, $admins, true))
            ? 'admin'
            : (string) $this->config->get('AUTH_DEFAULT_ROLE', 'viewer');
        if (!in_array($role, ['viewer', 'editor', 'admin'], true)) {
            $role = 'viewer';
        }

        $user = $this->users->upsert($identity, $email, $displayName, $role);
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'display_name' => $user['display_name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        return $user;
    }
}
