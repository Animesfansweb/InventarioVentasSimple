<?php

declare(strict_types=1);

// Mismo esquema que productos.php: primero el controlador y luego la vista.
if (!defined('INVENTARIO_VENTAS_VIEW')) {
    require_once dirname(__DIR__) . '/controllers/VentaController.php';
    VentaController::index();
    exit;
}

// Datos preparados por VentaController: búsqueda, carrito, clientes, totales, reporte del día, mensajes flash.

require_once __DIR__ . '/includes/helpers.php';

$navActive = 'ventas';
$retornoQ = $busqueda;
$tabActiva = (isset($_GET['tab']) && $_GET['tab'] === 'reporte') ? 'reporte' : 'venta';

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ventas — Inventario Ventas</title>
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

    <h1 class="h4 mb-3">Ventas</h1>

    <ul class="nav nav-tabs mb-3" id="ventasTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button
          class="nav-link<?php echo $tabActiva === 'venta' ? ' active' : ''; ?>"
          id="tab-venta-btn"
          data-bs-toggle="tab"
          data-bs-target="#tab-venta"
          type="button"
          role="tab"
          aria-controls="tab-venta"
          aria-selected="<?php echo $tabActiva === 'venta' ? 'true' : 'false'; ?>"
        >Nueva venta</button>
      </li>
      <li class="nav-item" role="presentation">
        <button
          class="nav-link<?php echo $tabActiva === 'reporte' ? ' active' : ''; ?>"
          id="tab-reporte-btn"
          data-bs-toggle="tab"
          data-bs-target="#tab-reporte"
          type="button"
          role="tab"
          aria-controls="tab-reporte"
          aria-selected="<?php echo $tabActiva === 'reporte' ? 'true' : 'false'; ?>"
        >Reporte del día</button>
      </li>
    </ul>

    <div class="tab-content" id="ventasTabsContent">
      <div
        class="tab-pane fade<?php echo $tabActiva === 'venta' ? ' show active' : ''; ?>"
        id="tab-venta"
        role="tabpanel"
        aria-labelledby="tab-venta-btn"
        tabindex="0"
      >
        <h2 class="h5 mb-3 text-muted">Registrar venta</h2>

        <div class="card border shadow-sm mb-4">
          <div class="card-header bg-white py-3">
            <h2 class="h6 mb-0">Buscar producto</h2>
          </div>
          <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="ventas.php">
              <div class="col-md-8">
                <label for="q" class="form-label small text-muted mb-1">Código o nombre</label>
                <input type="text" class="form-control" id="q" name="q" value="<?php echo h($busqueda); ?>"
                  placeholder="Ej. SKU, martillo, ñoño…" maxlength="120">
              </div>
              <div class="col-md-4 d-grid d-md-block">
                <button type="submit" class="btn btn-outline-primary">Buscar</button>
              </div>
            </form>
            <?php if ($busqueda !== '' && count($resultadosBusqueda) === 0) : ?>
              <p class="text-muted small mb-0 mt-3">Sin resultados para «<?php echo h($busqueda); ?>».</p>
            <?php endif; ?>
          </div>
        </div>

        <?php if (count($resultadosBusqueda) > 0) : ?>
          <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3">
              <h2 class="h6 mb-0">Resultados</h2>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Código</th>
                      <th>Nombre</th>
                      <th class="text-end">P. unit.</th>
                      <th class="text-end">Stock</th>
                      <th class="text-end">Agregar</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($resultadosBusqueda as $rp) : ?>
                      <tr>
                        <td><code><?php echo h($rp->codigo); ?></code></td>
                        <td><?php echo h($rp->nombre); ?></td>
                        <td class="text-end"><?php echo h(number_format($rp->precio, 2, ',', '.')); ?></td>
                        <td class="text-end"><?php echo (int) $rp->stock; ?></td>
                        <td class="text-end" style="max-width: 110px;">
                          <form method="post" action="ventas.php" class="d-flex gap-1 justify-content-end">
                            <input type="hidden" name="action" value="agregar_linea">
                            <input type="hidden" name="producto_id" value="<?php echo (int) $rp->id; ?>">
                            <input type="hidden" name="retorno_q" value="<?php echo h($retornoQ); ?>">
                            <input type="number" name="cantidad" class="form-control form-control-sm" value="1" min="1" max="<?php echo (int) $rp->stock; ?>" style="width: 4.5rem;">
                            <button type="submit" class="btn btn-sm btn-primary">Agregar</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="card border shadow-sm mb-4">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="h6 mb-0">Carrito</h2>
            <?php if (count($carrito) > 0) : ?>
              <form method="post" action="ventas.php" class="m-0" onsubmit="return confirm('¿Vaciar carrito?');">
                <input type="hidden" name="action" value="vaciar_carrito">
                <input type="hidden" name="retorno_q" value="<?php echo h($retornoQ); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Vaciar</button>
              </form>
            <?php endif; ?>
          </div>
          <div class="card-body p-0">
            <?php if (count($carrito) === 0) : ?>
              <p class="text-muted small mb-0 p-3">Busque productos y agréguelos aquí.</p>
            <?php else : ?>
              <div class="table-responsive">
                <table class="table mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Producto</th>
                      <th class="text-end">P. unit.</th>
                      <th class="text-end">Cant.</th>
                      <th class="text-end">Subtotal</th>
                      <th class="text-end"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($carrito as $idx => $ln) : ?>
                      <?php
                        $subL = round((float) $ln['precio_unitario'] * (int) $ln['cantidad'], 2);
                      ?>
                      <tr>
                        <td>
                          <div class="fw-medium"><?php echo h((string) $ln['nombre']); ?></div>
                          <div class="small text-muted"><code><?php echo h((string) $ln['codigo']); ?></code></div>
                        </td>
                        <td class="text-end"><?php echo h(number_format((float) $ln['precio_unitario'], 2, ',', '.')); ?></td>
                        <td class="text-end"><?php echo (int) $ln['cantidad']; ?></td>
                        <td class="text-end"><?php echo h(number_format($subL, 2, ',', '.')); ?></td>
                        <td class="text-end">
                          <form method="post" action="ventas.php" class="d-inline">
                            <input type="hidden" name="action" value="quitar_linea">
                            <input type="hidden" name="linea" value="<?php echo (int) $idx; ?>">
                            <input type="hidden" name="retorno_q" value="<?php echo h($retornoQ); ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Quitar</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="border-top bg-light p-3 small">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span><?php echo h(number_format($totales['subtotal'], 2, ',', '.')); ?></span></div>
                <div class="d-flex justify-content-between"><span>IVA (<?php echo h((string) $ivaPorcentaje); ?> %)</span><span><?php echo h(number_format($totales['iva_monto'], 2, ',', '.')); ?></span></div>
                <div class="d-flex justify-content-between fw-semibold mt-2"><span>Total</span><span><?php echo h(number_format($totales['total'], 2, ',', '.')); ?></span></div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card border shadow-sm mb-4">
          <div class="card-header bg-white py-3">
            <h2 class="h6 mb-0">Cliente</h2>
          </div>
          <div class="card-body">
            <form method="post" action="ventas.php">
              <input type="hidden" name="action" value="confirmar_venta">
              <input type="hidden" name="retorno_q" value="<?php echo h($retornoQ); ?>">

              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="modo_cliente" id="modo_existente" value="existente" checked>
                  <label class="form-check-label" for="modo_existente">Cliente ya registrado</label>
                </div>
                <select class="form-select mt-2" name="cliente_id" id="cliente_id" aria-label="Cliente registrado">
                  <?php foreach ($clientes as $c) : ?>
                    <option value="<?php echo (int) $c['id']; ?>"><?php echo h($c['nombre']); ?><?php echo $c['documento'] !== null && $c['documento'] !== '' ? ' — ' . h($c['documento']) : ''; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3 border-top pt-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="modo_cliente" id="modo_nuevo" value="nuevo">
                  <label class="form-check-label" for="modo_nuevo">Cliente nuevo (se guarda al confirmar la venta)</label>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-md-6">
                    <label for="nombre_cliente_nuevo" class="form-label small text-muted">Nombre</label>
                    <input type="text" class="form-control" id="nombre_cliente_nuevo" name="nombre_cliente_nuevo" maxlength="200" placeholder="Nombre o razón social">
                  </div>
                  <div class="col-md-6">
                    <label for="documento_cliente_nuevo" class="form-label small text-muted">Documento (opcional)</label>
                    <input type="text" class="form-control" id="documento_cliente_nuevo" name="documento_cliente_nuevo" maxlength="32" placeholder="RUC / CI / pasaporte">
                  </div>
                </div>
              </div>

              <button type="submit" class="btn btn-success" <?php echo count($carrito) === 0 ? 'disabled' : ''; ?>>Confirmar venta</button>
            </form>
          </div>
        </div>
      </div>

      <div
        class="tab-pane fade<?php echo $tabActiva === 'reporte' ? ' show active' : ''; ?>"
        id="tab-reporte"
        role="tabpanel"
        aria-labelledby="tab-reporte-btn"
        tabindex="0"
      >
        <h2 class="h5 mb-3 text-muted">Ventas de hoy</h2>
        <p class="small text-muted mb-3">Lista filtrada por la fecha del servidor de base de datos (día calendario actual).</p>

        <div class="row g-3 mb-4">
          <div class="col-sm-6 col-md-4">
            <div class="card border shadow-sm h-100">
              <div class="card-body py-3">
                <div class="small text-muted text-uppercase">Cantidad de ventas</div>
                <div class="fs-4 fw-semibold"><?php echo (int) $resumenHoy['cantidad']; ?></div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-md-4">
            <div class="card border shadow-sm h-100">
              <div class="card-body py-3">
                <div class="small text-muted text-uppercase">Total facturado</div>
                <div class="fs-4 fw-semibold"><?php echo h(number_format($resumenHoy['suma_total'], 2, ',', '.')); ?></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card border shadow-sm">
          <div class="card-header bg-white py-3">
            <h3 class="h6 mb-0">Detalle</h3>
          </div>
          <div class="card-body p-0">
            <?php if (count($ventasHoy) === 0) : ?>
              <p class="text-muted small mb-0 p-3">No hay ventas registradas hoy.</p>
            <?php else : ?>
              <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>Fecha y hora</th>
                      <th>Cliente</th>
                      <th class="text-end">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($ventasHoy as $v) : ?>
                      <tr>
                        <td><?php echo (int) $v['id']; ?></td>
                        <td class="small text-nowrap"><?php echo h($v['fecha']); ?></td>
                        <td><?php echo h($v['cliente']); ?></td>
                        <td class="text-end"><?php echo h(number_format($v['total'], 2, ',', '.')); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="assets/vendor/jquery/jquery-3.7.1.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sincroniza el parámetro tab en la URL al cambiar de pestaña para conservar la vista al recargar.
    (function () {
      var reporteBtn = document.getElementById('tab-reporte-btn');
      var ventaBtn = document.getElementById('tab-venta-btn');
      if (!reporteBtn || !ventaBtn) return;
      reporteBtn.addEventListener('shown.bs.tab', function () {
        var u = new URL(window.location.href);
        u.searchParams.set('tab', 'reporte');
        window.history.replaceState(null, '', u);
      });
      ventaBtn.addEventListener('shown.bs.tab', function () {
        var u = new URL(window.location.href);
        u.searchParams.delete('tab');
        window.history.replaceState(null, '', u);
      });
    })();
  </script>
</body>
</html>
