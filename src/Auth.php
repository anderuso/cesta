<?php

namespace App;

class Auth
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function login(string $username, string $password): bool
    {
        // Hard‑coded credentials for the admin
        if ($username === 'admin' && $password === $_ENV['ADMIN_PASSWORD']) {
            $_SESSION['user'] = 'admin';
            return true;
        }
        return false;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        session_destroy();
    }

    public function isGuest(): bool
    {
        return !isset($_SESSION['user']);
    }

    public function isAdmin(): bool
    {
        return isset($_SESSION['user']) && $_SESSION['user'] === 'admin';
    }
}
