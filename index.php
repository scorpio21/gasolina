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

// Últimos 5 repostajes
$sql2 = "SELECT fecha, km_actuales, litros, precio_litro, importe_total 
         FROM consumos ORDER BY fecha DESC LIMIT 5";
$ultimos = $conn->query($sql2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Control Gasolina</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/main.css" rel="stylesheet">
</head>
<body class="bg-light">
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

  <h3 class="mb-3">📅 Últimos 5 Repostajes</h3>
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
        <?php if ($ultimos && $ultimos->num_rows > 0): ?>
          <?php while($row = $ultimos->fetch_assoc()): ?>
            <tr>
              <td><?php echo e($row['fecha']); ?></td>
              <td><?php echo e((string)$row['km_actuales']); ?></td>
              <td><?php echo e((string)$row['litros']); ?></td>
              <td><?php echo e(number_format((float)$row['precio_litro'], 2)); ?></td>
              <td><?php echo e(number_format((float)$row['importe_total'], 2)); ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5">No hay registros aún</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
