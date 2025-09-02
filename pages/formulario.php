<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';

$conexion = getDb();
$BASE_URL = '..';
$errores = [];
$old = [
    'fecha' => '',
    'km_actuales' => '',
    'litros' => '',
    'precio_litro' => '',
    'km_recorridos' => ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitización básica y conservación de valores previos
    $old['fecha'] = isset($_POST['fecha']) ? (string)$_POST['fecha'] : '';
    $old['km_actuales'] = isset($_POST['km_actuales']) ? (string)$_POST['km_actuales'] : '';
    $old['litros'] = isset($_POST['litros']) ? (string)$_POST['litros'] : '';
    $old['precio_litro'] = isset($_POST['precio_litro']) ? (string)$_POST['precio_litro'] : '';
    $old['km_recorridos'] = isset($_POST['km_recorridos']) ? (string)$_POST['km_recorridos'] : '';

    $fecha = $old['fecha'];
    $km = (int)$old['km_actuales'];
    $litros = (float)$old['litros'];
    $precio = (float)$old['precio_litro'];
    $km_recorridos_input = ($old['km_recorridos'] !== '') ? (int)$old['km_recorridos'] : null;

    // Validaciones del lado del servidor
    if ($fecha === '') { $errores['fecha'] = 'La fecha es obligatoria.'; }
    if (!is_numeric($old['km_actuales']) || $km < 0) { $errores['km_actuales'] = 'Introduce kilómetros actuales válidos (>= 0).'; }
    if (!is_numeric($old['litros']) || $litros <= 0) { $errores['litros'] = 'Introduce litros válidos (> 0).'; }
    if (!is_numeric($old['precio_litro']) || $precio <= 0) { $errores['precio_litro'] = 'Introduce un precio por litro válido (> 0).'; }
    if ($km_recorridos_input !== null && $km_recorridos_input < 0) { $errores['km_recorridos'] = 'Los km recorridos no pueden ser negativos.'; }

    // Solo intentamos calcular km_recorridos si no hay errores de entrada
    if (empty($errores)) {
        $km_recorridos = 0;
        if ($km_recorridos_input !== null) {
            $km_recorridos = $km_recorridos_input;
        } else {
            $res = $conexion->query("SELECT km_actuales FROM consumos ORDER BY id DESC LIMIT 1");
            $ultimo = $res ? $res->fetch_assoc() : null;
            $km_recorridos = ($ultimo) ? max(0, $km - (int)$ultimo['km_actuales']) : 0;
        }

        $consumo = ($km_recorridos > 0 && $litros > 0) ? ($litros / $km_recorridos) * 100 : 0.0;

        // Inserción segura (importe_total es columna generada)
        $stmt = $conexion->prepare("INSERT INTO consumos (fecha, km_actuales, litros, precio_litro, km_recorridos, consumo_100km) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("siddid", $fecha, $km, $litros, $precio, $km_recorridos, $consumo);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: " . $BASE_URL . "/pages/listar.php?creado=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Repostaje</title>
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
<body>
<?php include __DIR__ . "/../includes/navbar.php"; ?>
<div class="container py-4">
  <h2 class="mb-4">➕ Nuevo Repostaje</h2>
  <?php if (!empty($errores)): ?>
    <div class="alert alert-danger">
      <strong>Revisa los campos:</strong>
      <ul class="mb-0">
        <?php foreach ($errores as $err): ?>
          <li><?php echo e($err); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
  <form method="post" class="card p-4 shadow" novalidate>
    <div class="mb-3">
      <label class="form-label">Fecha</label>
      <input type="date" name="fecha" class="form-control <?php echo isset($errores['fecha']) ? 'is-invalid' : ''; ?>" required value="<?php echo e($old['fecha']); ?>">
      <?php if (isset($errores['fecha'])): ?><div class="invalid-feedback"><?php echo e($errores['fecha']); ?></div><?php endif; ?>
      <div class="form-text">Día del repostaje.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Km actuales</label>
      <input type="number" name="km_actuales" min="0" step="1" class="form-control <?php echo isset($errores['km_actuales']) ? 'is-invalid' : ''; ?>" required value="<?php echo e($old['km_actuales']); ?>">
      <?php if (isset($errores['km_actuales'])): ?><div class="invalid-feedback"><?php echo e($errores['km_actuales']); ?></div><?php endif; ?>
      <div class="form-text">Lectura del cuentakilómetros en el momento de repostar.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Litros</label>
      <input type="number" step="0.01" min="0.01" name="litros" class="form-control <?php echo isset($errores['litros']) ? 'is-invalid' : ''; ?>" required value="<?php echo e($old['litros']); ?>">
      <?php if (isset($errores['litros'])): ?><div class="invalid-feedback"><?php echo e($errores['litros']); ?></div><?php endif; ?>
      <div class="form-text">Litros cargados en este repostaje. Se usa para calcular el consumo.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Precio por litro (€)</label>
      <input type="number" step="0.001" min="0.001" name="precio_litro" class="form-control <?php echo isset($errores['precio_litro']) ? 'is-invalid' : ''; ?>" required value="<?php echo e($old['precio_litro']); ?>">
      <?php if (isset($errores['precio_litro'])): ?><div class="invalid-feedback"><?php echo e($errores['precio_litro']); ?></div><?php endif; ?>
      <div class="form-text">Precio pagado por litro. El importe total se calcula automáticamente.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Km recorridos (opcional)</label>
      <input type="number" name="km_recorridos" min="0" step="1" class="form-control <?php echo isset($errores['km_recorridos']) ? 'is-invalid' : ''; ?>" placeholder="Si lo dejas vacío, se calcula por diferencia con el registro anterior" value="<?php echo e($old['km_recorridos']); ?>">
      <?php if (isset($errores['km_recorridos'])): ?><div class="invalid-feedback"><?php echo e($errores['km_recorridos']); ?></div><?php endif; ?>
      <div class="form-text">Si lo dejas vacío, se calculará como diferencia con el último "Km actuales" guardado.</div>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
</body>
</html>
