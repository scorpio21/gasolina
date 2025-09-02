<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';

$conn = getDb();

// Sanitizar/validar ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
}
if ($id <= 0) {
    http_response_code(400);
    echo 'ID inválido';
    exit;
}

$errores = [];
$exito = false;

// Si es POST, procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir valores
    $fecha = trim($_POST['fecha'] ?? '');
    $kmActuales = isset($_POST['km_actuales']) ? (int)$_POST['km_actuales'] : null;
    $litros = isset($_POST['litros']) ? (float)$_POST['litros'] : null;
    $precioLitro = isset($_POST['precio_litro']) ? (float)$_POST['precio_litro'] : null;
    $kmRecorridos = ($_POST['km_recorridos'] ?? '') === '' ? null : (int)$_POST['km_recorridos'];
    $consumo100 = ($_POST['consumo_100km'] ?? '') === '' ? null : (float)$_POST['consumo_100km'];

    // Validaciones básicas
    if ($fecha === '') { $errores[] = 'La fecha es obligatoria.'; }
    if ($kmActuales === null || $kmActuales < 0) { $errores[] = 'Km actuales debe ser un número entero ≥ 0.'; }
    if ($litros === null || $litros <= 0) { $errores[] = 'Litros debe ser un número mayor que 0.'; }
    if ($precioLitro === null || $precioLitro <= 0) { $errores[] = 'Precio/L debe ser un número mayor que 0.'; }

    if (empty($errores)) {
        $stmt = $conn->prepare("UPDATE consumos
            SET fecha = ?, km_actuales = ?, litros = ?, precio_litro = ?, km_recorridos = ?, consumo_100km = ?
            WHERE id = ?");
        if (!$stmt) {
            $errores[] = 'Error al preparar la consulta.';
        } else {
            // Nota: pasar null en bind_param envía NULL a MySQL
            $stmt->bind_param(
                'siddidi',
                $fecha,
                $kmActuales,
                $litros,
                $precioLitro,
                $kmRecorridos,
                $consumo100,
                $id
            );
            if ($stmt->execute()) {
                // Redirigir con flag de éxito
                header('Location: listar.php?actualizado=1');
                exit;
            } else {
                $errores[] = 'No se pudo actualizar el registro.';
            }
            $stmt->close();
        }
    }
}

// Obtener datos actuales para precargar formulario
$stmtSel = $conn->prepare('SELECT fecha, km_actuales, litros, precio_litro, km_recorridos, consumo_100km FROM consumos WHERE id = ? LIMIT 1');
$stmtSel->bind_param('i', $id);
$stmtSel->execute();
$res = $stmtSel->get_result();
$registro = $res ? $res->fetch_assoc() : null;
$stmtSel->close();

if (!$registro) {
    http_response_code(404);
    echo 'Registro no encontrado';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Repostaje</title>
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
  <h2 class="mb-4">✏️ Editar Repostaje</h2>

  <?php if (!empty($errores)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errores as $e): ?>
          <li><?php echo e($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="card p-4 shadow">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control" required value="<?php echo e($registro['fecha']); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Km actuales</label>
        <input type="number" name="km_actuales" class="form-control" min="0" required value="<?php echo e((string)$registro['km_actuales']); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Km recorridos (opcional)</label>
        <input type="number" name="km_recorridos" class="form-control" min="0" value="<?php echo $registro['km_recorridos'] !== null ? e((string)$registro['km_recorridos']) : ''; ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Litros</label>
        <input type="number" step="0.01" min="0.01" name="litros" class="form-control" required value="<?php echo e((string)$registro['litros']); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Precio/L</label>
        <input type="number" step="0.001" min="0.001" name="precio_litro" class="form-control" required value="<?php echo e((string)$registro['precio_litro']); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Consumo (L/100km) (opcional)</label>
        <input type="number" step="0.01" min="0" name="consumo_100km" class="form-control" value="<?php echo $registro['consumo_100km'] !== null ? e((string)$registro['consumo_100km']) : ''; ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Guardar cambios</button>
      <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
</body>
</html>
