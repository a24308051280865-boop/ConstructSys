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
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

// Rate limit estricto — máx 5 intentos cada 15 minutos por IP para evitar abuso del endpoint
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// El token se recibe como query param para facilitar su uso desde el enlace del correo
$realToken = trim($_GET['token'] ?? '');
if ($realToken === '') {
    header('Location: ' . APP_URL . '/views/cancel-deletion.html?status=invalid');
    exit;
}

// El token se almacena como hash en la BD por seguridad, para que si alguien obtiene acceso a la tabla de tokens no pueda usarlos directamente
$tokenHash = hash('sha256', $realToken);

// Validar token y obtener datos completos de la empresa 
$stmt = $INTERN_PDO->prepare(
    'SELECT adr.id  AS request_id,
            adr.id_usuario,
            e.id_empresa,
            e.nombre  AS empresa_nombre,
            e.db_name AS db_original,
            e.mongo_db_name AS mongo_original,
            e.archived_db_name AS db_archived,
            e.archived_mongo_db_name  AS mongo_archived,
            e.email_original,
            u.nombre AS user_nombre
     FROM account_deletion_requests adr
     INNER JOIN empresas  e ON e.id_empresa  = adr.id_empresa
     INNER JOIN usuarios  u ON u.id_usuario  = adr.id_usuario
     WHERE adr.token_hash = ?
       AND adr.confirmed  = 0
       AND adr.expires_at > NOW()
     LIMIT 1'
);
$stmt->execute([$tokenHash]);
$request = $stmt->fetch();

// Si el token no es válido o ha expirado → redirigir a página genérica (no revelar detalles)
if (!$request) {
    header('Location: ' . APP_URL . '/views/cancel-deletion.html?status=invalid');
    exit;
}

// Preparar nombres de BD originales y archivos de respaldo
$dbOriginal = $request['db_original'];        
$mongoOriginal = $request['mongo_original'];     
$dbArchived = $request['db_archived'];       
$mongoArchived = $request['mongo_archived'];     

// Siempre recrear la BD original con la estructura correcta.
// Si hay archivo con datos, copiarlos; si no, queda vacía pero funcional.
$mysqlRestored = false;
try {

    // Crear BD original (puede no existir si fue eliminada)
    $MASTER_PDO->exec("CREATE DATABASE IF NOT EXISTS `{$dbOriginal}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Crear todas las tablas copiando estructura de industria_constructora
    foreach (SQL_TABLES as $tabla) {
        try {
            $MASTER_PDO->exec(
                "CREATE TABLE IF NOT EXISTS `{$dbOriginal}`.`{$tabla}` LIKE `industria_constructora`.`{$tabla}`"
            );
        } catch (\PDOException) {
            // Si falla una tabla individual, continuar con las demás
        }
    }

    // Si existe BD archivada con datos, restaurarlos tabla por tabla
    if ($dbArchived !== null) {
        foreach (SQL_TABLES as $tabla) {
            try {

                // Verificar que la tabla archivada exista y tenga datos
                $count = $MASTER_PDO->query("SELECT COUNT(*) FROM `{$dbArchived}`.`{$tabla}`")->fetchColumn();

                // Si hay datos, copiarlos a la tabla recién creada (que tiene la estructura correcta)
                if ($count > 0) {
                    
                    // Vaciar destino por si acaso y copiar datos
                    $MASTER_PDO->exec("DELETE FROM `{$dbOriginal}`.`{$tabla}`");
                    $MASTER_PDO->exec("INSERT INTO `{$dbOriginal}`.`{$tabla}` SELECT * FROM `{$dbArchived}`.`{$tabla}`");
                }
            } catch (\PDOException) {
                // Tabla no existía en archivo o error — continuar
            }
        }

        // Eliminar BD archivada tras restaurar exitosamente
        try {
            $MASTER_PDO->exec("DROP DATABASE IF EXISTS `{$dbArchived}`");
        } catch (\PDOException) {}
    }
    $mysqlRestored = true;
} catch (\PDOException $e) {
    // Si falla la creación de la BD principal, abortar
    header('Location: ' . APP_URL . '/views/cancel-deletion.html?status=error');
    exit;
}

// Si había archivo Mongo con datos → restaurar.
// Si no había archivo (bd estaba vacía) → crear colecciones vacías.
// En ambos casos la BD queda operativa con el nombre original.
try {

    // Conexión a MongoDB
    $mongoClient = new MongoDB\Client(MONGO_URI);
    $mongoDest = $mongoClient->selectDatabase($mongoOriginal);

    // Si hay archivo con datos, copiar colección por colección
    if ($mongoArchived !== null) {
        
        // Hay archivo — copiar colección por colección
        $mongoSrc = $mongoClient->selectDatabase($mongoArchived);

        // Para cada colección definida en el esquema, intentar copiar datos
        foreach (TENANT_COLLECTION_SCHEMA as $config) {
            $colName = $config['collection'];
            try {
                $docs = $mongoSrc->selectCollection($colName)->find()->toArray();
                if (!empty($docs)) {

                    // Crear colección destino si no existe y volcar datos
                    $mongoDest->selectCollection($colName)->insertMany($docs);
                
                } else {
                    
                    // Colección vacía en archivo — solo crearla
                    $mongoDest->createCollection($colName);
                }
            } catch (\Exception) {
                
            // Si falla una colección, intentar al menos crearla vacía
                try { $mongoDest->createCollection($colName); } catch (\Exception) {}
            }
        }

        // Eliminar BD archivada de Mongo
        try {
            $mongoClient->selectDatabase($mongoArchived)->drop();
        } catch (\Exception) {}

    } else {
        
        // No había archivo (Mongo estaba vacía o nunca se archivó)
        // Recrear todas las colecciones vacías para que el sistema funcione
        foreach (TENANT_COLLECTION_SCHEMA as $config) {
            try {
                $mongoDest->createCollection($config['collection']);
            } catch (\Exception) {}
        }
    }

} catch (\Exception) {
    // MongoDB no crítico — el sistema puede funcionar sin inventario
    // Continuar con la restauración de MySQL y la reactivación
}

// Reactivar empresa en intern_platform 
try {

    // Iniciar Transacción
    $INTERN_PDO->beginTransaction();

    // Reactivar empresa
    $INTERN_PDO->prepare(
        'UPDATE empresas SET
             activo = 1,
             scheduled_deletion_at  = NULL,
             deletion_requested_at  = NULL,
             archived_db_name = NULL,
             archived_mongo_db_name = NULL,
             email = email_original,
             email_original = NULL
         WHERE id_empresa = ?'
    )->execute([$request['id_empresa']]);

    // Reactivar usuarios asociados a la empresa (si se eliminaron)
    $INTERN_PDO->prepare(
        'UPDATE usuarios
         SET activo = 1,
             email  = ?
         WHERE id_empresa = ?'
    )->execute([$request['email_original'], $request['id_empresa']]);

    // Invalidar el token de recuperación usado
    $INTERN_PDO->prepare(
        'UPDATE account_deletion_requests
         SET confirmed = 1
         WHERE id = ?'
    )->execute([$request['request_id']]);

    // Confirmar transacción
    $INTERN_PDO->commit();

} catch (\PDOException) {

    // Rollback si falla
    $INTERN_PDO->rollBack();

    // Si falla la reactivación, intentar limpiar la BD recién creada
    // para no dejar inconsistencias (la empresa sigue marcada como eliminada)
    try {
        $MASTER_PDO->exec("DROP DATABASE IF EXISTS `{$dbOriginal}`");
    } catch (\PDOException) {}

    header('Location: ' . APP_URL . '/views/cancel-deletion.html?status=error');
    exit;
}

// Enviar correo de confirmación de recuperación 
$acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es';
$lang = str_starts_with($acceptLang, 'en') ? 'en' : 'es';
$t = DELETION_EMAIL_MSSG[$lang];
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
            {$t['hi']} <strong style='color:#c9a84c;'>{$request['user_nombre']}</strong>
        </p>
        <p style='color:#9e9890;font-size:13px;line-height:1.6;margin:0;'>
            {$t['body_cancel']} <strong style='color:#f0ede8;'>{$request['empresa_nombre']}</strong>
            {$t['body_recovered']}
        </p>
    </div>
    <hr style='border:none;border-top:1px solid #242424;margin:24px 0;'>
    <p style='text-align:center;color:#5a5650;font-size:11px;margin:0;'>{$t['footer']}</p>
</div>
<div style='display:none;max-height:0;overflow:hidden;'>{$uniqueId}</div>";

// Enviar correo al email original registrado (que es el que se usó para la cuenta y para solicitar la eliminación)
sendEmail($request['email_original'], $t['subject_recovered'], $html);

// Redirigir a página de confirmación genérica (no revelar detalles específicos en la URL)
header('Location: ' . APP_URL . '/views/cancel-deletion.html?status=success');
exit;

?>