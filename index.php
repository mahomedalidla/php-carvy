<?php
// index.php
require 'vendor/autoload.php';

// Capturar la ruta
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Autoload simple para nuestras clases de Action
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require $file;
});

// Enrutador
switch ($path) {
    case '/remove-background':
        if ($method === 'POST') {
            include 'src/Actions/RemoveBackground.php';
        }
        break;

    case '/health': // Para que Render verifique que la API está viva
        echo json_encode(["status" => "ok"]);
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;
}