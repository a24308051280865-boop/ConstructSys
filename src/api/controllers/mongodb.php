<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Dependencias Externas
require_once __DIR__ . '/../../../vendor/autoload.php';


// Cliente de MongoDB e ID de BSON
use MongoDB\Client as MongoClient;
use MongoDB\Collection as MongoCollection;
use MongoDB\BSON\ObjectId as MongoObjectId;
use MongoDB\Driver\Exception\Exception as MongoException;

/**
 * @psalm-type CollectionConfig = array{
 *     collection: string,
 *     sort: array<string, int>,
 *     insert_fields: list<string>,
 *     delete_field: string
 * }
 */

/**
 * Clase MongoDatabase para gestionar conexiones y operaciones de MongoDB.
 *
 * Esta clase proporciona métodos para conectarse a una base de datos MongoDB usando
 * la librería oficial de PHP de MongoDB, y para ejecutar operaciones CRUD comunes.
 * Refleja la interfaz de la clase Database (PDO/MySQL) para mayor consistencia.
 *
 * @category   Database
 * @author     Carlos Gabriel Magallanes López
 * @license    https://opensource.org/licenses/MIT MIT License
 * @since      2026-05-18 Versión inicial (Versión: 1.0)
 * @version    2026-05-18 Versión inicial (Versión: 1.0)
 *
 * @see https://www.mongodb.com/docs/php-library/current/
 * @requires   ext-mongodb
 * @requires   mongodb/mongodb (composer require mongodb/mongodb)
 */
class MongoDatabase {

    /**
     * Identificador uniforme de recurso de conexión de MongoDB.
     * @var string
     */
    public string $uri;

    /**
     * Nombre de la base de datos de MongoDB.
     * @var string
     */
    public string $name;

    /**
     * Instancia del cliente de MongoDB.
     * @var MongoClient
     */
    private MongoClient $client;

    /**
     * Configuración para colecciones de MongoDB.
     * @var array<string, CollectionConfig>
     */
    private array $collections;

    
    /**
     * Constructor para la clase MongoDatabase.
     *
     * Inicializa la conexión a MongoDB usando el URI y el nombre de base de datos proporcionados,
     * y configura las configuraciones de colección.
     *
     * @param string $uri URI de conexión de MongoDB (p. ej., mongodb://localhost:27017).
     * @param string $name Nombre de la base de datos de MongoDB.
     * @param array  $collections Configuración para las colecciones de MongoDB.
     * @return void
     */
    public function __construct(string $uri, string $name, array $collections) {

        // Validar el identificador uniforme de recurso de conexión y el nombre de la base de datos
        if (empty($name)) {
            throw new InvalidArgumentException("El nombre de la base de datos no puede estar vacío.");
        }

        // Validar la configuración de colecciones usando la función de utilidad
        validateMongoEntry($name, ["uri" => $uri, "collections" => $collections]);

        // Asignar los parámetros de conexión a las propiedades de la clase
        $this->uri = $uri;
        $this->name = $name;
        $this->collections = $collections;

        // Establecer la conexión a MongoDB
        $this->connect();
    }

    /**
     * Se conecta al servidor de MongoDB.
     *
     * Instancia un MongoDB\\Client usando el URI proporcionado. Si la conexión
     * falla, devuelve una respuesta 500 en formato JSON y detiene la ejecución.
     *
     * @return void
     */
    private function connect(): void {
        try {

            // Crear una nueva instancia del cliente de MongoDB con el URI proporcionado
            $this->client = new MongoClient($this->uri);

        } catch (MongoException $error) {
            http_response_code(500);
            echo json_encode(["error" => "Fallo al conectar a MongoDB: " . $error->getMessage()]);
            exit();
        }
    }


    /**
     * Obtener el Cliente de MongoDB para esta base de datos.
     *
     * @return MongoClient La instancia del cliente de MongoDB.
     */
    public function getClient(): MongoClient {return $this->client;}

    /**
     * Devuelve una instancia de colecciono de MongoDB para el nombre de colección dado.
     *
     * Selecciona la base de datos y la colección basadas en el nombre de colección proporcionado.
     * Si la colección no existe, MongoDB la creará en la primera inserción.
     * 
     * @param string $collectionName Nombre real de la colección de MongoDB.
     * @return MongoCollection La instancia de la colección de MongoDB.
     */
    public function getCollection(string $collectionName): MongoCollection {
        return $this->client->selectDatabase($this->name)->selectCollection($collectionName);
    }

    /**
     * Maneja solicitudes GET para la colección especificada.
     *
     * Obtiene todos los documentos de la colección, aplicando el orden de clasificación configurado.
     * Convierte campos ObjectId a cadenas para compatibilidad con JSON.
     *
     * @param string $module Nombre del módulo como se define en la configuración de colecciones.
     * @return void
     */
    public function handleGet(string $module): void {

        // Validar que el módulo esté configurado para operaciones de búsqueda
        $collections = $this->collections;
        if (!isset($collections[$module])) {
            http_response_code(400);
            echo json_encode(["error" => "Módulo no configurado para GET: $module"]);
            return;
        }

        // Recuperar la configuración de colección para el módulo solicitado
        $config = $collections[$module];
        $collection = $this->getCollection($config['collection']);

        // Ejecutar la operación de búsqueda con el orden de clasificación configurado
        $cursor = $collection->find(
            filter: [],
            options: [
                'sort' => $config['sort'],
                'typeMap' => TYPE_MAP,
            ]
        );

        // Convertir documentos a matriz y serializar ObjectId a cadena
        $rows = array_map(
            fn(array $doc) => $this->serializeDocument($doc),
            iterator_to_array($cursor)
        );

        // Mostrar los documentos obtenidos en formato JSON para la respuesta de la API
        echo json_encode(array_values($rows));
    }

    /**
     * Maneja solicitudes POST para la colección especificada.
     *
     * Lee JSON del cuerpo de la solicitud, asigna los insert_fields configurados,
     * e inserta un nuevo documento en la colección.
     *
     * @param string $module Nombre del módulo como se define en la configuración de colecciones.
     * @return void
     */
    public function handlePost(string $module): void {

        // Validar que el módulo esté configurado para operaciones de inserción
        $collections = $this->collections;
        if (!isset($collections[$module])) {
            http_response_code(400);
            echo json_encode(["error" => "El módulo no está configurado para POST: $module"]);
            return;
        }

        // Decodificar el cuerpo de solicitud JSON en una matriz asociativa
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) return;

        // Recuperar la configuración de colección para el módulo solicitado
        $config = $collections[$module];
        $fields = $config['insert_fields'];

        // Construir el documento usando solo los campos configurados, nulo para los que faltan
        $document = [];
        foreach ($fields as $field) $document[$field] = $data[$field] ?? null;

        try {

            // Insertar el documento en la colección de MongoDB
            $result = $this->getCollection($config['collection'])->insertOne($document);

            // Mostrar un mensaje de éxito incluyendo el ObjectId del nuevo documento como cadena
            echo json_encode([
                "success" => true,
                "id" => (string) $result->getInsertedId()
            ]);

        } catch (MongoException $error) {
            http_response_code(500);
            echo json_encode(["error" => $error->getMessage()]);
        }
    }

    /**
     * Maneja solicitudes DELETE para la colección especificada.
     *
     * Lee el ID del documento de la cadena de consulta (?id=...), lo convierte en
     * un ObjectId de MongoDB y elimina el documento coincidente.
     *
     * @param string $module Nombre del módulo como se define en la configuración de colecciones.
     * @return void
     */
    public function handleDelete(string $module): void {

        // Validar que el módulo esté configurado para operaciones de eliminación
        $collections = $this->collections;
        if (!isset($collections[$module])) {
            http_response_code(400);
            echo json_encode(["error" => "El módulo no está configurado para DELETE: $module"]);
            return;
        }

        // Recuperar y validar el ID del documento de la cadena de consulta
        $rawId = trim($_GET['id'] ?? '');
        if ($rawId === '') {
            http_response_code(400);
            echo json_encode(["error" => "ID inválido o faltante"]);
            return;
        }

        try {

            // Convertir el ID de cadena a un ObjectId de MongoDB para el filtro de eliminación
            $objectId = new MongoObjectId($rawId);
            $config = $collections[$module];
            $deleteField = $config['delete_field'];

            // Ejecutar la operación de eliminación contra el campo de eliminación configurado
            $this->getCollection($config['collection'])->deleteOne([$deleteField => $objectId]);

            // Mostrar un mensaje de éxito en formato JSON
            echo json_encode(["success" => true]);

        } catch (InvalidArgumentException $error) {
            http_response_code(400);
            echo json_encode(["error" => "Formato de ID inválido: " . $error->getMessage()]);

        } catch (MongoException $error) {
            http_response_code(500);
            echo json_encode(["error" => $error->getMessage()]);
        }
    }

    /**
     * Serializa un documento BSON en una matriz PHP segura para JSON.
     *
     * Convierte instancias de ObjectId y cualquier tipo BSON anidado a su
     * representación de cadena para que json_encode() pueda manejarlos correctamente.
     *
     * @param  array $document Matriz asociativa que representa un documento de MongoDB.
     * @return array           Matriz asociativa segura para JSON.
     */
    private function serializeDocument(array $document): array {
        foreach ($document as $key => $value) {
            $document[$key] = match (true) {
                $value instanceof MongoObjectId => (string) $value,
                is_array($value) => $this->serializeDocument($value),
                default => $value
            };
        }
        return $document;
    }
  
    /**
     * Manejar solicitudes PUT para la colección especificada.
     *
     * Lee el ID del documento de la cadena de consulta (?id=...), lo convierte en
     * un ObjectId de MongoDB, y actualiza los campos configurados con los valores del cuerpo JSON.
     *
     * @param string $module Nombre del módulo como se define en la configuración de colecciones.
     * @return void
     */
    public function handleUpdate(string $module): void {

        // Validar que el módulo esté configurado para operaciones de actualización
        $config = $this->collections[$module];
        if (!isset($config)) {
            http_response_code(400);
            echo json_encode(['error' => 'ERR_MODULE_NOT_CONFIGURED']);
            return;
        }

        // Recuperar y validar el ID del documento de la cadena de consulta
        $rawId = trim($_GET['id'] ?? '');
        if ($rawId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'ERR_INVALID_ID']);
            return;
        }

        // Decodificar el cuerpo de solicitud JSON en una matriz asociativa
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'ERR_INVALID_BODY']);
            return;
        }

        // Recuperar los campos configurados para actualización y el campo de eliminación
        $fields  = $config['update_fields'];
        $deleteField = $config['delete_field']; 

        // Construir el documento $set solo con los campos configurados
        $set = [];
        foreach ($fields as $field) if (isset($data[$field])) $set[$field] = $data[$field];
        
        // Validar que haya al menos un campo para actualizar
        if (empty($set)) {
            http_response_code(400);
            echo json_encode(['error' => 'ERR_NO_FIELDS_TO_UPDATE']);
            return;
        }

        // Intentar convertir el ID a ObjectId y ejecutar la operación de actualización
        try {

            // Convertir el ID de cadena a un ObjectId de MongoDB para el filtro de actualización
            $objectId = new MongoObjectId($rawId);
            $result = $this->getCollection($config['collection'])->updateOne(
                [$deleteField => $objectId],
                ['$set' => $set]
            );

            // Verificar si se encontró un documento para actualizar
            if ($result->getMatchedCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'ERR_NOT_FOUND']);
                return;
            }

            // Mostrar un mensaje de éxito en formato JSON
            echo json_encode(['success' => true, 'updated' => $rawId]);

        } catch (InvalidArgumentException) {
            http_response_code(400);
            echo json_encode(['error' => 'ERR_INVALID_ID_FORMAT']);
        } catch (MongoException $error) {
            http_response_code(500);
            echo json_encode(['error' => 'ERR_MONGO_GENERIC', 'detail' => $error->getMessage()]);
        }
    }

}

?>
