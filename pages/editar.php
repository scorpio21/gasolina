<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';
require_auth();

$conn = getDb();
$vehiculoId = getActiveVehiculoId();
$useVehiculoScope = hasColumn('consumos', 'vehiculo_id') && $vehiculoId !== null;

// Sanitizar/validar ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
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
    if (($_POST['accion'] ?? '') === 'eliminar') {
        $sqlDelete = 'DELETE FROM consumos WHERE id = ?';
        if ($useVehiculoScope) {
            $sqlDelete .= ' AND vehiculo_id = ?';
        }
        $stmt = $conn->prepare($sqlDelete);
        if (!$stmt) {
            $errores[] = 'Error al preparar el borrado.';
        } else {
            if ($useVehiculoScope) {
                $stmt->bind_param('ii', $id, $vehiculoId);
            } else {
                $stmt->bind_param('i', $id);
            }
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                header('Location: listar.php?eliminado=1');
                exit;
            }
            $errores[] = 'No se pudo eliminar el registro.';
            $stmt->close();
        }
    }

    if (($_POST['accion'] ?? '') !== 'eliminar') {
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
        $sqlUpdate = "UPDATE consumos
            SET fecha = ?, km_actuales = ?, litros = ?, precio_litro = ?, km_recorridos = ?, consumo_100km = ?
            WHERE id = ?";
        if ($useVehiculoScope) {
            $sqlUpdate .= ' AND vehiculo_id = ?';
        }
        $stmt = $conn->prepare($sqlUpdate);
        if (!$stmt) {
            $errores[] = 'Error al preparar la consulta.';
        } else {
            // Nota: pasar null en bind_param envía NULL a MySQL
            if ($useVehiculoScope) {
                $stmt->bind_param(
                    'siddidii',
                    $fecha,
                    $kmActuales,
                    $litros,
                    $precioLitro,
                    $kmRecorridos,
                    $consumo100,
                    $id,
                    $vehiculoId
                );
            } else {
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
            }
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
}

// Obtener datos actuales para precargar formulario, respetando el vehículo activo.
$sqlSelect = 'SELECT fecha, km_actuales, litros, precio_litro, km_recorridos, consumo_100km FROM consumos WHERE id = ?';
if ($useVehiculoScope) {
    $sqlSelect .= ' AND vehiculo_id = ?';
}
$sqlSelect .= ' LIMIT 1';
$stmtSel = $conn->prepare($sqlSelect);
if ($useVehiculoScope) {
    $stmtSel->bind_param('ii', $id, $vehiculoId);
} else {
    $stmtSel->bind_param('i', $id);
}
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <link href="../css/main.css?v=20261020" rel="stylesheet">
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
<main class="container page-shell">
  <div class="page-heading">
    <div><div class="dashboard-kicker mb-1">Corrección</div><h2>Editar repostaje</h2><p>Actualiza el ticket sin perder el historial del vehículo.</p></div>
    <a class="btn btn-outline-primary" href="listar.php">Volver al historial</a>
  </div>

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
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
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
    <div class="mt-4 d-flex flex-wrap gap-2">
      <button type="submit" class="btn btn-primary">Guardar cambios</button>
      <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
  <form method="post" class="mt-2" onsubmit="return confirm('¿Seguro que deseas eliminar este repostaje? Esta acción no se puede deshacer.');">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <input type="hidden" name="accion" value="eliminar">
    <button type="submit" class="btn btn-outline-danger">🗑️ Eliminar registro</button>
  </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="../js/main.js?v=20261020"></script>
</body>
</html>
