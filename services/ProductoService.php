<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/Producto.php';

final class ProductoService
{
    public function __construct(private PDO $pdo)
    {
    }

    // Lista todos los productos ordenados alfabéticamente por nombre.
    public function listarTodos(): array
    {
        $sql = 'SELECT * FROM productos ORDER BY nombre ASC';
        $st = $this->pdo->query($sql);
        if ($st === false) {
            return [];
        }

        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[] = Producto::fromDbRow($row);
        }

        return $out;
    }

    public function buscarPorId(int $id): ?Producto
    {
        if ($id <= 0) {
            return null;
        }
        $st = $this->pdo->prepare('SELECT * FROM productos WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();

        return $row === false ? null : Producto::fromDbRow($row);
    }

    public function crear(Producto $p): void
    {
        $sql = 'INSERT INTO productos (codigo, nombre, descripcion, precio, stock, activo)
                VALUES (?, ?, ?, ?, ?, ?)';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            trim($p->codigo),
            trim($p->nombre),
            $p->descripcion !== '' ? $p->descripcion : null,
            round($p->precio, 2),
            $p->stock,
            $p->activo ? 1 : 0,
        ]);
    }

    public function actualizar(Producto $p): void
    {
        if ($p->id === null || $p->id <= 0) {
            throw new InvalidArgumentException('Producto sin id válido.');
        }
        $sql = 'UPDATE productos SET codigo = ?, nombre = ?, descripcion = ?, precio = ?, stock = ?, activo = ? WHERE id = ?';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            trim($p->codigo),
            trim($p->nombre),
            $p->descripcion !== '' ? $p->descripcion : null,
            round($p->precio, 2),
            $p->stock,
            $p->activo ? 1 : 0,
            $p->id,
        ]);
    }

    public function eliminar(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $st = $this->pdo->prepare('DELETE FROM productos WHERE id = ?');
        $st->execute([$id]);
    }

    // Búsqueda por código o nombre (LIKE) entre productos activos; usada en el módulo de ventas.
    public function buscarActivosPorTermino(string $termino, int $limite = 30): array
    {
        $t = trim($termino);
        if ($t === '') {
            return [];
        }
        $like = '%' . $t . '%';
        $lim = max(1, min(100, $limite));
        // El límite se concatena como entero acotado: con PDO emulado, LIMIT ? puede generar comillas inválidas en MySQL.
        $sql = 'SELECT * FROM productos WHERE activo = 1 AND (codigo LIKE ? OR nombre LIKE ?) ORDER BY nombre ASC LIMIT ' . $lim;
        $st = $this->pdo->prepare($sql);
        $st->execute([$like, $like]);
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[] = Producto::fromDbRow($row);
        }

        return $out;
    }

    // Descuenta stock al confirmar una venta; lanza excepción si no hay cantidad suficiente.
    public function descontarStock(int $productoId, int $cantidad): void
    {
        if ($productoId <= 0 || $cantidad <= 0) {
            throw new InvalidArgumentException('Datos inválidos para stock.');
        }
        $st = $this->pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?');
        $st->execute([$cantidad, $productoId, $cantidad]);
        if ($st->rowCount() === 0) {
            throw new RuntimeException('Stock insuficiente o producto inexistente.');
        }
    }
}
