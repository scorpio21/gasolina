<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';

$conexion = getDb();
$BASE_URL = '..';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = isset($_POST["fecha"]) ? (string)$_POST["fecha"] : '';
    $km = isset($_POST["km_actuales"]) ? (int)$_POST["km_actuales"] : 0;
    $litros = isset($_POST["litros"]) ? (float)$_POST["litros"] : 0.0;
    $precio = isset($_POST["precio_litro"]) ? (float)$_POST["precio_litro"] : 0.0;
    $km_recorridos_input = isset($_POST["km_recorridos"]) && $_POST["km_recorridos"] !== '' ? (int)$_POST["km_recorridos"] : null;
    $importe = $litros * $precio; // La BD lo calcula en la columna generada, no lo insertamos

    // Intentar calcular km_recorridos si no se proporcionó
    $km_recorridos = 0;
    if ($km_recorridos_input !== null && $km_recorridos_input >= 0) {
        $km_recorridos = $km_recorridos_input;
    } else {
        $res = $conexion->query("SELECT km_actuales FROM consumos ORDER BY id DESC LIMIT 1");
        $ultimo = $res ? $res->fetch_assoc() : null;
        $km_recorridos = ($ultimo) ? max(0, $km - (int)$ultimo['km_actuales']) : 0;
    }

    $consumo = ($km_recorridos > 0 && $litros > 0) ? ($litros / $km_recorridos) * 100 : 0.0;

    // No insertamos en importe_total porque es una columna generada
    $stmt = $conexion->prepare("INSERT INTO consumos (fecha, km_actuales, litros, precio_litro, km_recorridos, consumo_100km) 
                                VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        // Tipos: s (fecha), i (km_actuales), d (litros), d (precio_litro), i (km_recorridos), d (consumo_100km)
        $stmt->bind_param("siddid", $fecha, $km, $litros, $precio, $km_recorridos, $consumo);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $BASE_URL . "/pages/listar.php");
    exit;
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
<body class="bg-light">
<?php include __DIR__ . "/../includes/navbar.php"; ?>
<div class="container py-4">
  <h2 class="mb-4">➕ Nuevo Repostaje</h2>
  <form method="post" class="card p-4 shadow">
    <div class="mb-3">
      <label class="form-label">Fecha</label>
      <input type="date" name="fecha" class="form-control" required>
      <div class="form-text">Día del repostaje.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Km actuales</label>
      <input type="number" name="km_actuales" class="form-control" required>
      <div class="form-text">Lectura del cuentakilómetros en el momento de repostar.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Litros</label>
      <input type="number" step="0.01" name="litros" class="form-control" required>
      <div class="form-text">Litros cargados en este repostaje. Se usa para calcular el consumo.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Precio por litro (€)</label>
      <input type="number" step="0.001" name="precio_litro" class="form-control" required>
      <div class="form-text">Precio pagado por litro. El importe total se calcula automáticamente.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Km recorridos (opcional)</label>
      <input type="number" name="km_recorridos" class="form-control" placeholder="Si lo dejas vacío, se calcula por diferencia con el registro anterior">
      <div class="form-text">Si lo dejas vacío, se calculará como diferencia con el último "Km actuales" guardado.</div>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
</body>
</html>
