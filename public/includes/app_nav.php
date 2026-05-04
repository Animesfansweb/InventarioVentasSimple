<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Cada vista define $navActive para resaltar la sección actual en la barra de navegación.
$navActive = $navActive ?? 'inicio';
$authNav = auth_usuario_actual();

?>
<nav class="navbar navbar-expand-md navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">Inventario Ventas</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Alternar menú">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-md-auto align-items-md-center">
        <?php if ($authNav !== null) : ?>
        <li class="nav-item">
          <span class="navbar-text text-white-50 small px-md-2"><?php echo h($authNav['nombre']); ?></span>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link<?php echo $navActive === 'productos' ? ' active' : ''; ?>" href="productos.php">Productos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo $navActive === 'ventas' ? ' active' : ''; ?>" href="ventas.php">Ventas</a>
        </li>
        <?php if ($authNav !== null) : ?>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Salir</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
