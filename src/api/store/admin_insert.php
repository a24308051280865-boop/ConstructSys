<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(0);

require_once __DIR__ . '/../../settings/headers.php';
require_once __DIR__ . '/../../settings/constants.php';
require_once __DIR__ . '/../../settings/internal_controllers.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// Validar JWT
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

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$coleccion  = trim($body['coleccion'] ?? '');
$data       = $body['data'] ?? [];

$coleccionesPermitidas = ['materiales', 'herramientas', 'maquinaria', 'proveedores'];
if (!in_array($coleccion, $coleccionesPermitidas, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Colección no válida.']);
    exit;
}

// Obtener el nombre de la BD de inventario del tenant
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

$mongoDbName = $empresa['mongo_db_name'];

try {
    $mongo = new MongoDB\Client(MONGO_URI);
    $db    = $mongo->selectDatabase($mongoDbName);

    // ── Resolver proveedor_nombre → proveedor_id ──────────────────────────────
    if ($coleccion !== 'proveedores') {

        $proveedorNombre = $data['proveedor_nombre'] ?? '';
        if (empty($proveedorNombre)) {
            http_response_code(400);
            echo json_encode(['error' => 'Proveedor requerido.']);
            exit;
        }

        // Buscar proveedor por nombre en la colección del tenant
        $provDoc = $db->selectCollection('proveedores')->findOne(
            ['nombre' => $proveedorNombre],
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
        );

        if (!$provDoc) {
            http_response_code(404);
            echo json_encode(['error' => "Proveedor '{$proveedorNombre}' no encontrado. Regístralo primero."]);
            exit;
        }

        // Reemplazar nombre por ObjectId
        unset($data['proveedor_nombre']);
        $data['proveedor_id'] = $provDoc['_id'];
    }

    // ── Convertir tipos antes de insertar ─────────────────────────────────────
    if (isset($data['precio_unitario'])) $data['precio_unitario'] = (float) $data['precio_unitario'];
    if (isset($data['precio']))          $data['precio']          = (float) $data['precio'];
    if (isset($data['activo']))          $data['activo']          = (bool)  $data['activo'];

    // Eliminar campos vacíos opcionales
    foreach (['descripcion', 'imagen_clave', 'modelo', 'contacto', 'notas'] as $campo) {
        if (isset($data[$campo]) && $data[$campo] === '') unset($data[$campo]);
    }

    $db->selectCollection($coleccion)->insertOne($data);

    http_response_code(201);
    echo json_encode(['success' => true]);

} catch (MongoDB\Driver\Exception\BulkWriteException $e) {
    http_response_code(422);
    echo json_encode(['error' => 'Validación fallida: ' . $e->getMessage()]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al insertar: ' . $e->getMessage()]);
}
