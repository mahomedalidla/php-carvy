<?php
// Establece el header para la respuesta JSON
header('Content-Type: application/json');

// Incluye el autoloader de Composer, que ahora también carga phpdotenv
require_once __DIR__ . '/vendor/autoload.php';

// Carga las variables de entorno desde el archivo .env (si existe)
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// --- Configuración y Verificación de Claves ---
$publicKey = $_ENV['ILOVEIMG_PUBLIC_KEY'] ?? null;
$secretKey = $_ENV['ILOVEIMG_SECRET_KEY'] ?? null;

if (!$publicKey || !$secretKey) {
    http_response_code(503); // Service Unavailable
    echo json_encode([
        'error' => 'Las claves de la API no están configuradas en el servidor.',
        'details' => 'Asegúrate de haber añadido las variables de entorno ILOVEIMG_PUBLIC_KEY y ILOVEIMG_SECRET_KEY en el panel de Render.com.'
    ]);
    exit;
}

$outputDir = __DIR__ . '/output';

// --- Lógica de la API ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Esta API solo acepta peticiones POST.']);
    exit;
}

if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'error' => 'No se ha subido ningún archivo o ha ocurrido un error.',
        'field_name' => 'Asegúrate de enviar el archivo en un campo form-data llamado "image_file".'
    ]);
    exit;
}

// Crear el directorio de salida si no existe
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// 1. Crear una ruta temporal segura CON la extensión del archivo original.
$tmpFilePath = $_FILES['image_file']['tmp_name'];
$originalFilename = basename($_FILES['image_file']['name']); // Sanitizar nombre
$newTempPathWithExtension = $outputDir . '/' . uniqid('temp_processing_') . '_' . $originalFilename;

// 2. Mover el archivo subido a esta nueva ruta.
if (!move_uploaded_file($tmpFilePath, $newTempPathWithExtension)) {
    http_response_code(500);
    echo json_encode(['error' => 'Falló al mover el archivo subido a una ruta temporal. Verifica los permisos del directorio.']);
    exit;
}

try {
    // 3. Ejecutar el proceso de iLoveIMG usando la nueva ruta
    $iloveimg = new Iloveimg\Iloveimg($publicKey, $secretKey);
    
    $removeBgTask = $iloveimg->newTask('removebackground');
    $removeBgTask->addFile($newTempPathWithExtension);
    $removeBgTask->execute();

    $compressTask = $removeBgTask->next('compress');
    $compressTask->setCompressionLevel('recommended');
    $compressTask->execute();

    $downloadPath = $outputDir . '/';
    $compressTask->download($downloadPath);
    
    $outputFilename = $compressTask->outputFileName;

    // 4. Devolver una respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Imagen procesada y guardada con éxito.',
        'output_file' => 'output/' . $outputFilename
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Ocurrió un error durante el procesamiento con la API de iLoveIMG.',
        'details' => $e->getMessage()
    ]);
} finally {
    // 5. Limpiar: borrar el archivo temporal que creamos.
    if (file_exists($newTempPathWithExtension)) {
        unlink($newTempPathWithExtension);
    }
}
