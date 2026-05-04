<?php

declare(strict_types=1);

require_once __DIR__ . '/ProductoService.php';

final class VentaService
{
    public function __construct(
        private PDO $pdo,
        private ProductoService $productoService,
    ) {
    }

    // Obtiene el porcentaje de IVA desde la tabla parametros; si falta o es inválido, usa 15 %.
    public function obtenerIvaPorcentaje(): float
    {
        $st = $this->pdo->prepare('SELECT valor FROM parametros WHERE clave = ?');
        $st->execute(['iva_porcentaje']);
        $row = $st->fetch();
        if ($row === false) {
            return 15.0;
        }
        $v = (float) str_replace(',', '.', (string) $row['valor']);
        if ($v < 0.0 || $v > 100.0) {
            return 15.0;
        }

        return round($v, 2);
    }

    // Lista de clientes para el selector del formulario de venta.
    public function listarClientes(): array
    {
        $st = $this->pdo->query('SELECT id, nombre, documento FROM clientes ORDER BY nombre ASC');
        if ($st === false) {
            return [];
        }
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'nombre' => (string) $r['nombre'],
                'documento' => $r['documento'] !== null ? (string) $r['documento'] : null,
            ];
        }

        return $out;
    }

    public function clienteExiste(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $st = $this->pdo->prepare('SELECT 1 FROM clientes WHERE id = ?');
        $st->execute([$id]);

        return $st->fetch() !== false;
    }

    public function crearCliente(string $nombre, ?string $documento): int
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre del cliente es obligatorio.');
        }
        $doc = $documento !== null ? trim($documento) : null;
        if ($doc === '') {
            $doc = null;
        }
        $st = $this->pdo->prepare('INSERT INTO clientes (nombre, documento) VALUES (?, ?)');
        $st->execute([$nombre, $doc]);

        return (int) $this->pdo->lastInsertId();
    }

    // Registra cabecera y detalle en una transacción y actualiza el inventario (descuento de stock).
    public function registrarVenta(int $clienteId, array $lineas, float $ivaPorcentajeAplicado): int
    {
        if (!$this->clienteExiste($clienteId)) {
            throw new InvalidArgumentException('Cliente no válido.');
        }
        if ($lineas === []) {
            throw new InvalidArgumentException('La venta no tiene líneas.');
        }

        $subtotal = 0.0;
        foreach ($lineas as $ln) {
            $pid = (int) ($ln['producto_id'] ?? 0);
            $cant = (int) ($ln['cantidad'] ?? 0);
            $pu = (float) ($ln['precio_unitario'] ?? 0);
            if ($pid <= 0 || $cant <= 0 || $pu < 0) {
                throw new InvalidArgumentException('Línea de venta inválida.');
            }
            $subtotal += round($pu * $cant, 2);
        }
        $subtotal = round($subtotal, 2);
        $ivaMonto = round($subtotal * ($ivaPorcentajeAplicado / 100.0), 2);
        $total = round($subtotal + $ivaMonto, 2);

        $this->pdo->beginTransaction();
        try {
            foreach ($lineas as $ln) {
                $pid = (int) $ln['producto_id'];
                $cant = (int) $ln['cantidad'];
                $st = $this->pdo->prepare('SELECT stock, activo FROM productos WHERE id = ? FOR UPDATE');
                $st->execute([$pid]);
                $row = $st->fetch();
                if ($row === false || (int) $row['activo'] !== 1) {
                    throw new RuntimeException('Producto no disponible: #' . $pid);
                }
                if ((int) $row['stock'] < $cant) {
                    throw new RuntimeException('Stock insuficiente para el producto #' . $pid);
                }
            }

            $insV = $this->pdo->prepare(
                'INSERT INTO ventas (cliente_id, subtotal, iva_porcentaje, iva_monto, total) VALUES (?, ?, ?, ?, ?)'
            );
            $insV->execute([$clienteId, $subtotal, round($ivaPorcentajeAplicado, 2), $ivaMonto, $total]);
            $ventaId = (int) $this->pdo->lastInsertId();

            $insD = $this->pdo->prepare(
                'INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio_unitario, subtotal_linea) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($lineas as $ln) {
                $pid = (int) $ln['producto_id'];
                $cant = (int) $ln['cantidad'];
                $pu = round((float) $ln['precio_unitario'], 2);
                $subL = round($pu * $cant, 2);
                $insD->execute([$ventaId, $pid, $cant, $pu, $subL]);
                $this->productoService->descontarStock($pid, $cant);
            }

            $this->pdo->commit();

            return $ventaId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Devuelve las últimas ventas registradas, hasta el límite indicado.
    public function listarUltimasVentas(int $limite = 15): array
    {
        $lim = max(1, min(100, $limite));
        // Límite como literal entero acotado (evita problemas de LIMIT con parámetros en PDO emulado).
        $sql = 'SELECT v.id, v.fecha, c.nombre AS cliente, v.total
                FROM ventas v
                INNER JOIN clientes c ON c.id = v.cliente_id
                ORDER BY v.id DESC
                LIMIT ' . $lim;
        $st = $this->pdo->query($sql);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'fecha' => (string) $r['fecha'],
                'cliente' => (string) $r['cliente'],
                'total' => (float) $r['total'],
            ];
        }

        return $out;
    }

    // Ventas del día calendario actual según la fecha del servidor MySQL (CURDATE()).
    public function listarVentasHoy(int $limite = 200): array
    {
        $lim = max(1, min(500, $limite));
        // Límite como literal entero acotado (coherente con listarUltimasVentas y ProductoService).
        $sql = 'SELECT v.id, v.fecha, c.nombre AS cliente, v.total
                FROM ventas v
                INNER JOIN clientes c ON c.id = v.cliente_id
                WHERE DATE(v.fecha) = CURDATE()
                ORDER BY v.id DESC
                LIMIT ' . $lim;
        $st = $this->pdo->query($sql);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'fecha' => (string) $r['fecha'],
                'cliente' => (string) $r['cliente'],
                'total' => (float) $r['total'],
            ];
        }

        return $out;
    }

    // Resumen del día: cantidad de ventas y suma de totales (reporte diario).
    public function resumenVentasHoy(): array
    {
        $st = $this->pdo->query(
            'SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS suma FROM ventas WHERE DATE(fecha) = CURDATE()'
        );
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if ($r === false) {
            return ['cantidad' => 0, 'suma_total' => 0.0];
        }

        return [
            'cantidad' => (int) $r['n'],
            'suma_total' => (float) $r['suma'],
        ];
    }
}
