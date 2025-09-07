<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';

$BASE_URL = '..';
$db = getDb();
if (!hasTable('mantenimientos')) {
  die('La tabla "mantenimientos" no existe. Ejecuta la migración en sql/gasolinaBD.sql');
}
$vehiculoId = getActiveVehiculoId();
if ($vehiculoId === null) {
  die('Selecciona un vehículo desde la barra superior.');
}

function t(string $s): string { return trim($s); }
function nInt($v): ?int { return ($v === '' || $v === null) ? null : (int)$v; }

$errores = [];
$editId = isset($_GET['editar']) && ctype_digit((string)$_GET['editar']) ? (int)$_GET['editar'] : null;

// Borrar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    $stmt = $db->prepare('DELETE FROM mantenimientos WHERE id=? AND vehiculo_id=?');
    if ($stmt) { $stmt->bind_param('ii',$id,$vehiculoId); $stmt->execute(); $stmt->close(); }
  }
  header('Location: '.$BASE_URL.'/pages/mantenimientos.php?ok=eliminado');
  exit;
}

// Guardar (crear/editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar') {
  $id = isset($_POST['id']) && $_POST['id']!=='' ? (int)$_POST['id'] : null;
  $tipo = t($_POST['tipo'] ?? '');
  $cada_km = nInt($_POST['cada_km'] ?? null);
  $cada_meses = nInt($_POST['cada_meses'] ?? null);
  $ultima_fecha = t($_POST['ultima_fecha'] ?? '');
  $ultimo_km = nInt($_POST['ultimo_km'] ?? null);
  $nota = t($_POST['nota'] ?? '');

  if ($tipo==='') $errores[]='El tipo es obligatorio';

  // Calcular próximos
  $proximo_km_calc = null;
  if ($cada_km !== null && $ultimo_km !== null) { $proximo_km_calc = max(0, $ultimo_km + $cada_km); }
  $proxima_fecha_calc = null;
  if ($cada_meses !== null && $ultima_fecha !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $ultima_fecha);
    if ($dt) { $dt->modify('+'.$cada_meses.' month'); $proxima_fecha_calc = $dt->format('Y-m-d'); }
  }

  if (empty($errores)) {
    if ($id === null) {
      $stmt = $db->prepare('INSERT INTO mantenimientos (vehiculo_id, tipo, cada_km, cada_meses, ultima_fecha, ultimo_km, proxima_fecha_calc, proximo_km_calc, nota) VALUES (?,?,?,?,?,?,?,?,?)');
      if ($stmt) { $stmt->bind_param('isii­siiss', $vehiculoId, $tipo, $cada_km, $cada_meses, $ultima_fecha, $ultimo_km, $proxima_fecha_calc, $proximo_km_calc, $nota); $stmt->execute(); $stmt->close(); }
      header('Location: '.$BASE_URL.'/pages/mantenimientos.php?ok=creado'); exit;
    } else {
      $stmt = $db->prepare('UPDATE mantenimientos SET tipo=?, cada_km=?, cada_meses=?, ultima_fecha=?, ultimo_km=?, proxima_fecha_calc=?, proximo_km_calc=?, nota=? WHERE id=? AND vehiculo_id=?');
      if ($stmt) { $stmt->bind_param('siisi­sisii', $tipo, $cada_km, $cada_meses, $ultima_fecha, $ultimo_km, $proxima_fecha_calc, $proximo_km_calc, $nota, $id, $vehiculoId); $stmt->execute(); $stmt->close(); }
      header('Location: '.$BASE_URL.'/pages/mantenimientos.php?ok=actualizado'); exit;
    }
  }
}

// Cargar lista (solo del vehículo activo)
$items = [];
$res = $db->prepare('SELECT * FROM mantenimientos WHERE vehiculo_id=? ORDER BY proxima_fecha_calc IS NULL, proxima_fecha_calc, proximo_km_calc');
if ($res) { $res->bind_param('i',$vehiculoId); $res->execute(); $r = $res->get_result(); while($row=$r->fetch_assoc()){ $items[]=$row; } $res->close(); }

// Cargar edición
$edit = null;
if ($editId !== null) {
  foreach ($items as $row) { if ((int)$row['id']===$editId) { $edit=$row; break; } }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mantenimientos - Control Gasolina</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo $BASE_URL; ?>/css/main.css?v=20250902" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-4">
  <h1 class="mb-4">🛠️ Mantenimientos</h1>

  <?php if (!empty($errores)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errores as $e){ echo '<li>'.e($e).'</li>'; } ?></ul></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header">Listado</div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Último (fecha / km)</th>
                <th>Frecuencia</th>
                <th>Próximo (fecha / km)</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="5" class="text-center text-muted">No hay mantenimientos aún</td></tr>
              <?php else: foreach ($items as $it): ?>
                <tr>
                  <td><?php echo e($it['tipo']); ?></td>
                  <td>
                    <?php echo e($it['ultima_fecha'] ?? '—'); ?>
                    <?php echo isset($it['ultimo_km']) ? ' / '.(int)$it['ultimo_km'].' km' : ''; ?>
                  </td>
                  <td>
                    <?php echo isset($it['cada_km']) ? (int)$it['cada_km'].' km' : '—'; ?>
                    <?php echo isset($it['cada_meses']) ? ' / '.(int)$it['cada_meses'].' meses' : ''; ?>
                  </td>
                  <td>
                    <?php echo $it['proxima_fecha_calc'] ? e($it['proxima_fecha_calc']) : '—'; ?>
                    <?php echo $it['proximo_km_calc'] ? ' / '.(int)$it['proximo_km_calc'].' km' : ''; ?>
                  </td>
                  <td class="d-flex gap-2">
                    <a href="?editar=<?php echo (int)$it['id']; ?>" class="btn btn-sm btn-secondary">Editar</a>
                    <form method="post" onsubmit="return confirm('¿Eliminar mantenimiento?');">
                      <input type="hidden" name="accion" value="eliminar">
                      <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header"><?php echo $edit ? 'Editar mantenimiento' : 'Nuevo mantenimiento'; ?></div>
        <div class="card-body">
          <form method="post" class="row g-3">
            <input type="hidden" name="accion" value="guardar">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>"><?php endif; ?>
            <div class="col-12">
              <label class="form-label">Tipo</label>
              <input name="tipo" class="form-control" list="tipos-sugeridos" required value="<?php echo e($edit['tipo'] ?? ''); ?>">
              <datalist id="tipos-sugeridos">
                <option value="Aceite motor"></option>
                <option value="Filtro aceite"></option>
                <option value="Filtro aire"></option>
                <option value="Filtro habitáculo"></option>
                <option value="Bujías"></option>
                <option value="Correa/Variador"></option>
                <option value="Neumáticos"></option>
                <option value="ITV"></option>
                <option value="Seguro"></option>
              </datalist>
            </div>
            <div class="col-md-6">
              <label class="form-label">Cada (km)</label>
              <input type="number" min="0" step="1" name="cada_km" class="form-control" value="<?php echo e(isset($edit['cada_km'])?(string)$edit['cada_km']:''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cada (meses)</label>
              <input type="number" min="0" step="1" name="cada_meses" class="form-control" value="<?php echo e(isset($edit['cada_meses'])?(string)$edit['cada_meses']:''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Última fecha</label>
              <input type="date" name="ultima_fecha" class="form-control" value="<?php echo e($edit['ultima_fecha'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Último km</label>
              <input type="number" min="0" step="1" name="ultimo_km" class="form-control" value="<?php echo e(isset($edit['ultimo_km'])?(string)$edit['ultimo_km']:''); ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Nota</label>
              <textarea name="nota" rows="2" class="form-control"><?php echo e($edit['nota'] ?? ''); ?></textarea>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <?php if ($edit): ?><a class="btn btn-outline-secondary" href="<?php echo $BASE_URL; ?>/pages/mantenimientos.php">Cancelar</a><?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
