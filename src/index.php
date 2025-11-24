<?php
// 1. Buffering de Saída: Segura qualquer texto/erro acidental para não quebrar os headers
ob_start();

// 2. Configurações de Erro (Logar em arquivo, não na tela)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desliga erro na tela para não quebrar o JSON
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// 3. HEADERS CORS (Obrigatório vir antes de qualquer lógica ou include)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apikey, X-Requested-With');

// 4. Tratamento do Preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean(); // Limpa qualquer lixo anterior
    http_response_code(200);
    exit();
}

// 5. Agora sim, incluímos os arquivos
try {
    require_once 'SupabaseClient.php';
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Erro crítico ao carregar dependências: ' . $e->getMessage()]);
    exit();
}

// Limpa o buffer antes de começar a processar a lógica real
ob_clean(); 

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', trim($uri, '/'));
$table_name = $uri_segments[count($uri_segments) - 1];

// --- LÓGICA DE AUTH ---
try {
    $client = new SupabaseClient(); 

    if ($table_name === 'register' && $method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido enviado no corpo da requisição', 400);
        }

        $response = $client->auth('register', $data);
        http_response_code($response['code']);
        echo json_encode($response['data'] ?? ['error' => $response['error']]);
        exit();
    }

    if ($table_name === 'login' && $method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido enviado no corpo da requisição', 400);
        }

        $response = $client->auth('login', $data);
        http_response_code($response['code']);
        echo json_encode($response['data'] ?? ['error' => $response['error']]);
        exit();
    }

    // --- LÓGICA DE TABELAS (CRUD Padrão) ---
    $supported_tables = ['breeds', 'categories', 'posts', 'comments', 'profiles'];

    if (!in_array($table_name, $supported_tables)) {
        http_response_code(404);
        echo json_encode(['error' => "Rota '/$table_name' não encontrada."]);
        exit();
    }

    $response = [];

    switch ($method) {
        case 'GET':
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            $response = $client->get($table_name, $filter);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) throw new Exception('Dados vazios.', 422); 
            $response = $client->post($table_name, $data);
            break;

        case 'PUT':
        case 'PATCH':
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            if (empty($filter)) throw new Exception('Filtro obrigatório.', 400);
            $data = json_decode(file_get_contents('php://input'), true);
            $response = $client->put($table_name, $data, $filter);
            break;

        case 'DELETE':
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            if (empty($filter)) throw new Exception('Filtro obrigatório.', 400);
            $response = $client->delete($table_name, $filter);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido.']);
            exit();
    }

    http_response_code($response['code'] ?? 200);
    echo json_encode(isset($response['error']) ? ['error' => $response['error']] : ($response['data'] ?? []));

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}