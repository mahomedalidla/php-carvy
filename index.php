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

    case '/health': // Para que Render verifique que la API está viva
        echo json_encode(["status" => "ok"]);
        break;

    case '/remove-background':
        if ($method === 'POST') {
            // Aquí mandamos a llamar al archivo que ya escribimos
            require __DIR__ . '/src/Actions/RemovebackgroundImageTask.php';
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido. Usa POST."]);
        }
        break;

    case '/crop-image':
        if ($method === 'POST') {
            require __DIR__ . '/src/Actions/CropImageTask.php';
            $myTask = new \Iloveimg\CropImageTask('project_public_622e5f6e6864ca2d345b0fd8833f01a5_-Dy8L6ba4fe3787ebd9bbf72206272e60a6e7', 'secret_key_f2b00ae5e30972ec595408149c37b22e_mFUNDf4eba3dd01c59ce1c120ddea1f29ae99');

            $fileTmpName = null;
            $originalName = null;

            if (isset($_FILES['file']['tmp_name']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
                $fileTmpName = $_FILES['file']['tmp_name'];
                $originalName = $_FILES['file']['name'];
            } elseif (isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $fileTmpName = $_FILES['image']['tmp_name'];
                $originalName = $_FILES['image']['name'];
            }

            if ($fileTmpName === null) {
                http_response_code(400);
                echo json_encode(["error" => "No file uploaded or an error occurred during upload. Please use the 'file' or 'image' key."]);
                break;
            }
            
            $tempDir = 'temp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempPath = $tempDir . basename($originalName);

            if (!move_uploaded_file($fileTmpName, $tempPath)) {
                http_response_code(500);
                echo json_encode(["error" => "Failed to move uploaded file."]);
                break;
            }

            // Get the uploaded file
            $file = $myTask->addFile($tempPath);

            // Set crop parameters from the request
            $myTask->setCropWidth($_POST['crop_width']);
            $myTask->setCropHeight($_POST['crop_height']);
            $myTask->setCropX($_POST['crop_x']);
            $myTask->setCropY($_POST['crop_y']);

            // Process files
            $myTask->execute();

            // And download said file
            $myTask->download('temp');
            
            // Clean up the temporary file
            unlink($tempPath);

            echo json_encode(["status" => "ok", "message" => "Image cropped successfully"]);
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido. Usa POST."]);
        }
        break;

    case '/test':
    echo json_encode(["message" => "El enrutador funciona correctamente"]);
    break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;
}