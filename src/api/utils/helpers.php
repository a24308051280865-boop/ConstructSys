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
    // Primero $_ENV, luego getenv() — Railway usa getenv()
    return $_ENV[$key] ?? getenv($key) ?: '';
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
        CURLOPT_TIMEOUT => 10,         
        CURLOPT_CONNECTTIMEOUT => 5,
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
        CURLOPT_TIMEOUT => 10,        
        CURLOPT_CONNECTTIMEOUT => 5,
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
    header('Location: https://constructsys-production.up.railway.app?auth_error=' . urlencode($msg));
    exit;
}

/**
 * Envía un correo usando la API REST de Brevo vía cURL.
 *
 * @param string $to Correo del destinatario.
 * @param string $subject Asunto del correo.
 * @param string $html Cuerpo HTML del correo.
 * @return bool true si Brevo aceptó el envío (HTTP 201).
 */
function sendEmail(string $to, string $subject, string $html): bool {

    // Construir el payload según la documentación de Brevo
    $payload = [
        'sender' => ['email' => BREVO_FROM_EMAIL, 'name' => BREVO_FROM_NAME],
        'to' => [['email' => $to]],
        'subject' => $subject,
        'htmlContent' => $html,
    ];

    // Inicializar cURL para enviar el correo a través de la API de Brevo
    $curlHandle = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($curlHandle, [
        CURLOPT_POST => true,                                                                      // Indica que es una solicitud POST
        CURLOPT_POSTFIELDS  => json_encode($payload),                                              // Convertir el payload a JSON
        CURLOPT_RETURNTRANSFER => true,                                                            // Devuelve la respuesta como string en lugar de imprimirla
        CURLOPT_SSL_VERIFYPEER => false,                                                           // Necesario en XAMPP local, pero no recomendado en producción
        CURLOPT_HTTPHEADER => [
            'api-key: ' . BREVO_API_KEY, 
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    curl_exec($curlHandle);
    $status = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
    curl_close($curlHandle);

    // Brevo devuelve HTTP 201 Created si el correo fue aceptado para envío
    return $status === 201;   

}

?>
