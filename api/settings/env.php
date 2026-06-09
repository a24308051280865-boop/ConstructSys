<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

/**
 * Cargar variables de entorno desde un archivo .env.
 * 
 * Lee un archivo .env línea por línea, ignora comentarios y líneas vacías, 
 * y establece las variables de entorno en $_ENV y putenv() si no están ya definidas en el sistema.
 * 
 * @param string $path La ruta al archivo .env que se desea cargar.
 * 
 * @return void
 * 
 * @throws RuntimeException Si el archivo .env no se encuentra en la ruta especificada.
 */
function loadEnv(string $path): void {
    
    // Verificar que el archivo .env existe en la ruta especificada
    if (!file_exists($path)) throw new RuntimeException("Archivo .env no encontrado en: $path");

    // Leer el archivo .env línea por línea, ignorando líneas vacías y comentarios
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Procesar cada línea del archivo .env
    foreach ($lines as $line) {
        
        // Ignorar comentarios
        if (str_starts_with(trim($line), '#')) continue;

        // Dividir la línea en clave y valor usando el primer signo '=' como separador
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Solo establecer si no existe ya (las del sistema tienen prioridad)
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Cargar el .env automáticamente al incluir este archivo
loadEnv(__DIR__ . '/../../.env');

?>