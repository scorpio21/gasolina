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
    $importe = $litros * $precio;

    $res = $conexion->query("SELECT km_actuales FROM consumos ORDER BY id DESC LIMIT 1");
    $ultimo = $res ? $res->fetch_assoc() : null;
    $km_recorridos = ($ultimo) ? ($km - (int)$ultimo['km_actuales']) : 0;
    $consumo = ($km_recorridos > 0) ? ($litros / $km_recorridos) * 100 : 0.0;

    $stmt = $conexion->prepare("INSERT INTO consumos (fecha, km_actuales, litros, precio_litro, importe_total, km_recorridos, consumo_100km) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sidddid", $fecha, $km, $litros, $precio, $importe, $km_recorridos, $consumo);
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
</head>
<body class="bg-light">
<?php include __DIR__ . "/../includes/navbar.php"; ?>
<div class="container py-4">
  <h2 class="mb-4">➕ Nuevo Repostaje</h2>
  <form method="post" class="card p-4 shadow">
    <div class="mb-3">
      <label class="form-label">Fecha</label>
      <input type="date" name="fecha" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Km actuales</label>
      <input type="number" name="km_actuales" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Litros</label>
      <input type="number" step="0.01" name="litros" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Precio por litro (€)</label>
      <input type="number" step="0.001" name="precio_litro" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
</body>
</html>
