<?php

/**
 * Punto final: POST /auth/login.php
 *
 * Autentica a un usuario y devuelve un JWT firmado.
 * Este archivo es el punto de entrada HTTP — delega toda
 * la lógica a AuthService, que maneja la validación de credenciales.
 *
 * Respuestas posibles:
 * 200 {success: true,  token: "...", user: {...}}
 * 400 {success: false, error: "Email y contraseña son obligatorios."}
 * 401 {success: false, error: "Credenciales incorrectas"}
 * 403 {success: false, error: "Cuenta inactiva. Contacte con soporte."}
 * 405 {error: "No permitido. Utilice el método POST."}
 *
 * @author Carlos Gabriel Magallanes López
 * @since 2026-05-19
 * @version 1.0
 */

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Configuración de errores para producción: no mostrar errores en la salida, pero registrar todos los errores para su revisión.
ini_set('display_errors', '0');
error_reporting(0);

// Dependencias Internas
require_once __DIR__ . '/../settings/middleware.php';
require_once __DIR__ . '/../settings/headers.php';     
require_once __DIR__ . '/../settings/internal_controllers.php';         

// Configurar encabezados CORS para permitir solicitudes desde el cliente
rateLimitByIp(maxAttempts: 5, windowSeconds: 60);

// Solo permitir solicitudes POST a este punto final
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    // Responder con 405 Método no permitido si el método de solicitud no es POST  
    http_response_code(405);     
    echo json_encode(['error' => 'Método de solicitud no permitido.']);
    exit;
}

// Obtener la entrada JSON sin procesar del cuerpo de la solicitud y decodificarla en una matriz asociativa
$body = (string) file_get_contents('php://input');
$data = json_decode($body, true) ?? [];
$email = trim((string) ($data['email'] ?? ''));
$password = trim((string) ($data['password'] ?? ''));

// Validar que tanto el correo electrónico como la contraseña se proporcionan y no están vacíos
if ($email === '' || $password === '') {
    
    // Responder con 400 Solicitud incorrecta si falta el correo electrónico o la contraseña o están vacíos
    http_response_code(400);    
    echo json_encode(['error' => 'Email y contraseña son obligatorios.']);
    exit;

}

// Delegar la lógica de autenticación al servicio AuthService, que verificará las credenciales y generará un JWT si son válidas
$result = $AUTH_SERVICE->login($email, $password);

// Establecer el código de respuesta HTTP basado en el resultado del método de inicio de sesión y devolver el resultado como JSON
http_response_code($result['code']);
unset($result['code']);
echo json_encode($result);

?>