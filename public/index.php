<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión — Inventario Ventas</title>
  <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-body-secondary">
  <div class="container-fluid px-0">
    <div class="row g-0 min-vh-100 align-items-stretch">
      <div class="col-12 col-lg-6 p-0 col-login-left">
        <figure class="login-hero mb-0">
          <img
            src="assets/img/bodega.jpg"
            alt="Bodega o almacén, ilustrativo del inventario"
            loading="eager"
            fetchpriority="high"
          >
        </figure>
      </div>

      <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5 px-4 bg-white border-start">
        <main class="w-100" style="max-width: 380px;">
          <header class="mb-4">
            <h1 class="h4 fw-semibold mb-1">Inventario Ventas</h1>
            <p class="small text-muted mb-0">Inicio de sesión</p>
          </header>

          <section aria-labelledby="titulo-formulario">
            <h2 id="titulo-formulario" class="visually-hidden">Formulario de acceso</h2>

            <div class="card border shadow-sm">
              <div class="card-body p-4">
                <form id="form-login" action="#" method="post" autocomplete="on" novalidate>
                  <fieldset class="border-0 m-0 p-0">
                    <legend class="float-none w-100 p-0 small text-muted mb-3 pb-2 border-bottom">Datos de acceso</legend>

                    <div class="mb-3">
                      <label for="usuario" class="form-label">Usuario</label>
                      <input
                        type="text"
                        class="form-control"
                        id="usuario"
                        name="usuario"
                        required
                        autocomplete="username"
                        maxlength="128"
                        aria-describedby="error-usuario"
                      >
                      <div id="error-usuario" class="invalid-feedback">Ingrese el usuario.</div>
                    </div>

                    <div class="mb-4">
                      <label for="clave" class="form-label">Contraseña</label>
                      <input
                        type="password"
                        class="form-control"
                        id="clave"
                        name="clave"
                        required
                        autocomplete="current-password"
                        maxlength="256"
                        aria-describedby="error-clave"
                      >
                      <div id="error-clave" class="invalid-feedback">Ingrese la contraseña.</div>
                    </div>

                    <div class="d-grid">
                      <button type="submit" class="btn btn-primary">Ingresar</button>
                    </div>
                  </fieldset>
                </form>
              </div>
            </div>
          </section>
        </main>
      </div>
    </div>
  </div>

  <script src="assets/vendor/jquery/jquery-3.7.1.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/login.js"></script>
</body>
</html>
