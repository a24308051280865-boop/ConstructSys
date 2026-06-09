<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Desactivar errores en producción — el manejo de errores personalizado se encarga de todo
ini_set('display_errors', '0');
error_reporting(0);

// Cargar configuraciones, funciones y dependencias
require_once __DIR__ . '/../../settings/headers.php';
require_once __DIR__ . '/../../settings/constants.php';
require_once __DIR__ . '/../../settings/middleware.php';
require_once __DIR__ . '/../../settings/internal_controllers.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Limitar a 3 intentos por hora para evitar abusos
rateLimitByIp(maxAttempts: 3, windowSeconds: 3600); 

// Solo permitir POST para evitar que se exponga información por GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// Obtener y Validar token JWT
$authHeader = $_SERVER['HTTP_AUTHORIZATION']  ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']  ?? apache_request_headers()['Authorization'] ?? '';
if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado.']);
    exit;
}

// Validar token y obtener payload 
$payload = $JWT->validate(substr($authHeader, 7));
if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido o expirado.']);
    exit;
}

// Obtener ID de usuario del payload
$userId = $payload['sub'];

// Obtener empresa del usuario
$stmt = $INTERN_PDO->prepare(
    'SELECT e.id_empresa, e.nombre, e.db_name, e.mongo_db_name,
            e.scheduled_deletion_at,
            u.email, u.nombre AS user_nombre
     FROM empresas e
     INNER JOIN usuarios u ON u.id_empresa = e.id_empresa
     WHERE u.id_usuario = ? AND u.activo = 1 LIMIT 1'
);
$stmt->execute([$userId]);
$company = $stmt->fetch();

// Validar que la empresa exista y esté activa
if (!$company) {
    http_response_code(404);
    echo json_encode(['error' => 'Empresa no encontrada.']);
    exit;
}

// Si ya está programada para eliminación
if ($company['scheduled_deletion_at']) {
    http_response_code(409);
    echo json_encode(['error' => 'La cuenta ya está programada para eliminación.']);
    exit;
}

// Datos de Traducción para el correo 
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$lang = (isset($body['lang']) && $body['lang'] === 'en') ? 'en' : 'es';
$t = DELETION_EMAIL_MSSG[$lang];

// Definir nombres de BDs originales y de respaldo 
$originalDb  = $company['db_name'];
$originalMdb = $company['mongo_db_name'];
$archivedDb  = 'archived_' . $originalDb  . '_' . gmdate('Ymd_His');
$archivedMdb = 'archived_' . $originalMdb . '_' . gmdate('Ymd_His');

// Verificar si la BD ORIGINAL de MySQL tiene datos 
$emptyMySQL = true;
foreach (SQL_TABLES as $tabla) {
    try {
        $count = $MASTER_PDO->query(
            "SELECT COUNT(*) FROM `{$originalDb}`.`{$tabla}`"
        )->fetchColumn();
        if ($count > 0) { $emptyMySQL = false; break; }
    } catch (\PDOException) {}
}

// Verificar si la BD ORIGINAL de MongoDB tiene datos 
$emptyMongo = true;
try {
    $mongoClient = new MongoDB\Client(MONGO_URI);
    foreach (TENANT_COLLECTION_SCHEMA as $config) {
        try {
            $count = $mongoClient
                ->selectDatabase($originalMdb)
                ->selectCollection($config['collection'])
                ->countDocuments();
            if ($count > 0) { $emptyMongo = false; break; }
        } catch (\Exception) {}
    }
} catch (\Exception) {
    $emptyMongo = true;
}

// Archivar solo si tiene datos, luego eliminar original 
// MySQL
if (!$emptyMySQL) {
    try {
        $MASTER_PDO->exec("CREATE DATABASE IF NOT EXISTS `{$archivedDb}`");
        foreach (SQL_TABLES as $tabla) {
            try {
                $MASTER_PDO->exec(
                    "CREATE TABLE `{$archivedDb}`.`{$tabla}` 
                     SELECT * FROM `{$originalDb}`.`{$tabla}`"
                );
            } catch (\PDOException) {}
        }
    } catch (\PDOException) {
        $archivedDb = null;
    }
} else {
    $archivedDb = null;
}

// Eliminar BD original MySQL a toda costa
try {
    $MASTER_PDO->exec("DROP DATABASE IF EXISTS `{$originalDb}`");
} catch (\PDOException) {}

// MongoDB
if (!$emptyMongo) {
    try {
        $mongoClient = $mongoClient ?? new MongoDB\Client(MONGO_URI);
        foreach (TENANT_COLLECTION_SCHEMA as $config) {
            try {
                $docs = $mongoClient
                    ->selectDatabase($originalMdb)
                    ->selectCollection($config['collection'])
                    ->find()
                    ->toArray();
                if (!empty($docs)) {
                    $mongoClient
                        ->selectDatabase($archivedMdb)
                        ->selectCollection($config['collection'])
                        ->insertMany($docs);
                }
            } catch (\Exception) {}
        }
    } catch (\Exception) {
        $archivedMdb = null;
    }
} else {
    $archivedMdb = null;
}

// Eliminar BD original MongoDB a toda costa
try {
    $mongoClient = $mongoClient ?? new MongoDB\Client(MONGO_URI);
    $mongoClient->selectDatabase($originalMdb)->drop();
} catch (\Exception) {}

// Determinar si hay respaldo para enviar correo
$haveBackup = ($archivedDb !== null || $archivedMdb !== null);

// Generar token de recuperación solo si hay respaldo
$recoveryToken  = $haveBackup ? bin2hex(random_bytes(32)) : null;
$recoveryTokenHash = $recoveryToken ? hash('sha256', $recoveryToken) : null;
$scheduledAt = gmdate('Y-m-d H:i:s', strtotime('+30 days'));
$scheduledHuman = gmdate('d/m/Y', strtotime('+30 days'));
$idCompany  = $company['id_empresa'];
$maskedEmail  = 'deleted_' . time() . '_' . $company['email'];

// Transacción: desactivar empresa + guardar token 
try {

    // Iniciar Transacción
    $INTERN_PDO->beginTransaction();

    // Actualizar empresa: marcar como inactiva, programar eliminación, guardar nombres de BDs archivadas y enmascarar email
    $INTERN_PDO->prepare(
        'UPDATE empresas SET
             activo = 0,
             scheduled_deletion_at  = ?,
             archived_db_name = ?,
             archived_mongo_db_name = ?,
             deletion_requested_at = NOW(),
             email_original = ?,
             email = ?
         WHERE id_empresa = ?'
    )->execute([
        $haveBackup ? $scheduledAt : null,  // solo programar si hay respaldo
        $archivedDb,
        $archivedMdb,
        $company['email'],
        $maskedEmail,
        $idCompany
    ]);

    // Desactivar todos los usuarios de la empresa y enmascarar sus emails
    $INTERN_PDO->prepare(
        'UPDATE usuarios SET activo = 0, email = ? WHERE id_empresa = ?'
    )->execute([$maskedEmail, $idCompany]);

    // Invalida tokens anteriores
    $INTERN_PDO->prepare(
        'UPDATE account_deletion_requests SET confirmed = -1 
         WHERE id_empresa = ? AND confirmed = 0'
    )->execute([$idCompany]);

    // Guardar token solo si hay respaldo recuperable
    if ($recoveryTokenHash) {
        $INTERN_PDO->prepare(
            'INSERT INTO account_deletion_requests (id_empresa, id_usuario, token_hash, expires_at)
             VALUES (?, ?, ?, ?)'
        )->execute([$idCompany, $userId, $recoveryTokenHash, $scheduledAt]);
    }

    // Confirmar Transacción
    $INTERN_PDO->commit();

} catch (\PDOException $e) {
    $INTERN_PDO->rollBack();

    // Limpiar archivo MySQL archivado si se creó
    if ($archivedDb) {
        try { $MASTER_PDO->exec("DROP DATABASE IF EXISTS `{$archivedDb}`"); } catch (\PDOException) {}
    }
    // Limpiar colecciones MongoDB archivadas si se crearon
    if ($archivedMdb) {
        try {
            $mongoClient = $mongoClient ?? new MongoDB\Client(MONGO_URI);
            $mongoClient->selectDatabase($archivedMdb)->drop();
        } catch (\Exception) {}
    }

    // Responder con error genérico para no exponer detalles
    http_response_code(500);
    echo json_encode(['error' => 'Error interno. No se realizó ningún cambio.']);
    exit;
}

//  Enviar correo solo si hay respaldo recuperable 
if ($haveBackup && $recoveryToken) {
    $recoveryLink = APP_URL . '/api/auth/delete/recover.php?token=' . $recoveryToken;
    $uniqueId = bin2hex(random_bytes(8));
    $html = "
<div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px 28px;
            background:#101010;border:1px solid #242424;border-radius:6px;'>
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
            {$t['hi']} <strong style='color:#c9a84c;'>{$company['user_nombre']}</strong>,
        </p>
        <p style='color:#9e9890;font-size:13px;line-height:1.6;margin:0 0 16px;'>
            {$t['body_deletion_start']} <strong style='color:#f0ede8;'>{$company['nombre']}</strong>
            {$t['body_deletion_end']} <strong style='color:#f0ede8;'>{$scheduledHuman}</strong>.
        </p>
        <p style='color:#9e9890;font-size:13px;line-height:1.6;margin:0 0 24px;'>
            {$t['body_recovery_cta']}
        </p>
        <a href='{$recoveryLink}'
           style='display:inline-block;background:#c9a84c;color:#080808;padding:13px 28px;
                  text-decoration:none;border-radius:6px;font-weight:700;font-size:13px;
                  letter-spacing:0.06em;text-transform:uppercase;'>
            {$t['btn_recover']}
        </a>
        <p style='color:#5a5650;font-size:12px;line-height:1.6;margin:20px 0 0;'>
            {$t['expires_recovery_start']} {$scheduledHuman}. {$t['expires_recovery_end']}
        </p>
    </div>
    <hr style='border:none;border-top:1px solid #242424;margin:24px 0;'>
    <p style='text-align:center;color:#5a5650;font-size:11px;margin:0;'>{$t['footer']}</p>
</div>
<div style='display:none;max-height:0;overflow:hidden;'>{$uniqueId}</div>";

    sendEmail($company['email'], $t['subject_deletion'], $html);
}

// Responder con éxito
http_response_code(200);
echo json_encode(['success' => true]);

?>
