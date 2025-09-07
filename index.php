<?php
declare(strict_types=1);
require_once __DIR__ . '/app/config.php';
$BASE_URL = '.';

$conn = getDb();
$vehiculoId = getActiveVehiculoId();
$useVeh = hasColumn('consumos','vehiculo_id') && $vehiculoId !== null;

// Info del vehículo activo (nombre y foto)
$vehiculos = function_exists('getVehiculos') ? getVehiculos() : [];
$vehiculoNombre = 'Vehículo';
$vehiculoFoto = '';
foreach ($vehiculos as $v) {
    if ((int)$v['id'] === (int)($vehiculoId ?? 0)) {
        $vehiculoNombre = ($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '');
        $vehiculoFoto = isset($v['foto_url']) ? (string)$v['foto_url'] : '';
        break;
    }
}

// Totales
$sql = "SELECT 
            SUM(importe_total) as gasto_total,
            MAX(km_actuales) - MIN(km_actuales) as km_totales,
            (SUM(litros) / NULLIF(MAX(km_actuales) - MIN(km_actuales),0)) * 100 as consumo_medio
        FROM consumos" . ($useVeh ? " WHERE vehiculo_id=".(int)$vehiculoId : "");
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
         FROM consumos" . ($useVeh ? " WHERE vehiculo_id=".(int)$vehiculoId : "") . " ORDER BY fecha DESC LIMIT $r";
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - Control Gasolina</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/main.css?v=20250902" rel="stylesheet">
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
  <?php
    // Resolver src de la imagen (absoluta o relativa)
    $vehiculoImgSrc = './img/audi.png';
    if ($vehiculoFoto !== '') {
      if (stripos($vehiculoFoto, 'http://') === 0 || stripos($vehiculoFoto, 'https://') === 0) {
        $vehiculoImgSrc = $vehiculoFoto;
      } else {
        $vehiculoFoto = ltrim($vehiculoFoto, '/');
        $vehiculoImgSrc = './' . $vehiculoFoto;
      }
    }
  ?>
  <?php if (!empty($vehiculos)): ?>
  <div class="vehiculo-hero text-center mb-3">
    <img src="<?php echo e($vehiculoImgSrc); ?>" alt="<?php echo e($vehiculoNombre); ?>" class="vehiculo-foto" />
    <div class="vehiculo-nombre mt-2 fw-semibold"><?php echo e(trim($vehiculoNombre) !== '' ? $vehiculoNombre : 'Vehículo'); ?></div>
  </div>
  <?php endif; ?>
  <?php
    // ==========================
    // Próximos mantenimientos
    // ==========================
    $cards = [];
    if (hasTable('mantenimientos') && $vehiculoId !== null) {
      // km actuales (vehículo)
      $kmActuales = 0;
      $qKm = $conn->query("SELECT MAX(km_actuales) AS k FROM consumos" . ($useVeh ? " WHERE vehiculo_id=".(int)$vehiculoId : ""));
      if ($qKm) { $rKm = $qKm->fetch_assoc(); $kmActuales = (int)($rKm['k'] ?? 0); }

      // Lista de mantenimientos del vehículo
      $stmtM = $conn->prepare("SELECT id, tipo, cada_km, cada_meses, ultima_fecha, ultimo_km, proxima_fecha_calc, proximo_km_calc, nota FROM mantenimientos WHERE vehiculo_id=?");
      if ($stmtM) {
        $stmtM->bind_param('i', $vehiculoId);
        $stmtM->execute();
        $resM = $stmtM->get_result();
        $hoy = new DateTime('today');
        while ($m = $resM->fetch_assoc()) {
          $estado = 'OK';
          $badge = 'success';
          $diasRest = null;
          $kmRest = null;
          if (!empty($m['proxima_fecha_calc'])) {
            $dt = DateTime::createFromFormat('Y-m-d', (string)$m['proxima_fecha_calc']);
            if ($dt) { $diasRest = (int)$hoy->diff($dt)->format('%r%a'); }
          }
          if (!empty($m['proximo_km_calc'])) {
            $kmRest = (int)$m['proximo_km_calc'] - $kmActuales;
          }
          // Determinar estado combinando fecha/km (el peor de ambos)
          $isAtrasado = ($diasRest !== null && $diasRest < 0) || ($kmRest !== null && $kmRest <= 0);
          $isPronto = (!$isAtrasado) && ((($diasRest !== null) && $diasRest <= 30) || (($kmRest !== null) && $kmRest <= 500));
          if ($isAtrasado) { $estado = 'Atrasado'; $badge = 'danger'; }
          elseif ($isPronto) { $estado = 'Pronto'; $badge = 'warning'; }

          $cards[] = [
            'id' => (int)$m['id'],
            'tipo' => (string)$m['tipo'],
            'ultima_fecha' => $m['ultima_fecha'] ?? null,
            'ultimo_km' => isset($m['ultimo_km']) ? (int)$m['ultimo_km'] : null,
            'cada_km' => isset($m['cada_km']) ? (int)$m['cada_km'] : null,
            'cada_meses' => isset($m['cada_meses']) ? (int)$m['cada_meses'] : null,
            'proxima_fecha' => $m['proxima_fecha_calc'] ?? null,
            'proximo_km' => isset($m['proximo_km_calc']) ? (int)$m['proximo_km_calc'] : null,
            'estado' => $estado,
            'badge' => $badge,
            'diasRest' => $diasRest,
            'kmRest' => $kmRest,
          ];
        }
        $stmtM->close();

        // Ordenar: primero atrasados, luego pronto, luego OK; dentro por el más cercano
        usort($cards, function($a, $b) {
          $priority = ['Atrasado'=>0, 'Pronto'=>1, 'OK'=>2];
          $pa = $priority[$a['estado']] ?? 3; $pb = $priority[$b['estado']] ?? 3;
          if ($pa !== $pb) return $pa - $pb;
          // proximidad: menor díasRest o kmRest
          $aDist = 999999; $bDist = 999999;
          if ($a['diasRest'] !== null) $aDist = abs((int)$a['diasRest']);
          if ($a['kmRest'] !== null) $aDist = min($aDist, abs((int)$a['kmRest']));
          if ($b['diasRest'] !== null) $bDist = abs((int)$b['diasRest']);
          if ($b['kmRest'] !== null) $bDist = min($bDist, abs((int)$b['kmRest']));
          return $aDist <=> $bDist;
        });
      }
    }
  ?>
  <?php if (!empty($cards)): ?>
  <div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h2 class="h4 mb-0">🛠️ Próximos Mantenimientos</h2>
      <a class="btn btn-sm btn-outline-secondary" href="./pages/mantenimientos.php">Gestionar</a>
    </div>
    <div class="row g-3">
      <?php foreach (array_slice($cards, 0, 3) as $c): ?>
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <h5 class="card-title mb-1"><?php echo e($c['tipo']); ?></h5>
              <span class="badge bg-<?php echo e($c['badge']); ?>"><?php echo e($c['estado']); ?></span>
            </div>
            <div class="small text-muted mb-2">
              Último: <?php echo $c['ultima_fecha'] ? e($c['ultima_fecha']) : '—'; ?>
              <?php echo $c['ultimo_km'] !== null ? ' / '.(int)$c['ultimo_km'].' km' : ''; ?>
            </div>
            <ul class="list-unstyled mb-2">
              <li>Próximo:
                <?php echo $c['proxima_fecha'] ? e($c['proxima_fecha']) : '—'; ?>
                <?php echo $c['proximo_km'] !== null ? ' / '.(int)$c['proximo_km'].' km' : ''; ?>
              </li>
              <li>Frecuencia:
                <?php echo $c['cada_km'] !== null ? (int)$c['cada_km'].' km' : '—'; ?>
                <?php echo $c['cada_meses'] !== null ? ' / '.(int)$c['cada_meses'].' meses' : ''; ?>
              </li>
              <?php if ($c['kmRest'] !== null): ?><li>Restan: <?php echo (int)$c['kmRest']; ?> km</li><?php endif; ?>
              <?php if ($c['diasRest'] !== null): ?><li>Faltan: <?php echo (int)$c['diasRest']; ?> días</li><?php endif; ?>
            </ul>
            <a class="btn btn-sm btn-outline-primary" href="./pages/mantenimientos.php?editar=<?php echo (int)$c['id']; ?>">Marcar como hecho</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
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
