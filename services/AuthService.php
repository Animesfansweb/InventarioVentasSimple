<?php

declare(strict_types=1);

// Autenticación contra la tabla usuarios; en caso exitoso persiste datos en la sesión.
final class AuthService
{
    public function __construct(private PDO $pdo)
    {
    }

    // Devuelve true si las credenciales son correctas (password_verify) y carga la sesión.
    public function intentarLogin(string $login, string $clave): bool
    {
        $login = trim($login);
        if ($login === '' || $clave === '') {
            return false;
        }

        $st = $this->pdo->prepare(
            'SELECT id, login, nombre_mostrar, password_hash FROM usuarios WHERE login = ? AND activo = 1 LIMIT 1'
        );
        $st->execute([$login]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !password_verify($clave, (string) $row['password_hash'])) {
            return false;
        }

        $_SESSION['auth_usuario'] = [
            'id' => (int) $row['id'],
            'login' => (string) $row['login'],
            'nombre' => (string) $row['nombre_mostrar'],
        ];

        return true;
    }
}
