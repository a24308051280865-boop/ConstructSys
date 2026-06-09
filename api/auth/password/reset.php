<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Desactivar errores en producción — el manejo de errores personalizado se encarga de todo
ini_set('display_errors', '0');
error_reporting(0);

// Incluir archivos de configuración, controladores y funciones auxiliares
require_once __DIR__ . '/../../settings/headers.php';
require_once __DIR__ . '/../../settings/constants.php';
require_once __DIR__ . '/../../settings/internal_controllers.php';

// Metodo de Request
$method = $_SERVER['REQUEST_METHOD'];

//GET: Validar Token
if ($method === 'GET') {

    // Validar que el token esté presente
    $tokenReal = trim($_GET['token'] ?? '');
    if ($tokenReal === '') {
        http_response_code(400);
        echo json_encode(['valid' => false, 'error' => 'TOKEN_MISSING']);
        exit;
    }

    // Hash del token
    $tokenHash = hash('sha256', $tokenReal);

    // Ejecutar consulta para verificar token (no revelar detalles específicos)
    $statement = $INTERN_PDO->prepare(
        'SELECT id FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW() LIMIT 1'
    );
    $statement->execute([$tokenHash]);

    // Si no se encuentra → respuesta genérica de token inválido/expirado
    if (!$statement->fetch()) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'error' => 'TOKEN_INVALID_OR_EXPIRED']);
        exit;
    }

    // Token válido
    http_response_code(200);
    echo json_encode(['valid' => true]);
    exit;
}

// POST: Actualizar la contraseña
if ($method === 'POST') {

    // Leer y validar datos del cuerpo JSON
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $realToken = trim((string) ($body['token'] ?? ''));
    $password = trim((string) ($body['password'] ?? ''));
    $confirm = trim((string) ($body['confirm']  ?? ''));

    // Validaciones
    if ($realToken === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Campos incompletos.']);
        exit;
    }
    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.']);
        exit;
    }
    if ($password !== $confirm) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Las contraseñas no coinciden.']);
        exit;
    }

    // Hash del token
    $tokenHash = hash('sha256', $realToken);

    // Verificar el token de nuevo (doble validación)
    $statement = $INTERN_PDO->prepare(
        'SELECT id, id_usuario FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW() LIMIT 1;'
    );
    $statement->execute([$tokenHash]);
    $reset = $statement->fetch();

    // Si no se encuentra → respuesta genérica de token inválido/expirado
    if (!$reset) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El enlace es inválido o ha expirado.']);
        exit;
    }

    // Transacción: actualizar contraseña + invalidar token
    try {
        $INTERN_PDO->beginTransaction();
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $INTERN_PDO->prepare('UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?')->execute([$newHash, $reset['id_usuario']]);
        $INTERN_PDO->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset['id']]);
        $INTERN_PDO->commit();
    } catch (PDOException $e) {
        $INTERN_PDO->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno. Intenta de nuevo.']);
        exit;
    }

    // Respuesta de éxito
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
    exit;
}

// Si el método no es GET ni POST → error 405
http_response_code(405);
echo json_encode(['error' => 'Método no permitido.']);

?>