<?php

declare(strict_types=1);

// Si se accede directamente a esta URL, se delega primero al controlador.
if (!defined('INVENTARIO_PRODUCTOS_VIEW')) {
    require_once dirname(__DIR__) . '/controllers/ProductoController.php';
    ProductoController::index();
    exit;
}

// Variables definidas por ProductoController antes de incluir esta vista: productos, edición, flash, modos de vista.

require_once __DIR__ . '/includes/helpers.php';

$formCodigo = $editProduct?->codigo ?? '';
$formNombre = $editProduct?->nombre ?? '';
$formDesc = $editProduct?->descripcion ?? '';
$formPrecio = $editProduct !== null ? (string) $editProduct->precio : '';
$formStock = $editProduct !== null ? (string) $editProduct->stock : '0';
$formActivo = $editProduct === null ? true : $editProduct->activo;
$esEdicion = $editProduct !== null;

$navActive = 'productos';
if ($mostrarFormulario) {
    $pageTitle = $esEdicion ? 'Editar producto' : 'Nuevo producto';
} else {
    $pageTitle = 'Productos';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h($pageTitle); ?> — Inventario Ventas</title>
  <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-body-secondary">
  <?php require __DIR__ . '/includes/app_nav.php'; ?>

  <main class="container pb-5">
    <?php if ($flashType !== null && $flashMessage !== null && $flashMessage !== '') : ?>
      <div class="alert alert-<?php echo h($flashType); ?> alert-dismissible fade show" role="alert">
        <?php echo h($flashMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php endif; ?>

    <?php if ($mostrarLista) : ?>
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 mb-0">Catálogo de productos</h1>
        <a class="btn btn-primary" href="productos.php?vista=formulario">Registrar producto</a>
      </div>
      <div class="card border shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h2 class="h6 mb-0 text-muted">Listado</h2>
          <span class="badge text-bg-secondary"><?php echo count($productos); ?> registros</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th scope="col">Código</th>
                  <th scope="col">Nombre</th>
                  <th scope="col" class="text-end">Precio</th>
                  <th scope="col" class="text-end">Stock</th>
                  <th scope="col">Estado</th>
                  <th scope="col" class="small text-muted">Actualizado</th>
                  <th scope="col" class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($productos) === 0) : ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">No hay productos. Use «Registrar producto».</td>
                  </tr>
                <?php else : ?>
                  <?php foreach ($productos as $p) : ?>
                    <tr>
                      <td><code><?php echo h($p->codigo); ?></code></td>
                      <td><?php echo h($p->nombre); ?></td>
                      <td class="text-end"><?php echo h(number_format($p->precio, 2, ',', '.')); ?></td>
                      <td class="text-end"><?php echo (int) $p->stock; ?></td>
                      <td>
                        <?php if ($p->activo) : ?>
                          <span class="badge text-bg-success">Activo</span>
                        <?php else : ?>
                          <span class="badge text-bg-secondary">Inactivo</span>
                        <?php endif; ?>
                      </td>
                      <td class="small text-muted"><?php echo h((string) ($p->updatedAt ?? '')); ?></td>
                      <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="productos.php?edit=<?php echo (int) $p->id; ?>">Editar</a>
                        <form method="post" action="productos.php" class="d-inline" onsubmit="return confirm('¿Eliminar este producto?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo (int) $p->id; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    <?php else : ?>
      <div class="row justify-content-center">
        <div class="col-12 col-lg-7 col-xl-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <a href="productos.php" class="btn btn-outline-secondary btn-sm">Volver al listado</a>
            <span class="text-muted small"><?php echo $esEdicion ? 'Edición' : 'Alta'; ?></span>
          </div>
          <div class="card border shadow-sm">
            <div class="card-header bg-white py-3">
              <h1 class="h5 mb-0"><?php echo $esEdicion ? 'Editar producto' : 'Nuevo producto'; ?></h1>
            </div>
            <div class="card-body">
              <form method="post" action="productos.php" novalidate>
                <input type="hidden" name="action" value="<?php echo $esEdicion ? 'update' : 'create'; ?>">
                <?php if ($esEdicion && $editProduct->id !== null) : ?>
                  <input type="hidden" name="id" value="<?php echo (int) $editProduct->id; ?>">
                <?php endif; ?>

                <div class="mb-3">
                  <label for="codigo" class="form-label">Código</label>
                  <input type="text" class="form-control" id="codigo" name="codigo" required maxlength="64"
                    value="<?php echo h($formCodigo); ?>" autocomplete="off">
                </div>
                <div class="mb-3">
                  <label for="nombre" class="form-label">Nombre</label>
                  <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="200"
                    value="<?php echo h($formNombre); ?>">
                </div>
                <div class="mb-3">
                  <label for="descripcion" class="form-label">Descripción <span class="text-muted small">(opcional)</span></label>
                  <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="2000"><?php echo h($formDesc); ?></textarea>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="precio" class="form-label">Precio unitario <span class="text-muted small">(mayor que 0)</span></label>
                    <input type="number" class="form-control" id="precio" name="precio" min="0.01" step="0.01" required
                      value="<?php echo h($formPrecio); ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" class="form-control" id="stock" name="stock" min="0" step="1" required
                      value="<?php echo h($formStock); ?>">
                  </div>
                </div>
                <div class="form-check mb-4">
                  <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo $formActivo ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="activo">Activo</label>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="submit" class="btn btn-primary"><?php echo $esEdicion ? 'Guardar cambios' : 'Registrar'; ?></button>
                  <a href="productos.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <script src="assets/vendor/jquery/jquery-3.7.1.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
