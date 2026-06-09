<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Desactivar errores en producción — el manejo de errores personalizado se encarga de todo
ini_set('display_errors', '0');
error_reporting(0);

// Incluir archivos de configuración, controladores y funciones auxiliares
require_once __DIR__ . '/../../settings/headers.php';
require_once __DIR__ . '/../../settings/constants.php';
require_once __DIR__ . '/../../settings/middleware.php';
require_once __DIR__ . '/../../settings/internal_controllers.php';
require_once __DIR__ . '/../../utils/helpers.php';

// Rate limit estricto — máx 5 intentos cada 15 minutos por IP para evitar abuso del endpoint
rateLimitByIp(maxAttempts: 5, windowSeconds: 900);

// Solo permitir POST — el endpoint es para iniciar el proceso de restablecimiento de contraseña
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// Leer y validar el email del cuerpo JSON
$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string) ($body['email'] ?? ''));

// Validación básica del email — no revelar detalles sobre la existencia del correo
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Si el correo existe, recibirás un enlace en breve.']);
    exit;
}

// Buscar usuario por email
$statement = $INTERN_PDO->prepare('SELECT id_usuario, nombre, password_hash FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1');
$statement->execute([$email]);
$user = $statement->fetch();

// Si no existe → respuesta genérica 
if (!$user) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Si el correo existe, recibirás un enlace en breve.']);
    exit;
}

// ID único para el email (no se usa en la lógica, solo para que Gmail no recorte el correo como repetido si se solicitan varios en poco tiempo)
$uniqueId = bin2hex(random_bytes(8));

// Obtener textos según el idioma (por ahora solo español e inglés, con español como predeterminado)
$language = (isset($body['lang']) && $body['lang'] === 'en') ? 'en' : 'es';
$t = EMAIL_MSSG[$language];

// Si es cuenta OAuth (sin contraseña local) → correo diferente
if (empty($user['password_hash'])) {
    $html = "
        <div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px 28px;background:#101010;border:1px solid #242424;border-radius:6px;'>
            <div style='text-align:center;margin-bottom:28px;'>
                <h2 style='font-size:20px;font-weight:700;color:#c9a84c;margin:0;letter-spacing:0.05em;'>
                    ConstructSys
                </h2>
                <p style='font-size:10px;color:#f0ede8;letter-spacing:0.25em;text-transform:uppercase;margin:4px 0 0;'>
                    {$t['tagline']}
                </p>
            </div>
            <div style='background:#161616;border:1px solid #2e2e2e;border-radius:6px;padding:24px;text-align:center;'>
                <p style='color:#f0ede8;font-size:14px;margin:0 0 8px;'>
                    {$t['hi']}
                    <strong style='color:#c9a84c;'>
                        {$user['nombre']}
                    </strong>
                </p>
                <p style='color:#9e9890;font-size:13px;line-height:1.6;margin:0 0 16px;'>
                    {$t['google_body']}
                </p>
                <p style='color:#9e9890;font-size:13px;line-height:1.6;margin:0;'>
                    {$t['google_hint']}
                </p>
            </div>
            <p style='text-align:center;color:#5a5650;font-size:11px;margin-top:24px;'>
                {$t['footer']}
            </p>
        </div>
        <div style='display:none;max-height:0;overflow:hidden;'>
            {$uniqueId}
        </div>
    ";
    sendEmail($email, $t['subject_google'], $html);
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Si el correo existe, recibirás un enlace en breve.']);
    exit;
}

// Invalidar tokens anteriores no usados del mismo usuario
$INTERN_PDO->prepare('UPDATE password_resets SET used = 1 WHERE id_usuario = ? AND used = 0')->execute([$user['id_usuario']]);

// Generar token criptográfico seguro
$realToken = bin2hex(random_bytes(32));           // 64 chars hex es el que va en el email
$tokenHash = hash('sha256', $realToken);          // SHA-256 es el que se guarda en la BD

// Guardar hash en la BD (NUNCA el token real)
$INTERN_PDO->prepare('INSERT INTO password_resets (id_usuario, token_hash, expires_at) VALUES (?, ?, ?)')->execute([$user['id_usuario'], $tokenHash, date('Y-m-d H:i:s', time() + 900)]);

// Construir email HTML con estilo profesional y claro
$html = "
    <div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px 28px;background:#101010;border:1px solid #242424;border-radius:6px;'>
        <div style='text-align:center;margin-bottom:28px;'>
            <h2 style='font-size:20px;font-weight:700;color:#c9a84c;margin:0;letter-spacing:0.05em;'>ConstructSys</h2>
            <p style='font-size:10px;color:#f0ede8;letter-spacing:0.25em;text-transform:uppercase;margin:4px 0 0;'>{$t['tagline']}</p>
        </div>
        <div style='background:#161616;border:1px solid #2e2e2e;border-radius:6px;padding:24px;text-align:center;'>
            <p style='color:#f0ede8;font-size:14px;margin:0 0 8px;'>{$t['hi']} <strong style='color:#c9a84c;'>{$user['nombre']}</strong></p>
            <p style='color:#9e9890;font-size:13px;line-height:1.6;margin:0 0 24px;'>{$t['reset_body']}</p>
            <a href='" . APP_URL . "/views/reset-password.html?token={$realToken}'
            style='display:inline-block;background:#c9a84c;color:#080808;padding:12px 28px;text-decoration:none;border-radius:6px;font-weight:700;font-size:14px;letter-spacing:0.08em;text-transform:uppercase;'>
                {$t['btn']}
            </a>
            <p style='color:#5a5650;font-size:12px;line-height:1.6;margin:20px 0 0;'>{$t['expires']}<br>{$t['ignore']}</p>
        </div>
        <hr style='border:none;border-top:1px solid #242424;margin:24px 0;'>
        <p style='text-align:center;color:#5a5650;font-size:11px;margin:0;'>{$t['footer']}</p>
    </div>
    <div style='display:none;max-height:0;overflow:hidden;'>{$uniqueId}</div>";
 
// Enviar email (función interna que maneja el envío real)
sendEmail($email, $t['subject_reset'], $html);

// Respuesta genérica para evitar revelar si el correo existe o no
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Si el correo existe, recibirás un enlace en breve.']);

?>