<?php
declare(strict_types=1);
require_once __DIR__ . '/app/config.php';
$BASE_URL = '.';

$conn = getDb();

// Totales
$sql = "SELECT 
            SUM(importe_total) as gasto_total,
            MAX(km_actuales) - MIN(km_actuales) as km_totales,
            (SUM(litros) / NULLIF(MAX(km_actuales) - MIN(km_actuales),0)) * 100 as consumo_medio
        FROM consumos";
$res = $conn->query($sql);
$totales = $res ? $res->fetch_assoc() : [
    'gasto_total' => 0,
    'km_totales' => 0,
    'consumo_medio' => 0,
];

// Selección de rango para dashboard (5/10/30)
$rangoPermitido = [5, 10, 30];
$r = isset($_GET['r']) ? (int)$_GET['r'] : 5;
if (!in_array($r, $rangoPermitido, true)) { $r = 5; }

// Últimos N repostajes
$sql2 = "SELECT fecha, km_actuales, litros, precio_litro, importe_total, consumo_100km 
         FROM consumos ORDER BY fecha DESC LIMIT $r";
$ultimos = $conn->query($sql2);
// Convertir resultado en array para la tabla
$ultimosArr = [];
if ($ultimos) {
    while ($row = $ultimos->fetch_assoc()) {
        $ultimosArr[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Control Gasolina</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/main.css" rel="stylesheet">
  <!-- Favicon (escritorio) -->
  <link rel="icon" type="image/x-icon" href="/img/gasolina.ico">
  <!-- Apple Touch Icons (iOS - pantalla de inicio) -->
  <link rel="apple-touch-icon" sizes="180x180" href="/img/gasolina-180.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/img/gasolina-152.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/img/gasolina-120.png">
  <!-- PWA manifest (opcional) -->
  <link rel="manifest" href="/manifest.webmanifest">
  <!-- Ajustes iOS para web app -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <!-- Ajustes Android/Chrome PWA -->
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Gasolina">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
</head>
<body>
<?php include __DIR__ . "/includes/navbar.php"; ?>

<div class="container py-4">
  <h1 class="mb-4 text-center">📊 Resumen de Consumo</h1>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">💶 Gasto Total</h5>
          <p class="fs-4 fw-bold">
            <?php echo e(number_format((float)($totales['gasto_total'] ?? 0), 2)); ?> €
          </p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">🚗 Km Totales</h5>
          <p class="fs-4 fw-bold">
            <?php echo e(number_format((float)($totales['km_totales'] ?? 0), 0)); ?> km
          </p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">⛽ Consumo Medio</h5>
          <p class="fs-4 fw-bold">
            <?php echo e(number_format((float)($totales['consumo_medio'] ?? 0), 2)); ?> L/100km
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
    <h3 class="mb-0">📅 Últimos <?php echo (int)$r; ?> Repostajes</h3>
    <form method="get" class="d-flex align-items-center gap-2">
      <label for="rango" class="form-label mb-0">Rango:</label>
      <select id="rango" name="r" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="5" <?php echo $r===5?'selected':''; ?>>5</option>
        <option value="10" <?php echo $r===10?'selected':''; ?>>10</option>
        <option value="30" <?php echo $r===30?'selected':''; ?>>30</option>
      </select>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-bordered text-center">
      <thead class="table-dark">
        <tr>
          <th>Fecha</th>
          <th>Km</th>
          <th>Litros</th>
          <th>Precio/L</th>
          <th>Importe (€)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($ultimosArr)): ?>
          <?php foreach($ultimosArr as $row): ?>
            <tr>
              <td><?php echo e($row['fecha']); ?></td>
              <td><?php echo e((string)$row['km_actuales']); ?></td>
              <td><?php echo e((string)$row['litros']); ?></td>
              <td><?php echo e(number_format((float)$row['precio_litro'], 2)); ?></td>
              <td><?php echo e(number_format((float)$row['importe_total'], 2)); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5">No hay registros aún</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js?v=20250902"></script>
</body>
</html>
