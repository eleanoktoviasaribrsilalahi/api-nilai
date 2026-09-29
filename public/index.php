<?php

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode([
        'status' => 'success',
        'message' => 'API Nilai berhasil dijalankan',
        'method' => 'GET'
    ]);
    exit;
}

echo json_encode([
    'status' => 'error',
    'message' => 'Method tidak didukung'
]);