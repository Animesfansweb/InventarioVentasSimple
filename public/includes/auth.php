<?php

declare(strict_types=1);

// Devuelve los datos del usuario autenticado en sesión, o null si no hay sesión válida.
function auth_usuario_actual(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }

    $u = $_SESSION['auth_usuario'] ?? null;
    if (!is_array($u) || !isset($u['id'], $u['login'], $u['nombre'])) {
        return null;
    }

    return [
        'id' => (int) $u['id'],
        'login' => (string) $u['login'],
        'nombre' => (string) $u['nombre'],
    ];
}

// Exige sesión iniciada; en caso contrario redirige al inicio de sesión.
function auth_requerir(): void
{
    if (auth_usuario_actual() === null) {
        header('Location: index.php');
        exit;
    }
}
