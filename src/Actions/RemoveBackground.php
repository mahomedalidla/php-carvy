<?php
use Iloveimg\Iloveimg;

// 1. Validar que realmente se envió un archivo y que no hubo error de subida
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió una imagen válida o el archivo excede el límite.']);
    exit;
}

// 2. Validar que sea realmente una imagen (Seguridad)
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$fileType = mime_content_type($_FILES['image']['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato no permitido. Solo JPG, PNG y WEBP.']);
    exit;
}

// 3. Configuración de llaves (Desde variables de entorno de Render)
$publicKey = getenv('ILOVE_PUBLIC_KEY');
$secretKey = getenv('ILOVE_SECRET_KEY');

if (!$publicKey || !$secretKey) {
    http_response_code(500);
    echo json_encode(['error' => 'API Keys no configuradas en el servidor.']);
    exit;
}

try {
    // Inicializar iLoveIMG
    $iloveimg = new Iloveimg($publicKey, $secretKey);
    
    // Crear la tarea específica
    $myTask = $iloveimg->newTask('removebackground');
    
    // Añadir el archivo temporal
    $file = $myTask->addFile($_FILES['image']['tmp_name']);
    
    // Ejecutar la tarea en los servidores de iLoveIMG
    $myTask->execute();
    
    // Definir ruta temporal para descargar el resultado
    $outputDir = __DIR__ . '/../../tmp';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }
    
    // Descargar el archivo procesado
    $myTask->download($outputDir);
    
    $processedFilePath = $outputDir . '/' . $myTask->getOutputFileName();
    
    // 4. Devolver la imagen al cliente
    if (file_exists($processedFilePath)) {
        header("Content-Type: image/png"); // iLoveIMG suele devolver PNG al quitar fondo
        header("Content-Length: " . filesize($processedFilePath));
        
        // Streaming del archivo para ahorrar RAM
        readfile($processedFilePath);
        
        // Limpiar: Borrar el archivo procesado después de enviarlo
        unlink($processedFilePath);
        exit;
    } else {
        throw new Exception("Error al recuperar el archivo procesado.");
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en el procesamiento de imagen',
        'details' => $e->getMessage()
    ]);
}