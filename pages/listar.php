<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';

$conexion = getDb();
$BASE_URL = '..';

// Selector de rango para gráficas (5/10/30)
$rangoPermitido = [5, 10, 30];
$r = isset($_GET['r']) ? (int)$_GET['r'] : 5;
if (!in_array($r, $rangoPermitido, true)) { $r = 5; }

// Datos de la tabla (historial completo)
$resultado = $conexion->query("SELECT * FROM consumos ORDER BY fecha DESC");

// Datos para las gráficas (últimos N)
$resGraficas = $conexion->query(
  "SELECT fecha, litros, precio_litro, importe_total, consumo_100km 
   FROM consumos ORDER BY fecha DESC LIMIT $r"
);
$fechas = $importes = $litros = $precios = $consumos = [];
if ($resGraficas) {
  while ($row = $resGraficas->fetch_assoc()) {
    $fechas[] = $row['fecha'];
    $importes[] = (float)$row['importe_total'];
    $litros[] = (float)$row['litros'];
    $precios[] = (float)$row['precio_litro'];
    $consumos[] = isset($row['consumo_100km']) && $row['consumo_100km'] !== null ? (float)$row['consumo_100km'] : null;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Historial</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/main.css" rel="stylesheet">
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
<body class="bg-light">
<?php include __DIR__ . "/../includes/navbar.php"; ?>
<div class="container py-4">
  <h2 class="mb-4">📊 Historial de Repostajes</h2>
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
    <h5 class="mb-0">Gráficas de los últimos <?php echo (int)$r; ?> registros</h5>
    <form method="get" class="d-flex align-items-center gap-2">
      <label for="rango" class="form-label mb-0">Rango:</label>
      <select id="rango" name="r" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="5" <?php echo $r===5?'selected':''; ?>>5</option>
        <option value="10" <?php echo $r===10?'selected':''; ?>>10</option>
        <option value="30" <?php echo $r===30?'selected':''; ?>>30</option>
      </select>
      <div class="form-check ms-2">
        <input class="form-check-input" type="checkbox" id="toggleTendencia">
        <label class="form-check-label" for="toggleTendencia">Mostrar tendencia</label>
      </div>
    </form>
    <button id="btnExportPDF" class="btn btn-outline-secondary btn-sm" type="button">Exportar PDF</button>
  </div>
  <?php if (isset($_GET['actualizado']) && $_GET['actualizado'] === '1'): ?>
    <div class="alert alert-success">Registro actualizado correctamente.</div>
  <?php endif; ?>
  <section id="historial-export" class="bg-white p-3 rounded border">
    <div class="export-header d-flex align-items-center justify-content-between mb-3">
      <div class="d-flex align-items-center gap-2">
        <img src="../img/gasolina-180.png" alt="Logo" width="36" height="36" />
        <strong>Historial de Repostajes</strong>
      </div>
      <div class="text-muted small">Generado: <?php echo date('Y-m-d H:i'); ?></div>
    </div>
  <table class="table table-striped table-bordered shadow mb-0">
    <thead class="table-dark">
      <tr>
        <th>Fecha</th>
        <th>Km actuales</th>
        <th>Km recorridos</th>
        <th>Litros</th>
        <th>Precio/L</th>
        <th>Importe (€)</th>
        <th>Consumo (L/100km)</th>
        <th>Acciones</th>
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
        <td>
          <a class="btn btn-sm btn-primary" href="editar.php?id=<?php echo (int)$row['id']; ?>">Editar</a>
        </td>
      </tr>
      <?php } } ?>
    </tbody>
  </table>
  </section>

  <?php if (!empty($fechas)): ?>
  <h3 class="mt-5 mb-3">📈 Gráficas</h3>
  <!-- Datos para JS -->
  <div id="dashboard-data"
       data-fechas='<?php echo htmlspecialchars(json_encode($fechas), ENT_QUOTES, 'UTF-8'); ?>'
       data-importes='<?php echo htmlspecialchars(json_encode($importes), ENT_QUOTES, 'UTF-8'); ?>'
       data-litros='<?php echo htmlspecialchars(json_encode($litros), ENT_QUOTES, 'UTF-8'); ?>'
       data-precios='<?php echo htmlspecialchars(json_encode($precios), ENT_QUOTES, 'UTF-8'); ?>'
       data-consumos='<?php echo htmlspecialchars(json_encode($consumos), ENT_QUOTES, 'UTF-8'); ?>'
       data-rango='<?php echo (int)$r; ?>'></div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card p-3 shadow-sm">
        <h5 class="text-center">Gasto (€)</h5>
        <canvas id="graficoGasto" height="180"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3 shadow-sm">
        <h5 class="text-center">Litros</h5>
        <canvas id="graficoLitros" height="180"></canvas>
      </div>
    </div>
  </div>
  <div class="row g-4 mt-1">
    <div class="col-md-6">
      <div class="card p-3 shadow-sm">
        <h5 class="text-center">Precio/L (€)</h5>
        <canvas id="graficoPrecio" height="180"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3 shadow-sm">
        <h5 class="text-center">Consumo (L/100km)</h5>
        <canvas id="graficoConsumo" height="180"></canvas>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Librería de gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Exportación a PDF (cliente) -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="../js/main.js"></script>
<script src="../js/export-pdf.js"></script>
</body>
</html>
