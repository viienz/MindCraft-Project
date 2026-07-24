<?php
require_once __DIR__ . '/../controller/LLMController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $controller = new LLMController();
    $response = $controller->getResponse($message);

    // Simpan log respons
    file_put_contents(__DIR__ . '/../logs/final_response.json', json_encode(['reply' => $response]));

    echo json_encode(['reply' => $response]);
} else {
    echo json_encode(['reply' => 'Invalid request']);
}