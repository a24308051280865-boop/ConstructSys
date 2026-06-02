<?php

/**
 * Endpoint: POST /api/auth/register.php
 *
 * Crea una empresa nueva (tenant) junto con su usuario administrador.
 * Provisiona MySQL + MongoDB y devuelve un JWT para acceso inmediato.
 *
 * Body esperado:
 * {
 *   "empresa":   "Constructora Alpha",
 *   "nombre":    "Carlos",
 *   "apellido":  "Magallanes",
 *   "email":     "carlos@alpha.com",
 *   "password":  "MiPassword123!"
 * }
 *
 * Respuestas:
 * 201 { success: true, token: "...", user: {...}, empresa: {...} }
 * 400 { success: false, error: "Campos obligatorios faltantes." }
 * 409 { success: false, error: "La empresa 'Alpha' ya existe." }
 * 409 { success: false, error: "Este email ya está registrado." }
 * 405 { error: "Método no permitido." }
 *
 * @author  Carlos Gabriel Magallanes López
 * @since   2026-05-23
 * @version 1.0
 */

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Configuración de errores para producción: no mostrar errores en la salida, pero registrar todos los errores para su revisión.
ini_set('display_errors', '0');// Declaraciones de tipo para verificación de tipos estrictos
error_reporting(0);

// Dependencias internas
require_once __DIR__ . '/../settings/middleware.php';
require_once __DIR__ . '/../settings/headers.php';
require_once __DIR__ . '/../settings/internal_controllers.php';

// Seguridad: rate limit + clave interna
rateLimitByIp(maxAttempts: 3, windowSeconds: 60);  

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// Leer y validar entrada JSON
$data = json_decode((string) file_get_contents('php://input'), true) ?? [];
$company = trim((string) ($data['empresa']  ?? ''));
$name = trim((string) ($data['nombre']   ?? ''));
$surname = trim((string) ($data['apellido'] ?? ''));
$email = trim((string) ($data['email']    ?? ''));
$password= trim((string) ($data['password'] ?? ''));

// Validaciones básicas
if ($company === '' || $name === '' || $surname === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios.']);
    exit;
}

// Validar formato de email y longitud de contraseña
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El email no tiene un formato válido.']);
    exit;
}

// Validar que la contraseña tenga al menos 8 caracteres
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.']);
    exit;
}

// Validar el Resultado de la creación de la empresa
$companyResult = $DB_RECORD->createCompany($company);
if (!$companyResult['success']) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $companyResult['error']]);
    exit;
}

// Extraer detalles de la empresa recién creada para su uso posterior
$idCompany  = $companyResult['id_empresa'];
$sqlDbName  = $companyResult['db_name'];
$mongoDbName = $companyResult['mongo_db_name'];


// Registrar el usuario administrador para la empresa recién creada
$registerResult = $AUTH_SERVICE->register(
    name: $name,
    surname: $surname,
    email: $email,
    password: $password,
    role: 'admin',         
);

// Validar el Resultado del registro del usuario administrador
if (!$registerResult['success']) {
    $INTERN_PDO->prepare('DELETE FROM empresas WHERE id_empresa = ?')->execute([$idCompany]);
    http_response_code($registerResult['code']);
    echo json_encode(['success' => false, 'error' => $registerResult['error']]);
    exit;
}

// Asociar usuario con empresa
$userId = $registerResult['id'];
$INTERN_PDO->prepare('UPDATE usuarios SET id_empresa = ? WHERE id_usuario = ?')->execute([$idCompany, $userId]);

// Generar un JWT para el usuario administrador recién creado
$token = $JWT->generate($userId, $email, 'admin', $name, $sqlDbName);

// Responder con éxito, incluyendo el token JWT, los detalles del usuario y la empresa
http_response_code(201);
echo json_encode([
    'success' => true,
    'token' => $token,
    'user' => [
        'id' => $userId,
        'nombre' => $name,
        'apellido' => $surname,
        'email' => $email,
        'rol' => 'admin',
        'db_name' => $sqlDbName,
    ],
    'empresa' => [
        'id' => $idCompany,
        'nombre' => $company,
        'db_name' => $sqlDbName,
        'mongo_db_name' => $mongoDbName,
    ],
]);

?>