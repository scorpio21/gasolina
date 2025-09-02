<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';

$conexion = getDb();
$BASE_URL = '..';
$resultado = $conexion->query("SELECT * FROM consumos ORDER BY fecha DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Historial</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/main.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . "/../includes/navbar.php"; ?>
<div class="container py-4">
  <h2 class="mb-4">📊 Historial de Repostajes</h2>
  <table class="table table-striped table-bordered shadow">
    <thead class="table-dark">
      <tr>
        <th>Fecha</th>
        <th>Km actuales</th>
        <th>Km recorridos</th>
        <th>Litros</th>
        <th>Precio/L</th>
        <th>Importe (€)</th>
        <th>Consumo (L/100km)</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($resultado) { while ($row = $resultado->fetch_assoc()) { ?>
      <tr>
        <td><?php echo e($row['fecha']); ?></td>
        <td><?php echo e((string)$row['km_actuales']); ?></td>
        <td><?php echo e((string)$row['km_recorridos']); ?></td>
        <td><?php echo e((string)$row['litros']); ?></td>
        <td><?php echo e(number_format((float)$row['precio_litro'], 2)); ?> €</td>
        <td><?php echo e(number_format((float)$row['importe_total'], 2)); ?> €</td>
        <td><?php echo e(number_format((float)$row['consumo_100km'], 2)); ?></td>
      </tr>
      <?php } } ?>
    </tbody>
  </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
</body>
</html>
