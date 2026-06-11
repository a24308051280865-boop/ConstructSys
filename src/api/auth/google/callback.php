<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Dependencias Internas
require_once __DIR__ . '/../../settings/headers.php';
require_once __DIR__ . '/../../settings/constants.php';
require_once __DIR__ . '/../../settings/internal_controllers.php';
require_once __DIR__ . '/../../utils/helpers.php';

// Verificar state (protección de falsificación de solicitudes entre sitios (CSRF))
$receivedState = $_GET['state'] ?? '';
$savedState = $_COOKIE['oauth_state'] ?? '';

// Compara Estado Actual con el Estado de la Cookie
if ($receivedState === '' || !hash_equals($savedState, $receivedState)) {
    redirectWithError('Estado inválido. Intenta de nuevo.');
}

// Limpiar cookie de state
setcookie('oauth_state', '', time() - 3600, '/');

// Verificar que Google no devolvió error
if (isset($_GET['error'])) {
    redirectWithError('Acceso cancelado por el usuario.');
}

// Obtener Código de Autorización
$code = $_GET['code'] ?? '';
if ($code === '') {
    redirectWithError('No se recibió código de autorización.');
}

// Intercambiar code por tokens (CURL=)
$tokens = curlPost('https://oauth2.googleapis.com/token', [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
]);
 
// Validar que se halla obtenido el Token de Google
$idToken = $tokens['id_token'] ?? '';
if (empty($idToken)) {
    redirectWithError('No se pudo obtener el token de Google.');
}

// Verificar id_token con Google y obtener datos del usuario 
$userInfo = curlGet('https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken);

// Obtener Datos Especifícos
$email = $userInfo['email'] ?? '';
$name = $userInfo['given_name'] ?? 'Usuario';
$surname = $userInfo['family_name'] ?? 'Google';
$verified = $userInfo['email_verified'] ?? 'false';

// Verificar el Correo
if ($email === '' || $verified !== 'true') redirectWithError('No se pudo verificar el correo de Google.');

// Validar que el Usuario no exista
$statement = $INTERN_PDO->prepare('SELECT id_usuario, activo FROM usuarios WHERE email = ? LIMIT 1');
$statement->execute([$email]);
$user = $statement->fetch();

// Registrar si no existe
if (!$user) {

    // Usuario nuevo — se registra sin contraseña (solo puede entrar con Google)
    $statement = $INTERN_PDO->prepare(
        'INSERT INTO usuarios (nombre, apellido, email, password_hash, activo)
         VALUES (?, ?, ?, ?, 1)'
    );
    $statement->execute([$name, $surname, $email, '']);
    $userId = (int) $INTERN_PDO->lastInsertId();

} 
elseif (!$user['activo']) { redirectWithError('Cuenta inactiva. Contacta con soporte.');}
else {
    $userId = (int) $user['id_usuario'];
}

// Obtener empresa del usuario (MySQL y nombre)
$statementDb = $INTERN_PDO->prepare(
    'SELECT e.db_name, e.nombre AS empresa_nombre
     FROM empresas e
     INNER JOIN usuarios u ON u.id_empresa = e.id_empresa
     WHERE u.id_usuario = ? LIMIT 1'
);
$statementDb->execute([$userId]);
$company = $statementDb->fetch();
$db = $company['db_name'] ?? '';
$companyName = $company['empresa_nombre'] ?? '';
    
// Si el usuario no tiene una empresa asignada, no se puede generar un token válido
if (!$company || empty($db)) {
    redirectWithError('Tu cuenta no está asignada a ninguna empresa. Contacta con soporte.');
}

// Generar JWT con empresa incluida
$token = $JWT->generate($userId, $email, $name, $db, $companyName);

// Redirigir al frontend con el token en la URL (puede ser manejado por el frontend para iniciar sesión)
header("Location: " . APP_URL . "views/login.html?google_token=" . urlencode($token));
exit;

?>
