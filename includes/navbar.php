<?php
$base = isset($BASE_URL) ? $BASE_URL : '.';
$homeUrl = $base . '/index.php';
$currentPage = basename((string)($_SERVER['PHP_SELF'] ?? ''));
$navActive = static function (string $page) use ($currentPage): string {
  return $currentPage === $page ? ' active' : '';
};
// Si llega un cambio de vehículo vía GET, lo aplicamos a la sesión
if (isset($_GET['vehiculo'])) {
  $vid = is_numeric($_GET['vehiculo']) ? (int)$_GET['vehiculo'] : null;
  if ($vid !== null) { setActiveVehiculoId($vid); }
}
$vehiculos = function_exists('getVehiculos') ? getVehiculos() : [];
$vehiculoActivo = function_exists('getActiveVehiculoId') ? getActiveVehiculoId() : null;
$vehiculoNombre = 'Vehículo';
$vehiculoFoto = '';
foreach ($vehiculos as $v) {
  if ((int)$v['id'] === (int)($vehiculoActivo ?? 0)) {
    $vehiculoNombre = $v['marca'] . ' ' . $v['modelo'];
    $vehiculoFoto = isset($v['foto_url']) ? (string)$v['foto_url'] : '';
    break;
  }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?php echo $base; ?>/index.php"><span class="brand-mark" aria-hidden="true">⛽</span>Control Gasolina</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
      aria-controls="menu" aria-expanded="false" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
        <li class="nav-item">
          <a class="nav-link<?php echo $navActive('index.php'); ?>" href="<?php echo e($homeUrl); ?>">🏠 Inicio</a>
        </li>
        <?php if (!empty($vehiculos)): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            🚘 <?php
              echo e($vehiculoNombre);
            ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach ($vehiculos as $v): ?>
              <li>
                <a class="dropdown-item<?php echo ((int)$v['id'] === (int)($vehiculoActivo ?? 0)) ? ' active' : ''; ?>" href="?vehiculo=<?php echo (int)$v['id']; ?>">
                  <?php echo e($v['marca'] . ' ' . $v['modelo']); ?>
                  <?php if (!empty($v['anio'])): ?> (<?php echo e((string)$v['anio']); ?>)<?php endif; ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
        <!-- Imagen del vehículo activo -->
        <li class="nav-item d-none d-lg-block">
          <?php
            $src = $base . '/img/audi.png';
            if ($vehiculoFoto !== '') {
              // Si es absoluta (http/https) usar tal cual; si es relativa, resolver con $base
              if (stripos($vehiculoFoto, 'http://') === 0 || stripos($vehiculoFoto, 'https://') === 0) {
                $src = $vehiculoFoto;
              } else {
                $vehiculoFoto = ltrim($vehiculoFoto, '/');
                $src = $base . '/' . $vehiculoFoto;
              }
            }
          ?>
          <img src="<?php echo e($src); ?>" width="28" height="28" class="rounded shadow-sm" alt="<?php echo e($vehiculoNombre); ?>" />
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link<?php echo $navActive('vehiculos.php'); ?>" href="<?php echo $base; ?>/pages/vehiculos.php">🚘 Vehículos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo $navActive('mantenimientos.php'); ?>" href="<?php echo $base; ?>/pages/mantenimientos.php">🛠️ Mantenimientos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo $navActive('formulario.php'); ?>" href="<?php echo $base; ?>/pages/formulario.php">➕ Nuevo Repostaje</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo $navActive('listar.php'); ?>" href="<?php echo $base; ?>/pages/listar.php">📊 Historial</a>
        </li>
        <li class="nav-item">
          <button id="toggleTema" type="button" class="btn btn-outline-light btn-sm" title="Cambiar tema">
            <span class="d-inline" id="iconoTema" aria-hidden="true">🌙</span>
            <span class="visually-hidden">Cambiar tema</span>
          </button>
        </li>
        <?php if (function_exists('auth_is_enabled') && auth_is_enabled() && !empty($_SESSION['authenticated'])): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo $base; ?>/pages/logout.php">🚪 Salir</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
