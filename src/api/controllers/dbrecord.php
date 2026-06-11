<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Dependencias
use MongoDB\Client as MongoClient;

/**
 * DBRecord — Registro dinámico de bases de datos SQL + NoSQL por tenant.
 *
 * Lee las empresas activas desde intern_platform y construye en tiempo
 * de ejecución el array que Router consume. Cada empresa recibe:
 *   - Una base de datos MySQL  (copia de industria_constructora)
 *   - Una base de datos MongoDB (colecciones del esquema de inventario)
 *
 * Módulos resultantes para empresa con db_name = "tenant_alpha":
 *   MySQL → tenant_alpha_clients, tenant_alpha_projects, ...
 *   MongoDB → tenant_alpha_productos, tenant_alpha_maquinas, ...
 *
 * @author Carlos Gabriel Magallanes López
 * @since 2026-05-23
 */
class DBRecord {

    /**
     * Conexión PDO a intern_platform para leer la tabla de empresas.
     * @var PDO
     */
    private PDO $pdo;
    
    /**
     * Cliente MongoDB global para crear bases de datos y colecciones.
     * @var MongoClient
     */
    private MongoClient $mongo;
    
    /**
     * Esquema de tablas MySQL por tenant.
     * @var array
     */
    private array       $tableSchema;
    
    /**
     * Esquema de colecciones MongoDB por tenant.
     * @var array
     */
    private array $collectionSchema;
    
    /**
     * Array de configuración de bases de datos para el Router, construido dinámicamente.
     * @var array
     */
    private array $databases;

    /**
     * @param PDO $pdo Conexión a intern_platform.
     * @param MongoClient $mongo Cliente MongoDB global.
     * @param array $tableSchema Esquema MySQL por tenant (TENANT_TABLE_SCHEMA).
     * @param array $collectionSchema Esquema MongoDB por tenant (TENANT_COLLECTION_SCHEMA).
     */
    public function __construct(
        PDO $pdo,
        MongoClient $mongo,
        array $tableSchema,
        array $collectionSchema
    ) {
        $this->pdo = $pdo;
        $this->mongo = $mongo;
        $this->tableSchema = $tableSchema;
        $this->collectionSchema = $collectionSchema;
        $this->build();
    }

    /**
     * Construye el array de configuración para el Router.
     * Incluye las DBs globales y una entrada por cada tenant activo.
     */
    private function build(): void {

        // Bases de datos globales (platform MySQL siempre presente)
        $this->databases = getDatabasesGlobal();

        // Leer tenants activos
        $statement = $this->pdo->query(
            'SELECT db_name, mongo_db_name FROM empresas WHERE activo = 1'
        );

        // Registrar cada tenant en el array de bases de datos
        foreach ($statement->fetchAll() as $row) {
            $this->registerTenant($row['db_name'], $row['mongo_db_name']);
        }
    }

    /**
     * Añade las entradas MySQL y MongoDB de un tenant al array de databases.
     *
     * @param string $sqlDb Nombre de la base de datos MySQL del tenant.
     * @param string $mongoDb Nombre de la base de datos MongoDB del tenant.
     */
    private function registerTenant(string $sqlDb, string $mongoDb): void {

        // MySQL 
        $this->databases[$sqlDb] = [
            'driver' => 'mysql',
            'host' => INTERNAL_DB_HOST,
            'name' => $sqlDb,
            'user' => INTERNAL_DB_USER,
            'password' => INTERNAL_DB_PASS,
            'tables' => $this->prefixModules($sqlDb, $this->tableSchema),
            'options' => DEFAULT_PDO_OPTIONS,
        ];

        // MongoDB
        $this->databases["{$sqlDb}_inv"] = [
            'driver' => 'mongodb',
            'uri' => MONGO_URI,
            'name' => $mongoDb,
            'collections' => $this->prefixModules($sqlDb, $this->collectionSchema),
        ];
    }

    /**
     * Prefijar los módulos con el db_name del tenant para evitar colisiones.
     * Ejemplo: 'clients' → 'tenant_alpha_clients'
     *
     * @param string $prefix Prefijo (db_name del tenant).
     * @param array $schema Esquema base de tablas o colecciones.
     * @return array Esquema con claves prefijadas.
     */
    private function prefixModules(string $prefix, array $schema): array {
        $result = [];
        foreach ($schema as $module => $config) $result["{$prefix}_{$module}"] = $config;
        return $result;
    }

    /**
     * Devuelve el array listo para pasarle al Router.
     * Incluye las DBs globales y las de cada tenant, con sus módulos prefijados.
     * 
     * @return array Array de configuración de bases de datos para el Router.
     */
    public function getDatabases(): array { return $this->databases;}

    /**
     * Crea una empresa nueva: provisiona MySQL + MongoDB y la registra en intern_platform.
     *
     * @param string $name Nombre comercial de la empresa.
     * @return array Resultado con success, db_name, mongo_db_name, id_empresa o error.
     */
    public function createCompany(string $name): array {

        // Generar nombres de bases de datos seguros y únicos
        $slug = preg_replace('/[^a-z0-9]/', '_', strtolower(trim($name)));
        $sqlDb = "tenant_{$slug}";
        $mongoDb = "tenant_{$slug}_inv";
        $pdo = $this->pdo;

        // Verificar que no exista ya
        $check = $pdo->prepare('SELECT id_empresa FROM empresas WHERE db_name = ?');
        $check->execute([$sqlDb]);

        if ($check->fetch()) return ['success' => false, 'error' => "La empresa '$name' ya existe."];
    
        // Intentar crear las bases de datos y registrar la empresa
        try {

            // Crear base de datos MySQL (copia de industria_constructora)
            $pdo->exec(
                "CREATE DATABASE IF NOT EXISTS `$sqlDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );

            // Crear tablas copiando la estructura de industria_constructora
            foreach (SQL_TABLES as $table) {
                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS `$sqlDb`.`$table` LIKE `industria_constructora`.`$table`"
                );
            }

            // MongoDB crea la DB al primer acceso
            $mongoDatabase = $this->mongo->selectDatabase($mongoDb);

            // Crear colecciones según el esquema definido
            foreach (MONGO_COLLECTION_VALIDATORS as $colName => $validator) {
                try {
                    $mongoDatabase->createCollection($colName, $validator);
                } catch (\Exception) {}
            }

            $proveedoresDefault = [
                ['nombre' => 'Ferremax del Norte',        'activo' => true],
                ['nombre' => 'Aceros y Perfiles Juárez',  'activo' => true],
                ['nombre' => 'Distribuidora Montoya S.A.','activo' => true],
                ['nombre' => 'Materiales Frontera',        'activo' => true],
            ];
            try {
                $mongoDatabase->selectCollection('proveedores')->insertMany($proveedoresDefault);
            } catch (\Exception) {}
            
            // Registrar la empresa en intern_platform
            $statement = $pdo->prepare(
                'INSERT INTO empresas (nombre, db_name, mongo_db_name, activo) VALUES (?, ?, ?, 1)'
            );
            $statement->execute([$name, $sqlDb, $mongoDb]);
            
            // Obtener el ID de la empresa recién creada
            $idEmpresa = (int) $pdo->lastInsertId();
 
            // ── 4. Registrar en tiempo de ejecución ───────────────────────────
            $this->registerTenant($sqlDb, $mongoDb);

            // Devolver resultado exitoso con detalles de la empresa creada
            return [
                'success' => true,
                'id_empresa' => $idEmpresa,
                'db_name' => $sqlDb,
                'mongo_db_name'=> $mongoDb,
            ];

        } catch (\PDOException $error) {
            return ['success' => false, 'error' => 'MySQL: ' . $error->getMessage()];
        } catch (\Exception $error) {
            return ['success' => false, 'error' => 'MongoDB: ' . $error->getMessage()];
        }
    }

    /**
     * Desactiva una empresa (soft delete — no borra las bases de datos).
     *
     * @param int $idEmpresa ID de la empresa a desactivar.
     * @return array Resultado con success o error.
     */
    public function deactivateCompany(int $idEmpresa): array {

        // Marcar la empresa como inactiva en intern_platform
        $statement = $this->pdo->prepare('UPDATE empresas SET activo = 0 WHERE id_empresa = ?');
        $statement->execute([$idEmpresa]);

        // Valor de rowCount() es 0 si no se encontró la empresa o ya estaba inactiva
        if ($statement->rowCount() === 0) {
            return ['success' => false, 'error' => 'Empresa no encontrada o ya inactiva.'];
        }

        // No eliminamos las bases de datos para preservar datos, pero el Router ya no las incluirá
        return ['success' => true, 'code' => 200];
    }

    /**
     * Lista todas las empresas registradas con su estado.
     *
     * @return array Lista de empresas.
     */
    public function listCompanys(): array {
        $statement = $this->pdo->query(
            'SELECT id_empresa, nombre, db_name, mongo_db_name, activo, created_at FROM empresas ORDER BY id_empresa DESC'
        );
        return $statement->fetchAll();
    }
}

?>
