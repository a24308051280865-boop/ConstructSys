<?php
declare(strict_types = 1);

require_once __DIR__ . '/../settings/constants.php';
require_once __DIR__ . '/../settings/internal_controllers.php';

// Obtener todas las empresas cuyo período de gracia expiró
$expired = $INTERN_PDO->query(
    'SELECT id_empresa, archived_db_name, archived_mongo_db_name
     FROM empresas
     WHERE activo = 0
       AND scheduled_deletion_at IS NOT NULL
       AND scheduled_deletion_at < NOW()'
)->fetchAll();

if (empty($expired)) {
    echo '[CRON] No hay cuentas que eliminar.' . PHP_EOL;
    exit;
}

$mongoClient = new MongoDB\Client(MONGO_URI);

foreach ($expired as $company) {

    $idEmpresa = $company['id_empresa'];

    // ── 1. Drop MySQL archivado ──────────────────────────────────
    if ($company['archived_db_name']) {
        try {
            $MASTER_PDO->exec(
                "DROP DATABASE IF EXISTS `{$company['archived_db_name']}`"
            );
            echo "[CRON] MySQL eliminado: {$company['archived_db_name']}" . PHP_EOL;
        } catch (\PDOException $e) {
            echo "[CRON] Error MySQL: " . $e->getMessage() . PHP_EOL;
        }
    }

    // ── 2. Drop MongoDB archivado ────────────────────────────────
    if ($company['archived_mongo_db_name']) {
        try {
            $mongoClient->selectDatabase($company['archived_mongo_db_name'])->drop();
            echo "[CRON] MongoDB eliminado: {$company['archived_mongo_db_name']}" . PHP_EOL;
        } catch (\Exception $e) {
            echo "[CRON] Error MongoDB: " . $e->getMessage() . PHP_EOL;
        }
    }

    // ── 3. Eliminar tokens ───────────────────────────────────────
    $INTERN_PDO->prepare(
        'DELETE FROM account_deletion_requests WHERE id_empresa = ?'
    )->execute([$idEmpresa]);

    // ── 4. Eliminar usuarios ─────────────────────────────────────
    $INTERN_PDO->prepare(
        'DELETE FROM usuarios WHERE id_empresa = ?'
    )->execute([$idEmpresa]);

    // ── 5. Eliminar empresa ──────────────────────────────────────
    $INTERN_PDO->prepare(
        'DELETE FROM empresas WHERE id_empresa = ?'
    )->execute([$idEmpresa]);

    echo "[CRON] Empresa {$idEmpresa} eliminada permanentemente." . PHP_EOL;
}

echo '[CRON] Limpieza completada.' . PHP_EOL;
?>