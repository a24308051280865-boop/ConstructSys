<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Dpendencias Internas y Externas
require_once __DIR__ . '/../../vendor/autoload.php';
require_once  __DIR__ . '/settings/headers.php';    
require_once __DIR__ . '/controllers/router.php';
require_once __DIR__ . '/settings/middleware.php';
require_once __DIR__ . '/settings/internal_controllers.php';


// El Router ya no usa la constante DATABASES — usa el registro dinámico
(new Router($DB_RECORD->getDatabases()))->dispatch();

?>
