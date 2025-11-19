<?php
// index.php

require_once 'SupabaseClient.php';

// --- Configuração CORS e Headers ---
// Headers devem ser enviados antes de qualquer output.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apikey');

// 1. TRATAMENTO DO OPTIONS (PREFLIGHT): Responde e encerra imediatamente.
// Isso resolve o erro de CORS que você estava enfrentando.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// --- FIM Configuração CORS ---

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', trim($uri, '/'));
$table_name = $uri_segments[count($uri_segments) - 1];

$client = new SupabaseClient(); 

// --- LÓGICA DE AUTH (ADICIONADO) ---
if ($table_name === 'register' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = $client->auth('register', $data);
    http_response_code($response['code']);
    echo json_encode($response['data'] ?? ['error' => $response['error']]);
    exit();
}

if ($table_name === 'login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = $client->auth('login', $data);
    http_response_code($response['code']);
    echo json_encode($response['data'] ?? ['error' => $response['error']]);
    exit();
}
$supported_tables = ['breeds', 'categories', 'posts', 'comments','profiles'];

if (!in_array($table_name, $supported_tables)) {
    http_response_code(404);
    echo json_encode(['error' => 'Tabela não encontrada ou não suportada.']);
    exit();
}

$client = new SupabaseClient();
$response = [];

try {
    switch ($method) {
        case 'GET':
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            $response = $client->get($table_name, $filter);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                throw new Exception('Dados de entrada inválidos ou vazios.', 422); 
            }
            $response = $client->post($table_name, $data);
            break;

        case 'PUT':
        case 'PATCH':
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            if (empty($filter)) {
                throw new Exception('Filtro de atualização (query string) é obrigatório.', 400);
            }
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                throw new Exception('Dados de entrada inválidos ou vazios.', 422);
            }
            $response = $client->put($table_name, $data, $filter);
            break;

        case 'DELETE':
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            if (empty($filter)) {
                throw new Exception('Filtro de exclusão (query string) é obrigatório.', 400);
            }
            $response = $client->delete($table_name, $filter);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido.']);
            exit();
    }

    // Código final de resposta (não alterado, está correto)
    http_response_code($response['code'] ?? 200);

    if (isset($response['error'])) {
        echo json_encode(['error' => $response['error']]);
    } else {
        echo json_encode($response['data'] ?? []);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}