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
    
    // En producción (Railway) no existe .env — las vars vienen del sistema
    if (!file_exists($path)) return; // ← cambiar throw por return

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Cargar el .env automáticamente al incluir este archivo
loadEnv(__DIR__ . '/../../.env');

?>
