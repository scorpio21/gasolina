<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';

$BASE_URL = '..';
$db = getDb();

if (!hasTable('vehiculos')) {
  die('La tabla "vehiculos" no existe. Revisa sql/gasolinaBD.sql');
}

// Utilidades simples
function limpiarTexto(string $s): string { return trim(filter_var($s, FILTER_UNSAFE_RAW)); }
function limpiarEntero($v): ?int { return ($v === '' || $v === null) ? null : (int)$v; }
function limpiarDecimal($v): ?float { return ($v === '' || $v === null) ? null : (float)$v; }

$cols = [
  'marca' => true,
  'modelo' => true,
  'anio' => hasColumn('vehiculos','anio'),
  'combustible' => hasColumn('vehiculos','combustible'),
  'matricula' => hasColumn('vehiculos','matricula'),
  'vin' => hasColumn('vehiculos','vin'),
  'foto_url' => hasColumn('vehiculos','foto_url'),
  'capacidad_deposito_l' => hasColumn('vehiculos','capacidad_deposito_l'),
  'activo' => hasColumn('vehiculos','activo'),
];

$errores = [];
$editId = isset($_GET['editar']) && ctype_digit((string)$_GET['editar']) ? (int)$_GET['editar'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_token();
}

// Activar vehículo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'activar' && ctype_digit((string)($_POST['id'] ?? ''))) {
  $activarId = (int)$_POST['id'];
  $activado = true;
  if ($cols['activo']) {
    if (!$db->query("UPDATE vehiculos SET activo=0")) {
      $errores[] = 'No se pudo actualizar el vehículo activo.';
      $activado = false;
    }
    $stmt = $db->prepare("UPDATE vehiculos SET activo=1 WHERE id=?");
    if (!$stmt) {
      $errores[] = 'No se pudo preparar la activación.';
      $activado = false;
    } else {
      $stmt->bind_param('i', $activarId);
      if (!$stmt->execute() || $stmt->affected_rows < 1) {
        $errores[] = 'No se pudo activar el vehículo seleccionado.';
        $activado = false;
      }
      $stmt->close();
    }
  }
  if ($activado && empty($errores)) {
    setActiveVehiculoId($activarId);
    header('Location: '.$BASE_URL.'/pages/vehiculos.php?ok=activado');
    exit;
  }
}

// Eliminar vehículo (solo POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['accion']) && $_POST['accion']==='eliminar')) {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id > 0) {
    // Solo permitir borrar si no hay consumos asociados
    $res = $db->query("SELECT COUNT(*) AS c FROM consumos WHERE vehiculo_id=".(int)$id);
    if (!$res) {
      $errores[] = 'No se pudo comprobar si el vehículo tiene consumos.';
    } elseif ((int)$res->fetch_assoc()['c'] > 0) {
      $errores[] = 'No puedes borrar un vehículo con consumos asociados.';
    } else {
      $stmt = $db->prepare("DELETE FROM vehiculos WHERE id=?");
      if (!$stmt) {
        $errores[] = 'No se pudo preparar el borrado del vehículo.';
      } else {
        $stmt->bind_param('i',$id);
        if (!$stmt->execute() || $stmt->affected_rows < 1) {
          $errores[] = 'No se pudo eliminar el vehículo.';
        }
        $stmt->close();
      }
      if (empty($errores)) {
        if (getActiveVehiculoId() === $id) { setActiveVehiculoId(null); }
        header('Location: '.$BASE_URL.'/pages/vehiculos.php?ok=eliminado');
        exit;
      }
    }
  } else {
    $errores[] = 'Identificador de vehículo no válido.';
  }
}

// Crear/Actualizar vehículo (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion']==='guardar') {
  $id = isset($_POST['id']) && $_POST['id']!=='' ? (int)$_POST['id'] : null;
  $marca = limpiarTexto($_POST['marca'] ?? '');
  $modelo = limpiarTexto($_POST['modelo'] ?? '');
  $anio = $cols['anio'] ? limpiarEntero($_POST['anio'] ?? null) : null;
  $comb = $cols['combustible'] ? limpiarTexto($_POST['combustible'] ?? '') : '';
  $mat = $cols['matricula'] ? limpiarTexto($_POST['matricula'] ?? '') : '';
  $vin  = $cols['vin'] ? limpiarTexto($_POST['vin'] ?? '') : '';
  $foto = $cols['foto_url'] ? limpiarTexto($_POST['foto_url'] ?? '') : '';
  $cap  = $cols['capacidad_deposito_l'] ? limpiarDecimal($_POST['capacidad_deposito_l'] ?? null) : null;
  $activo = $cols['activo'] ? (isset($_POST['activo']) ? 1 : 0) : 1;

  if ($marca==='') $errores[]='La marca es obligatoria';
  if ($modelo==='') $errores[]='El modelo es obligatorio';

  if (empty($errores)) {
    if ($id === null) {
      // INSERT
      $campos = ['marca','modelo'];
      $place = ['?','?'];
      $tipos = 'ss';
      $vals = [&$marca,&$modelo];
      if ($cols['anio']) { $campos[]='anio'; $place[]='?'; $tipos.='i'; $vals[]=&$anio; }
      if ($cols['combustible']) { $campos[]='combustible'; $place[]='?'; $tipos.='s'; $vals[]=&$comb; }
      if ($cols['matricula']) { $campos[]='matricula'; $place[]='?'; $tipos.='s'; $vals[]=&$mat; }
      if ($cols['vin']) { $campos[]='vin'; $place[]='?'; $tipos.='s'; $vals[]=&$vin; }
      if ($cols['foto_url']) { $campos[]='foto_url'; $place[]='?'; $tipos.='s'; $vals[]=&$foto; }
      if ($cols['capacidad_deposito_l']) { $campos[]='capacidad_deposito_l'; $place[]='?'; $tipos.='d'; $vals[]=&$cap; }
      if ($cols['activo']) { $campos[]='activo'; $place[]='?'; $tipos.='i'; $vals[]=&$activo; }
      $sql = 'INSERT INTO vehiculos ('.implode(',',$campos).') VALUES ('.implode(',',$place).')';
      $stmt = $db->prepare($sql);
      if (!$stmt) {
        $errores[] = 'No se pudo preparar el alta del vehículo.';
      } else {
        $stmt->bind_param($tipos, ...$vals);
        if (!$stmt->execute()) {
          $errores[] = 'No se pudo guardar el vehículo.';
        }
        $nuevoId = $stmt->insert_id;
        $stmt->close();
        if (empty($errores) && $activo && $cols['activo'] && !$db->query('UPDATE vehiculos SET activo=0 WHERE id<>'.(int)$nuevoId)) {
          $errores[] = 'El vehículo se guardó, pero no se pudo actualizar el vehículo activo.';
        }
      }
      if (empty($errores)) { header('Location: '.$BASE_URL.'/pages/vehiculos.php?ok=creado'); exit; }
    } else {
      // UPDATE
      $set = ['marca=?','modelo=?'];
      $tipos='ss';
      $vals=[&$marca,&$modelo];
      if ($cols['anio']) { $set[]='anio=?'; $tipos.='i'; $vals[]=&$anio; }
      if ($cols['combustible']) { $set[]='combustible=?'; $tipos.='s'; $vals[]=&$comb; }
      if ($cols['matricula']) { $set[]='matricula=?'; $tipos.='s'; $vals[]=&$mat; }
      if ($cols['vin']) { $set[]='vin=?'; $tipos.='s'; $vals[]=&$vin; }
      if ($cols['foto_url']) { $set[]='foto_url=?'; $tipos.='s'; $vals[]=&$foto; }
      if ($cols['capacidad_deposito_l']) { $set[]='capacidad_deposito_l=?'; $tipos.='d'; $vals[]=&$cap; }
      if ($cols['activo']) { $set[]='activo=?'; $tipos.='i'; $vals[]=&$activo; }
      $tipos.='i';
      $vals[]=&$id;
      $sql='UPDATE vehiculos SET '.implode(',',$set).' WHERE id=?';
      $stmt=$db->prepare($sql);
      if (!$stmt) {
        $errores[] = 'No se pudo preparar la actualización del vehículo.';
      } else {
        $stmt->bind_param($tipos, ...$vals);
        if (!$stmt->execute()) { $errores[] = 'No se pudo actualizar el vehículo.'; }
        $stmt->close();
      }
      if (empty($errores) && $cols['activo'] && $activo && !$db->query('UPDATE vehiculos SET activo=0 WHERE id<>'.(int)$id)) {
        $errores[] = 'El vehículo se actualizó, pero no se pudo actualizar el vehículo activo.';
      }
      if (empty($errores)) { header('Location: '.$BASE_URL.'/pages/vehiculos.php?ok=actualizado'); exit; }
    }
  }
}

// Datos para la lista
$vehiculos = [];
$res = $db->query("SELECT * FROM vehiculos ORDER BY activo DESC, marca, modelo");
if ($res) { while ($r=$res->fetch_assoc()) { $vehiculos[]=$r; } }

// Si se está editando, cargar datos
$editRow = null;
if ($editId !== null) {
  foreach ($vehiculos as $r) { if ((int)$r['id']===$editId) { $editRow=$r; break; } }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vehículos - Control Gasolina</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo $BASE_URL; ?>/css/main.css?v=20250902" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-4">
  <h1 class="mb-4">🚘 Vehículos</h1>

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
                <th>ID</th>
                <th>Marca</th>
                <th>Modelo</th>
                <?php if ($cols['anio']): ?><th>Año</th><?php endif; ?>
                <?php if ($cols['combustible']): ?><th>Combustible</th><?php endif; ?>
                <?php if ($cols['foto_url']): ?><th>Foto</th><?php endif; ?>
                <?php if ($cols['activo']): ?><th>Activo</th><?php endif; ?>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($vehiculos)): ?>
                <tr><td colspan="8" class="text-center text-muted">No hay vehículos aún</td></tr>
              <?php else: foreach ($vehiculos as $v): ?>
                <tr>
                  <td><?php echo (int)$v['id']; ?></td>
                  <td><?php echo e((string)$v['marca']); ?></td>
                  <td><?php echo e((string)$v['modelo']); ?></td>
                  <?php if ($cols['anio']): ?><td><?php echo e((string)($v['anio'] ?? '')); ?></td><?php endif; ?>
                  <?php if ($cols['combustible']): ?><td><?php echo e((string)($v['combustible'] ?? '')); ?></td><?php endif; ?>
                  <?php if ($cols['foto_url']): ?><td><?php if (!empty($v['foto_url'])): ?><img src="<?php echo e((strpos($v['foto_url'],'http')===0)?$v['foto_url']:$BASE_URL.'/'.ltrim($v['foto_url'],'/')); ?>" alt="foto" width="40"><?php endif; ?></td><?php endif; ?>
                  <?php if ($cols['activo']): ?><td><?php echo isset($v['activo']) && (int)$v['activo']===1 ? 'Sí' : 'No'; ?></td><?php endif; ?>
                  <td class="d-flex gap-2">
                    <a href="?editar=<?php echo (int)$v['id']; ?>" class="btn btn-sm btn-secondary">Editar</a>
                    <form method="post">
                      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                      <input type="hidden" name="accion" value="activar">
                      <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-primary">Hacer activo</button>
                    </form>
                    <form method="post" onsubmit="return confirm('¿Seguro que deseas eliminar este vehículo?');">
                      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                      <input type="hidden" name="accion" value="eliminar">
                      <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
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
        <div class="card-header"><?php echo $editRow ? 'Editar vehículo' : 'Nuevo vehículo'; ?></div>
        <div class="card-body">
          <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="accion" value="guardar">
            <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>"><?php endif; ?>
            <div class="col-12">
              <label class="form-label">Marca</label>
              <input type="text" name="marca" class="form-control" required value="<?php echo e($editRow['marca'] ?? ''); ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Modelo</label>
              <input type="text" name="modelo" class="form-control" required value="<?php echo e($editRow['modelo'] ?? ''); ?>">
            </div>
            <?php if ($cols['anio']): ?>
            <div class="col-md-6">
              <label class="form-label">Año</label>
              <input type="number" name="anio" class="form-control" min="1900" max="2100" value="<?php echo e((string)($editRow['anio'] ?? '')); ?>">
            </div>
            <?php endif; ?>
            <?php if ($cols['combustible']): ?>
            <div class="col-md-6">
              <label class="form-label">Combustible</label>
              <input type="text" name="combustible" class="form-control" value="<?php echo e($editRow['combustible'] ?? ''); ?>">
            </div>
            <?php endif; ?>
            <?php if ($cols['matricula']): ?>
            <div class="col-md-6">
              <label class="form-label">Matrícula</label>
              <input type="text" name="matricula" class="form-control" value="<?php echo e($editRow['matricula'] ?? ''); ?>">
            </div>
            <?php endif; ?>
            <?php if ($cols['vin']): ?>
            <div class="col-md-6">
              <label class="form-label">Número de bastidor (VIN)</label>
              <input type="text" name="vin" class="form-control" placeholder="17 caracteres" value="<?php echo e($editRow['vin'] ?? ''); ?>">
            </div>
            <?php endif; ?>
            <?php if ($cols['capacidad_deposito_l']): ?>
            <div class="col-md-6">
              <label class="form-label">Capacidad depósito (L)</label>
              <input type="number" step="0.1" min="0" name="capacidad_deposito_l" class="form-control" value="<?php echo e(isset($editRow['capacidad_deposito_l']) ? (string)$editRow['capacidad_deposito_l'] : ''); ?>">
            </div>
            <?php endif; ?>
            <?php if ($cols['foto_url']): ?>
            <div class="col-12">
              <label class="form-label">Foto (URL o ruta relativa)</label>
              <input type="text" name="foto_url" class="form-control" placeholder="p. ej. img/audi.png" value="<?php echo e($editRow['foto_url'] ?? ''); ?>">
            </div>
            <?php endif; ?>
            <?php if ($cols['activo']): ?>
            <div class="col-12 form-check">
              <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo (!empty($editRow) && isset($editRow['activo']) && (int)$editRow['activo']===1) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="activo">Activo</label>
            </div>
            <?php endif; ?>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <?php if ($editRow): ?><a class="btn btn-outline-secondary" href="<?php echo $BASE_URL; ?>/pages/vehiculos.php">Cancelar</a><?php endif; ?>
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
