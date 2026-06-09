<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Constantes para la configuración de la clase Database
require_once __DIR__ . '/../settings/constants.php';

/**
 * Matriz asociativa que asigna índices de opciones de PDO a sus nombres.
 * 
 * @internal No use esta constante fuera de este archivo. Solo uso interno.
 */
const PDO_ARG_NAMES = [
    1 => 'PDO::ATTR_ERRMODE',
    2 => 'PDO::ATTR_DEFAULT_FETCH_MODE',
    3 => 'PDO::ATTR_EMULATE_PREPARES'
];


/**
 * Valida la estructura del esquema de configuración para las tablas de la base de datos.
 * 
 * Esta función verifica que el esquema proporcionado no esté vacío y que cada configuración de tabla
 * contenga las claves requeridas: 'select', 'insert', 'insert_fields' y 'delete'. También se asegura de que
 * 'insert_fields' sea una matriz. Si alguna de estas condiciones no se cumple, se lanza una InvalidArgumentException 
 * con un mensaje de error descriptivo.
 * 
 * @param array $schema Esquema de configuración a validar, típicamente una matriz asociativa donde las claves son nombres de tabla y los valores son sus configuraciones.
 * @return void
 * @throws InvalidArgumentException Si el esquema está vacío o si falta alguna clave requerida en la configuración de tabla o tiene tipos inválidos.
 */
function validateSchema(array $schema): void{
        
    // Validar que el esquema no esté vacío
    if (empty($schema)) {
        throw new InvalidArgumentException("El esquema de configuración no puede estar vacío. Proporcione al menos una configuración de tabla.");
    }

    // Validar cada par clave-valor en el esquema
    foreach ($schema as $table => $config) {
        
        // Validar que la configuración de cada tabla sea una matriz
        if (!is_array($config)) {
            throw new InvalidArgumentException("La configuración de la tabla '$table' debe ser una matriz.");
        }

        // Definir las claves requeridas para cada configuración de tabla
        foreach (REQUIRED_SCHEMA_KEYS as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidArgumentException(
                    "Falta la clave requerida '$key' en la configuración de la tabla '$table'. Cada configuración de tabla debe incluir las claves 'select', 'insert', 'insert_fields' y 'delete'."
                );
            }
        }

        // Validar que 'insert_fields' sea una matriz
        if (!is_array($config['insert_fields'])) {
            throw new InvalidArgumentException("Error en la tabla '$table': 'insert_fields' debe ser una matriz de cadenas.");
        }
    }
}


/**
 * Valida las claves específicas de MongoDB de una entrada de configuración de base de datos.
 *
 * Utiliza REQUIRED_MONGO_KEYS para la presencia de claves y valida el esquema de URI
 * antes de delegar la validación de colección a validateCollections().
 *
 * @param string $dbName Nombre de la base de datos que se está validando.
 * @param array $dbConfig Matriz de configuración para esta entrada de MongoDB.
 * @return void
 *
 * @throws InvalidArgumentException Si falta alguna clave específfica de MongoDB o es inválida.
 */
function validateMongoEntry(string $dbName, array $dbConfig): void {

    // Validar que todas las claves requeridas específicas de MongoDB estén presentes (REQUIRED_MONGO_KEYS)
    foreach (REQUIRED_MONGO_KEYS as $key) {
        if (!array_key_exists($key, $dbConfig)) {
            throw new InvalidArgumentException(
                "La configuración de MongoDB para '$dbName' falta la clave requerida '$key'."
            );
        }
    }

    // Obtener el URI de la configuración para validación
    $uri = $dbConfig['uri'];

    // El 'uri' debe ser una cadena no vacía con un esquema de MongoDB válido
    if (!is_string($uri) || trim($uri) === '') {
        throw new InvalidArgumentException(
            "El campo 'uri' en '$dbName' debe ser una cadena no vacía."
        );
    }

    // Validar que el URI comience con 'mongodb://' o 'mongodb+srv://'
    if (!str_starts_with($uri, 'mongodb://') && !str_starts_with($uri, 'mongodb+srv://')) {
        throw new InvalidArgumentException(
            "El campo 'uri' en '$dbName' debe comenzar con 'mongodb://' o 'mongodb+srv://'."
        );
    }

    // Validar la matriz de colecciones usando REQUIRED_COLLECTION_KEYS
    validateCollections($dbName, $dbConfig['collections']);
}

/**
 * Valida que las opciones contengan todas las opciones de PDO requeridas.
 * 
 * Esta función verifica que la matriz de opciones proporcionada contenga todas las opciones de PDO requeridas definidas en REQUIRED_PDO_OPTIONS. 
 * Ayuda a garantizar que la conexión a la base de datos esté configurada correctamente y evita errores en tiempo de ejecución debido a opciones de PDO faltantes o inválidas.
 * Si falta alguna opción requerida o es inválida, se lanza una InvalidArgumentException.
 * 
 * @param array $options Matriz de opciones de PDO a validar.
 * @return void
 * @throws InvalidArgumentException Si falta alguna opción de PDO requerida o es inválida.
 */
function validatePDOOptions(array $options): void {

    // Verificar si todas las opciones requeridas están presentes en la matriz de opciones proporcionada
    foreach (REQUIRED_PDO_OPTIONS as $index => $option) {
        
        // Validar que la opción sea una constante de PDO válida para las dos primeras opciones (modo de error y modo de búsqueda)
        if ($index < 2) {
            if (!is_int($option) || !in_array($option, PDO_REFLECTION->getConstants(), true)) {
                throw new InvalidArgumentException("Opción de PDO inválida " . PDO_ARG_NAMES[$option] . ". Se esperaba una constante de PDO válida.");
            }
        }

        // Validar que la opción requerida esté presente en la matriz de opciones proporcionada
        if (!array_key_exists($option, $options)) {
            throw new InvalidArgumentException(
                "Opción de PDO requerida faltante: " . PDO_ARG_NAMES[$option] . ". Proporcione esta opción en la configuración."
            );
        }
    }

}

/**
 * Valida una matriz de bases de datos.
 *
 * Comprueba que la matriz de bases de datos proporcionada no esté vacía y que
 * cada configuración de base de datos contenga todas las claves requeridas (host, name, user, password, tables) con tipos válidos.
 *
 * @param array $databases Matriz asociativa, configuración de bases de datos a validar.
 * @return void Verdadero si la configuración es válida, falso en caso contrario.
 *
 * @throws InvalidArgumentException Si la configuración es inválida
 */
function validateDatabase(array $databases): void {

    // Validar que la matriz de bases de datos no esté vacía
    if (empty($databases)) {
        throw new InvalidArgumentException(
            "La matriz de bases de datos no puede estar vacía. Proporcione al menos una configuración de base de datos."
            );
    }

    // Validar cada configuración de base de datos
    foreach ($databases as $db => $dbConfig) {
        if ($dbConfig['driver'] === 'mysql') validateDBEntry($db, $dbConfig);
        else validateMongoEntry($db, $dbConfig);
    }

}

/**
 * Valida una entrada de configuración de base de datos.
 *
 * Comprueba que la configuración de base de datos proporcionada contenga todas las claves requeridas 
 * (host, name, user, password, tables) con tipos válidos.
 *
 * @param string $dbName Nombre de la base de datos siendo validada, utilizado en mensajes de error.
 * @param mixed $dbConfig La matriz de configuración para la base de datos a validar.
 * 
 * @return void 
 *
 * @throws InvalidArgumentException Si la configuración es inválida
 */
function validateDBEntry(string $dbName, mixed $dbConfig): void {

    // Validar que la configuración de la base de datos sea una matriz
    if (!is_array($dbConfig)) {
        throw new InvalidArgumentException(
            "La configuración de la base de datos '$dbName' debe ser una matriz."
        );
    }

    // Validar que todas las claves requeridas estén presentes en la configuración de la base de datos
    foreach (REQUIRED_DB_KEYS as $requiredKey) {
        if (!array_key_exists($requiredKey, $dbConfig)) {
            throw new InvalidArgumentException(
                "Falta la clave '$requiredKey' en la configuración de la base de datos '$dbName'."
            );
        }
    }

    // Validar que host, name y user sean cadenas no vacías
    foreach (REQUIRED_STR_DB_KEYS as $field) {
        $field = $dbConfig[$field];
        if (!is_string($field) || trim($field) === '') {
            throw new InvalidArgumentException(
                "El campo '$field' en la base de datos '$dbName' debe ser una cadena no vacía."
            );
        }
    }

    // Validar que la contraseña sea una cadena (puede estar vacía)
    if (!is_string($dbConfig['password'])) {
        throw new InvalidArgumentException(
            "El campo 'password' en la base de datos '$dbName' debe ser una cadena."
        );
    }

    // Validar tablas
    validateDBTables($dbName, $dbConfig['tables']);

    // Validar opciones de PDO
    validatePDOOptions($dbConfig['options']);
}
 
/**
 * Valida la configuración de las tablas para una base de datos.
 *
 * Comprueba que la configuración de tablas proporcionada sea una matriz y que cada configuración de tabla 
 * contenga todas las claves requeridas (select, insert, insert_fields, delete) con tipos válidos. 
 *
 * @param string $dbName Nombre de la base de datos siendo validada, utilizado en mensajes de error.
 * @param array $tables La matriz de configuración para las tablas de la base de datos a validar.
 * 
 * @return void 
 *
 * @throws InvalidArgumentException Si la configuración es inválida
 */
function validateDBTables(string $dbName, array $tables): void {

    // Validar que la configuración de tablas sea una matriz y no esté vacía
    if (!is_array($tables) || empty($tables)) {
        throw new InvalidArgumentException(
            "La configuración de 'tables' para la base de datos '$dbName' debe ser una matriz no vacía."
        );
    }

    // Validar cada configuración de tabla
    foreach ($tables as $tableName => $tableConfig) {
        if (!is_array($tableConfig)) {
            throw new InvalidArgumentException(
                "La configuración de la tabla '$tableName' en la base de datos '$dbName' debe ser una matriz."
            );
        }
 
        // Validar que todas las claves requeridas estén presentes en la configuración de tabla
        foreach (REQUIRED_SCHEMA_KEYS as $key) {
            if (!array_key_exists($key, $tableConfig)) {
                throw new InvalidArgumentException(
                    "La configuración de la tabla '$tableName' en la base de datos '$dbName' no contiene la clave requerida '$key'" 
                );
            }
        }

        // Validar que los comandos SQL para select, insert y delete sean cadenas no vacías
        foreach (REQUIRED_SQL_KEYS as $sql) {
        $sqlCommand  = $tableConfig[$sql];    
        if (!is_string($sqlCommand) || trim($sqlCommand) === '') {
                throw new InvalidArgumentException( 
                    "La clave '$sql' en la tabla '$tableName' de la base de datos '$dbName' debe ser una cadena SQL no vacía."
                );
            }
        }
 
        // Validar que 'insert_fields' sea una matriz no vacía de cadenas
        $insertFields = $tableConfig['insert_fields'];
        if (!is_array($insertFields) || empty($insertFields)) {
            throw new InvalidArgumentException(
                "'insert_fields' en la tabla '$tableName' de la base de datos '$dbName' debe ser una matriz no vacía de cadenas."
            );
        }

        // Validar que cada elemento en 'insert_fields' sea una cadena no vacía
        foreach ($insertFields as $index => $field) {
            if (!is_string($field) || trim($field) === '') {
                throw new InvalidArgumentException(
                    "El elemento [$index] de 'insert_fields' en la tabla '$tableName' de la base de datos '$dbName' debe ser una cadena no vacía."
                );
            }
        }
    }
}

/**
 * Valida la matriz completa de colecciones de una entrada de configuración de base de datos de MongoDB.
 *
 * Comprueba que la configuración de colecciones sea una matriz no vacía y valida
 * cada entrada de colección usando validateCollection().
 * 
 * @param string $dbName Alias de la base de datos principal en DATABASES.
 * @param mixed $collections Valor de la clave 'collections' en la configuración de base de datos.
 * @return void
 *
 * @throws InvalidArgumentException Si la estructura falta, está vacía o alguna
 *                                  colección individual falla en la validación.
 */
function validateCollections(string $dbName, mixed $collections): void {

    // Las colecciones deben ser una matriz asociativa no vacía
    if (!is_array($collections) || empty($collections)) {
        throw new InvalidArgumentException(
            "El campo 'collections' en '$dbName' debe ser una matriz no vacía de configuraciones de colección."
        );
    }

    // Validar cada configuración de colección usando validateCollection()
    foreach ($collections as $moduleKey => $collectionConfig) {
        validateCollection($dbName, $moduleKey, $collectionConfig);
    }

}

/**
 * Valida la configuración de una entrada única de colección de MongoDB.
 *
 * Utiliza REQUIRED_COLLECTION_KEYS para verificar la presencia de clave y valida
 * tipos y valores para cada campo:
 *
 *  - 'collection': cadena — nombre real de la colección de MongoDB
 *  - 'sort': array<string,int> — pares campo => 1 | -1
 *  - 'insert_fields': list<string> — campos a asignar en POST
 *  - 'delete_field': cadena — campo usado como filtro en DELETE
 *
 * @param string $dbName Alias de la base de datos principal en DATABASES.
 * @param string $moduleKey Clave utilizada para identificar este módulo en el enrutador.
 * @param mixed $collectionConfig Valor de configuración para esta colección.
 * @return void
 *
 * @throws InvalidArgumentException Si alguna clave requerida está ausente, tiene el
 *                                  tipo incorrecto o contiene un valor inválido.
 */
function validateCollection(string $dbName, string $moduleKey, mixed $collectionConfig): void {

    // Cada entrada de colección debe ser una matriz asociativa
    if (!is_array($collectionConfig)) {
        throw new InvalidArgumentException(
            "La configuración de la colección '$moduleKey' en '$dbName' debe ser una matriz."
        );
    }

    // Las cuatro claves requeridas deben estar presentes — usa REQUIRED_COLLECTION_KEYS
    foreach (REQUIRED_COLLECTION_KEYS as $key) {
        if (!array_key_exists($key, $collectionConfig)) {
            throw new InvalidArgumentException(
                "La configuración de la colección '$moduleKey' en '$dbName' carece de la clave requerida '$key'."
            );
        }
    }

    // 'collection' — nombre real de la colección de MongoDB, debe ser una cadena no vacía
    if (!is_string($collectionConfig['collection']) ||
        trim($collectionConfig['collection']) === '') {
        throw new InvalidArgumentException(
            "El campo 'collection' en '$moduleKey' de '$dbName' debe ser una cadena no vacía."
        );
    }

    // 'sort' — debe ser una matriz no vacía de pares campo => dirección (1 o -1)
    $sort = $collectionConfig['sort'];
    if (!is_array($sort) || empty($sort)) {
        throw new InvalidArgumentException(
            "El campo 'sort' en '$moduleKey' de '$dbName' debe ser una matriz no vacía"
        );
    }

    foreach ($sort as $field => $direction) {

        if (!is_string($field) || trim($field) === '') {
            throw new InvalidArgumentException(
                "Las claves en 'sort' para '$moduleKey' de '$dbName' deben ser cadenas no vacías."
            );
        }

        if ($direction !== 1 && $direction !== -1) {
            throw new InvalidArgumentException(
                "La dirección en 'sort' para '$field' en '$moduleKey' de '$dbName' debe ser 1 o -1."
            );
        }
    }

    // 'insert_fields' — matriz no vacía de cadenas no vacías
    
    $insertFields = $collectionConfig['insert_fields'];
    if (!is_array($insertFields) || empty($insertFields)) {
        throw new InvalidArgumentException(
            "'insert_fields' en '$moduleKey' de '$dbName' debe ser una matriz no vacía."
        );
    }

    // Validar que cada elemento en 'insert_fields' sea una cadena no vacía
    foreach ($insertFields as $i => $field) {
        if (!is_string($field) || trim($field) === '') {
            throw new InvalidArgumentException(
                "El elemento [$i] en 'insert_fields' para '$moduleKey' de '$dbName' debe ser una cadena no vacía."
            );
        }
    }

    // 'delete_field' — campo usado como filtro en DELETE, debe ser una cadena no vacía (normalmente '_id')
    $deleteField = $collectionConfig['delete_field'];
    if (!is_string($deleteField) || trim($deleteField) === '') {
        throw new InvalidArgumentException(
            "El campo 'delete_field' en '$moduleKey' de '$dbName' debe ser una cadena no vacía (normalmente '_id')."
        );
    }
}

/** 
 * Valida la clave secreta utilizada para firmar tokens JWT.
 *
 * Comprueba que la clave secreta proporcionada sea una cadena de al menos 32 caracteres para garantizar firmas JWT sólidas.
 *
 * @param string $secret La clave secreta utilizada para firmar tokens JWT, típicamente proporcionada en la configuración.
 * @return void
 *
 * @throws InvalidArgumentException Si alguna clave requerida está ausente, tiene el tipo incorrecto o contiene un valor inválido.
 */
function validateSecretTokenKey(string $secret): void {

    // Aplicar una longitud mínima de secreto para prevenir firmas débiles
    if (strlen($secret) < 32) {
        throw new InvalidArgumentException(
            'El secreto JWT debe tener al menos 32 caracteres de largo.'
        );
    }

}

?>