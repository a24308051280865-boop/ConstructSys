<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Importar la función validadora de esquema del archivo de validadores
require_once __DIR__ . '/../utils/validators.php';

// Importar las constantes de conexión a la base de datos del archivo de constantes
require_once __DIR__ . '/../settings/constants.php';
 
/**
 * @psalm-type TableConfig = array{
 *     select: string,
 *     insert: string,
 *     insert_fields: list<string>,
 *     delete: string
 * }
 */

/**
 * Clase Database para gestionar conexiones y operaciones de base de datos.
 *
 * Esta clase proporciona métodos para conectarse a una base de datos MySQL usando PDO,
 * y para ejecutar operaciones comunes de base de datos.
 *
 * @category Database
 * @author Carlos Gabriel Magallanes López 
 * @since 2026-05-17 Versión inicial (Versión: 1.0)
 * @version 2026-05-17 Versión inicial (Versión: 1.0)
 */
class Database {

    /**
     * Dirección del servidor de base de datos.
     * @var string
     */
    public string $host;

    /**
     * Usuario para la conexión a la base de datos.
     * @var string
     */
    public string $user;
    
    /**
     * Contraseña para la conexión a la base de datos.
     * @var string
     */
    public string $password;
    
    /**
     * Nombre de la base de datos.
     * @var string
     */
    public string $name;
    
    /**
     * Instancia de PDO para la conexión a la base de datos.
     * @var PDO
     */
    private PDO $pdo;
   
    /**
     * Configuración para tablas de base de datos y sus correspondientes declaraciones SQL.
     * @var array
     */
    private array $tables;

    /**
     * Opciones adicionales para la instancia de PDO.
     * @var array
     */
    private array $options;

    /**
     * Constructor para la clase Database.
     *
     * Inicializa la conexión a la base de datos usando la configuración proporcionada y configura
     * las configuraciones de tabla.
     *
     * @param string $host Host del servidor de base de datos.
     * @param string $user Usuario de la base de datos.
     * @param string $password Contraseña de la base de datos.
     * @param string $name Nombre de la base de datos.
     * @param array<string, TableConfig> $tables Configuración para las tablas de la base de datos.
     * @param array $options Opciones adicionales para la instancia de PDO.
     * @return void
     */
    public function __construct(                                                                   
        string $name, 
        array $tables,
        string $host,                                   
        string $user, 
        string $password, 
        array $options = DEFAULT_PDO_OPTIONS
    ) {
        
        // Validar que los parámetros de conexión a la base de datos no estén vacíos
        if (empty($name)) throw new InvalidArgumentException("El nombre de la base de datos no puede estar vacío.");
        if (empty($host)) throw new InvalidArgumentException("El host de la base de datos no puede estar vacío.");
        if (empty($user)) throw new InvalidArgumentException("El usuario de la base de datos no puede estar vacío.");

        // Asignar los parámetros de conexión de la base de datos a las propiedades de la clase
        $this->host = $host;                                                                              
        $this->user = $user;
        $this->password = $password;
        $this->name = $name;
  
        // Validar el esquema de configuración de tabla proporcionado y asignarlo a la propiedad de la clase
        validateSchema($tables);
        $this->tables = $tables;
  
        // Validar el esquema de opciones de PDO proporcionado y asignarlo a la propiedad de la clase
        if ($options !== DEFAULT_PDO_OPTIONS) validatePDOOptions($options);
        $this->options = $options;
 
        // Conectar a la base de datos usando la configuración proporcionada
        $this->connect();

    }

    /**
     * Conecta a la base de datos.
     *
     * Este método establece una conexión a la base de datos usando la extensión PDO. 
     * Construye la cadena DSN basada en el host y el nombre de base de datos proporcionados,
     * e intenta crear una nueva instancia de PDO con el usuario, la contraseña y las opciones especificadas. 
     * Si la conexión falla, atrapa la excepción de PDO y devuelve una respuesta de error en formato JSON.
     * 
     * @return void
     */
    private function connect(): void {
     try {
         
         // Leer el puerto del entorno, default 3306 si no existe
         $port = getenv('DB_PORT') ?: '3306';
         
         // ✅ Con puerto incluido
         $dsn = "mysql:host={$this->host};port={$port};dbname={$this->name};charset=utf8mb4";
     
         $this->pdo = new PDO($dsn, $this->user, $this->password, $this->options);
     
     } catch (PDOException $error) {            
         http_response_code(500);            
         echo json_encode(["error" => "Conexión fallida: " . $error->getMessage()]);            
         exit();
     }
    }

    /**
     * Obtener la instancia de PDO para la conexión a la base de datos.
     *
     * @return PDO Devuelve la instancia de PDO para la conexión a la base de datos.
     */
    public function getConnection(): PDO {return $this->pdo;}

    /**
     * Maneja solicitudes GET para la tabla especificada.
     *
     * Este método verifica si la tabla solicitada está configurada para selección en la matriz de tablas.
     * Si la tabla no está configurada, devuelve una respuesta de solicitud incorrecta con un mensaje de error en formato JSON.
     * 
     * @param string $table Nombre de la tabla para la cual manejar la solicitud GET.
     * 
     * @return void
     */
    public function handleGet(string $table): void {
        
        // Obtener las configuraciones de tabla de la propiedad de la clase
        $sql = $this->tables[$table]['select'];

        // Validar que la tabla solicitada esté configurada en la matriz de tablas
        if (!isset($sql)) {            
            http_response_code(400);
            echo json_encode(["error" => "Tabla no configurada para SELECT: $table"]);            
            return;
        }

        // Ejecutar la consulta SELECT para la tabla solicitada y obtener los resultados
        $statement = $this->pdo->query($sql);
        $rows = $statement->fetchAll($this->options[PDO::ATTR_DEFAULT_FETCH_MODE]);
        
        // Mostrar las filas obtenidas en formato JSON para la respuesta de la API
        echo json_encode($rows);
    }

    /**
     * Maneja solicitudes POST para la tabla especificada.
     *
     * Este método verifica si la tabla solicitada está configurada para inserción en la matriz de tablas.
     * Si la tabla no está configurada, devuelve una respuesta de solicitud incorrecta con un mensaje de error en formato JSON.
     * 
     * @param string $table Nombre de la tabla para la cual manejar la solicitud POST.
     * 
     * @return void
     */
    public function handlePost(string $table): void {
        
        // Variable local para mantener las configuraciones de tabla de la propiedad de la clase
        $tableData = $this->tables[$table];
        $sql = $tableData['insert'];
        $fields = $tableData['insert_fields'];

        if (!isset($sql) || !isset($fields)) {           
            http_response_code(400);            
            echo json_encode(["error" => "Tabla no configurada para INSERT: $table"]);            
            return;
        }

        // Obtener y validar los datos de entrada del cuerpo de la solicitud, esperando que estén en formato JSON
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) return;
 
        // Definir los campos y valores para la tabla solicitada
        $values = [];
        $pdo = $this->pdo;
        
        // Agregar los valores en el orden correcto, usando nulo para los campos faltantes
        foreach ($fields as $field) $values[] = $data[$field] ?? null;
    
        // Preparar la declaración INSERT para la tabla solicitada
        $statement = $pdo->prepare($sql);
        
        try {
            
            // Ejecutar la declaración preparada con los valores proporcionados
            $statement->execute($values);
            
            // Mostrar un mensaje de éxito en formato JSON, incluyendo el ID del registro recién insertado
            echo json_encode(["success" => true, "id" => $pdo->lastInsertId()]);

        } catch (PDOException $error) {            
            http_response_code(500);            
            echo json_encode(["error" => $error->getMessage()]);
        }
    }

    /**
     * Maneja solicitudes DELETE para la tabla especificada.
     *
     * Este método verifica si la tabla solicitada está configurada para eliminación en la matriz de tablas.
     * Si la tabla no está configurada, devuelve una respuesta de solicitud incorrecta con un mensaje de error en formato JSON.
     * 
     * @param string $table Nombre de la tabla para la cual manejar la solicitud DELETE.
     * 
     * @return void
     */
    public function handleDelete(string $table): void {
    
        // Variable local para mantener las configuraciones de tabla de la propiedad de la clase
        $tables = $this->tables;

        // Validar que la tabla solicitada esté configurada para eliminación en la matriz de tablas
        if (!isset($tables[$table]['delete'])) {            
            http_response_code(400);            
            echo json_encode(["error" => "Tabla no configurada para DELETE: $table"]);            
            return;
        }
        
        // Obtener el ID de los parámetros de consulta y validarlo, esperando que sea un entero positivo
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // Validar que el ID sea un entero positivo, de lo contrario, devolver una respuesta de solicitud incorrecta con un mensaje de error en formato JSON
        if ($id <= 0) {            
            http_response_code(400);        
            echo json_encode(["error" => "ID inválido"]);        
            return;
        }
        
        // Preparar la declaración DELETE para la tabla solicitada
        $statementt = $this->pdo->prepare($tables[$table]['delete']);
        
        try {
        
            // Ejecutar la declaración preparada con el ID proporcionado
            $statementt->execute([$id]);
            
            // Mostrar un mensaje de éxito en formato JSON indicando que el registro fue eliminado correctamente
            echo json_encode(["success" => true]);
        
        } catch (PDOException $error) {            
            http_response_code(500);            
            echo json_encode(["error" => $error->getMessage()]);
        }
    }

    /**
     * Maneja solicitudes PUT para la tabla especificada.
     *
     * Lee el ID del query string, los campos del body JSON,
     * y ejecuta un UPDATE con los campos configurados.
     *
     * @param string $table Nombre de la tabla para la cual manejar la solicitud PUT.
     * @return void
     */
    public function handleUpdate(string $table): void {

        // Obtener las configuraciones de tabla de la propiedad de la clase
        $tableData = $this->tables[$table];
        $sql = $tableData['update'];
        $fields = $tableData['update_fields'];

        // Validar que la tabla esté configurada para UPDATE
        if (!isset($sql) || !isset($fields)) {
            http_response_code(400);
            echo json_encode(["error" => "Tabla no configurada para UPDATE: $table"]);
            return;
        }

        // Obtener y validar el ID del query string
        $getId = $_GET['id'];
        $id = isset($getId) ? intval($getId) : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "ID inválido o faltante"]);
            return;
        }

        // Leer y validar el body JSON
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data) || empty($data)) {
            http_response_code(400);
            echo json_encode(["error" => "Body JSON inválido o vacío"]);
            return;
        }

        // Definir los valores para la tabla solicitada, mapeando los campos en el orden correcto
        $values = [];

        // Mapear los valores en el orden correcto — null si el campo no viene en el body
        foreach ($fields as $field) $values[] = $data[$field] ?? null;

        // El ID va al final del array (corresponde al ? del WHERE)
        $values[] = $id;

        // Preparar la declaración UPDATE para la tabla solicitada
        $statement = $this->pdo->prepare($sql);

        try {

            // Ejecutar la declaración preparada con los valores proporcionados
            $statement->execute($values);

            // rowCount() = 0 si el ID no existe
            if ($statement->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["error" => "Registro no encontrado: ID $id"]);
                return;
            }

            // Mostrar un mensaje de éxito en formato JSON indicando que el registro fue actualizado correctamente
            echo json_encode(["success" => true, "updated" => $id]);

        } catch (PDOException $error) {
            http_response_code(500);
            echo json_encode(["error" => $error->getMessage()]);
        }
    }

}

?>
