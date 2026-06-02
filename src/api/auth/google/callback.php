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
$idToken = $tokens['id_token'];
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
$statement = $INTERN_PDO->prepare(
    'SELECT id_usuario, rol, activo FROM usuarios WHERE email = ? LIMIT 1'
);
$statement->execute([$email]);
$user = $statement->fetch();

// Registrar si no existe
if (!$user) {

    // Usuario nuevo — se registra sin contraseña (solo puede entrar con Google)
    $statement = $INTERN_PDO->prepare(
        'INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $statement->execute([$name, $surname, $email, '', 'empleado']);
    $userId = (int) $INTERN_PDO->lastInsertId();
    $role = 'empleado';

} 
elseif (!$user['activo'])  redirectWithError('Cuenta inactiva. Contacta con soporte.');
else {
    $userId = (int) $user['id_usuario'];
    $role = $user['rol'];
}

// Obtener el nombre de la base de datos de la empresa del usuario (si existe)
$statementDb = $INTERN_PDO->prepare(
    'SELECT e.db_name FROM empresas e
     INNER JOIN usuarios u ON u.id_empresa = e.id_empresa
     WHERE u.id_usuario = ? LIMIT 1'
);
$statementDb->execute([$userId]);
$company = $statementDb->fetch();
$db = $company['db_name'];

// Valor de empresa es obligatorio para acceder al sistema
if (!$company || empty($db)) {
    redirectWithError('Tu cuenta no está asignada a ninguna empresa. Contacta con soporte.');
}

// Generar JWT y redirigir al dashboard 
$token = $JWT->generate($userId, $email, $role, $name, $db);

// Pasar el token al frontend via query string — el JS lo guarda en localStorage
header("Location: http://localhost/ConstructIndustry/?google_token=" . urlencode($token) . '&rol=' . urlencode($role));
exit;

?>