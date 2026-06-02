<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Importar la función validadora de esquema del archivo de validadores
require_once __DIR__ . '/../utils/validators.php';

// Controlador de MongoDB para manejar solicitudes de API
require_once __DIR__ . '/mongodb.php';

// Controlador de base de datos para manejar solicitudes de API
require_once __DIR__ . '/database.php';

/**
 * Clase Router responsable de enviar solicitudes HTTP entrantes a los controladores de base de datos apropiados
 * 
 * La clase Router lee el parámetro de consulta 'module' de la solicitud HTTP entrante, determina qué base de datos y controlador es propietario de ese módulo, y envía la solicitud al controlador correcto basado en el método HTTP (GET, POST, DELETE). También maneja respuestas de error para parámetros faltantes, módulos no registrados, controladores no compatibles y métodos HTTP no compatibles.
 * 
 * @category Router
 * @author Carlos Gabriel Magallanes López 
 * @since 2026-05-17 Versión inicial (Versión: 1.0)
 * @version 2026-05-17 Versión inicial (Versión: 1.0)
 */
class Router {

    /**
     * Matriz para almacenar las configuraciones de base de datos y esquemas de tablas.
     */
    private array $databases;
 
    /**
     * Constructor para la clase Router.
     *
     * Inicializa las configuraciones de base de datos y las valida
     * usando la función de validación proporcionada.
     *
     * @param array $databases Una matriz asociativa que contiene las configuraciones de base de datos y esquemas de tablas.
     * @return void
     */
    public function __construct(array $databases) {
        
        // Validar las configuraciones de base de datos antes de almacenarlas
        validateDatabase($databases);
        $this->databases = $databases;
    
    }

    /**
     * Envía la solicitud HTTP entrante al controlador de base de datos correcto.
     *
     * Lee el parámetro de consulta 'module', resuelve qué base de datos y controlador
     * es propietario de ese módulo, instancia la clase correcta (Database o MongoDatabase),
     * y llama al método de manejo correspondiente.
     *
     * @return void
     */
    public function dispatch(): void {

        // Leer el parámetro de módulo de la cadena de consulta
        $module = trim($_GET['module'] ?? '');

        // Si falta el parámetro del módulo, responder con una solicitud 400 incorrecta
        if ($module === '') {
            $this->respond(400, ['error' => 'El parámetro module falta']);
            return;
        }

        // Resolver la configuración de la base de datos y el controlador del módulo solicitado
        [$dbConfig, $driver] = $this->resolve($module);

        // Si ninguna base de datos es propietaria del módulo solicitado, responder con 404 No encontrado
        if ($dbConfig === null) {
            $this->respond(404, ['error' => "Módulo no registrado: '$module'"]);
            return;
        }

        // Instanciar la clase de base de datos correcta basada en el controlador
        $db = match ($driver) {
            'mysql' => new Database(
                name: $dbConfig['name'],
                tables: $dbConfig['tables'],
                host: $dbConfig['host'],
                user: $dbConfig['user'],
                password: $dbConfig['password'],
                options: $dbConfig['options'],
            ),
            'mongodb' => new MongoDatabase(
                uri: $dbConfig['uri'],
                name: $dbConfig['name'],
                collections: $dbConfig['collections'],
            ),
            default => null
        };

        // Si el controlador no es compatible, responder con 500 Error interno del servidor
        if ($db === null) {
            $this->respond(500, ['error' => "Controlador no compatible: '$driver'"]);
            return;
        }

        // Enviar la solicitud al controlador correspondiente en función del método HTTP
        match ($_SERVER['REQUEST_METHOD']) {
            'GET' => $db->handleGet($module),
            'POST' => $db->handlePost($module),
            'DELETE' => $db->handleDelete($module),
            'PUT' => $db->handleUpdate($module),
            default => $this->respond(405, ['error' => 'Método no permitido'])
        };
    }

    /**
     * Resuelve la configuración de la base de datos y el controlador para un módulo determinado.
     *
     * Busca en todas las bases de datos verificando 'tables' para entradas mysql
     * y 'collections' para entradas mongodb, devolviendo la primera coincidencia.
     *
     * @param string $module Nombre del módulo de la cadena de consulta.
     * @return array{0: array|null, 1: string|null} [dbConfig, driver] o [null, null] si no se encuentra.
     */
    private function resolve(string $module): array {

        foreach ($this->databases as $dbConfig) {

            // Determinar el grupo de módulos basado en el controlador
            $driver = $dbConfig['driver'];
            $modulePool = $driver === 'mysql' ? 'tables' : 'collections';

            // Devolver la configuración y el controlador si se encuentra el módulo en el grupo
            if (array_key_exists($module, $dbConfig[$modulePool])) return [$dbConfig, $driver];
        }

        // Ninguna base de datos es propietaria del módulo solicitado
        return [null, null];
    }


    /**
     * Envía una respuesta HTTP.
     *
     * Envía una respuesta HTTP con el código de estado especificado y el cuerpo, codificando el cuerpo como JSON.
     *
     * @param int $code El código de estado HTTP a enviar en la respuesta.
     * @param array $body El cuerpo de la respuesta, que se codificará como JSON.
     * 
     * @return void
     */ 
    private function respond(int $code, array $body): void {
        http_response_code($code);
        echo json_encode($body);
    }

}

?>