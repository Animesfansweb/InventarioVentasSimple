<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/public/includes/helpers.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/Producto.php';
require_once dirname(__DIR__) . '/services/ProductoService.php';

final class ProductoController
{
    public static function index(): void
    {
        if (defined('INVENTARIO_PRODUCTOS_VIEW')) {
            return;
        }

        session_start();

        require_once dirname(__DIR__) . '/public/includes/auth.php';
        auth_requerir();

        try {
            $pdo = db();
        } catch (Throwable $e) {
            self::renderDbError($e->getMessage());

            return;
        }

        $service = new ProductoService($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::procesarPost($service);
            exit;
        }

        $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
        $editProduct = $editId > 0 ? $service->buscarPorId($editId) : null;
        if ($editId > 0 && $editProduct === null) {
            $editId = 0;
        }

        $vista = isset($_GET['vista']) ? (string) $_GET['vista'] : '';
        $mostrarFormulario = ($vista === 'formulario')
            || ($editId > 0 && $editProduct !== null);
        $mostrarLista = !$mostrarFormulario;

        $productos = $service->listarTodos();

        $flashType = $_SESSION['flash_type'] ?? null;
        $flashMessage = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);

        define('INVENTARIO_PRODUCTOS_VIEW', true);
        // Se usa require (no require_once): este archivo se ejecuta también como punto de entrada y debe incluirse de nuevo.
        require dirname(__DIR__) . '/public/productos.php';
    }

    private static function redirectFlash(string $type, string $message): void
    {
        $_SESSION['flash_type'] = $type;
        $_SESSION['flash_message'] = $message;
        header('Location: productos.php', true, 303);
        exit;
    }

    private static function parseDecimal(mixed $v): float
    {
        $s = trim(str_replace(' ', '', (string) $v));
        $s = str_replace(',', '.', $s);
        if ($s === '' || !is_numeric($s)) {
            return 0.0;
        }

        return max(0.0, round((float) $s, 2));
    }

    private static function parseIntNonNeg(mixed $v): int
    {
        $n = filter_var($v, FILTER_VALIDATE_INT);

        return $n === false ? 0 : max(0, $n);
    }

    private static function procesarPost(ProductoService $service): void
    {
        $action = (string) ($_POST['action'] ?? '');

        try {
            if ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    self::redirectFlash('warning', 'Identificador no válido.');
                }
                $service->eliminar($id);
                self::redirectFlash('success', 'Producto eliminado.');
            }

            $codigo = trim((string) ($_POST['codigo'] ?? ''));
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
            $precio = self::parseDecimal($_POST['precio'] ?? 0);
            $stock = self::parseIntNonNeg($_POST['stock'] ?? 0);
            $activo = isset($_POST['activo']);

            $idPost = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $producto = new Producto(
                $action === 'update' && $idPost > 0 ? $idPost : null,
                $codigo,
                $nombre,
                $descripcion,
                $precio,
                $stock,
                $activo,
            );

            $err = $producto->validar();
            if ($err !== []) {
                self::redirectFlash('warning', implode(' ', $err));
            }

            if ($action === 'create') {
                $service->crear($producto);
                self::redirectFlash('success', 'Producto creado.');
            }

            if ($action === 'update') {
                if ($idPost <= 0) {
                    self::redirectFlash('warning', 'Identificador no válido.');
                }
                $service->actualizar($producto);
                self::redirectFlash('success', 'Producto actualizado.');
            }

            self::redirectFlash('warning', 'Acción no reconocida.');
        } catch (PDOException $e) {
            $sqlState = $e->errorInfo[0] ?? '';
            if ($sqlState === '23000' || str_contains($e->getMessage(), 'Duplicate')) {
                self::redirectFlash('danger', 'Ya existe un producto con ese código.');
            }
            self::redirectFlash('danger', 'Error al guardar en la base de datos.');
        }
    }

    private static function renderDbError(string $message): void
    {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error DB</title>';
        echo '<link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css"></head><body class="p-4">';
        echo '<div class="alert alert-danger"><strong>No hay conexión a MySQL.</strong><br>';
        echo h($message);
        echo '<p class="mb-2 small">Comprueba que el contenedor <code>mysql</code> esté en marcha y que <code>DB_USER</code> / <code>DB_PASS</code> en el servicio PHP coincidan con <code>MYSQL_USER</code> / <code>MYSQL_PASSWORD</code> en MySQL.</p>';

        if (str_contains($message, '1045')) {
            echo '<div class="card border"><div class="card-body small">';
            echo '<p class="fw-semibold mb-2">Error 1045 (acceso denegado) suele pasar si el volumen de MySQL se creó antes con otra contraseña: los datos guardados no se actualizan al cambiar el compose.</p>';
            echo '<p class="mb-2"><strong>Opción A (recomendada en demo):</strong> borrar el volumen y recrear la base (se pierden datos locales):</p>';
            echo '<pre class="bg-light p-2 rounded">docker compose down -v
docker compose up -d</pre>';
            echo '<p class="mb-2"><strong>Opción B:</strong> conectarse a MySQL como <code>root</code> (con la contraseña con la que se creó el volumen) y ejecutar <code>ALTER USER</code> / <code>GRANT</code> para que el usuario <code>inventario</code> coincida con <code>MYSQL_PASSWORD</code> del <code>docker-compose.yml</code>.</p>';
            echo '<p class="mb-0 text-muted small">Si no conoce la clave de root del volumen actual, use la opción A.</p>';
            echo '</div></div>';
        }

        echo '</body></html>';
    }
}
