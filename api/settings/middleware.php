<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

/**
 * Verifica que la request venga de tu propio frontend.
 *
 * Llama esto al inicio de cada endpoint PHP.
 * 
 * @return void 
 */
function requireInternalKey(): void {

    // Comparar la clave interna esperada con el valor del encabezado HTTP 'X-Internal-Key' de la solicitud
    if (!hash_equals(INTERNAL_API_KEY, $_SERVER['HTTP_X_INTERNAL_KEY'] ?? '')) {
        
        // Responder con 403 Prohibido si la clave interna no coincide o falta
        http_response_code(403);
        echo json_encode(['error' => 'Acceso prohibido: clave interna inválida.']);
        exit;
    }
}

/**
 * Rate limiting simple usando archivos temporales.
 * Para producción real usa Redis, pero esto funciona bien en la mayoría de casos.
 *
 * @param int $maxAttempts  Intentos máximos permitidos.
 * @param int $windowSeconds Ventana de tiempo en segundos.
 */
function rateLimitByIp(int $maxAttempts = 10, int $windowSeconds = 60): void {

    // Obtener la dirección IP del cliente
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    /**
     * Si no existe IP válida generar una clave alternativa.
     * Esto evita que múltiples "unknown" compartan el mismo archivo.
     */
    if ($ip === null || $ip === '') {
        $fallback = ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(as_float: true));
        $key = hash('sha256', $fallback);

    } 
    else $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $ip);

    // Generar archivo para almacenar los intentos
    $file = sys_get_temp_dir() . "/rl_$key.json";
    $now = time();

    /**
     * Abrir archivo en modo c+
     * - Lo crea si no existe
     * - Permite lectura y escritura
     */
    $openedFile = fopen($file, 'c+');

    // Si no se pudo abrir el archivo salir silenciosamente
    if ($openedFile === false) return;
    
    /**
     * Bloquear el archivo para evitar condiciones de carrera.
     * Esto impide sobrescrituras simultáneas.
     */
    flock($openedFile, LOCK_EX);

    // Leer el estado actual de intentos para esta IP
    $contents = stream_get_contents($openedFile);

    $data = $contents ? (json_decode($contents, associative: true) ?? []) : [];

    // Si no existe estructura válida inicializar datos
    if (!isset($data['count']) || !isset($data['window_start'])) {
        $data = ['count' => 0, 'window_start' => $now];
    }

    // Si la ventana de tiempo expiró, reiniciar el contador
    if ($now - $data['window_start'] > $windowSeconds) {
        $data = ['count' => 0, 'window_start' => $now];
    }

    // Incrementar el contador de intentos
    $data['count']++;

    // Limpiar archivo antes de escribir el nuevo contenido
    ftruncate($openedFile, 0);

    // Posicionar el puntero al inicio del archivo para escribir desde el principio
    rewind($openedFile);

    // Guardar el estado actualizado
    fwrite($openedFile, json_encode($data));

    // Liberar bloqueo y cerrar archivo
    flock($openedFile, LOCK_UN);
    fclose($openedFile);

    // Si se exceden los intentos permitidos, responder con 429 Too Many Requests
    if ($data['count'] > $maxAttempts) {

        // Obtener el tiempo restante para que la ventana de tiempo expire
        $retryAfter = $windowSeconds - ($now - $data['window_start']);

        // Responder con código de estado 429
        http_response_code(429);

        // Incluir el encabezado 'Retry-After' para indicar al cliente cuándo puede volver a intentar
        header("Retry-After: $retryAfter");
        echo json_encode([
            'error' => 'Muchas solicitudes, inténtalo de nuevo más tarde.',
            'retry_after_seconds' => $retryAfter
        ]);
        exit;
    }
}

?>