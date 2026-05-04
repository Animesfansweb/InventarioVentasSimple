<?php

declare(strict_types=1);

// Entidad de producto; corresponde a los registros de la tabla productos.
final class Producto
{
    public function __construct(
        public ?int $id,
        public string $codigo,
        public string $nombre,
        public string $descripcion,
        public float $precio,
        public int $stock,
        public bool $activo,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    // Construye un Producto a partir de una fila asociativa devuelta por PDO (nombres en snake_case).
    public static function fromDbRow(array $row): self
    {
        $desc = $row['descripcion'] ?? null;

        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['codigo'] ?? ''),
            (string) ($row['nombre'] ?? ''),
            $desc !== null && $desc !== false ? (string) $desc : '',
            isset($row['precio']) ? (float) $row['precio'] : 0.0,
            isset($row['stock']) ? (int) $row['stock'] : 0,
            isset($row['activo']) ? (int) $row['activo'] === 1 : true,
            isset($row['created_at']) ? (string) $row['created_at'] : null,
            isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }

    // Devuelve una lista de mensajes de error; si está vacía, los datos son válidos.
    public function validar(): array
    {
        $e = [];
        if (trim($this->codigo) === '') {
            $e[] = 'El código es obligatorio.';
        }
        if (trim($this->nombre) === '') {
            $e[] = 'El nombre es obligatorio.';
        }
        if ($this->precio <= 0) {
            $e[] = 'El precio debe ser mayor que cero.';
        }
        if ($this->stock < 0) {
            $e[] = 'El stock no puede ser negativo.';
        }

        return $e;
    }
}
