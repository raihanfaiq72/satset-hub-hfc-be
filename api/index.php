<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/eloquent.php';

session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

$base_path = '/api';
$path = str_replace($base_path, '', $request_uri);
$path = trim($path, '/');

error_log("Request URI (path only): $request_uri");
error_log("Request Method: $request_method");
error_log("Path after removing base: '$path'");

Route::loadRoutes(__DIR__ . '/../routes/web.php');

try {
    $response = Route::dispatch($request_method, $path);
    
    if (!http_response_code()) {
        http_response_code(200);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
