<?php
$base = isset($BASE_URL) ? $BASE_URL : '.';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="<?php echo $base; ?>/index.php">⛽ Control Gasolina</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
      aria-controls="menu" aria-expanded="false" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
        <li class="nav-item">
          <a class="nav-link" href="<?php echo $base; ?>/pages/formulario.php">➕ Nuevo Repostaje</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo $base; ?>/pages/listar.php">📊 Historial</a>
        </li>
        <li class="nav-item">
          <button id="toggleTema" type="button" class="btn btn-outline-light btn-sm" title="Cambiar tema">
            <span class="d-inline" id="iconoTema" aria-hidden="true">🌙</span>
            <span class="visually-hidden">Cambiar tema</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</nav>
