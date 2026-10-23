<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

verify_csrf_token();

$id = isset($_POST['id']) && ctype_digit((string)$_POST['id']) ? (int)$_POST['id'] : 0;
$return = (string)($_POST['return'] ?? '');
if ($return === '' || strpos($return, '/') !== 0 || strpos($return, '//') === 0) {
    $return = '../index.php';
}

if ($id <= 0 || !hasTable('vehiculos')) {
    http_response_code(400);
    exit('Vehículo no válido.');
}

$db = getDb();
$stmt = $db->prepare('SELECT id FROM vehiculos WHERE id=? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    exit('No se pudo seleccionar el vehículo.');
}

$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows === 1;
$stmt->close();

if (!$exists) {
    http_response_code(404);
    exit('Vehículo no encontrado.');
}

setActiveVehiculoId($id);
header('Location: ' . $return);
exit;
