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
$data      = $body['data']           ?? [];

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
if (empty($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Sin datos para actualizar.']);
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

// Campos permitidos por colección (whitelist)
$camposPermitidos = [
    'materiales'   => ['nombre','tipo','precio_unitario','unidad_medida','descripcion','activo'],
    'herramientas' => ['nombre','tipo','marca','modelo','precio','unidad_medida','descripcion','activo'],
    'maquinaria'   => ['nombre','tipo','marca','modelo','precio','unidad_medida','descripcion','activo'],
    'proveedores'  => ['nombre','contacto','notas','activo'],
];

// Filtrar solo campos permitidos
$updates = [];
foreach ($camposPermitidos[$coleccion] as $campo) {
    if (!array_key_exists($campo, $data)) continue;

    $val = $data[$campo];

    if ($campo === 'activo') {
        $updates[$campo] = (bool) $val;
    } elseif (in_array($campo, ['precio_unitario', 'precio'], true)) {
        $updates[$campo] = (float) $val;
    } elseif (in_array($campo, ['descripcion', 'modelo', 'contacto', 'notas'], true)) {
        // Eliminar campo si viene vacío (opcional)
        if ($val === '' || $val === null) continue;
        $updates[$campo] = (string) $val;
    } else {
        $updates[$campo] = (string) $val;
    }
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'Sin campos válidos para actualizar.']);
    exit;
}

try {
    $mongo  = new MongoDB\Client(MONGO_URI);
    $col    = $mongo->selectDatabase($empresa['mongo_db_name'])
                    ->selectCollection($coleccion);

    $result = $col->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($id)],
        ['$set' => $updates]
    );

    if ($result->getMatchedCount() === 0) {
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
    echo json_encode(['error' => 'Error al actualizar: ' . $e->getMessage()]);
}
