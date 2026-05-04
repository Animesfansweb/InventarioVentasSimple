<?php

declare(strict_types=1);

// Cierra la sesión del usuario y redirige al login.
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['auth_usuario']);
session_regenerate_id(true);

header('Location: index.php');
exit;
