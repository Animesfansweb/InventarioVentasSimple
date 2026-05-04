<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/public/includes/helpers.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/services/ProductoService.php';
require_once dirname(__DIR__) . '/services/VentaService.php';

final class VentaController
{
    private const CARRITO = 'venta_carrito_lineas';

    public static function index(): void
    {
        if (defined('INVENTARIO_VENTAS_VIEW')) {
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

        $productoSvc = new ProductoService($pdo);
        $ventaSvc = new VentaService($pdo, $productoSvc);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::procesarPost($productoSvc, $ventaSvc);
            exit;
        }

        if (!isset($_SESSION[self::CARRITO]) || !is_array($_SESSION[self::CARRITO])) {
            $_SESSION[self::CARRITO] = [];
        }

        $busqueda = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $resultadosBusqueda = $busqueda !== '' ? $productoSvc->buscarActivosPorTermino($busqueda) : [];

        $ivaPorcentaje = $ventaSvc->obtenerIvaPorcentaje();
        $clientes = $ventaSvc->listarClientes();
        $carrito = $_SESSION[self::CARRITO];
        $totales = self::calcularTotales($carrito, $ivaPorcentaje);
        $ventasHoy = $ventaSvc->listarVentasHoy(200);
        $resumenHoy = $ventaSvc->resumenVentasHoy();

        $flashType = $_SESSION['flash_type'] ?? null;
        $flashMessage = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);

        define('INVENTARIO_VENTAS_VIEW', true);
        // Igual que en productos: require_once impediría volver a cargar la vista y dejaría la página en blanco.
        require dirname(__DIR__) . '/public/ventas.php';
    }

    // Calcula subtotal, IVA y total a partir del carrito en sesión antes de confirmar la venta.
    private static function calcularTotales(array $carrito, float $ivaPct): array
    {
        $sub = 0.0;
        foreach ($carrito as $ln) {
            $sub += round((float) ($ln['precio_unitario'] ?? 0) * (int) ($ln['cantidad'] ?? 0), 2);
        }
        $sub = round($sub, 2);
        $ivaMonto = round($sub * ($ivaPct / 100.0), 2);

        return [
            'subtotal' => $sub,
            'iva_monto' => $ivaMonto,
            'total' => round($sub + $ivaMonto, 2),
        ];
    }

    private static function redirectTrasPost(string $type, string $msg): void
    {
        $_SESSION['flash_type'] = $type;
        $_SESSION['flash_message'] = $msg;
        $ret = isset($_POST['retorno_q']) ? trim((string) $_POST['retorno_q']) : '';
        $url = 'ventas.php';
        if ($ret !== '') {
            $url .= '?q=' . rawurlencode($ret);
        }
        header('Location: ' . $url, true, 303);
        exit;
    }

    private static function procesarPost(ProductoService $productoSvc, VentaService $ventaSvc): void
    {
        if (!isset($_SESSION[self::CARRITO]) || !is_array($_SESSION[self::CARRITO])) {
            $_SESSION[self::CARRITO] = [];
        }
        $cart = &$_SESSION[self::CARRITO];

        $action = (string) ($_POST['action'] ?? '');

        try {
            if ($action === 'agregar_linea') {
                $pid = (int) ($_POST['producto_id'] ?? 0);
                $cant = (int) ($_POST['cantidad'] ?? 1);
                if ($cant < 1) {
                    $cant = 1;
                }
                $p = $productoSvc->buscarPorId($pid);
                if ($p === null || !$p->activo) {
                    self::redirectTrasPost('warning', 'Producto no encontrado o inactivo.');
                }
                if ($p->stock < $cant) {
                    self::redirectTrasPost('warning', 'Stock insuficiente para la cantidad solicitada.');
                }

                $merged = false;
                foreach ($cart as $i => $ln) {
                    if ((int) $ln['producto_id'] === $pid) {
                        $nueva = (int) $ln['cantidad'] + $cant;
                        if ($nueva > $p->stock) {
                            self::redirectTrasPost('warning', 'La cantidad total en carrito supera el stock disponible.');
                        }
                        $cart[$i]['cantidad'] = $nueva;
                        $merged = true;
                        break;
                    }
                }
                if (!$merged) {
                    $cart[] = [
                        'producto_id' => (int) $p->id,
                        'codigo' => $p->codigo,
                        'nombre' => $p->nombre,
                        'precio_unitario' => round($p->precio, 2),
                        'cantidad' => $cant,
                    ];
                }
                self::redirectTrasPost('success', 'Producto agregado al carrito.');
            }

            if ($action === 'quitar_linea') {
                $idx = (int) ($_POST['linea'] ?? -1);
                if (isset($cart[$idx])) {
                    array_splice($cart, $idx, 1);
                    $_SESSION[self::CARRITO] = array_values($cart);
                }
                self::redirectTrasPost('info', 'Línea eliminada.');
            }

            if ($action === 'vaciar_carrito') {
                $_SESSION[self::CARRITO] = [];
                self::redirectTrasPost('info', 'Carrito vaciado.');
            }

            if ($action === 'confirmar_venta') {
                $modo = (string) ($_POST['modo_cliente'] ?? 'existente');
                $clienteId = 0;
                if ($modo === 'nuevo') {
                    $nom = trim((string) ($_POST['nombre_cliente_nuevo'] ?? ''));
                    $doc = trim((string) ($_POST['documento_cliente_nuevo'] ?? ''));
                    if ($nom === '') {
                        self::redirectTrasPost('warning', 'Ingrese el nombre del cliente nuevo.');
                    }
                    $clienteId = $ventaSvc->crearCliente($nom, $doc !== '' ? $doc : null);
                } else {
                    $clienteId = (int) ($_POST['cliente_id'] ?? 0);
                    if (!$ventaSvc->clienteExiste($clienteId)) {
                        self::redirectTrasPost('warning', 'Seleccione un cliente válido.');
                    }
                }

                if ($cart === []) {
                    self::redirectTrasPost('warning', 'Agregue al menos un producto al carrito.');
                }

                $lineas = [];
                foreach ($cart as $ln) {
                    $lineas[] = [
                        'producto_id' => (int) $ln['producto_id'],
                        'cantidad' => (int) $ln['cantidad'],
                        'precio_unitario' => (float) $ln['precio_unitario'],
                    ];
                }

                $iva = $ventaSvc->obtenerIvaPorcentaje();
                $vid = $ventaSvc->registrarVenta($clienteId, $lineas, $iva);
                $_SESSION[self::CARRITO] = [];
                $_SESSION['flash_type'] = 'success';
                $_SESSION['flash_message'] = 'Venta #' . $vid . ' registrada correctamente.';
                header('Location: ventas.php', true, 303);
                exit;
            }

            self::redirectTrasPost('warning', 'Acción no reconocida.');
        } catch (Throwable $e) {
            self::redirectTrasPost('danger', $e->getMessage());
        }
    }

    private static function renderDbError(string $message): void
    {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error DB</title>';
        echo '<link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css"></head><body class="p-4">';
        echo '<div class="alert alert-danger"><strong>MySQL no disponible.</strong><br>' . h($message) . '</div></body></html>';
    }
}
