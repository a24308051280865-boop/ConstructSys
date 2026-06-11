<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(0);

require_once __DIR__ . '/../settings/headers.php';
require_once __DIR__ . '/../settings/constants.php';
require_once __DIR__ . '/../settings/internal_controllers.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado.']);
    exit;
}
$payload = $JWT->validate(substr($authHeader, 7));
if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido o expirado.']);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$coleccion = trim($body['coleccion'] ?? '');
$id        = trim($body['id']        ?? '');

$coleccionesPermitidas = ['materiales', 'herramientas', 'maquinaria', 'proveedores'];
if (!in_array($coleccion, $coleccionesPermitidas, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Colección no válida.']);
    exit;
}
if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID requerido.']);
    exit;
}

$stmtEmpresa = $INTERN_PDO->prepare(
    'SELECT e.mongo_db_name
     FROM empresas e
     INNER JOIN usuarios u ON u.id_empresa = e.id_empresa
     WHERE u.id_usuario = ? LIMIT 1'
);
$stmtEmpresa->execute([$payload['sub']]);
$empresa = $stmtEmpresa->fetch();

if (!$empresa) {
    http_response_code(404);
    echo json_encode(['error' => 'Empresa no encontrada.']);
    exit;
}

try {
    $mongo  = new MongoDB\Client(MONGO_URI);
    $col    = $mongo->selectDatabase($empresa['mongo_db_name'])
                    ->selectCollection($coleccion);

    $result = $col->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);

    if ($result->getDeletedCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Registro no encontrado.']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido.']);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
}
