<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Dependencias Internas
require_once __DIR__ . '/../../settings/headers.php';
require_once __DIR__ . '/../../settings/constants.php';

// Generar state aleatorio para prevenir falsificación de solicitudes entre sitios (CSRF)
$state = bin2hex(random_bytes(16));

// Guardar en cookie segura — válida 5 minutos
setcookie('oauth_state', $state, [
    'expires' => time() + 300,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Construir URL de autorización de Google
$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',  // Siempre muestra el selector de cuenta
]);

// Redirigir al usuario a la página de autorización de Google
header("Location: $url");
exit;

?>