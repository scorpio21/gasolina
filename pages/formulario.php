<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';
require_auth();

$conexion = getDb();
$BASE_URL = '..';
$vehiculoId = getActiveVehiculoId();
$hasVehiculoColumn = hasColumn('consumos', 'vehiculo_id');
$useVeh = $hasVehiculoColumn && $vehiculoId !== null;
$useLleno = hasColumn('consumos','lleno');
$hasGasolinera = hasColumn('consumos', 'gasolinera');
$errores = [];
$old = [
    'fecha' => '',
    'km_actuales' => '',
    'litros' => '',
    'precio_litro' => '',
    'gasolinera' => '',
    'km_recorridos' => '',
    'importe_total' => '',
    'lleno' => '1'
];

// Precarga los datos útiles del último repostaje sin reutilizar sus kilómetros.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['duplicar']) && $_GET['duplicar'] === '1') {
    $dupFields = 'fecha, litros, precio_litro';
    if ($hasGasolinera) { $dupFields .= ', gasolinera'; }
    if ($useLleno) { $dupFields .= ', lleno'; }
    $dupWhere = $useVeh ? ' WHERE vehiculo_id='.(int)$vehiculoId : '';
    $dup = $conexion->query("SELECT {$dupFields} FROM consumos{$dupWhere} ORDER BY fecha DESC, id DESC LIMIT 1");
    if ($dup && ($last = $dup->fetch_assoc())) {
        $old['fecha'] = date('Y-m-d');
        $old['precio_litro'] = (string)($last['precio_litro'] ?? '');
        if ($hasGasolinera) { $old['gasolinera'] = (string)($last['gasolinera'] ?? ''); }
        if ($useLleno) { $old['lleno'] = (string)((int)($last['lleno'] ?? 1)); }
    }
}

// No permitir nuevos repostajes sin vehículo cuando la base admite esa relación.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasVehiculoColumn && $vehiculoId === null) {
    $errores['vehiculo_id'] = 'Selecciona un vehículo en el menú antes de guardar el repostaje.';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    // Sanitización básica y conservación de valores previos
    $old['fecha'] = isset($_POST['fecha']) ? (string)$_POST['fecha'] : '';
    $old['km_actuales'] = isset($_POST['km_actuales']) ? (string)$_POST['km_actuales'] : '';
    $old['litros'] = isset($_POST['litros']) ? (string)$_POST['litros'] : '';
    $old['precio_litro'] = isset($_POST['precio_litro']) ? (string)$_POST['precio_litro'] : '';
    $old['gasolinera'] = isset($_POST['gasolinera']) ? trim((string)$_POST['gasolinera']) : '';
    $old['km_recorridos'] = isset($_POST['km_recorridos']) ? (string)$_POST['km_recorridos'] : '';
    $old['importe_total'] = isset($_POST['importe_total']) ? (string)$_POST['importe_total'] : '';
    $old['lleno'] = isset($_POST['lleno']) ? '1' : '0';

    $fecha = $old['fecha'];
    $km = (int)$old['km_actuales'];
    $precio = (float)$old['precio_litro'];
    $km_recorridos_input = ($old['km_recorridos'] !== '') ? (int)$old['km_recorridos'] : null;
    
    // Si se proporciona importe_total pero no litros, calcular litros
    if ($old['importe_total'] !== '' && $old['litros'] === '') {
        $importe = (float)$old['importe_total'];
        if ($precio > 0) {
            $litros = round($importe / $precio, 2);
            $old['litros'] = (string)$litros;
        }
    } else {
        $litros = (float)$old['litros'];
    }

    // Validaciones del lado del servidor
    if ($fecha === '') { $errores['fecha'] = 'La fecha es obligatoria.'; }
    if (!is_numeric($old['km_actuales']) || $km < 0) { $errores['km_actuales'] = 'Introduce kilómetros actuales válidos (>= 0).'; }
    if (!is_numeric($old['precio_litro']) || $precio <= 0) { $errores['precio_litro'] = 'Introduce un precio por litro válido (> 0).'; }
    if ($km_recorridos_input !== null && $km_recorridos_input < 0) { $errores['km_recorridos'] = 'Los km recorridos no pueden ser negativos.'; }
    
    // Validar importe_total (obligatorio)
    if ($old['importe_total'] === '' || !is_numeric($old['importe_total']) || (float)$old['importe_total'] <= 0) {
        $errores['importe_total'] = 'Introduce un importe total válido (> 0).';
    }
    
    // Validar litros si se proporcionan (opcional)
    if ($old['litros'] !== '' && (!is_numeric($old['litros']) || (float)$old['litros'] <= 0)) {
        $errores['litros'] = 'Introduce litros válidos (> 0).';
    }
    if ($hasGasolinera && strlen($old['gasolinera']) > 120) {
        $errores['gasolinera'] = 'La gasolinera o localidad no puede superar 120 caracteres.';
    }
    
    $llenoVal = isset($_POST['lleno']) ? 1 : 0;

    // Solo intentamos calcular km_recorridos si no hay errores de entrada
    if (empty($errores)) {
        $km_recorridos = 0;
        if ($km_recorridos_input !== null) {
            $km_recorridos = $km_recorridos_input;
        } else {
            $whereVeh = $useVeh ? (" WHERE vehiculo_id=".(int)$vehiculoId) : "";
            $res = $conexion->query("SELECT km_actuales FROM consumos".$whereVeh." ORDER BY id DESC LIMIT 1");
            $ultimo = $res ? $res->fetch_assoc() : null;
            $km_recorridos = ($ultimo) ? max(0, $km - (int)$ultimo['km_actuales']) : 0;
        }

        $consumo = ($km_recorridos > 0 && $litros > 0) ? ($litros / $km_recorridos) * 100 : 0.0;

        // Inserción segura (importe_total es columna generada).
        $insertFields = ['fecha', 'km_actuales', 'litros', 'precio_litro', 'km_recorridos', 'consumo_100km'];
        $insertTypes = 'siddid';
        $insertValues = [$fecha, $km, $litros, $precio, $km_recorridos, $consumo];
        if ($hasGasolinera) { $insertFields[] = 'gasolinera'; $insertTypes .= 's'; $insertValues[] = $old['gasolinera']; }
        if ($useVeh) { $insertFields[] = 'vehiculo_id'; $insertTypes .= 'i'; $insertValues[] = $vehiculoId; }
        if ($useLleno) { $insertFields[] = 'lleno'; $insertTypes .= 'i'; $insertValues[] = $llenoVal; }
        $placeholders = implode(', ', array_fill(0, count($insertFields), '?'));
        $stmt = $conexion->prepare('INSERT INTO consumos (' . implode(', ', $insertFields) . ') VALUES (' . $placeholders . ')');
        $insertado = false;
        if (!$stmt) {
            $errores[] = 'No se pudo preparar el alta del repostaje.';
        } else {
            $bindArgs = [$insertTypes];
            foreach ($insertValues as $key => $value) { $bindArgs[] = &$insertValues[$key]; }
            call_user_func_array([$stmt, 'bind_param'], $bindArgs);
            if ($stmt->execute()) {
                $insertado = true;
            } else {
                $errores[] = 'No se pudo guardar el repostaje.';
            }
            $stmt->close();

            // Si guardamos 'lleno', recalculamos consumo real "lleno a lleno" para este registro
            if ($insertado && $useLleno && $llenoVal === 1) {
                $nuevoId = $conexion->insert_id;
                if ($nuevoId > 0) {
                    // Buscar el último 'lleno' anterior (mismo vehículo si aplica)
                    $condVeh = $useVeh ? (" AND vehiculo_id=".(int)$vehiculoId) : "";
                    $prev = $conexion->query(
                        "SELECT id, km_actuales FROM consumos WHERE lleno=1 AND id<".(int)$nuevoId.$condVeh." ORDER BY id DESC LIMIT 1"
                    );
                    if ($prev && $prev->num_rows === 1) {
                        $p = $prev->fetch_assoc();
                        $prevId = (int)$p['id'];
                        $prevKm = (int)$p['km_actuales'];
                        $kmDelta = max(0, $km - $prevKm);
                        if ($kmDelta > 0) {
                            // Sumar litros entre prevId (excluido) y nuevoId (incluido)
                            $sumQ = $conexion->query(
                                "SELECT SUM(litros) AS litros_total FROM consumos WHERE id>".(int)$prevId." AND id<=".(int)$nuevoId.$condVeh
                            );
                            $s = $sumQ ? $sumQ->fetch_assoc() : null;
                            $litrosTotal = $s ? (float)$s['litros_total'] : 0.0;
                            if ($litrosTotal > 0) {
                                $consumoReal = ($litrosTotal / $kmDelta) * 100.0;
                                // Actualizar consumo_100km del registro actual con el valor real lleno a lleno
                                $upd = $conexion->prepare("UPDATE consumos SET consumo_100km=? WHERE id=?");
                                if ($upd) { $upd->bind_param("di", $consumoReal, $nuevoId); $upd->execute(); $upd->close(); }
                            }
                        }
                    }
                }
            }
        }
        if ($insertado && empty($errores)) {
            header("Location: " . $BASE_URL . "/pages/listar.php?creado=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Repostaje</title>
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
    <div><div class="dashboard-kicker mb-1">Registro</div><h2>Nuevo repostaje</h2><p>Guarda los datos del ticket y deja que el sistema calcule el resto.</p></div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary" href="formulario.php?duplicar=1">Duplicar último</a>
      <a class="btn btn-outline-primary" href="listar.php">Ver historial</a>
    </div>
  </div>
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
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
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
    <?php if ($hasGasolinera): ?>
    <div class="mb-3">
      <label class="form-label" for="gasolinera">Gasolinera o localidad</label>
      <input type="text" id="gasolinera" name="gasolinera" maxlength="120" class="form-control <?php echo isset($errores['gasolinera']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['gasolinera']); ?>" placeholder="Ej. Cádiz, Pontevedra...">
      <?php if (isset($errores['gasolinera'])): ?><div class="invalid-feedback"><?php echo e($errores['gasolinera']); ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="mb-3">
      <label class="form-label">Precio por litro (€)</label>
      <input type="number" step="0.001" min="0.001" name="precio_litro" id="precio_litro" class="form-control <?php echo isset($errores['precio_litro']) ? 'is-invalid' : ''; ?>" required value="<?php echo e($old['precio_litro']); ?>">
      <?php if (isset($errores['precio_litro'])): ?><div class="invalid-feedback"><?php echo e($errores['precio_litro']); ?></div><?php endif; ?>
      <div class="form-text">Precio pagado por litro.</div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Litros (opcional)</label>
        <input type="number" step="0.01" min="0.01" name="litros" id="litros" class="form-control <?php echo isset($errores['litros']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['litros']); ?>">
        <?php if (isset($errores['litros'])): ?><div class="invalid-feedback"><?php echo e($errores['litros']); ?></div><?php endif; ?>
        <div class="form-text">Se calcula automáticamente si introduces el importe total.</div>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Importe total (€)</label>
        <input type="number" step="0.01" min="0.01" name="importe_total" id="importe_total" class="form-control <?php echo isset($errores['importe_total']) ? 'is-invalid' : ''; ?>" required value="<?php echo e($old['importe_total']); ?>">
        <?php if (isset($errores['importe_total'])): ?><div class="invalid-feedback"><?php echo e($errores['importe_total']); ?></div><?php endif; ?>
        <div class="form-text">Introduce el importe total pagado. Los litros se calcularán automáticamente.</div>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Km recorridos (opcional)</label>
      <input type="number" name="km_recorridos" min="0" step="1" class="form-control <?php echo isset($errores['km_recorridos']) ? 'is-invalid' : ''; ?>" placeholder="Si lo dejas vacío, se calcula por diferencia con el registro anterior" value="<?php echo e($old['km_recorridos']); ?>">
      <?php if (isset($errores['km_recorridos'])): ?><div class="invalid-feedback"><?php echo e($errores['km_recorridos']); ?></div><?php endif; ?>
      <div class="form-text">Si lo dejas vacío, se calculará como diferencia con el último "Km actuales" guardado.</div>
    </div>
    <div class="mb-3 form-check">
      <input class="form-check-input" type="checkbox" id="lleno" name="lleno" <?php echo ($old['lleno'] === '1') ? 'checked' : ''; ?>>
      <label class="form-check-label" for="lleno">Depósito lleno</label>
      <div class="form-text">Marca esta opción cuando hayas llenado el depósito. Permite calcular el consumo real "lleno a lleno".</div>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
  </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="../js/main.js?v=20261020"></script>
<script>
(function() {
  'use strict';
  const precioLitro = document.getElementById('precio_litro');
  const litros = document.getElementById('litros');
  const importeTotal = document.getElementById('importe_total');

  function calcularLitros() {
    if (precioLitro.value && importeTotal.value && parseFloat(precioLitro.value) > 0) {
      const precio = parseFloat(precioLitro.value);
      const importe = parseFloat(importeTotal.value);
      litros.value = (importe / precio).toFixed(2);
    }
  }

  function calcularImporte() {
    if (precioLitro.value && litros.value && parseFloat(precioLitro.value) > 0) {
      const precio = parseFloat(precioLitro.value);
      const lit = parseFloat(litros.value);
      importeTotal.value = (lit * precio).toFixed(2);
    }
  }

  precioLitro.addEventListener('input', function() {
    if (importeTotal.value) {
      calcularLitros();
    }
  });

  importeTotal.addEventListener('input', function() {
    if (this.value) {
      calcularLitros();
    } else {
      litros.value = '';
    }
  });

  litros.addEventListener('input', function() {
    if (this.value) {
      calcularImporte();
    } else {
      importeTotal.value = '';
    }
  });
})();
</script>
</body>
</html>
