<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

/**
 * Obtener el valor de una variable de entorno de forma segura.
 * 
 * Lee una variable de entorno y falla explícitamente si no existe.
 * Evita silenciar secretos faltantes en producción.
 * 
 * @param string $key El nombre de la variable de entorno a obtener.
 * 
 * @return string El valor de la variable de entorno solicitada.
 * 
 * @throws RuntimeException Si la variable de entorno no está definida o es una cadena vacía.
 */
function getEnvValue(string $key): string {
    
    // Primero intenta obtener la variable de entorno desde $_ENV, luego desde getenv()
    $value = $_ENV[$key] ?? getenv($key);
    
    // Si el valor es false o una cadena vacía, lanzamos una excepción
    if ($value === false) {
        throw new RuntimeException("Missing required environment variable: $key");
    }
    
    // Devolvemos el valor como cadena
    return (string) $value;
}

/**
 * Realiza una solicitud HTTP POST usando cURL.
 * 
 * Configura cURL para enviar datos como application/x-www-form-urlencoded, lo cual es común para APIs de autenticación.
 * 
 * @param string $url La URL a la que se hará la solicitud POST.
 * @param array $httpQueryData Un array asociativo de datos que se enviarán como cuerpo de la solicitud POST.
 * 
 * @return array El valor de la variable de entorno solicitada como array.
 */
function curlPost(string $url, array $httpQueryData): array {
    
    // Inicializar cURL
    $curlHandle = curl_init($url);
    
    // Configurar opciones de cURL para una solicitud POST con datos form-urlencoded
    curl_setopt_array($curlHandle, [
        CURLOPT_RETURNTRANSFER => true,                                                            // Devuelve la respuesta como string en lugar de imprimirla
        CURLOPT_POST => true,                                                                      // Indica que es una solicitud POST
        CURLOPT_POSTFIELDS => http_build_query($httpQueryData),                                    // Convierte el array de datos a formato application/x-www-form-urlencoded
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],                 // Establece el encabezado de tipo de contenido
        CURLOPT_SSL_VERIFYPEER => false,                                                           // Necesario en XAMPP local, pero no recomendado en producción   
    ]);
    
    // Ejecutar la solicitud y obtener la respuesta
    $response = curl_exec($curlHandle);
    
    // Cerrar el recurso cURL
    curl_close($curlHandle);

    // Decodificar la respuesta JSON a un array asociativo y devolverlo
    return json_decode((string) $response, true) ?? [];
}

/**
 * Realiza una solicitud HTTP GET usando cURL.
 * 
 * Configura cURL para realizar una solicitud GET.
 * 
 * @param string $url La URL a la que se hará la solicitud GET.
 * 
 * @return array El valor de la variable de entorno solicitada como array.
 */
function curlGet(string $url): array {
    
    // Inicializar cURL
    $curlHandle = curl_init($url);
    
    // Configurar opciones de cURL para una solicitud GET
    curl_setopt_array($curlHandle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,  
    ]);

    // Ejecutar la solicitud y obtener la respuesta
    $response = curl_exec($curlHandle);
    
    // Cerrar el recurso cURL
    curl_close($curlHandle);
    
    // Decodificar la respuesta JSON a un array asociativo y devolverlo
    return json_decode((string) $response, true) ?? [];
}

/**
 * Redirige al usuario a la página de error con un mensaje específico.
 * 
 * @param string $msg El mensaje de error específico que se mostrará al usuario.
 * 
 * @return never Redirige al usuario a la página de error con un mensaje específico.
 */
function redirectWithError(string $msg): never {
    header('Location: http://localhost/ConstructIndustry/?auth_error=' . urlencode($msg));
    exit;
}

?>