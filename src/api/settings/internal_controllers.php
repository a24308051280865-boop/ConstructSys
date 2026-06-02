<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Dependencias Internas
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../controllers/database.php';
require_once __DIR__ . '/../controllers/mongodb.php';
require_once __DIR__ . '/../controllers/dbrecord.php';
require_once __DIR__ . '/../auth/jwt.php';
require_once __DIR__ . '/../auth/service.php';

// Configuración de la base de datos global (intern_platform)
$PLATFORM_DB = new Database(
    name: INTERNAL_DB_NAME,
    tables: DATABASES_GLOBAL['platform']['tables'],
    host: INTERNAL_DB_HOST,
    user: INTERNAL_DB_USER,
    password: INTERNAL_DB_PASS,
);

// Conexión a la base de datos MongoDB global (para operaciones administrativas)
$MONGO_DB = new MongoDatabase(
    uri: MONGO_URI,
    name: INTERNAL_MONGO_DB,        
    collections: TENANT_COLLECTION_SCHEMA,
);

// Registro dinámico de bases de datos para el Router
$DB_RECORD = new DBRecord(
    pdo: $PLATFORM_DB->getConnection(),
    mongo: $MONGO_DB -> getClient(),
    tableSchema: TENANT_TABLE_SCHEMA,
    collectionSchema: TENANT_COLLECTION_SCHEMA,
);

// Instancias globales de JWT y AuthService para uso en controladores internos
$JWT = new JWT(JWT_SECRET);

// La AuthService interna se conecta a la base de datos global (intern_platform) para gestionar usuarios y empresas
$AUTH_SERVICE = new AuthService($PLATFORM_DB, $JWT);

// Conexión global a la base de datos SQL para controladores internos (si necesitan acceso directo)
$INTERN_PDO = $PLATFORM_DB->getConnection();

?>